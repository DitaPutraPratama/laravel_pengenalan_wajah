// Page controllers for /enroll, /check and /delete. Config and text come from data-* on #face-app.
import { post } from './api.js';
import { runScan, ScanError } from './scanner.js';

const root = document.getElementById('face-app');

// --- shared UI ------------------------------------------------------------------

const $ = (selector) => root.querySelector(selector);

function show(...names) {
    root.querySelectorAll('[data-section]').forEach((el) => {
        el.hidden = !names.includes(el.dataset.section);
    });
}

function scannerUi() {
    const circle = $('[data-circle]');
    return {
        video: $('[data-video]'),
        instruction: (text) => ($('[data-instruction]').textContent = text),
        steps: (text) => ($('[data-steps]').textContent = text),
        circleOk: (ok) => circle.classList.toggle('ok', ok),
    };
}

let countdown = null;

/**
 * AlertBox with action buttons/links. Rate limits count down before the action is enabled.
 * @param {string} text
 * @param {Array<{label:string, onClick?:Function, href?:string}>} actions
 */
function alertBox(text, actions = [], retryAfter = 0) {
    clearInterval(countdown);
    const box = $('[data-alert]');
    const label = $('[data-alert-text]');
    const holder = $('[data-alert-actions]');
    holder.replaceChildren(
        ...actions.map(({ label: name, onClick, href }) => {
            const el = document.createElement(href ? 'a' : 'button');
            el.className = 'button';
            el.textContent = name;
            if (href) el.href = href;
            else {
                el.type = 'button';
                el.addEventListener('click', onClick);
            }
            return el;
        }),
    );

    let seconds = retryAfter;
    const render = () => {
        label.textContent = text.replace('{n}', String(seconds));
        holder.querySelectorAll('button').forEach((b) => (b.disabled = seconds > 0));
    };
    render();
    if (seconds > 0) {
        countdown = setInterval(() => {
            seconds -= 1;
            render();
            if (seconds <= 0) clearInterval(countdown);
        }, 1000);
    }

    box.hidden = false;
    box.focus();
}

function hideAlert() {
    clearInterval(countdown);
    $('[data-alert]').hidden = true;
}

function errorText(msg, code) {
    return msg.errors[code] ?? msg[code] ?? msg.errors.server_error;
}

/** Scan, then send. Shows the matching error with a retry that starts a fresh session. */
async function scanAndSend({ cfg, msg, purpose, endpoint, extra = {}, onSuccess, onError }) {
    hideAlert();
    show('scanner');
    const ui = scannerUi();
    const retry = { label: msg.retry, onClick: () => scanAndSend({ cfg, msg, purpose, endpoint, extra, onSuccess, onError }) };

    try {
        const body = await runScan({ cfg, msg, purpose, ui });
        ui.instruction(msg.processing);
        const result = await post(endpoint, { ...body, ...extra });
        if (!result.ok) throw new ScanError(result.code, result.retryAfter);
        onSuccess(result.data);
    } catch (error) {
        const code = error instanceof ScanError ? error.code : 'server_error';
        show();
        if (!onError?.(code)) {
            const fatal = ['camera_denied', 'camera_not_found', 'model_failed', 'capacity_reached', 'validation_failed'];
            const action = code === 'session_invalid' ? { ...retry, label: msg.restart } : retry;
            alertBox(errorText(msg, code), fatal.includes(code) ? [] : [action], error.retryAfter ?? 0);
        }
    }
}

// --- pages ----------------------------------------------------------------------

function enrollPage(cfg, msg) {
    const consent = $('[data-consent]');
    const start = $('[data-start]');
    consent.addEventListener('change', () => (start.disabled = !consent.checked));

    start.addEventListener('click', () =>
        scanAndSend({
            cfg,
            msg,
            purpose: 'enroll',
            endpoint: 'enroll',
            extra: { consent: consent.checked, consent_version: cfg.consentVersion },
            onSuccess: ({ delete_code: code }) => showDeleteCode(code),
            onError: (code) => {
                if (code !== 'face_already_registered') return false;
                alertBox(errorText(msg, code), [
                    { label: msg.home, href: '/' },
                    { label: msg.go_delete, href: '/delete' },
                ]);
                return true;
            },
        }),
    );
}

function showDeleteCode(code) {
    show('success');
    // Shown once, never stored in localStorage or the URL (docs/DESIGN.md §8).
    $('[data-code]').textContent = code.match(/.{1,4}/g).join(' ');
    $('[data-copy]').addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(code);
            const note = $('[data-copied]');
            note.textContent = note.dataset.text;
        } catch {
            /* the code stays visible for manual copying */
        }
    });
    const done = $('[data-done]');
    $('[data-saved]').addEventListener('change', (e) => done.setAttribute('aria-disabled', String(!e.target.checked)));
    $('[data-success-title]').focus();
}

function checkPage(cfg, msg) {
    $('[data-start]').addEventListener('click', () =>
        scanAndSend({
            cfg,
            msg,
            purpose: 'identify',
            endpoint: 'identify',
            onSuccess: ({ registered }) => show(registered ? 'registered' : 'not-registered'),
        }),
    );
}

function deletePage(cfg, msg) {
    const form = $('[data-section="code"]');
    const input = $('[data-code-input]');
    const confirm = $('[data-confirm]');

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (input.value.replace(/[\s-]/g, '') === '') return input.focus();
        hideAlert();
        show('confirm');
        confirm.focus();
    });

    $('[data-cancel]').addEventListener('click', () => {
        show('code');
        input.focus();
    });

    confirm.addEventListener('click', async () => {
        confirm.disabled = true;
        const original = confirm.textContent;
        confirm.textContent = msg.deleting;
        const result = await post('delete/code', { delete_code: input.value });
        confirm.disabled = false;
        confirm.textContent = original;

        if (result.ok) return show('done');
        show('code');
        alertBox(errorText(msg, result.code), [], result.retryAfter ?? 0);
    });
}

// --- boot (last, so every helper above is initialized) ----------------------------

if (root) {
    const d = root.dataset;
    const cfg = {
        modelUrl: d.modelUrl,
        embeddingDim: Number(d.embeddingDim),
        consentVersion: d.consentVersion,
        challengeTimeout: Number(d.challengeTimeout),
        earClosed: Number(d.earClosed),
        earOpen: Number(d.earOpen),
        blinkClosedFrames: Number(d.blinkClosedFrames),
        yawPeak: Number(d.yawPeak),
        yawReturn: Number(d.yawReturn),
        minDurationMs: Number(d.minDurationMs),
        minFrames: Number(d.minFrames),
        circleMinRatio: Number(d.circleMinRatio),
        circleMaxRatio: Number(d.circleMaxRatio),
        circleHoldMs: Number(d.circleHoldMs),
    };
    const msg = JSON.parse(d.messages);
    const pages = { enroll: enrollPage, check: checkPage, delete: deletePage };
    pages[d.page]?.(cfg, msg);
}
