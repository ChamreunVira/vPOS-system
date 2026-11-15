/**
 * Alpine component that powers the checkout screen.
 * Products are supplied by the server; checkout is persisted by the POS endpoint.
 */
export default ({ products, config }) => ({
    products,
    config,

    storageKey: "vpos-current-sale",

    query: "",
    barcode: "",
    category: "All",
    notice: "",
    scannerOpen: false,
    scannerMessage: "",
    videoStream: null,
    detector: null,
    scanFrame: null,

    cart: [],
    heldSales: [],
    customerId: "",
    discountPercent: 0,

    paymentOpen: false,
    receiptOpen: false,
    completing: false,
    checkoutError: "",
    method: "cash",
    received: "",
    lastSale: null,

    init() {
        try {
            const saved = JSON.parse(
                localStorage.getItem(this.storageKey) || "{}",
            );
            this.cart = Array.isArray(saved.cart) ? saved.cart : [];
            this.heldSales = Array.isArray(saved.heldSales)
                ? saved.heldSales
                : [];
            this.cart = this.cart
                .filter((line) =>
                    this.products.some((product) => product.id === line.id),
                )
                .map((line) => {
                    const product = this.products.find(
                        (item) => item.id === line.id,
                    );
                    return {
                        ...line,
                        name: product.name,
                        price: product.price,
                        stock: product.stock,
                        emoji: product.emoji,
                        image: product.image,
                        allowDecimal: product.allow_decimal,
                    };
                });
        } catch {
            this.cart = [];
            this.heldSales = [];
        }

        this.$watch("cart", () => this.persistCart());
        this.$watch("heldSales", () => this.persistCart());
    },
    persistCart() {
        try {
            localStorage.setItem(
                this.storageKey,
                JSON.stringify({ cart: this.cart, heldSales: this.heldSales }),
            );
        } catch {
            this.flash("This browser could not save the cart for later.");
        }
    },

    // ---------- formatting ----------
    usd(value) {
        return (
            "$" +
            Number(value).toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    },
    khr(value) {
        const rounded =
            Math.round((value * this.config.exchangeRate) / 100) * 100;
        return "៛" + rounded.toLocaleString("en-US");
    },

    // ---------- catalogue ----------
    get categories() {
        return [
            "All",
            ...new Set(this.products.map((product) => product.category)),
        ];
    },
    get filteredProducts() {
        const term = this.query.trim().toLowerCase();
        return this.products.filter((product) => {
            const inCategory =
                this.category === "All" || product.category === this.category;
            const matches =
                !term ||
                [product.name, product.sku, product.barcode].some((value) =>
                    value.toLowerCase().includes(term),
                );
            return product.status === "active" && inCategory && matches;
        });
    },

    // ---------- cart ----------
    addToCart(product) {
        const minimumQuantity = product.allow_decimal ? 0.01 : 1;
        if (product.stock < minimumQuantity) return;
        const line = this.cart.find((item) => item.id === product.id);
        if (line) {
            this.increase(line);
            return;
        }
        const quantity = Math.min(1, product.stock);
        this.cart.push({
            id: product.id,
            name: product.name,
            emoji: product.emoji,
            image: product.image,
            price: product.price,
            stock: product.stock,
            allowDecimal: product.allow_decimal,
            quantity,
        });
    },
    increase(line) {
        const step = line.allowDecimal ? 0.1 : 1;
        line.quantity = Math.min(
            line.stock,
            Math.round((line.quantity + step) * 100) / 100,
        );
    },
    decrease(line) {
        const step = line.allowDecimal ? 0.1 : 1;
        const nextQuantity = Math.round((line.quantity - step) * 100) / 100;
        if (nextQuantity > 0) line.quantity = nextQuantity;
        else this.removeFromCart(line);
    },
    removeFromCart(line) {
        this.cart = this.cart.filter((item) => item.id !== line.id);
    },
    clearCart() {
        this.cart = [];
        this.discountPercent = 0;
        this.customerId = "";
    },
    scanBarcode() {
        const code = this.barcode.trim();
        if (!code) return;
        this.addProductByCode(code);
        this.barcode = "";
    },
    addProductByCode(code) {
        const product = this.products.find(
            (item) =>
                item.barcode === code ||
                item.sku.toLowerCase() === code.toLowerCase(),
        );
        if (product) {
            const previousQuantity =
                this.cart.find((line) => line.id === product.id)?.quantity ?? 0;
            this.addToCart(product);
            const line = this.cart.find((item) => item.id === product.id);
            if (line && line.quantity > previousQuantity) {
                this.flash(`${product.name} added to sale`);
            } else {
                this.flash(`${product.name} has no more stock available`);
            }
        } else {
            this.flash(`No product found for "${code}"`);
        }
    },
    async startBarcodeScanner() {
        this.scannerMessage = "";
        this.scannerOpen = true;

        if (
            !window.isSecureContext ||
            !navigator.mediaDevices?.getUserMedia ||
            !("BarcodeDetector" in window)
        ) {
            this.scannerMessage = "Camera scanning needs a supported browser and a secure HTTPS connection. Use the barcode field instead.";
            return;
        }

        try {
            const supportedFormats = await BarcodeDetector.getSupportedFormats();
            if (!this.scannerOpen) return;
            const formats = [
                "ean_13",
                "ean_8",
                "upc_a",
                "upc_e",
                "code_128",
                "code_39",
            ].filter((format) => supportedFormats.includes(format));
            if (!formats.length) {
                throw new Error("No supported barcode formats");
            }

            this.detector = new BarcodeDetector({ formats });
            const videoStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: "environment" } },
                audio: false,
            });
            if (!this.scannerOpen) {
                videoStream.getTracks().forEach((track) => track.stop());
                return;
            }
            this.videoStream = videoStream;
            this.$refs.barcodeVideo.srcObject = this.videoStream;
            await this.$refs.barcodeVideo.play();
            if (!this.scannerOpen) return;
            this.scanBarcodeFromCamera();
        } catch {
            const scannerWasOpen = this.scannerOpen;
            this.stopBarcodeScanner();
            if (scannerWasOpen) {
                this.scannerOpen = true;
                this.scannerMessage = "Could not open the camera. Check camera permission and use the barcode field if needed.";
            }
        }
    },
    async scanBarcodeFromCamera() {
        if (!this.scannerOpen || !this.detector || !this.videoStream) return;

        try {
            const matches = await this.detector.detect(this.$refs.barcodeVideo);
            if (matches.length) {
                this.addProductByCode(matches[0].rawValue.trim());
                this.stopBarcodeScanner();
                return;
            }
        } catch {
            this.scannerMessage = "Could not read the barcode. Try again or enter it manually.";
        }

        this.scanFrame = requestAnimationFrame(() => this.scanBarcodeFromCamera());
    },
    stopBarcodeScanner() {
        this.scannerOpen = false;
        if (this.scanFrame) cancelAnimationFrame(this.scanFrame);
        this.scanFrame = null;
        this.videoStream?.getTracks().forEach((track) => track.stop());
        this.videoStream = null;
        this.detector = null;
    },
    destroy() {
        this.stopBarcodeScanner();
    },
    holdSale() {
        if (!this.cart.length) return;
        this.heldSales.push({
            cart: this.cart,
            customerId: this.customerId,
            discountPercent: this.discountPercent,
        });
        this.cart = [];
        this.customerId = "";
        this.discountPercent = 0;
    },
    resumeSale() {
        const held = this.heldSales.pop();
        if (!held) return;
        this.cart = held.cart;
        this.customerId = held.customerId;
        this.discountPercent = held.discountPercent;
    },
    flash(message) {
        this.notice = message;
        setTimeout(() => (this.notice = ""), 2500);
    },

    // ---------- totals ----------
    get itemCount() {
        return this.cart.reduce((sum, line) => sum + line.quantity, 0);
    },
    get subtotal() {
        return Math.round(
            this.cart.reduce(
                (sum, line) =>
                    sum + Math.round(line.price * line.quantity * 100) / 100,
                0,
            ) * 100,
        ) / 100;
    },
    get discountAmount() {
        return Math.round(this.subtotal * this.discountPercent) / 100;
    },
    get vatAmount() {
        return Math.round(
            ((this.subtotal - this.discountAmount) * this.config.vatRate),
        ) / 100;
    },
    get total() {
        return Math.round(
            (this.subtotal - this.discountAmount + this.vatAmount) * 100,
        ) / 100;
    },

    // ---------- payment ----------
    get receivedAmount() {
        return parseFloat(this.received) || 0;
    },
    get change() {
        return Math.max(this.receivedAmount - this.total, 0);
    },
    get canComplete() {
        return (
            !this.completing &&
            this.cart.length > 0 &&
            (this.method !== "cash" || this.receivedAmount >= this.total)
        );
    },
    get quickAmounts() {
        const exact = Math.ceil(this.total);
        return [
            ...new Set([
                this.total,
                exact,
                Math.ceil(exact / 5) * 5,
                Math.ceil(exact / 10) * 10,
                Math.ceil(exact / 20) * 20,
            ]),
        ].slice(0, 4);
    },
    openPayment() {
        if (!this.cart.length) return;
        this.method = "cash";
        this.received = "";
        this.checkoutError = "";
        this.paymentOpen = true;
    },
    pressKey(key) {
        if (key === "back") this.received = this.received.slice(0, -1);
        else if (key === "." && this.received.includes(".")) return;
        else this.received += key;
    },
    async completeSale() {
        if (!this.canComplete || this.completing) return;
        this.completing = true;
        this.checkoutError = "";

        try {
            const response = await fetch(this.config.checkoutUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
                body: JSON.stringify({
                    items: this.cart.map((line) => ({
                        product_id: line.id,
                        quantity: line.quantity,
                    })),
                    customer_id: this.customerId || null,
                    discount_percent: this.discountPercent,
                    payment_method: this.method,
                    amount_received:
                        this.method === "cash" ? this.receivedAmount : null,
                }),
            });
            const payload = await response.json();

            if (!response.ok) {
                const validationMessage = Object.values(
                    payload.errors || {},
                ).flat()[0];
                throw new Error(
                    validationMessage ||
                        payload.message ||
                        "Unable to complete this sale.",
                );
            }

            this.products = this.products.map((product) => ({
                ...product,
                stock: payload.stock?.[product.id] ?? product.stock,
            }));
            this.lastSale = payload.receipt;
            this.cart = [];
            this.customerId = "";
            this.discountPercent = 0;
            this.received = "";
            this.paymentOpen = false;
            this.receiptOpen = true;
        } catch (error) {
            this.checkoutError =
                error.message || "Unable to reach the server. Try again.";
        } finally {
            this.completing = false;
        }
    },
    startNewSale() {
        this.clearCart();
        this.receiptOpen = false;
        this.lastSale = null;
    },
});
