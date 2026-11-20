// Pure geometry on Human's 468-point face mesh (MediaPipe indices). Points are [x, y, z] in video pixels.

// p1..p6 per eye: outer corner, two upper lid points, inner corner, two lower lid points.
const EYE_A = [33, 160, 158, 133, 153, 144];
const EYE_B = [362, 385, 387, 263, 373, 380];
const NOSE_TIP = 1;

const dist = (a, b) => Math.hypot(a[0] - b[0], a[1] - b[1]);

function eyeAspect(mesh, [p1, p2, p3, p4, p5, p6]) {
    return (dist(mesh[p2], mesh[p6]) + dist(mesh[p3], mesh[p5])) / (2 * dist(mesh[p1], mesh[p4]));
}

/** Eye aspect ratio averaged over both eyes: about 0.3 open, below 0.2 closed. */
export function eyeAspectRatio(mesh) {
    return (eyeAspect(mesh, EYE_A) + eyeAspect(mesh, EYE_B)) / 2;
}

/**
 * Yaw in degrees, positive = the user's right (docs/API.md).
 * Magnitude comes from Human; the sign from the raw (unmirrored) frame, where the
 * user's right is the image's left: turning right moves the nose left of the eye midpoint.
 */
export function userYaw(mesh, yawRadians) {
    const eyeMidX = (mesh[EYE_A[0]][0] + mesh[EYE_B[0]][0]) / 2;
    const sign = Math.sign(eyeMidX - mesh[NOSE_TIP][0]) || 1;

    return (sign * Math.abs(yawRadians) * 180) / Math.PI;
}

/**
 * Where the face sits in the camera circle. The circle shows the centered square
 * crop of the video (object-fit: cover), so sizes are relative to its shorter side.
 * Returns none | multiple | far | near | off_center | ok.
 */
export function circleStatus(boxes, videoWidth, videoHeight, { minRatio, maxRatio }) {
    if (boxes.length === 0) return 'none';
    if (boxes.length > 1) return 'multiple';

    const side = Math.min(videoWidth, videoHeight);
    const [x, y, w, h] = boxes[0];
    const ratio = w / side;
    if (ratio < minRatio) return 'far';
    if (ratio > maxRatio) return 'near';

    const offset = Math.hypot(x + w / 2 - videoWidth / 2, y + h / 2 - videoHeight / 2);
    return offset > side * 0.15 ? 'off_center' : 'ok';
}

// Nose bridge between the eyes: inner eye corners, glabella (168), and below the eyes (197).
const INNER_CORNERS = [133, 362];
const BRIDGE_TOP = 168;
const BRIDGE_BOTTOM = 197;

/**
 * Crop [x, y, w, h] over the nose bridge, where an eyeglass frame crosses the face.
 * Middle half between the inner eye corners (no lashes), from above the glabella to below the eyes.
 */
export function bridgeRect(mesh) {
    const [a, b] = INNER_CORNERS.map((i) => mesh[i][0]);
    const span = Math.abs(b - a);
    const top = mesh[BRIDGE_TOP][1];
    const bottom = mesh[BRIDGE_BOTTOM][1];
    const y = top - (bottom - top) * 0.5;

    return [Math.min(a, b) + span * 0.25, y, span * 0.5, bottom - y];
}

/**
 * Strongest horizontal edge in a grayscale crop (row-major, 0–255): the largest
 * |row mean of g[y+1] − g[y−1]|. A glasses bridge is a bar across the whole width, so its
 * edge survives the row mean; skin shading is gentle and noise averages out.
 */
export function bridgeEdge(gray, width, height) {
    let max = 0;
    for (let y = 1; y < height - 1; y++) {
        let sum = 0;
        for (let x = 0; x < width; x++) sum += gray[(y + 1) * width + x] - gray[(y - 1) * width + x];
        max = Math.max(max, Math.abs(sum / width));
    }

    return max;
}
