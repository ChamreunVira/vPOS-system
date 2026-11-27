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
    cameraFacingMode: "environment",
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
    recentlyAddedId: null,
    removedLine: null,
    removeTimer: null,
    addHighlightTimer: null,
    modalTrigger: null,
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
                        image: product.image_url,
                        allowDecimal: product.unit?.allow_decimal ?? false,
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
    handleShortcut(event) {
        if (event.altKey || event.ctrlKey || event.metaKey) return;

        if (event.key === "Escape") {
            if (this.mobileModalOpen) this.closeDialog("mobileModalOpen");
            else if (this.scannerOpen) this.stopBarcodeScanner();
            else if (this.paymentOpen) this.closeDialog("paymentOpen");
            else if (this.receiptOpen) this.closeDialog("receiptOpen");
            return;
        }

        if (
            (this.mobileModalOpen || this.scannerOpen || this.paymentOpen || this.receiptOpen) &&
            event.key !== "F10"
        ) {
            return;
        }

        if (event.key === "F2") {
            event.preventDefault();
            this.$refs.barcode?.focus();
        } else if (event.key === "F4") {
            event.preventDefault();
            this.selectPaymentMethod("cash");
        } else if (event.key === "F8") {
            event.preventDefault();
            this.selectPaymentMethod("khqr");
        } else if (event.key === "F9") {
            event.preventDefault();
            this.selectPaymentMethod("card");
        } else if (event.key === "F10") {
            event.preventDefault();
            this.completeSale();
        }
    },
    selectPaymentMethod(method) {
        this.method = method;
        if (method === "cash") {
            this.$nextTick(() => {
                const receivedInput = this.paymentOpen
                    ? this.$refs.paymentReceived
                    : this.$refs.received;
                receivedInput?.focus();
            });
        }
    },
    rememberModalTrigger() {
        this.modalTrigger = document.activeElement;
    },
    closeDialog(name) {
        this[name] = false;
        this.$nextTick(() => {
            this.modalTrigger?.focus?.();
            this.modalTrigger = null;
        });
    },
    trapDialogFocus(event) {
        const focusable = Array.from(
            event.currentTarget.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            ),
        ).filter((element) => !element.closest('[x-cloak]'));
        const visibleFocusable = focusable.filter((element) => element.getClientRects().length > 0);

        if (!visibleFocusable.length) return;

        const first = visibleFocusable[0];
        const last = visibleFocusable[visibleFocusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            first.focus();
        }
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
            ...new Set(
                this.products.map(
                    (product) => product.category?.name ?? "Uncategorized",
                ),
            ),
        ];
    },
    get filteredProducts() {
        const term = this.query.trim().toLowerCase();
        return this.products.filter((product) => {
            const inCategory =
                this.category === "All" ||
                (product.category?.name ?? "Uncategorized") === this.category;
            const matches =
                !term ||
                [product.name, product.sku, product.barcode].some((value) =>
                    String(value ?? "").toLowerCase().includes(term),
                );
            return product.status === "active" && inCategory && matches;
        });
    },

    // ---------- cart ----------
    addToCart(product) {
        const allowDecimal = product.unit?.allow_decimal ?? false;
        const minimumQuantity = allowDecimal ? 0.01 : 1;
        if (product.stock < minimumQuantity) return;
        const line = this.cart.find((item) => item.id === product.id);
        if (line) {
            this.increase(line);
            this.highlightCartLine(product.id);
            return;
        }
        const quantity = Math.min(1, product.stock);
        this.cart.push({
            id: product.id,
            name: product.name,
            emoji: product.emoji,
            image: product.image_url,
            price: product.price,
            stock: product.stock,
            allowDecimal,
            quantity,
        });
        this.highlightCartLine(product.id);
    },
    highlightCartLine(productId) {
        this.recentlyAddedId = productId;
        clearTimeout(this.addHighlightTimer);
        this.addHighlightTimer = setTimeout(() => {
            this.recentlyAddedId = null;
        }, 1200);
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
        clearTimeout(this.removeTimer);
        this.removedLine = {
            line: { ...line },
            index: this.cart.findIndex((item) => item.id === line.id),
        };
        this.cart = this.cart.filter((item) => item.id !== line.id);
        this.removeTimer = setTimeout(() => {
            this.removedLine = null;
        }, 5000);
    },
    undoRemove() {
        if (!this.removedLine) return;
        this.cart.splice(this.removedLine.index, 0, this.removedLine.line);
        this.removedLine = null;
        clearTimeout(this.removeTimer);
    },
    requestClearCart(confirmationMessage) {
        if (!this.cart.length) return;
        if (window.confirm(confirmationMessage)) {
            this.clearCart();
        }
    },
    clearCart() {
        this.cart = [];
        this.discountPercent = 0;
        this.customerId = "";
        this.received = "";
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
        if (!this.scannerOpen) this.rememberModalTrigger();
        this.scannerMessage = "";
        this.scannerLoading = true;
        this.scannerOpen = true;

        try {
            await this.$nextTick();
            document.querySelector("[data-camera-close]")?.focus();

            if (!this.scannerInstance) {
                this.scannerInstance = createCameraScanner({
                    elementId: "pos-barcode-reader",
                    facingMode: this.cameraFacingMode,
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
    async switchBarcodeCamera() {
        if (!this.scannerInstance) return;

        this.scannerLoading = true;
        this.scannerMessage = "";

        try {
            await this.scannerInstance.switchCamera(this.cameraFacingMode);
        } catch (error) {
            this.scannerMessage = error?.message || "Could not switch cameras.";
        } finally {
            this.scannerLoading = false;
        }
    },
    async stopBarcodeScanner() {
        this.closeDialog("scannerOpen");
        this.scannerLoading = false;
        if (this.scannerInstance) {
            await this.scannerInstance.stop();
        }
    },
    destroy() {
        this.stopBarcodeScanner();
        this.stopScannerPolling();
        if (this.countdownTimer) clearInterval(this.countdownTimer);
        clearTimeout(this.removeTimer);
        clearTimeout(this.addHighlightTimer);
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
        this.rememberModalTrigger();
        this.mobileModalOpen = true;
        await this.$nextTick();
        document.querySelector("[data-mobile-close]")?.focus();
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
            } else {
                this.mobileError = result.message || "Could not create scanner session.";
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
            } else {
                this.mobileError = result.message || "Could not refresh pairing code.";
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
        this.received = "";
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
        this.rememberModalTrigger();
        this.method = "cash";
        this.received = "";
        this.checkoutError = "";
        this.paymentOpen = true;
        this.$nextTick(() => this.$refs.paymentReceived?.focus());
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
            if (!this.paymentOpen) this.rememberModalTrigger();
            this.paymentOpen = false;
            this.receiptOpen = true;
            this.$nextTick(() => document.querySelector("[data-receipt-focus]")?.focus());
        } catch (error) {
            this.checkoutError =
                error.message || "Unable to reach the server. Try again.";
        } finally {
            this.completing = false;
        }
    },
    startNewSale() {
        this.clearCart();
        this.closeDialog("receiptOpen");
        this.lastSale = null;
    },
});
