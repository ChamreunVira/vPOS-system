# 10 · Testing Strategy

## 1. Goals

- Money and stock logic is provably correct.
- Every business rule in 03 has at least one automated test.
- A refactor never silently breaks checkout.

## 2. Test Pyramid

| Level | Share | Scope | Tools |
|-------|-------|-------|-------|
| Unit | ~50% | Pure logic: totals calculator, enums, helpers | Pest or PHPUnit |
| Feature (HTTP + DB) | ~40% | Routes, Form Requests, Actions with a real PostgreSQL test database | Pest/PHPUnit, `RefreshDatabase` |
| Browser / E2E | ~10% | POS checkout, shift open/close, refund | Laravel Dusk or Playwright |

Use PostgreSQL for tests (not SQLite) so partial indexes, `decimal` behaviour, and row locks match production.

## 3. Test Data

- Model factories for every table, with states: `Product::factory()->lowStock()`, `Order::factory()->completed()`, `CashSession::factory()->open()`.
- A seeder for the three default roles and their permissions, reused in tests through a helper such as `actingAsCashier()`.
- Fixed exchange rate and VAT in tests to keep expected numbers stable.

## 4. Critical Test Cases

### Pricing (unit)

| ID | Case | Expected |
|----|------|----------|
| T-PRC-01 | Worked example in 03 §1 | subtotal 4.25, discount 0.43, VAT 0.38, total 4.20 |
| T-PRC-02 | Discount 0% | discount_amount 0.00 |
| T-PRC-03 | Each allowed discount (5, 10, 15) | Correct amounts |
| T-PRC-04 | Discount 20% | Rejected |
| T-PRC-05 | Rounding half-up at 0.005 | Rounds up |
| T-PRC-06 | Changing VAT setting after sale | Past order keeps its own `tax_rate` |

### Checkout (feature)

| ID | Case | Expected |
|----|------|----------|
| T-POS-01 | Cashier without open session pays an order | `NO_OPEN_CASH_SESSION` |
| T-POS-02 | Full cash payment with change | Order `completed`, payment saved, stock reduced, sale movements created |
| T-POS-03 | Stock insufficient | Rolled back; no payment, no movement, stock unchanged |
| T-POS-04 | Two concurrent checkouts for the last unit | Exactly one succeeds |
| T-POS-05 | Client sends manipulated price or total | Ignored; server values used |
| T-POS-06 | Partial payment | Order stays `pending` |
| T-POS-07 | KHQR payment not confirmed | Order stays `pending` |
| T-POS-08 | Inactive product in cart | Rejected |
| T-POS-09 | Double click on Charge | Only one payment recorded |

### Orders and refunds

| ID | Case | Expected |
|----|------|----------|
| T-ORD-01 | Edit a completed order | Blocked |
| T-ORD-02 | Cancel a pending order | Status `cancelled`, no stock change |
| T-ORD-03 | Partial refund with restock | `return` movement, `partially_refunded`, refunded_amount updated |
| T-ORD-04 | Refund without restock | No stock movement |
| T-ORD-05 | Refund more than paid | `REFUND_EXCEEDS_PAID` |
| T-ORD-06 | Cash refund | `cash_out` movement created in the open session |
| T-ORD-07 | Cashier attempts refund | 403 |

### Inventory and purchasing

| ID | Case | Expected |
|----|------|----------|
| T-INV-01 | `products.stock` equals the last movement `balance` after a mixed sequence | Equal |
| T-INV-02 | Adjustment without note | 422 |
| T-INV-03 | Receive purchase order | Stock up, `purchase` movements, status `received` |
| T-INV-04 | Receive twice | `PURCHASE_ALREADY_RECEIVED` |
| T-INV-05 | Edit a received purchase order | Blocked |

### Cash sessions

| ID | Case | Expected |
|----|------|----------|
| T-CSH-01 | Open a second session for the same user | `SESSION_ALREADY_OPEN` |
| T-CSH-02 | Open a second session on the same register | Blocked |
| T-CSH-03 | Close with sales, cash in, cash out, refunds | `expected_amount` matches BR-CSH-04; variance correct |
| T-CSH-04 | Close with pending orders | Blocked |
| T-CSH-05 | Modify a closed session | Blocked |

### Access control

| ID | Case | Expected |
|----|------|----------|
| T-ACL-01 | Each route is hit by Admin, Manager, Cashier | Matches the matrix in 06 |
| T-ACL-02 | Inactive user logs in | Rejected |
| T-ACL-03 | Cashier requests another cashier's order | 404 or 403 |
| T-ACL-04 | Deactivate the last Admin | Blocked |
| T-ACL-05 | Unauthenticated request | Redirect to login or 401 |

### Validation

One data-provider test per Form Request that feeds invalid values (missing, wrong type, too long, duplicate SKU/barcode, negative amounts) and asserts the error keys from 09 §5.

## 5. Non-Functional Testing

| Area | Approach |
|------|----------|
| Performance | Seed 10k products and 100k orders; check product search, orders list, and sales report timings against NFR-01; look for N+1 with `preventLazyLoading()` in tests |
| Security | Check CSRF on writes, mass-assignment (`$fillable`), file upload type/size, password hashing, IDOR on orders and sessions |
| Data integrity | Scheduled test or command that compares `products.stock` with the latest movement balance and `orders.total` with item and payment sums |
| Printing | Manual check of receipt on 58 mm and 80 mm printers |
| Accessibility | Keyboard-only run of the POS flow; contrast check |
| Browsers | Latest Chrome and Edge on desktop; Chrome on Android tablet |

## 6. Process

- Tests run on every pull request in CI (PostgreSQL service container, `composer install`, `php artisan test --parallel`).
- Coverage targets: 90%+ for `Actions/` and `Services/Pricing`, 70%+ overall.
- A bug fix starts with a failing test that reproduces it.
- Static analysis (Larastan level 6+) and code style (Laravel Pint) run in CI.
- Release checklist: all tests green, migrations tested on a copy of production data, backup taken, smoke test of login, open shift, one sale, one refund.

## 7. Manual UAT Script (before go-live)

1. Admin creates a category, five products, and a Cashier user.
2. Manager creates a purchase order and receives it; stock rises.
3. Cashier opens a shift with a 50.00 float.
4. Sell with cash (with change), with KHQR, and with 10% discount; print receipts.
5. Refund one item with restock; refund another without.
6. Record a cash-out for a small supplier payment.
7. Close the shift; verify expected amount and variance.
8. Admin exports the daily sales report to PDF and Excel and checks the numbers by hand.
