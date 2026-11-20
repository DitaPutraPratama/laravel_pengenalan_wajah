// Camera + Human + liveness flow shared by / (login) and /enroll (F-02, F-03).
import { post } from './api.js';
import { bridgeEdge, bridgeRect, circleStatus, eyeAspectRatio, userYaw } from './geometry.js';
import { createChallenge } from './liveness.js';

/** Thrown with a key from lang/id/face.php "js" (camera_denied, liveness_failed, …). */
export class ScanError extends Error {
    constructor(code, retryAfter) {
        super(code);
        this.code = code;
        this.retryAfter = retryAfter;
    }
}

const nextFrame = () => new Promise((resolve) => requestAnimationFrame(resolve));
const EMBEDDING_FRAMES = 3;
const EMBEDDING_TIMEOUT_MS = 5000;
const BRIDGE_W = 32;
const BRIDGE_H = 24;

let humanPromise = null;

/** Loads Human once per page; models come from FACE_MODEL_URL (ADR-012). */
function loadHuman(cfg) {
    humanPromise ??= (async () => {
        const { Human } = await import('@vladmandic/human');
        const human = new Human({
            modelBasePath: cfg.modelUrl,
            cacheSensitivity: 0, // never reuse old results: blinks last only a few frames
            debug: false,
            face: {
                enabled: true,
                detector: { maxDetected: 2, minConfidence: 0.5, rotation: false },
                mesh: { enabled: true },
                iris: { enabled: true },
                description: { enabled: true },
                emotion: { enabled: false },
                antispoof: { enabled: false },
                liveness: { enabled: false },
                attention: { enabled: false },
            },
            body: { enabled: false },
            hand: { enabled: false },
            object: { enabled: false },
            gesture: { enabled: false },
            segmentation: { enabled: false },
        });
        await human.load();
        return human;
    })().catch((error) => {
        humanPromise = null;
        throw error;
    });

    return humanPromise;
}

let bridgeCtx = null;

/** True when an eyeglass frame crosses the nose bridge (F-17). The tiny crop never leaves memory. */
function wearsGlasses(video, mesh, maxEdge) {
    if (!maxEdge) return false;

    bridgeCtx ??= Object.assign(document.createElement('canvas'), { width: BRIDGE_W, height: BRIDGE_H })
        .getContext('2d', { willReadFrequently: true });
    const [x, y, w, h] = bridgeRect(mesh);
    bridgeCtx.drawImage(video, x, y, w, h, 0, 0, BRIDGE_W, BRIDGE_H);
    const { data } = bridgeCtx.getImageData(0, 0, BRIDGE_W, BRIDGE_H);
    const gray = new Float32Array(BRIDGE_W * BRIDGE_H);
    for (let i = 0; i < gray.length; i++) {
        gray[i] = 0.299 * data[i * 4] + 0.587 * data[i * 4 + 1] + 0.114 * data[i * 4 + 2];
    }

    return bridgeEdge(gray, BRIDGE_W, BRIDGE_H) > maxEdge;
}

async function startCamera(video) {
    if (!navigator.mediaDevices?.getUserMedia) throw new ScanError('camera_not_found');

    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
        });
    } catch (error) {
        const missing = ['NotFoundError', 'OverconstrainedError', 'NotReadableError'].includes(error.name);
        throw new ScanError(missing ? 'camera_not_found' : 'camera_denied');
    }

    video.srcObject = stream;
    await video.play();

    return () => {
        stream.getTracks().forEach((track) => track.stop());
        video.srcObject = null;
    };
}

/**
 * Runs the whole scan and returns the request body for enroll/login:
 * { embedding, liveness: { session_token, challenges } }. No image ever leaves the browser.
 *
 * @param {object} p
 * @param {object} p.cfg      config from data-* attributes
 * @param {object} p.msg      lang/id/face.php "js"
 * @param {string} p.purpose  enroll | login
 * @param {object} p.ui       { video, instruction(text), steps(text), circleOk(bool) }
 */
