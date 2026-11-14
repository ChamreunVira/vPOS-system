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
        localStorage.setItem(
            this.storageKey,
            JSON.stringify({ cart: this.cart, heldSales: this.heldSales }),
        );
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
        if (product.stock <= 0) return;
        const line = this.cart.find((item) => item.id === product.id);
        if (line) {
            if (line.quantity < product.stock) line.quantity++;
            return;
        }
        this.cart.push({
            id: product.id,
            name: product.name,
            emoji: product.emoji,
            price: product.price,
            stock: product.stock,
            quantity: 1,
        });
    },
    increase(line) {
        if (line.quantity < line.stock) line.quantity++;
    },
    decrease(line) {
        if (line.quantity > 1) line.quantity--;
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
        const product = this.products.find(
            (item) =>
                item.barcode === code ||
                item.sku.toLowerCase() === code.toLowerCase(),
        );
        if (product) this.addToCart(product);
        else this.flash(`No product found for "${code}"`);
        this.barcode = "";
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
        return this.cart.reduce(
            (sum, line) => sum + line.price * line.quantity,
            0,
        );
    },
    get discountAmount() {
        return (this.subtotal * this.discountPercent) / 100;
    },
    get vatAmount() {
        return (
            ((this.subtotal - this.discountAmount) * this.config.vatRate) / 100
        );
    },
    get total() {
        return this.subtotal - this.discountAmount + this.vatAmount;
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
