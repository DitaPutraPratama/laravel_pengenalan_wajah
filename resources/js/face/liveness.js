// Challenge trackers (F-03). Feed one sample per detected frame; telemetry matches docs/API.md.

const round = (value, digits) => Number(value.toFixed(digits));

/**
 * @param {'blink'|'turn_right'|'turn_left'} type
 * @param {{earClosed:number, earOpen:number, blinkClosedFrames:number, yawPeak:number, yawReturn:number, minDurationMs:number, minFrames:number}} cfg
 */
export function createChallenge(type, cfg) {
    const direction = type === 'turn_left' ? -1 : 1;
    let start = null;
    let end = null;
    let frames = 0;
    let gesture = false; // the movement itself happened
    // blink
    let minEar = Infinity;
    let maxEar = -Infinity;
    let closedRun = 0;
    let closed = false;
    // turn
    let peak = 0;
    let endYaw = 0;

    function feedBlink(ear) {
        minEar = Math.min(minEar, ear);
        maxEar = Math.max(maxEar, ear);
        closedRun = ear < cfg.earClosed ? closedRun + 1 : 0;
        if (closedRun >= cfg.blinkClosedFrames) closed = true;
        if (closed && ear > cfg.earOpen) gesture = true;
    }

    function feedTurn(yaw) {
        peak = Math.max(peak, yaw * direction);
        if (peak >= cfg.yawPeak && Math.abs(yaw) <= cfg.yawReturn) {
            gesture = true;
            endYaw = yaw;
        }
    }

    return {
        /** @returns {boolean} true once the challenge is complete */
        feed({ ear, yaw, t }) {
            if (end !== null) return true;
            start ??= t;
            frames += 1;
            if (!gesture) {
                if (type === 'blink') feedBlink(ear);
                else feedTurn(yaw);
            }
            // The server also wants a minimum duration and frame count, so a very quick gesture waits a moment.
            if (gesture && t - start >= cfg.minDurationMs && frames >= cfg.minFrames) end = t;
            return end !== null;
        },

        telemetry() {
            const base = { type, duration_ms: Math.round(end - start), frames };
            return type === 'blink'
                ? { ...base, min_ear: round(minEar, 3), max_ear: round(maxEar, 3) }
                : { ...base, peak_yaw: round(peak * direction, 1), end_yaw: round(endYaw, 1) };
        },
    };
}
