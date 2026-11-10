# 02 · Functional Requirements

Priority: **MUST** = MVP · **SHOULD** = MVP if time allows · **COULD** = post-MVP

## FR-AUTH · Authentication & Access

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-AUTH-01 | Users sign in with email (or phone) and password | MUST |
| FR-AUTH-02 | Inactive users cannot sign in | MUST |
| FR-AUTH-03 | Each user has exactly one role; each role has many permissions | MUST |
| FR-AUTH-04 | Every page and action is authorised by permission (`module.action`) | MUST |
| FR-AUTH-05 | Users can change their own password | MUST |
| FR-AUTH-06 | Login throttling after repeated failures | SHOULD |

## FR-PROD · Products & Categories

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-PROD-01 | CRUD for categories with active/inactive status | MUST |
| FR-PROD-02 | CRUD for products: name, category, SKU, barcode, cost, price, min stock, image, status | MUST |
| FR-PROD-03 | SKU and barcode are unique | MUST |
| FR-PROD-04 | Search and filter by name, SKU, barcode, category, status | MUST |
| FR-PROD-05 | Low-stock indicator when `stock <= min_stock` | MUST |
| FR-PROD-06 | Inactive products are hidden from the POS screen | MUST |
| FR-PROD-07 | Bulk import from CSV/Excel | COULD |

## FR-INV · Inventory

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-INV-01 | Every stock change creates a stock movement row | MUST |
| FR-INV-02 | Manual adjustment with required reason (damage, count correction) | MUST |
| FR-INV-03 | Stock movement history per product with running balance | MUST |
| FR-INV-04 | Low-stock report | MUST |
| FR-INV-05 | Stock take (bulk count and adjust) | COULD |

## FR-POS · Checkout

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-POS-01 | A cashier must have an open cash session to sell | MUST |
| FR-POS-02 | Add products by barcode scan, search, or tap | MUST |
| FR-POS-03 | Edit quantity, remove line, clear cart | MUST |
| FR-POS-04 | Order-level discount of 0 / 5 / 10 / 15 percent | MUST |
| FR-POS-05 | VAT applied at the configured rate (default 10%) | MUST |
| FR-POS-06 | Optional customer attachment (search or quick create) | MUST |
| FR-POS-07 | Pay with cash, KHQR, or card; change calculated for cash | MUST |
| FR-POS-08 | Split payment across multiple methods | SHOULD |
| FR-POS-09 | Receipt preview and print | MUST |
| FR-POS-10 | Prevent selling more than available stock (configurable) | MUST |
| FR-POS-11 | Hold and resume a cart | COULD |

## FR-ORD · Orders, Refunds, Returns

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ORD-01 | List and filter orders by date, status, cashier, customer, payment method | MUST |
| FR-ORD-02 | Order detail with items, payments, and stock effect | MUST |
| FR-ORD-03 | Cancel an order only before payment completes | MUST |
| FR-ORD-04 | Full or partial refund with reason, restocking optional per item | MUST |
| FR-ORD-05 | Reprint receipt | MUST |

## FR-CUS · Customers

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-CUS-01 | CRUD for customers (name, phone, email, address, notes, status) | MUST |
| FR-CUS-02 | Purchase history per customer | SHOULD |

## FR-PUR · Suppliers & Purchasing

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-PUR-01 | CRUD for suppliers | MUST |
| FR-PUR-02 | Create a purchase order with items, unit cost, quantity | MUST |
| FR-PUR-03 | Receive a purchase order: increases stock and writes stock movements | MUST |
| FR-PUR-04 | Optionally update product cost on receive | SHOULD |
| FR-PUR-05 | Cancel a purchase order that is not yet received | MUST |

## FR-EXP · Expenses

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-EXP-01 | CRUD for expense categories | MUST |
| FR-EXP-02 | Record an expense: category, amount, date, description, payment method | MUST |
| FR-EXP-03 | Filter expenses by date range and category | MUST |

## FR-CASH · Cash Management

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-CASH-01 | CRUD for cash registers | MUST |
| FR-CASH-02 | Open a session with an opening float | MUST |
| FR-CASH-03 | Record cash in / cash out with a reason during a session | MUST |
| FR-CASH-04 | Close a session: enter counted amount, system shows expected and variance | MUST |
| FR-CASH-05 | Only one open session per register and per cashier | MUST |
| FR-CASH-06 | Daily cash summary | MUST |

## FR-PAY · Payments

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-PAY-01 | Enable or disable payment methods | MUST |
| FR-PAY-02 | Generate KHQR for the order amount and confirm payment | MUST |
| FR-PAY-03 | Record reference (bank transaction ID) for non-cash payments | MUST |
| FR-PAY-04 | Track refunded amount per payment | MUST |

## FR-REP · Reports

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-REP-01 | Sales summary by day, week, month | MUST |
| FR-REP-02 | Top products, sales by category, sales by cashier | MUST |
| FR-REP-03 | Profit estimate (sales minus cost of goods minus expenses) | SHOULD |
| FR-REP-04 | Inventory valuation and low-stock | MUST |
| FR-REP-05 | Export any report to PDF and Excel | MUST |

## FR-USR · Employees, Roles, Settings

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-USR-01 | CRUD for users with role and status | MUST |
| FR-USR-02 | Manage roles and assign permissions per module/action | MUST |
| FR-SET-01 | Business profile and logo | MUST |
| FR-SET-02 | Currency (USD/KHR), exchange rate, VAT rate | MUST |
| FR-SET-03 | Receipt header/footer text | MUST |
| FR-SET-04 | Activity log of sensitive actions (refund, price change, stock adjust) | SHOULD |

## Non-Functional Requirements

| ID | Requirement |
|----|-------------|
| NFR-01 | Checkout response under 500 ms for cart operations on a typical store dataset (10k products) |
| NFR-02 | All monetary fields use `decimal`; all multi-table writes run in a database transaction |
| NFR-03 | Passwords hashed with bcrypt/argon2; CSRF protection on all forms |
| NFR-04 | UI usable on a 10" tablet and a desktop; touch targets at least 44 px |
| NFR-05 | UI supports Khmer and English text rendering (Khmer-capable font) |
| NFR-06 | Daily automated database backup |
| NFR-07 | Timestamps stored in UTC, displayed in `Asia/Phnom_Penh` |
