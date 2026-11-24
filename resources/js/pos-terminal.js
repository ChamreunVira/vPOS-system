/**
 * Alpine component that powers the checkout screen.
 * Products are supplied by the server; checkout is persisted by the POS endpoint.
 */
import { createCameraScanner } from "./barcode-scanner";

export default ({ products, config }) => ({
    products,
    config,

    storageKey: "vpos-current-sale",

    query: "",
    barcode: "",
    category: "All",
    notice: "",
    scannerOpen: false,
    scannerLoading: false,
    scannerMessage: "",
    continuousScan: false,
    scannedFeedback: "",
    lastScannedCode: "",
    lastScannedAt: 0,
    scannerInstance: null,

    mobileModalOpen: false,
    pairingTab: "qr",
    mobileSession: null,
    isMobileConnected: false,
    mobileLoading: false,
    mobileError: "",
    lastEventId: 0,
    pollTimer: null,
    countdownText: "",
    countdownTimer: null,

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

        this.startScannerPolling();
        document.addEventListener("visibilitychange", () => {
            if (document.hidden) {
                this.stopScannerPolling();
            } else {
                this.startScannerPolling();
            }
        });
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
        const cleanCode = String(code).trim().toLowerCase();
        const product = this.products.find(
            (item) =>
                String(item.barcode ?? "").trim().toLowerCase() === cleanCode ||
                String(item.sku ?? "").trim().toLowerCase() === cleanCode ||
                String(item.id) === cleanCode,
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
        this.scannerLoading = true;
        this.scannerOpen = true;

        try {
            await this.$nextTick();

            if (!this.scannerInstance) {
                this.scannerInstance = createCameraScanner({
                    elementId: "pos-barcode-reader",
                    onScan: (code) => {
                        const now = Date.now();
                        if (code === this.lastScannedCode && now - this.lastScannedAt < 1500) {
                            return;
                        }
                        this.lastScannedCode = code;
                        this.lastScannedAt = now;

                        this.scannedFeedback = `Scanned: ${code}`;
                        setTimeout(() => {
                            this.scannedFeedback = "";
                        }, 2500);

                        this.addProductByCode(code);

                        if (!this.continuousScan) {
                            this.stopBarcodeScanner();
                        }
                    },
                });
            }

            await this.scannerInstance.start();
            this.scannerLoading = false;
        } catch (error) {
            this.scannerLoading = false;
            const message = error?.message || String(error);
            if (message.includes("NotAllowedError") || message.includes("Permission")) {
                this.scannerMessage = "Camera permission was denied. Please allow camera access in browser settings.";
            } else if (message.includes("NotFoundError") || message.includes("DevicesNotFoundError")) {
                this.scannerMessage = "No camera was detected on this device.";
            } else if (message.includes("NotReadableError")) {
                this.scannerMessage = "Camera is currently in use by another application.";
            } else {
                this.scannerMessage = message || "Could not open camera. Please check permissions.";
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
            this.scannerMessage = "Could not find a valid barcode in that image. Try a clearer image.";
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
        this.stopScannerPolling();
        if (this.countdownTimer) clearInterval(this.countdownTimer);
    },

    // ---------- mobile scanner integration ----------
    startScannerPolling() {
        if (this.pollTimer || !this.config.scannerEventsUrl) return;
        this.pollScannerEvents();
        this.pollTimer = setInterval(() => {
            if (!document.hidden) {
                this.pollScannerEvents();
            }
        }, 1500);
    },
    stopScannerPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    },
    async pollScannerEvents() {
        if (!this.config.scannerEventsUrl) return;
        try {
            const url = `${this.config.scannerEventsUrl}?after_id=${this.lastEventId}`;
            const response = await fetch(url, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });
            const result = await response.json();
            if (response.ok && result.success && result.data) {
                this.isMobileConnected = Boolean(result.data.is_paired);
                const events = Array.isArray(result.data.events)
                    ? result.data.events
                    : [];
                for (const event of events) {
                    this.addProductByCode(event.barcode);
                    this.lastEventId = Math.max(
                        this.lastEventId,
                        Number(event.id),
                    );
                }
            }
        } catch {
            // ignore transient polling errors
        }
    },
    async openMobileScannerModal() {
        this.mobileModalOpen = true;
        await this.fetchMobileSession();
    },
    async fetchMobileSession() {
        if (!this.config.scannerSessionUrl) return;
        this.mobileLoading = true;
        this.mobileError = "";
        try {
            const response = await fetch(this.config.scannerSessionUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });
            const result = await response.json();
            if (response.ok && result.success && result.data) {
                this.mobileSession = result.data;
                this.isMobileConnected = Boolean(result.data.is_paired);
                this.startCountdown(result.data.expires_at);
                if (result.data.qr_url) {
                    await this.renderQr(result.data.qr_url);
                }
            }
        } catch {
            this.mobileError = "Could not create scanner session.";
        } finally {
            this.mobileLoading = false;
        }
    },
    async regenerateMobileCode() {
        if (!this.config.scannerRegenerateUrl) return;
        this.mobileLoading = true;
        this.mobileError = "";
        try {
            const response = await fetch(this.config.scannerRegenerateUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });
            const result = await response.json();
            if (response.ok && result.success && result.data) {
                this.mobileSession = result.data;
                this.isMobileConnected = false;
                this.startCountdown(result.data.expires_at);
                if (result.data.qr_url) {
                    await this.renderQr(result.data.qr_url);
                }
            }
        } catch {
            this.mobileError = "Could not refresh pairing code.";
        } finally {
            this.mobileLoading = false;
        }
    },
    async disconnectMobileScanner() {
        if (!this.config.scannerDisconnectUrl) return;
        try {
            await fetch(this.config.scannerDisconnectUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });
            this.isMobileConnected = false;
            await this.regenerateMobileCode();
        } catch {
            // ignore
        }
    },
    async renderQr(text) {
        await this.$nextTick();
        if (!window.QRCode) {
            await new Promise((resolve) => {
                const existing = document.querySelector('script[data-qr="qrcode"]');
                if (existing) {
                    existing.addEventListener("load", () => resolve());
                    return;
                }
                const s = document.createElement("script");
                s.src = "/vendor/qrcode/qrcode.min.js";
                s.dataset.qr = "qrcode";
                s.async = true;
                s.onload = () => resolve();
                document.head.appendChild(s);
            });
        }
        const el = document.getElementById("desktop-pairing-qr");
        if (el) {
            el.innerHTML = "";
            new window.QRCode(el, {
                text: text,
                width: 180,
                height: 180,
                colorDark: "#0a0a0a",
                colorLight: "#ffffff",
                correctLevel: window.QRCode.CorrectLevel.M,
            });
        }
    },
    startCountdown(expiresAt) {
        if (this.countdownTimer) clearInterval(this.countdownTimer);
        const targetTime = new Date(expiresAt).getTime();
        const update = () => {
            const diff = Math.max(0, Math.floor((targetTime - Date.now()) / 1000));
            if (diff <= 0) {
                this.countdownText = "Expired · Click Refresh";
                clearInterval(this.countdownTimer);
            } else {
                const m = Math.floor(diff / 60);
                const s = diff % 60;
                this.countdownText = `Expires in ${m}:${s < 10 ? "0" : ""}${s}`;
            }
        };
        update();
        this.countdownTimer = setInterval(update, 1000);
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
        return (
            Math.round(
                this.cart.reduce(
                    (sum, line) =>
                        sum +
                        Math.round(line.price * line.quantity * 100) / 100,
                    0,
                ) * 100,
            ) / 100
        );
    },
    get discountAmount() {
        return Math.round(this.subtotal * this.discountPercent) / 100;
    },
    get vatAmount() {
        return (
            Math.round(
                (this.subtotal - this.discountAmount) * this.config.vatRate,
            ) / 100
        );
    },
    get total() {
        return (
            Math.round(
                (this.subtotal - this.discountAmount + this.vatAmount) * 100,
            ) / 100
        );
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
