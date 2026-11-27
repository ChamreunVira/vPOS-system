const SCANNER_SCRIPT_URL = "/vendor/html5-qrcode/html5-qrcode.min.js";

const DUPLICATE_SCAN_COOLDOWN_MS = 1500;
const IMAGE_MAX_SIDE_OPTIONS_PX = [1600, 1000];

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

let scriptLoadPromise = null;
let sharedAudioContext = null;

const CAMERA_ERROR_MESSAGES = {
    NotAllowedError:
        "Camera permission denied. Allow camera access in browser settings.",
    NotFoundError: "No camera was found on this device.",
    NotReadableError: "Camera is being used by another application.",
    OverconstrainedError: "The requested camera is not supported.",
    SecurityError: "Camera access requires HTTPS or localhost.",
};

export function playBeep() {
    try {
        const AudioContextClass =
            window.AudioContext || window.webkitAudioContext;

        if (!AudioContextClass) return;

        sharedAudioContext ??= new AudioContextClass();

        if (sharedAudioContext.state === "suspended") {
            sharedAudioContext.resume();
        }

        const now = sharedAudioContext.currentTime;
        const oscillator = sharedAudioContext.createOscillator();
        const gain = sharedAudioContext.createGain();

        oscillator.type = "sine";
        oscillator.frequency.setValueAtTime(1850, now);

        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.12);

        oscillator.connect(gain);
        gain.connect(sharedAudioContext.destination);

        oscillator.start(now);
        oscillator.stop(now + 0.12);
    } catch (error) {
        console.warn("Beep unavailable:", error);
    }
}

export function loadHtml5Qrcode() {
    if (window.Html5Qrcode) {
        return Promise.resolve(window.Html5Qrcode);
    }

    if (scriptLoadPromise) return scriptLoadPromise;

    scriptLoadPromise = new Promise((resolve, reject) => {
        const script = document.createElement("script");

        script.src = SCANNER_SCRIPT_URL;
        script.async = true;

        const fail = (message) => {
            script.remove();
            scriptLoadPromise = null;
            reject(new Error(message));
        };

        script.onload = () => {
            if (window.Html5Qrcode) {
                resolve(window.Html5Qrcode);
            } else {
                fail("Html5Qrcode could not be loaded.");
            }
        };

        script.onerror = () => {
            fail(`Failed to load ${SCANNER_SCRIPT_URL}`);
        };

        document.head.appendChild(script);
    });

    return scriptLoadPromise;
}

function createScannerInstance(elementId) {
    const { Html5Qrcode, Html5QrcodeSupportedFormats } = window;

    const formats = SUPPORTED_FORMAT_NAMES.map(
        (name) => Html5QrcodeSupportedFormats?.[name],
    ).filter((format) => format !== undefined);

    return new Html5Qrcode(elementId, {
        formatsToSupport: formats.length ? formats : undefined,
        verbose: false,
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true,
        },
    });
}

function calculateScanBox(width, height) {
    return {
        width: Math.floor(width * 0.9),
        height: Math.floor(Math.min(height * 0.8, width * 0.5)),
    };
}

const SCAN_CONFIG = {
    fps: 15,
    qrbox: calculateScanBox,
    disableFlip: false,
};

function getErrorMessage(error) {
    return typeof error === "string" ? error : (error?.message ?? "");
}

function getCameraErrorName(error) {
    if (error?.name && CAMERA_ERROR_MESSAGES[error.name]) {
        return error.name;
    }

    return Object.keys(CAMERA_ERROR_MESSAGES).find((name) =>
        getErrorMessage(error).includes(name),
    );
}

