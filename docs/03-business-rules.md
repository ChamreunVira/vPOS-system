# 03 · Business Rules

Rules the system must enforce regardless of which screen triggers them. Each rule has an ID so tests and code can reference it.

## 1. Pricing & Totals

| ID | Rule |
|----|------|
| BR-PRC-01 | `line_total = quantity × unit_price` |
| BR-PRC-02 | `subtotal = Σ line_total` |
| BR-PRC-03 | `discount_amount = subtotal × discount_percent / 100` |
| BR-PRC-04 | `tax_amount = (subtotal − discount_amount) × tax_rate / 100` |
| BR-PRC-05 | `total = subtotal − discount_amount + tax_amount` |
| BR-PRC-06 | Allowed `discount_percent` values: 0, 5, 10, 15 |
| BR-PRC-07 | Default `tax_rate` is 10 (percent) and comes from settings, then is **copied onto the order** so later setting changes never alter past orders |
| BR-PRC-08 | `unit_price` and `unit_cost` are **snapshotted** from the product when the item is added to the order |
| BR-PRC-09 | Rounding: calculate in full precision, round half-up to 2 decimals once at each stored amount |
| BR-PRC-10 | Price must be greater than or equal to 0; selling below cost raises a warning but is allowed |

### Worked example

| Item | Qty | Unit price | Line total |
|------|-----|-----------|------------|
| Coca-Cola 330ml | 3 | 0.75 | 2.25 |
| Instant noodles | 5 | 0.40 | 2.00 |

- Subtotal = 4.25
- Discount 10% = 0.425 → 0.43
- Taxable = 3.82
- VAT 10% = 0.382 → 0.38
- **Total = 4.20**

## 2. Currency

| ID | Rule |
|----|------|
| BR-CUR-01 | USD is the base currency for storage |
| BR-CUR-02 | KHR amounts are derived using the exchange rate in settings (for example 1 USD = 4,100 KHR) |
| BR-CUR-03 | The exchange rate used is recorded with the payment when paid in KHR (**Proposed** columns, see 04) |
| BR-CUR-04 | KHR cash is rounded to the nearest 100 riel on display |

## 3. Order Lifecycle

```
pending ──► completed ──► partially_refunded ──► refunded
   │
   └──► cancelled
```

| ID | Rule |
|----|------|
| BR-ORD-01 | `order_number` is unique and sequential per day or per store, for example `INV-20261110-0001` |
| BR-ORD-02 | An order is `pending` until payments cover the full total |
| BR-ORD-03 | When `Σ payments.amount − Σ payments.refunded_amount ≥ total`, the order becomes `completed` and `placed_at` is set |
| BR-ORD-04 | A `completed` order can never be edited or deleted. Only refunds are allowed |
| BR-ORD-05 | A `pending` order may be cancelled; no stock was deducted yet |
| BR-ORD-06 | An order has at least one item |
| BR-ORD-07 | `cashier_id` is the user who completed the sale and cannot be changed |

## 4. Payments

| ID | Rule |
|----|------|
| BR-PAY-01 | Payment methods: `cash`, `khqr`, `card`; each can be enabled or disabled in settings |
| BR-PAY-02 | Payment `status`: `pending`, `paid`, `failed`, `partially_refunded`, `refunded` |
| BR-PAY-03 | Cash: amount tendered may exceed the due amount; the order stores only the due amount and the UI shows change |
| BR-PAY-04 | KHQR and card payments cannot exceed the amount due |
| BR-PAY-05 | KHQR payment is `paid` only after confirmation (webhook, polling, or cashier confirmation); store the bank `reference` |
| BR-PAY-06 | `refunded_amount ≤ amount` on every payment |
| BR-PAY-07 | Refund goes back through the original payment method where possible; cash refunds create a `cash_out` movement in the open session |

## 5. Stock & Inventory

| ID | Rule |
|----|------|
| BR-STK-01 | Stock never changes without a `stock_movements` row |
| BR-STK-02 | `stock_movements.quantity` is **signed**: positive adds stock, negative removes it |
| BR-STK-03 | `stock_movements.balance` stores `products.stock` after the movement is applied |
| BR-STK-04 | Movement types: `purchase`, `sale`, `return`, `adjustment`, `damage` |
| BR-STK-05 | Completing an order creates one `sale` movement per order item (linked by `order_item_id`) |
| BR-STK-06 | A refund with restock creates a `return` movement; a refund without restock (damaged goods) creates none, or a `damage` movement |
| BR-STK-07 | Receiving a purchase order creates one `purchase` movement per item (linked by `purchase_order_id`) |
| BR-STK-08 | Manual `adjustment` and `damage` require a non-empty `note` |
| BR-STK-09 | Selling is blocked when `quantity > stock`, unless the store setting "allow negative stock" is on |
| BR-STK-10 | Stock updates use row locking (`lockForUpdate`) inside the transaction to avoid two cashiers overselling |
| BR-STK-11 | Low stock means `stock <= min_stock` |

## 6. Purchasing

| ID | Rule |
|----|------|
| BR-PUR-01 | Purchase status: `draft` → `ordered` → `received`; or `cancelled` |
| BR-PUR-02 | `purchase_number` is unique |
| BR-PUR-03 | `line_total = quantity × unit_cost`; `total = Σ line_total` |
| BR-PUR-04 | Stock is increased only on `received`, never on `ordered` |
| BR-PUR-05 | A `received` purchase order cannot be edited; corrections are made through stock adjustments |
| BR-PUR-06 | Optional: on receive, set `products.cost` to the latest `unit_cost` (setting-controlled) |

## 7. Cash Sessions

| ID | Rule |
|----|------|
| BR-CSH-01 | A cashier can have at most one open session (`closed_at IS NULL`) |
| BR-CSH-02 | A register can have at most one open session |
| BR-CSH-03 | Cash sales and shift-related payments require an open session for the cashier |
| BR-CSH-04 | `expected_amount = opening_float + cash payments − cash refunds + cash_in − cash_out − cash expenses paid from the drawer` |
| BR-CSH-05 | On close, the cashier enters `closing_amount`; `variance = closing_amount − expected_amount` |
| BR-CSH-06 | A closed session is immutable |
| BR-CSH-07 | `cash_movements.type` is `cash_in` or `cash_out`; `reason` is required; `amount > 0` |
| BR-CSH-08 | A session cannot be closed while any of its orders is `pending` |

## 8. Expenses

| ID | Rule |
|----|------|
| BR-EXP-01 | `amount > 0` and `expense_date` is required (not in the future) |
| BR-EXP-02 | An expense belongs to one category and is attributed to `created_by` |
| BR-EXP-03 | Editing or deleting expenses requires `expenses.update` or `expenses.delete` and is written to the activity log |

## 9. Users, Roles, Data Integrity

| ID | Rule |
|----|------|
| BR-USR-01 | Email is unique; phone is unique when provided |
| BR-USR-02 | The last active Admin cannot be deactivated or deleted |
| BR-USR-03 | Users with transaction history are **deactivated**, never hard-deleted |
| BR-USR-04 | Products, customers, suppliers, categories with history are set to `inactive`, never hard-deleted |
| BR-USR-05 | Deleting a category is blocked while it has products |
| BR-DAT-01 | All multi-table writes (checkout, refund, receive, close session) run in a single database transaction |
| BR-DAT-02 | Monetary values are `decimal(14,2)`; quantities are `decimal(14,3)` to allow weighed goods |