export async function runScan({ cfg, msg, purpose, ui }) {
    ui.instruction(msg.loading);
    let human;
    try {
        human = await loadHuman(cfg);
    } catch {
        throw new ScanError('model_failed');
    }

    ui.instruction(msg.camera_request);
    const stopCamera = await startCamera(ui.video);

    try {
        const detect = async ({ embedding = false, glasses = false } = {}) => {
            const result = await human.detect(ui.video, { face: { description: { enabled: embedding } } });
            return result.face.map((face) => ({
                box: face.box,
                ear: eyeAspectRatio(face.mesh),
                yaw: userYaw(face.mesh, face.rotation?.angle?.yaw ?? 0),
                embedding: face.embedding,
                glasses: glasses && result.face.length === 1 && wearsGlasses(ui.video, face.mesh, cfg.glassesMaxEdge),
            }));
        };

        await waitForPosition({ cfg, msg, ui, detect });

        // The session starts only now: the server checks durations against its own clock.
        const session = await post('sessions', { purpose });
        if (!session.ok) throw new ScanError(session.code, session.retryAfter);
        const { session_token: token, challenges } = session.data;

        const telemetry = [];
        for (const [index, type] of challenges.entries()) {
            ui.steps(msg.step.replace(':current', index + 1).replace(':total', challenges.length));
            ui.instruction(msg.challenge[type]);
            telemetry.push(await runChallenge(type, cfg, detect));
        }

        ui.steps('');
        ui.instruction(msg.hold_still);
        const embedding = await captureEmbedding(cfg, detect);

        return { embedding, liveness: { session_token: token, challenges: telemetry } };
    } finally {
        stopCamera();
        ui.circleOk(false);
    }
}

async function waitForPosition({ cfg, msg, ui, detect }) {
    const limits = { minRatio: cfg.circleMinRatio, maxRatio: cfg.circleMaxRatio };
    let okSince = null;

    for (;;) {
        const faces = await detect({ glasses: true });
        let status = circleStatus(faces.map((f) => f.box), ui.video.videoWidth, ui.video.videoHeight, limits);
        if (status === 'ok' && faces[0].glasses) status = 'glasses';
        ui.circleOk(status === 'ok');
        ui.instruction(msg.position[status]);

        if (status === 'ok') {
            okSince ??= performance.now();
            if (performance.now() - okSince >= cfg.circleHoldMs) return;
        } else {
            okSince = null;
        }
        await nextFrame();
    }
}

async function runChallenge(type, cfg, detect) {
    const tracker = createChallenge(type, cfg);
    const started = performance.now();

    for (;;) {
        if (performance.now() - started > cfg.challengeTimeout * 1000) throw new ScanError('liveness_failed');

        const faces = await detect();
        if (faces.length === 1 && tracker.feed({ ear: faces[0].ear, yaw: faces[0].yaw, t: performance.now() })) {
            return tracker.telemetry();
        }
        await nextFrame();
    }
}

/** Averages a few frontal embeddings for a steadier result. Frames with glasses do not count. */
async function captureEmbedding(cfg, detect) {
    const samples = [];
    const started = performance.now();
    let glassesSeen = false;

    while (samples.length < EMBEDDING_FRAMES) {
        if (performance.now() - started > EMBEDDING_TIMEOUT_MS) {
            throw new ScanError(glassesSeen ? 'glasses' : 'liveness_failed');
        }

        const faces = await detect({ embedding: true, glasses: true });
        const face = faces.length === 1 ? faces[0] : null;
        if (face?.glasses) {
            glassesSeen = true;
        } else if (face?.embedding?.length === cfg.embeddingDim && Math.abs(face.yaw) <= cfg.yawReturn) {
            samples.push(face.embedding);
        }
        await nextFrame();
    }

    return samples[0].map((_, i) => samples.reduce((sum, s) => sum + s[i], 0) / samples.length);
}