function toCameraError(error) {
    const name = getCameraErrorName(error);

    return new Error(
        name
            ? CAMERA_ERROR_MESSAGES[name]
            : `Could not start camera: ${getErrorMessage(error) || "Unknown error"}`,
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

async function createPaddedImageVariant(image, maxSide) {
    const scale = Math.min(
        1,
        maxSide / Math.max(image.naturalWidth, image.naturalHeight),
    );

    const width = Math.round(image.naturalWidth * scale);
    const height = Math.round(image.naturalHeight * scale);

    const padding = Math.max(40, Math.round(Math.max(width, height) * 0.1));

    const canvas = document.createElement("canvas");

    canvas.width = width + padding * 2;
    canvas.height = height + padding * 2;

    const context = canvas.getContext("2d");

    if (!context) {
        throw new Error("Could not process the selected image.");
    }

    context.fillStyle = "#ffffff";
    context.fillRect(0, 0, canvas.width, canvas.height);

    context.drawImage(image, padding, padding, width, height);

    const blob = await new Promise((resolve) => {
        canvas.toBlob(resolve, "image/png");
    });

    if (!blob) {
        throw new Error("Could not process the selected image.");
    }

    return new File([blob], "barcode-variant.png", {
        type: "image/png",
    });
}

export function createCameraScanner({
    elementId,
    onScan,
    facingMode = "environment",
}) {
    let html5Scanner = null;
    let lastCode = "";
    let lastDetectedAt = 0;
    let currentFacingMode = facingMode;

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

        const scanner = html5Scanner;
        html5Scanner = null;

        try {
            if (scanner.isScanning) {
                await scanner.stop();
            }

            await scanner.clear();
        } catch (error) {
            console.warn("Scanner cleanup:", error);
        }
    }

    async function getCameras() {
        await loadHtml5Qrcode();

        try {
            return await window.Html5Qrcode.getCameras();
        } catch (error) {
            console.warn("Could not list cameras:", error);
            return [];
        }
    }

    async function start() {
        if (!window.isSecureContext) {
            throw new Error("Camera access requires HTTPS or localhost.");
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error(
                "Camera scanning is not supported by this browser.",
            );
        }

        await loadHtml5Qrcode();

        const container = document.getElementById(elementId);

        if (!container) {
            throw new Error(`Scanner container #${elementId} not found.`);
        }

        if (container.offsetWidth === 0) {
            throw new Error("Scanner container is hidden.");
        }

        await stop();

        const cameras = await getCameras();

        if (!cameras.length) {
            throw new Error("No available camera was found.");
        }

        const isFrontCamera = (camera) =>
            /front|user|facetime|selfie/i.test(camera.label || "");

        const isBackCamera = (camera) =>
            /back|rear|environment|world/i.test(camera.label || "");

        let selectedCamera;

        if (currentFacingMode === "user") {
            selectedCamera = cameras.find(isFrontCamera) || cameras[0];
        } else {
            selectedCamera =
                cameras.find(isBackCamera) || cameras[cameras.length - 1];
        }

        const attempts = [selectedCamera.id, { facingMode: currentFacingMode }];

        let firstError = null;

        for (const camera of attempts) {
            html5Scanner = createScannerInstance(elementId);

            try {
                await html5Scanner.start(
                    camera,
                    {
                        ...SCAN_CONFIG,
                        videoConstraints: {
                            facingMode: currentFacingMode,
                            width: { ideal: 1920 },
                            height: { ideal: 1080 },
                        },
                    },
                    handleDecoded,
                    () => {},
                );

                return;
            } catch (error) {
                console.warn("Camera start failed:", error);

                firstError ??= error;

                await stop();

                if (getCameraErrorName(error) === "NotAllowedError") {
                    break;
                }
            }
        }

        throw toCameraError(firstError);
    }

    async function switchCamera(mode) {
        if (!["user", "environment"].includes(mode)) {
            throw new Error("Invalid camera mode.");
        }

        currentFacingMode = mode;

        await stop();
        await start();
    }

    async function scanFile(file) {
        if (!file) {
            throw new Error("Please select an image.");
        }

        await loadHtml5Qrcode();

        const container = document.getElementById(elementId);

        if (!container) {
            throw new Error(`Scanner container #${elementId} not found.`);
        }

        await stop();

        const fileScanner = createScannerInstance(elementId);

        try {
            const image = await loadImage(file);

            const candidates = [file];

            for (const maxSide of IMAGE_MAX_SIDE_OPTIONS_PX) {
                candidates.push(await createPaddedImageVariant(image, maxSide));
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
                    // Try the next image variant.
                }
            }
        } finally {
            try {
                await fileScanner.clear();
            } catch {
                // Ignore cleanup errors.
            }
        }

        throw new Error(
            "No barcode detected. Try a clear image with the barcode horizontal.",
        );
    }

    return {
        start,
        stop,
        scanFile,
        getCameras,
        switchCamera,
        isScanning: () => Boolean(html5Scanner?.isScanning),
        getFacingMode: () => currentFacingMode,
    };
}

if (typeof window !== "undefined") {
    window.vposBarcodeScanner = {
        playBeep,
        loadHtml5Qrcode,
        createCameraScanner,
    };
}
