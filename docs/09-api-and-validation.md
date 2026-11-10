# 09 · API and Validation

vPOS is a server-rendered Blade application. "API" here means the routes the app exposes: standard web routes for pages and forms, plus a few JSON endpoints used by the POS screen (session-authenticated, same CSRF rules). A public token API is out of scope for the MVP.

## 1. Conventions

| Topic | Convention |
|-------|------------|
| Route style | Resourceful (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) |
| Route names | `module.action`, for example `products.index`, `orders.refund` |
| JSON prefix | `/pos/...` for POS endpoints |
| Auth | Session cookie; all routes behind `auth` and `active` middleware except login |
| Authorisation | `can:module.action` middleware plus Form Request `authorize()` |
| Money in JSON | Strings with 2 decimals (`"4.20"`) to avoid float errors |
| Dates in JSON | ISO 8601 UTC |
| Pagination | `?page=`, `?per_page=` (max 100) |

## 2. Web Routes (summary)

| Module | Routes |
|--------|--------|
| Auth | `GET /login`, `POST /login`, `POST /logout`, `GET/PUT /profile/password` |
| Dashboard | `GET /dashboard` |
| Products | resource `/products`, `POST /products/{id}/adjust-stock`, `GET /products/{id}/movements` |
| Categories | resource `/categories` |
| Customers | resource `/customers` |
| Suppliers | resource `/suppliers` |
| Purchases | resource `/purchase-orders`, `POST /purchase-orders/{id}/order`, `POST /purchase-orders/{id}/receive`, `POST /purchase-orders/{id}/cancel` |
| Orders | `GET /orders`, `GET /orders/{id}`, `POST /orders/{id}/cancel`, `POST /orders/{id}/refund`, `GET /orders/{id}/receipt` |
| Expenses | resource `/expenses`, resource `/expense-categories` |
| Cash | resource `/cash-registers`, `GET /cash-sessions`, `GET /cash-sessions/{id}`, `POST /cash-sessions/open`, `POST /cash-sessions/{id}/close`, `POST /cash-sessions/{id}/movements` |
| Payments | `GET /payments`, `GET/PUT /payment-methods` |
| Reports | `GET /reports/{sales|products|inventory|cash|expenses}`, `GET /reports/{type}/export?format=pdf|xlsx` |
| Users & roles | resource `/users`, resource `/roles` (with permission assignment) |
| Settings | `GET /settings`, `PUT /settings` |

## 3. POS JSON Endpoints

| Method | Path | Purpose | Permission |
|--------|------|---------|------------|
| GET | `/pos/products?search=&category_id=` | Product grid and search, max 20 rows | `pos.use` |
| GET | `/pos/products/barcode/{barcode}` | Single product lookup for scanner | `pos.use` |
| GET | `/pos/customers?search=` | Customer picker | `pos.use` |
| POST | `/pos/orders` | Create pending order with items | `pos.use` |
| POST | `/pos/orders/{order}/payments` | Add payment line; completes order when fully paid | `pos.use` |
| POST | `/pos/orders/{order}/khqr` | Request a KHQR code | `pos.use` |
| GET | `/pos/orders/{order}/payment-status` | Poll KHQR status | `pos.use` |

### Example: create order

Request

```json
{
  "customer_id": 12,
  "discount_percent": 10,
  "items": [
    { "product_id": 101, "quantity": "3" },
    { "product_id": 205, "quantity": "5" }
  ]
}
```

Response `201`

```json
{
  "data": {
    "id": 5521,
    "order_number": "INV-20261110-0042",
    "status": "pending",
    "subtotal": "4.25",
    "discount_percent": "10.00",
    "discount_amount": "0.43",
    "tax_rate": "10.00",
    "tax_amount": "0.38",
    "total": "4.20"
  }
}
```

Prices and totals are always computed on the server; the client only sends product IDs and quantities.

## 4. Error Format

Validation (`422`)

```json
{
  "message": "The given data was invalid.",
  "errors": { "items.0.quantity": ["The quantity must be at least 0.001."] }
}
```

Business-rule failure (`409` or `422`)

```json
{
  "message": "Insufficient stock for Coca-Cola 330ml.",
  "code": "INSUFFICIENT_STOCK",
  "context": { "product_id": 101, "available": "2.000", "requested": "3.000" }
}
```

| HTTP | Meaning |
|------|---------|
| 401 | Not signed in |
| 403 | Missing permission |
| 404 | Not found (or not visible to this user) |
| 409 | State conflict (shift already open, order already completed) |
| 422 | Validation or business rule failure |
| 429 | Throttled |
| 500 | Unexpected error (details only in logs) |

