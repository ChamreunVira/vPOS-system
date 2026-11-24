const SCANNER_SCRIPT_URL = "/vendor/html5-qrcode/html5-qrcode.min.js";
const DUPLICATE_SCAN_COOLDOWN_MS = 1500;
const IMAGE_MAX_SIDE_OPTIONS_PX = [1600, 1000];
const QUIET_ZONE_RATIO = 0.1;
const MIN_QUIET_ZONE_PX = 40;

const SUPPORTED_FORMAT_NAMES = [
    "EAN_13",
    "EAN_8",
    "CODE_128",
    "CODE_39",
    "CODE_93",
    "UPC_A",
    "UPC_E",
    "ITF",
    "CODABAR",
    "QR_CODE",
    "DATA_MATRIX",
];

const CAMERA_ERROR_MESSAGES = {
    NotAllowedError:
        "Camera permission was denied. Allow camera access in the browser's site settings, then reload the page.",
    NotFoundError: "No camera was found on this device.",
    NotReadableError:
        "The camera is being used by another app or tab. Close it and try again.",
    OverconstrainedError:
        "The requested camera settings are not supported by this device.",
    SecurityError:
        "Camera access was blocked by the browser's security policy.",
};

let sharedAudioContext = null;
let scriptLoadPromise = null;

export function playBeep() {
    try {
        const AudioContextClass =
            window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) return;

        sharedAudioContext ??= new AudioContextClass();
        if (sharedAudioContext.state === "suspended")
            sharedAudioContext.resume();

        const { currentTime } = sharedAudioContext;
        const oscillator = sharedAudioContext.createOscillator();
        const gainNode = sharedAudioContext.createGain();

        oscillator.type = "sine";
        oscillator.frequency.setValueAtTime(1850, currentTime);
        gainNode.gain.setValueAtTime(0.2, currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, currentTime + 0.12);

        oscillator.connect(gainNode);
        gainNode.connect(sharedAudioContext.destination);
        oscillator.start(currentTime);
        oscillator.stop(currentTime + 0.12);
    } catch {
        // Audio may be blocked until a user gesture; ignore.
    }
}

export function loadHtml5Qrcode() {
    if (window.Html5Qrcode) return Promise.resolve(window.Html5Qrcode);
    if (scriptLoadPromise) return scriptLoadPromise;

    scriptLoadPromise = new Promise((resolve, reject) => {
        const script = document.createElement("script");
        script.src = SCANNER_SCRIPT_URL;
        script.async = true;

        const fail = (message) => {
            script.remove();
            scriptLoadPromise = null; // allow retry
            reject(new Error(message));
        };

        script.onload = () =>
            window.Html5Qrcode
                ? resolve(window.Html5Qrcode)
                : fail(
                      "Barcode scanner script loaded but Html5Qrcode is not defined.",
                  );
        script.onerror = () =>
            fail(
                `Failed to load barcode scanner script: ${SCANNER_SCRIPT_URL}`,
            );

        document.head.appendChild(script);
    });

    return scriptLoadPromise;
}

function createScannerInstance(elementId) {
    const { Html5Qrcode, Html5QrcodeSupportedFormats } = window;
    const formatsToSupport = SUPPORTED_FORMAT_NAMES.map(
        (name) => Html5QrcodeSupportedFormats?.[name],
    ).filter((format) => format !== undefined);

    return new Html5Qrcode(elementId, {
        formatsToSupport: formatsToSupport.length
            ? formatsToSupport
            : undefined,
        verbose: false,
        // Native BarcodeDetector (Android Chrome) is far better at 1D codes; ZXing is the fallback.
        experimentalFeatures: { useBarCodeDetectorIfSupported: true },
    });
}

// Wide, short scan box suits 1D barcodes and decodes the region at a higher effective resolution.
function calculateScanBox(viewfinderWidth, viewfinderHeight) {
    const width = Math.floor(viewfinderWidth * 0.9);
    const height = Math.floor(Math.min(viewfinderHeight * 0.9, width * 0.5));
    return { width, height };
}

const BASE_SCAN_CONFIG = {
    fps: 10,
    qrbox: calculateScanBox,
    disableFlip: true,
};

const CAMERA_START_ATTEMPTS = [
    {
        camera: { facingMode: "environment" },
        config: {
            ...BASE_SCAN_CONFIG,
            videoConstraints: {
                facingMode: "environment",
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
        },
    },
    { camera: { facingMode: "environment" }, config: BASE_SCAN_CONFIG },
    { camera: { facingMode: "user" }, config: BASE_SCAN_CONFIG },
];

function getErrorMessage(error) {
    return typeof error === "string" ? error : (error?.message ?? "");
}

function getCameraErrorName(error) {
    if (error?.name && CAMERA_ERROR_MESSAGES[error.name]) return error.name;
    // Html5Qrcode sometimes rejects with a plain string containing the DOMException name.
    return Object.keys(CAMERA_ERROR_MESSAGES).find((name) =>
        getErrorMessage(error).includes(name),
    );
}

function toCameraError(error) {
    const errorName = getCameraErrorName(error);
    if (errorName) return new Error(CAMERA_ERROR_MESSAGES[errorName]);
    return new Error(
        `Could not start the camera: ${getErrorMessage(error) || "unknown error"}`,
    );
}

function loadImage(file) {
    return new Promise((resolve, reject) => {
        const objectUrl = URL.createObjectURL(file);
        const image = new Image();
        image.onload = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(image);
        };
        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error("Could not read the selected image."));
        };
        image.src = objectUrl;
    });
}

