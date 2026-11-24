/**
 * Alpine component for the Product Create / Edit form.
 * Handles margin calculation, barcode camera scanning, and image OCR text recognition.
 */
export default (config = {}) => ({
    name: config.name || "",
    userModifiedName: Boolean(config.userModifiedName),
    cost: Number(config.cost) || 0,
    price: Number(config.price) || 0,
    barcode: config.barcode || "",
    recognizeUrl: config.recognizeUrl || "/admin/products/recognize-image",

    ocrLoading: false,
    ocrMessage: "",
    ocrSuggestedName: "",
    ocrCandidates: [],

    scannerOpen: false,
    scannerLoading: false,
    scannerMessage: "",
    scannedFeedback: "",
    scannerInstance: null,

    get margin() {
        return this.price > 0
            ? Math.round(((this.price - this.cost) / this.price) * 100)
            : 0;
    },

    async recognizeProductImage(file) {
        if (!file || !file.type.startsWith("image/")) return;

        this.ocrLoading = true;
        this.ocrMessage = "Recognizing product text…";
        this.ocrSuggestedName = "";
        this.ocrCandidates = [];

        const formData = new FormData();
        formData.append("image", file);

        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            const response = await fetch(this.recognizeUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken || "",
                    Accept: "application/json",
                },
                body: formData,
            });

            const result = await response.json();

            if (response.ok && result.success && result.data) {
                const suggested = result.data.product_name;
                this.ocrCandidates = Array.isArray(result.data.extracted_text)
                    ? result.data.extracted_text
                    : [];

                if (suggested) {
                    this.ocrSuggestedName = suggested;
                    if (!this.name.trim() || !this.userModifiedName) {
                        this.name = suggested;
                        this.userModifiedName = false;
                        this.ocrMessage = "Product name auto-suggested from image.";
                    } else {
                        this.ocrMessage = "OCR detected: " + suggested;
                    }
                } else if (this.ocrCandidates.length > 0) {
                    this.ocrMessage =
                        "Text recognized. Choose a suggestion below or enter manually.";
                } else {
                    this.ocrMessage =
                        "No product name could be detected. You can type it manually.";
                }
            } else {
                this.ocrMessage =
                    result.message ||
                    "Could not recognize text from image. You can type it manually.";
            }
        } catch {
            this.ocrMessage =
                "Recognition request failed. You can type the product name manually.";
        } finally {
            this.ocrLoading = false;
        }
    },

    async startBarcodeScanner() {
        this.scannerMessage = "";
        this.scannerLoading = true;
        this.scannerOpen = true;

        try {
            await this.$nextTick();

            if (!this.scannerInstance) {
                this.scannerInstance =
                    window.vposBarcodeScanner.createCameraScanner({
                        elementId: "product-barcode-reader",
                        onScan: (code) => {
                            this.barcode = code;
                            this.scannedFeedback = "Scanned: " + code;
                            this.stopBarcodeScanner();
                            setTimeout(() => {
                                this.scannedFeedback = "";
                            }, 3000);
                        },
                    });
            }

            await this.scannerInstance.start();
            this.scannerLoading = false;
        } catch (error) {
            this.scannerLoading = false;
            const message = error?.message || String(error);
            if (
                message.includes("NotAllowedError") ||
                message.includes("Permission")
            ) {
                this.scannerMessage =
                    "Camera permission was denied. Please allow camera access in browser settings.";
            } else if (
                message.includes("NotFoundError") ||
                message.includes("DevicesNotFoundError")
            ) {
                this.scannerMessage = "No camera was detected on this device.";
            } else if (message.includes("NotReadableError")) {
                this.scannerMessage =
                    "Camera is currently in use by another application.";
            } else {
                this.scannerMessage =
                    message || "Could not open camera. Please check permissions.";
            }
        }
    },

    async scanFromFile(event) {
        const file = event.target.files?.[0];
        if (!file || !this.scannerInstance) return;
        this.scannerMessage = "";
        try {
            await this.scannerInstance.scanFile(file);
        } catch {
            this.scannerMessage =
                "Could not find a valid barcode in that image. Try a clearer photo.";
        }
        event.target.value = "";
    },

    async stopBarcodeScanner() {
        this.scannerOpen = false;
        this.scannerLoading = false;
        if (this.scannerInstance) {
            await this.scannerInstance.stop();
        }
    },

    destroy() {
        this.stopBarcodeScanner();
    },
});