Business error codes: `INSUFFICIENT_STOCK`, `NO_OPEN_CASH_SESSION`, `SESSION_ALREADY_OPEN`, `ORDER_NOT_PAYABLE`, `PAYMENT_EXCEEDS_DUE`, `REFUND_EXCEEDS_PAID`, `PURCHASE_ALREADY_RECEIVED`, `INVALID_DISCOUNT`.

## 5. Validation Rules

Each rule set lives in a Form Request class. Laravel syntax shown; `required` is for create, use `sometimes` on update where noted.

### Users

| Field | Rules |
|-------|-------|
| name | required, string, max:120 |
| email | required, email, unique:users,email (ignore current on update) |
| phone | nullable, string, max:30, unique:users,phone |
| password | required on create, min:8, confirmed; nullable on update |
| role_id | required, exists:roles,id |
| status | required, in:active,inactive |

### Products

| Field | Rules |
|-------|-------|
| category_id | required, exists:categories,id |
| name | required, string, max:200 |
| sku | required, string, max:60, unique:products,sku |
| barcode | nullable, string, max:60, unique:products,barcode |
| cost | required, numeric, min:0, max:99999999.99 |
| price | required, numeric, min:0, max:99999999.99 |
| min_stock | required, numeric, min:0 |
| image | nullable, image, mimes:jpg,jpeg,png,webp, max:2048 |
| status | required, in:active,inactive |

### Stock adjustment

| Field | Rules |
|-------|-------|
| type | required, in:adjustment,damage |
| quantity | required, numeric, not_in:0 (signed) |
| note | required, string, max:500 |

### Customers / Suppliers

| Field | Rules |
|-------|-------|
| name | required, string, max:150 |
| phone | nullable, string, max:30 |
| email | nullable, email, max:150 |
| address | nullable, string, max:500 |
| notes | nullable, string, max:1000 (customers) |
| status | required, in:active,inactive |

### POS order

| Field | Rules |
|-------|-------|
| customer_id | nullable, exists:customers,id |
| discount_percent | required, in:0,5,10,15 |
| items | required, array, min:1 |
| items.*.product_id | required, exists:products,id (active only) |
| items.*.quantity | required, numeric, min:0.001 |

### Payment line

| Field | Rules |
|-------|-------|
| payment_method_id | required, exists enabled payment method |
| amount | required, numeric, gt:0 |
| amount_tendered | required for cash, gte:amount |
| reference | required for khqr and card, string, max:100 |

### Refund

| Field | Rules |
|-------|-------|
| items | required, array, min:1 |
| items.*.order_item_id | required, belongs to this order |
| items.*.quantity | required, numeric, min:0.001, max: remaining refundable quantity |
| items.*.restock | required, boolean |
| reason | required, string, max:500 |
| refund_payment_method_id | required, exists:payment_methods,id |

### Purchase order

| Field | Rules |
|-------|-------|
| supplier_id | required, exists:suppliers,id |
| items | required, array, min:1 |
| items.*.product_id | required, exists:products,id, distinct |
| items.*.quantity | required, numeric, min:0.001 |
| items.*.unit_cost | required, numeric, min:0 |

### Expense

| Field | Rules |
|-------|-------|
| expense_category_id | required, exists:expense_categories,id |
| payment_method_id | required, exists:payment_methods,id |
| description | required, string, max:255 |
| amount | required, numeric, gt:0 |
| expense_date | required, date, before_or_equal:today |

### Cash session and movements

| Field | Rules |
|-------|-------|
| cash_register_id | required, exists:cash_registers,id, no open session on it |
| opening_float | required, numeric, min:0 |
| closing_amount | required on close, numeric, min:0 |
| type | required on movement, in:cash_in,cash_out |
| amount | required, numeric, gt:0 |
| reason | required, string, max:255 |

### Settings

| Field | Rules |
|-------|-------|
| business_name | required, string, max:150 |
| logo | nullable, image, max:2048 |
| vat_rate | required, numeric, between:0,100 |
| exchange_rate | required, numeric, gt:0 |
| receipt_footer | nullable, string, max:500 |

## 6. Cross-field and Database-level Validation

Some rules cannot be expressed as simple field rules and are checked inside the action:

- Stock sufficient for every item (with row locks).
- Payments cover the total before completion.
- Refund quantity does not exceed what was sold minus what was already refunded.
- One open session per user and per register (also enforced by partial unique indexes, see 04 §5).
- Purchase order is in a state that allows the requested transition.
