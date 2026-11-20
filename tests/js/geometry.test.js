import { describe, expect, test } from 'vitest';
import { circleStatus, eyeAspectRatio, userYaw } from '../../resources/js/face/geometry.js';

/** Synthetic 468-point mesh: eyes 40 px wide with the given lid gap, nose at noseX. No real face data. */
function mesh({ lidGap = 12, noseX = 320 } = {}) {
    const points = Array.from({ length: 468 }, () => [0, 0, 0]);
    const eye = (outer, upper1, upper2, inner, lower1, lower2, x0) => {
        points[outer] = [x0, 200, 0];
        points[inner] = [x0 + 40, 200, 0];
        points[upper1] = [x0 + 13, 200 - lidGap / 2, 0];
        points[upper2] = [x0 + 27, 200 - lidGap / 2, 0];
        points[lower1] = [x0 + 27, 200 + lidGap / 2, 0];
        points[lower2] = [x0 + 13, 200 + lidGap / 2, 0];
    };
    eye(33, 160, 158, 133, 153, 144, 240); // left side of the image
    eye(362, 385, 387, 263, 373, 380, 400); // right side of the image
    points[1] = [noseX, 250, 0];
    return points;
}

describe('eyeAspectRatio', () => {
    test('open eyes are about 0.3', () => {
        expect(eyeAspectRatio(mesh({ lidGap: 12 }))).toBeCloseTo(0.3, 2);
    });

    test('closed eyes fall below 0.2', () => {
        expect(eyeAspectRatio(mesh({ lidGap: 2 }))).toBeLessThan(0.2);
    });
});

describe('userYaw', () => {
    const rad = (deg) => (deg * Math.PI) / 180;

    test('nose left of the eye midpoint in the raw frame means the user turned right (positive)', () => {
        expect(userYaw(mesh({ noseX: 300 }), rad(-25))).toBeCloseTo(25, 5);
    });

    test('nose right of the midpoint means the user turned left (negative)', () => {
        expect(userYaw(mesh({ noseX: 360 }), rad(25))).toBeCloseTo(-25, 5);
    });
});

describe('circleStatus', () => {
    const limits = { minRatio: 0.4, maxRatio: 0.7 };
    // 640x480 video: the circle shows the centered 480x480 square.
    const centered = (w) => [320 - w / 2, 240 - w / 2, w, w];

    test.each([
        ['none', []],
        ['multiple', [centered(240), centered(240)]],
        ['far', [centered(150)]],
        ['near', [centered(400)]],
        ['off_center', [[0, 0, 240, 240]]],
        ['ok', [centered(240)]],
    ])('%s', (expected, boxes) => {
        expect(circleStatus(boxes, 640, 480, limits)).toBe(expected);
    });
});