// Downscales the image and surrounds it with white so tightly cropped barcodes get a quiet zone.
async function createPaddedImageVariant(image, maxSidePx) {
    const scale = Math.min(
        1,
        maxSidePx / Math.max(image.naturalWidth, image.naturalHeight),
    );
    const width = Math.round(image.naturalWidth * scale);
    const height = Math.round(image.naturalHeight * scale);
    const padding = Math.max(
        MIN_QUIET_ZONE_PX,
        Math.round(Math.max(width, height) * QUIET_ZONE_RATIO),
    );

    const canvas = document.createElement("canvas");
    canvas.width = width + padding * 2;
    canvas.height = height + padding * 2;

    const context = canvas.getContext("2d");
    context.fillStyle = "#ffffff";
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(image, padding, padding, width, height);

    const blob = await new Promise((resolve) =>
        canvas.toBlob(resolve, "image/png"),
    );
    if (!blob) throw new Error("Could not process the selected image.");
    return new File([blob], "barcode-variant.png", { type: "image/png" });
}

export function createCameraScanner({ elementId, onScan }) {
    let html5Scanner = null;
    let lastCode = "";
    let lastDetectedAt = 0;

    function handleDecoded(decodedText, decodedResult) {
        const code = decodedText?.trim();
        if (!code) return;

        const now = Date.now();
        const isDuplicate =
            code === lastCode &&
            now - lastDetectedAt < DUPLICATE_SCAN_COOLDOWN_MS;
        lastCode = code;
        lastDetectedAt = now;
        if (isDuplicate) return;

        playBeep();
        onScan(code, decodedResult);
    }

    async function stop() {
        if (!html5Scanner) return;
        try {
            if (html5Scanner.isScanning) await html5Scanner.stop();
            html5Scanner.clear();
        } catch {
            // Ignore cleanup errors.
        }
        html5Scanner = null;
    }

    async function start() {
        if (!window.isSecureContext) {
            throw new Error(
                "Camera access requires HTTPS (or localhost). Open this page over HTTPS to use the camera.",
            );
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error(
                "Camera scanning is not supported in this browser. Please use a handheld scanner or enter the barcode.",
            );
        }

        await loadHtml5Qrcode();

        const container = document.getElementById(elementId);
        if (!container)
            throw new Error(`Scanner container #${elementId} not found.`);
        if (container.offsetWidth === 0) {
            throw new Error(
                `Scanner container #${elementId} is hidden. Show it before starting the camera.`,
            );
        }

        await stop();
        html5Scanner = createScannerInstance(elementId);

        let firstError = null;
        for (const { camera, config } of CAMERA_START_ATTEMPTS) {
            try {
                await html5Scanner.start(
                    camera,
                    config,
                    handleDecoded,
                    () => {},
                );
                return;
            } catch (error) {
                console.warn("Camera start attempt failed:", camera, error);
                firstError ??= error;
                if (getCameraErrorName(error) === "NotAllowedError") break; // retrying won't help
            }
        }

        html5Scanner = null;
        throw toCameraError(firstError);
    }

    async function scanFile(file) {
        await loadHtml5Qrcode();
        if (!document.getElementById(elementId)) {
            throw new Error(`Scanner container #${elementId} not found.`);
        }

        await stop(); // file scanning is not allowed while the camera is running

        const fileScanner = createScannerInstance(elementId);
        try {
            const image = await loadImage(file);
            const candidates = [file];
            for (const maxSidePx of IMAGE_MAX_SIDE_OPTIONS_PX) {
                candidates.push(
                    await createPaddedImageVariant(image, maxSidePx),
                );
            }

            for (const candidate of candidates) {
                try {
                    const decodedText = await fileScanner.scanFile(
                        candidate,
                        false,
                    );
                    const code = decodedText?.trim();
                    if (code) {
                        playBeep();
                        onScan(code);
                        return code;
                    }
                } catch {
                    // Try the next variant.
                }
            }
        } finally {
            try {
                fileScanner.clear();
            } catch {
                // Ignore cleanup errors.
            }
        }

        throw new Error(
            "No barcode detected. Use a sharp, well-lit image with the barcode horizontal.",
        );
    }

    return {
        start,
        stop,
        scanFile,
        isScanning: () => Boolean(html5Scanner?.isScanning),
    };
}

if (typeof window !== "undefined") {
    window.vposBarcodeScanner = {
        playBeep,
        loadHtml5Qrcode,
        createCameraScanner,
    };
}
