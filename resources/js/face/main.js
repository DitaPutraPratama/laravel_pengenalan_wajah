// Page controllers for / (login), /enroll and /dashboard (attendance). Config and text come from data-* on #face-app.
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
        ...actions.map(({ label: name, onClick, href }, index) => {
            const el = document.createElement(href ? 'a' : 'button');
            el.className = index === 0 ? 'button' : 'button outline';
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
            // A reload also renews an expired CSRF token, not just the liveness session.
            const restart = { label: msg.restart, onClick: () => window.location.reload() };
            const action = code === 'session_invalid' ? restart : retry;
            alertBox(errorText(msg, code), fatal.includes(code) ? [] : [action], error.retryAfter ?? 0);
        }
    }
}

// --- pages ----------------------------------------------------------------------

// Both scans end in a signed-in session; the server says where to go next.
const goTo = ({ redirect }) => window.location.assign(redirect);

function loginPage(cfg, msg) {
    $('[data-start]').addEventListener('click', () =>
        scanAndSend({
            cfg,
            msg,
            purpose: 'login',
            endpoint: 'login',
            onSuccess: goTo,
            onError: (code) => {
                if (code !== 'face_not_found') return false;
                alertBox(errorText(msg, code), [
                    { label: msg.retry, onClick: () => $('[data-start]').click() },
                    { label: msg.go_enroll, href: '/enroll' },
                ]);
                return true;
            },
        }),
    );
}

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
            onSuccess: goTo,
            onError: (code) => {
                if (code !== 'face_already_registered') return false;
                alertBox(errorText(msg, code), [{ label: msg.go_login, href: '/' }]);
                return true;
            },
        }),
    );
}

/** Attendance: a photo-only scan (no challenges) checked 1:1 against the signed-in account. */
function dashboardPage(cfg, msg) {
    const start = $('[data-start]');
    if (!start) return; // no name yet, or already attended today

    start.addEventListener('click', () =>
        scanAndSend({
            cfg,
            msg,
            purpose: null,
            endpoint: 'attendance',
            onSuccess: goTo,
            onError: (code) => {
                if (code !== 'face_not_found') return false;
                alertBox(msg.errors.face_mismatch, [{ label: msg.retry, onClick: () => start.click() }]);
                return true;
            },
        }),
    );
}

/** Header toggle: flips light/dark and remembers the choice (the head script applies it on load). */
function themeToggle() {
    const button = document.querySelector('[data-theme-toggle]');
    if (!button) return;
    const html = document.documentElement;
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)');
    const current = () => html.dataset.theme ?? (systemDark.matches ? 'dark' : 'light');
    const sync = () => button.setAttribute('aria-pressed', String(current() === 'dark'));

    button.addEventListener('click', () => {
        html.dataset.theme = current() === 'dark' ? 'light' : 'dark';
        try {
            localStorage.setItem('theme', html.dataset.theme);
        } catch {
            /* private mode: the choice lasts until the page closes */
        }
        sync();
    });
    systemDark.addEventListener('change', sync);
    sync();
}

// --- boot (last, so every helper above is initialized) ----------------------------

themeToggle();

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
        glassesMaxEdge: Number(d.glassesMaxEdge),
    };
    const msg = JSON.parse(d.messages);
    const pages = { login: loginPage, enroll: enrollPage, dashboard: dashboardPage };
    pages[d.page]?.(cfg, msg);
}
