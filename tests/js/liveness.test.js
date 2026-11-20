import { describe, expect, test } from 'vitest';
import { createChallenge } from '../../resources/js/face/liveness.js';

const cfg = { earClosed: 0.2, earOpen: 0.25, blinkClosedFrames: 2, yawPeak: 20, yawReturn: 10, minDurationMs: 300, minFrames: 5 };

/** Feeds samples 50 ms apart; returns the telemetry once done, or null. */
function run(type, samples) {
    const challenge = createChallenge(type, cfg);
    for (const [i, sample] of samples.entries()) {
        if (challenge.feed({ ear: 0.3, yaw: 0, ...sample, t: i * 50 })) return challenge.telemetry();
    }
    return null;
}

const ears = (...values) => values.map((ear) => ({ ear }));
const yaws = (...values) => values.map((yaw) => ({ yaw }));

describe('blink', () => {
    test('detects closed eyes for two frames, then open', () => {
        const t = run('blink', ears(0.31, 0.3, 0.15, 0.12, 0.28, 0.3, 0.3, 0.3));
        expect(t).toMatchObject({ type: 'blink', min_ear: 0.12, max_ear: 0.31 });
        expect(t.frames).toBeGreaterThanOrEqual(5);
        expect(t.duration_ms).toBeGreaterThanOrEqual(300);
    });

    test('ignores a single closed frame', () => {
        expect(run('blink', ears(0.3, 0.15, 0.3, 0.3, 0.3, 0.3, 0.3, 0.3, 0.3))).toBeNull();
    });

    test('ignores half-closed eyes', () => {
        expect(run('blink', ears(0.3, 0.22, 0.21, 0.22, 0.3, 0.3, 0.3, 0.3, 0.3))).toBeNull();
    });
});

describe('head turn', () => {
    test('detects a right turn and the return to center', () => {
        const t = run('turn_right', yaws(0, 8, 15, 24, 27, 18, 6, 2, 1));
        expect(t).toMatchObject({ type: 'turn_right', peak_yaw: 27 });
        expect(Math.abs(t.end_yaw)).toBeLessThanOrEqual(10);
    });

    test('detects a left turn with negative yaw', () => {
        expect(run('turn_left', yaws(0, -10, -22, -26, -12, -4, 0, 0))).toMatchObject({ type: 'turn_left', peak_yaw: -26 });
    });

    test('rejects turning the wrong way', () => {
        expect(run('turn_left', yaws(0, 15, 25, 28, 10, 2, 0, 0, 0))).toBeNull();
    });

    test('rejects a turn that is too small', () => {
        expect(run('turn_right', yaws(0, 8, 15, 18, 10, 2, 0, 0, 0))).toBeNull();
    });

    test('waits for the return to center', () => {
        expect(run('turn_right', yaws(0, 15, 25, 28, 26, 25, 24, 23))).toBeNull();
    });
});

test('telemetry has the docs/API.md shape', () => {
    const t = run('turn_right', yaws(0, 25, 5, 0, 0, 0, 0, 0));
    expect(Object.keys(t).sort()).toEqual(['duration_ms', 'end_yaw', 'frames', 'peak_yaw', 'type']);
});
