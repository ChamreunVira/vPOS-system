# 08 · UI/UX Guidelines

Blade + Tailwind CSS, black and white theme, built from the Figma prototype. Goal: fast, calm, readable at arm's length on a counter tablet.

## 1. Principles

1. **Speed first on the POS screen.** Fewest taps from scan to receipt; keyboard and scanner friendly.
2. **One visual language.** Same components everywhere; no one-off styles.
3. **Monochrome with purpose.** Black, white, and greys carry the interface; colour appears only for state (success, warning, danger).
4. **Obvious state.** Always show what is selected, what is disabled, and why.
5. **Forgiving.** Confirm destructive actions, allow undo where possible, keep the cart on errors.

## 2. Design Tokens

### Colour

| Token | Value | Use |
|-------|-------|-----|
| `--color-ink` | `#0A0A0A` | Primary text, primary buttons |
| `--color-ink-soft` | `#404040` | Secondary text |
| `--color-muted` | `#737373` | Hints, placeholders |
| `--color-line` | `#E5E5E5` | Borders, dividers |
| `--color-surface` | `#F5F5F5` | Page background, table headers |
| `--color-paper` | `#FFFFFF` | Cards, inputs |
| `--color-success` | `#15803D` | Paid, in stock |
| `--color-warning` | `#B45309` | Low stock, pending |
| `--color-danger` | `#B91C1C` | Errors, refunds, out of stock |

Status colour is never the only signal: always pair with a label or icon.

### Typography

| Use | Spec |
|-----|------|
| Font family | One sans-serif with Khmer support (e.g. Inter + Noto Sans Khmer fallback) |
| Base size | 14 px for admin pages, 16 px on the POS screen |
| Numbers | Tabular figures for prices and tables |
| Scale | 12 / 14 / 16 / 20 / 24 / 32 |
| Weights | 400 body, 500 labels, 600 headings |

### Spacing, radius, elevation

- Spacing scale: 4 px steps (4, 8, 12, 16, 24, 32).
- Radius: 6 px inputs and buttons, 10 px cards.
- Elevation: 1 px border by default; a soft shadow only for modals and dropdowns.

## 3. Layouts

| Layout | Used by | Structure |
|--------|---------|-----------|
| `auth` | Login | Centered card on a plain background |
| `app` | Admin pages | Left sidebar (collapsible), top bar (user, shift status), content area with page header |
| `pos` | POS and checkout | Full-screen, minimal chrome: product area left (about 65%), cart right (about 35%) |

Sidebar groups: Dashboard · POS · Sales (Orders, Customers) · Catalog (Products, Categories) · Inventory · Purchasing (Suppliers, Purchase Orders) · Finance (Expenses, Payments, Cash) · Reports · Administration (Users, Roles, Settings). Items are hidden when the user lacks the permission.

## 4. Reusable Blade Components

| Component | Notes |
|-----------|-------|
| `<x-button>` | variants: primary (black), secondary (outline), ghost, danger; sizes sm/md/lg; loading state |
| `<x-input>`, `<x-select>`, `<x-textarea>` | Label, hint, error message built in |
| `<x-money-input>` | Currency prefix, decimal handling |
| `<x-card>` | Header, body, footer slots |
| `<x-table>` | Sticky header, empty state, pagination slot, row actions |
| `<x-badge>` | Status badges mapped from enums |
| `<x-modal>`, `<x-confirm-dialog>` | Focus trap, Esc to close |
| `<x-stat-card>` | Dashboard number with label and delta |
| `<x-page-header>` | Title, breadcrumb, action buttons |
| `<x-filter-bar>` | Search, date range, status filters |
| `<x-empty-state>` | Icon, message, call to action |
| `<x-toast>` | Success and error feedback |
| `<x-product-tile>` | POS grid item: image, name, price, stock |
| `<x-cart-line>` | Name, qty stepper, line total, remove |
| `<x-receipt>` | Printable receipt, 58 mm and 80 mm widths |

Rules: components take data through props and slots only; no queries inside components; every component is shown with examples in a component README.

## 5. Screen Guidelines

### POS screen

- Search/scan field is focused on load and after every add; scanner Enter adds the product.
- Category chips above a product grid; grid shows image, name, price; out-of-stock tiles are dimmed and not tappable.
- Cart shows lines, qty stepper (min 1), line total, remove. Clear cart needs confirmation.
- Totals block: Subtotal · Discount (selector 0/5/10/15%) · VAT · **Total** (largest text on the screen). Show the KHR equivalent below the total.
- Primary button **Charge** is full width at the bottom of the cart and disabled when the cart is empty.
- Shift status (register name, opened time) always visible in the top bar.

### Checkout and payment

- Large method buttons: Cash, KHQR, Card.
- Cash: quick amounts (exact, +1, +5, +10, +20) and a numeric keypad; change due shown in large text.
- KHQR: QR displayed at a readable size with amount and countdown; status line updates on confirmation.
- Success screen: change due, Print receipt, New sale.

### Lists (products, orders, customers, expenses ...)

- Page header with the primary action on the right.
- Filter bar, then table, then pagination.
- Row actions in a single menu; destructive actions confirm.
- Empty state explains what to do next.

### Forms

- Single column, labels above fields, required fields marked.
- Validation errors appear under the field and in a summary on long forms.
- Save and Cancel buttons placed consistently at the bottom right.

### Dashboard

- Role-aware: Cashier sees own shift stats; Manager and Admin see sales today, orders, low stock, top products, recent orders, payment-method split.
- Profit figures only for users with `reports.view_profit`.

## 6. States

Every data view defines: **loading** (skeleton), **empty**, **error** (message plus retry), **success** (toast). Buttons that trigger requests show a loading state and prevent double submit, which is critical for the Charge button.

## 7. Receipts

- Header: logo, business name, address, phone, VAT number if any.
- Body: order number, date/time (Phnom Penh), cashier, items (name, qty × price, line total).
- Totals: subtotal, discount, VAT, total, paid, change; KHR equivalent.
- Footer: custom thank-you text from settings.
- Print CSS targets 58 mm and 80 mm thermal paper; monospace-friendly widths; no colour.

## 8. Accessibility and Localisation

- Contrast ratio at least 4.5:1 for text (the black and white palette meets this easily).
- Visible focus ring on all interactive elements; full keyboard operation on the POS screen.
- Touch targets at least 44 × 44 px on the POS screen.
- Form labels tied to inputs; icons that carry meaning have text or `aria-label`.
- Text in `lang/en` and `lang/km` files from the start so Khmer can be added without refactoring; allow text to grow 30% without breaking layouts.
- Dates `DD/MM/YYYY`, amounts with thousands separators; KHR without decimals.

## 9. Responsive Behaviour

| Width | Behaviour |
|-------|-----------|
| 1280 px and up | Full layout, sidebar open |
| 768–1279 px (tablet, main POS target) | Sidebar collapses to icons; POS keeps two columns |
| Below 768 px | Admin pages usable; POS cart becomes a slide-up sheet |
