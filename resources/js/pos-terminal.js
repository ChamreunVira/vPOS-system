/**
 * Alpine component that powers the checkout screen.
 * It runs entirely on the client with the products passed from Blade,
 * so you can replace the TODO in completeSale() with a real POST request.
 */
export default ({ products, config }) => ({
    products,
    config,

    query: '',
    barcode: '',
    category: 'All',
    notice: '',

    cart: [],
    heldSales: [],
    customerId: '',
    discountPercent: 0,

    paymentOpen: false,
    receiptOpen: false,
    method: 'cash',
    received: '',
    lastSale: null,

    // ---------- formatting ----------
    usd(value) {
        return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    khr(value) {
        const rounded = Math.round((value * this.config.exchangeRate) / 100) * 100;
        return '៛' + rounded.toLocaleString('en-US');
    },

    // ---------- catalogue ----------
    get categories() {
        return ['All', ...new Set(this.products.map((product) => product.category))];
    },
    get filteredProducts() {
        const term = this.query.trim().toLowerCase();
        return this.products.filter((product) => {
            const inCategory = this.category === 'All' || product.category === this.category;
            const matches = !term || [product.name, product.sku, product.barcode].some((value) => value.toLowerCase().includes(term));
            return product.status === 'active' && inCategory && matches;
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
        this.cart.push({ id: product.id, name: product.name, emoji: product.emoji, price: product.price, stock: product.stock, quantity: 1 });
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
        this.customerId = '';
    },
    scanBarcode() {
        const code = this.barcode.trim();
        if (!code) return;
        const product = this.products.find((item) => item.barcode === code || item.sku.toLowerCase() === code.toLowerCase());
        if (product) this.addToCart(product);
        else this.flash(`No product found for "${code}"`);
        this.barcode = '';
    },
    holdSale() {
        if (!this.cart.length) return;
        this.heldSales.push({ cart: this.cart, customerId: this.customerId, discountPercent: this.discountPercent });
        this.cart = [];
        this.customerId = '';
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
        setTimeout(() => (this.notice = ''), 2500);
    },

    // ---------- totals ----------
    get itemCount() {
        return this.cart.reduce((sum, line) => sum + line.quantity, 0);
    },
    get subtotal() {
        return this.cart.reduce((sum, line) => sum + line.price * line.quantity, 0);
    },
    get discountAmount() {
        return (this.subtotal * this.discountPercent) / 100;
    },
    get vatAmount() {
        return ((this.subtotal - this.discountAmount) * this.config.vatRate) / 100;
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
        return this.method !== 'cash' || this.receivedAmount >= this.total;
    },
    get quickAmounts() {
        const exact = Math.ceil(this.total);
        return [...new Set([this.total, exact, Math.ceil(exact / 5) * 5, Math.ceil(exact / 10) * 10, Math.ceil(exact / 20) * 20])].slice(0, 4);
    },
    openPayment() {
        if (!this.cart.length) return;
        this.method = 'cash';
        this.received = '';
        this.paymentOpen = true;
    },
    pressKey(key) {
        if (key === 'back') this.received = this.received.slice(0, -1);
        else if (key === '.' && this.received.includes('.')) return;
        else this.received += key;
    },
    completeSale() {
        if (!this.canComplete) return;
        // TODO: POST this payload to this.config.checkoutUrl (include the CSRF token) and use the response.
        const customer = this.config.customers.find((item) => String(item.id) === String(this.customerId));
        this.lastSale = {
            number: '#INV-' + (this.config.nextInvoice + Math.floor(Math.random() * 5)),
            placedAt: new Date().toLocaleString('en-GB'),
            customer: customer ? customer.name : 'Walk-in customer',
            lines: this.cart.map((line) => ({ ...line })),
            subtotal: this.subtotal,
            discountPercent: this.discountPercent,
            discountAmount: this.discountAmount,
            vatAmount: this.vatAmount,
            total: this.total,
            method: this.method,
            received: this.method === 'cash' ? this.receivedAmount : this.total,
            change: this.method === 'cash' ? this.change : 0,
        };
        this.paymentOpen = false;
        this.receiptOpen = true;
    },
    startNewSale() {
        this.clearCart();
        this.receiptOpen = false;
        this.lastSale = null;
    },
});
