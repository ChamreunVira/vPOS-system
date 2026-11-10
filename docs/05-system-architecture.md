# 05 · System Architecture

## 1. Overview

A server-rendered Laravel monolith. Blade renders pages, Alpine.js handles cart interactivity, and a small set of JSON endpoints serves the POS screen. PostgreSQL is the only data store.

```
 Browser (Blade + Tailwind + Alpine.js)
        │  HTTP (forms, fetch for POS)
        ▼
 Laravel 13
   Routes ─► Middleware (auth, active user, permission)
          ─► Controller (thin)
          ─► Form Request (validation + authorisation)
          ─► Action / Service (business logic, DB transaction)
          ─► Eloquent Models ─► PostgreSQL
          ─► View (Blade components)
        │
        ├─ Payment gateway interface ─► Bakong KHQR
        └─ Storage (product images, logos, exports)
```

## 2. Layer Responsibilities

| Layer | Responsibility | Must not |
|-------|----------------|----------|
| Routes | Map URL → controller, attach middleware | Contain logic |
| Controller | Receive request, call an action, return view or redirect | Calculate totals, touch multiple models |
| Form Request | Validate input, check permission | Query unrelated data |
| Action / Service | One business operation (e.g. `CompleteOrderAction`), runs in a transaction | Render views |
| Model | Relationships, casts, scopes, simple accessors | Hold workflow logic |
| Policy | Resource-level authorisation (e.g. can this user refund this order) | |
| View / Component | Presentation only | Query the database |

## 3. Suggested Directory Structure

```
app/
├── Actions/
│   ├── Orders/        CreateOrderAction, CompleteOrderAction, RefundOrderAction
│   ├── Inventory/     RecordStockMovementAction, AdjustStockAction
│   ├── Purchasing/    ReceivePurchaseOrderAction
│   └── Cash/          OpenCashSessionAction, CloseCashSessionAction
├── Enums/             OrderStatus, PaymentStatus, StockMovementType, CashMovementType
├── Http/
│   ├── Controllers/
│   ├── Middleware/    EnsureUserIsActive, EnsureCashSessionIsOpen
│   └── Requests/
├── Models/
├── Policies/
├── Services/
│   ├── Pricing/       OrderTotalsCalculator
│   ├── Payments/      PaymentGatewayInterface, BakongKhqrGateway
│   └── Reports/
└── View/Components/   Blade component classes
resources/views/
├── layouts/           app.blade.php, pos.blade.php, auth.blade.php
├── components/        button, input, table, modal, badge, stat-card ...
└── pages/             dashboard, pos, products, orders ...
```

## 4. Key Design Decisions

| Decision | Reason |
|----------|--------|
| Business logic in Action classes | Each operation is testable on its own and reused by web and JSON routes |
| `OrderTotalsCalculator` as a pure class | BR-PRC rules live in one place, easy to unit test |
| PHP backed enums for status/type | Prevents typo strings; one place for allowed values |
| Transactions around every multi-table write | BR-DAT-01 |
| `lockForUpdate()` on product rows during checkout | Prevents overselling (BR-STK-10) |
| Gateway interface for KHQR | Swap real Bakong, a sandbox, or a fake in tests |
| Stock changed only through `RecordStockMovementAction` | Guarantees BR-STK-01 and correct `balance` |
| Permission check by `module.action` string | Matches the `permissions` table directly |

## 5. Example: Completing an Order

```php
final class CompleteOrderAction
{
    public function __construct(
        private readonly OrderTotalsCalculator $orderTotalsCalculator,
        private readonly RecordStockMovementAction $recordStockMovement,
    ) {}

    public function execute(Order $order, array $paymentLines, User $cashier): Order
    {
        return DB::transaction(function () use ($order, $paymentLines, $cashier) {
            $order->load('items.product');

            foreach ($order->items as $orderItem) {
                $product = Product::whereKey($orderItem->product_id)->lockForUpdate()->firstOrFail();
                $this->recordStockMovement->execute(
                    product: $product,
                    type: StockMovementType::Sale,
                    quantity: -$orderItem->quantity,
                    user: $cashier,
                    orderItem: $orderItem,
                );
            }

            // create payments, verify they cover the total, then:
            $order->update(['status' => OrderStatus::Completed, 'placed_at' => now()]);

            return $order->fresh();
        });
    }
}
```

## 6. Security

| Area | Measure |
|------|---------|
| Authentication | Laravel session auth, `password` hashed, login throttling |
| Authorisation | Middleware `can:module.action` plus Policies for resource checks |
| CSRF | Enabled on all non-GET routes; POS fetch calls send the token |
| Input | Form Requests for every write; never trust client-side totals, always recompute on the server |
| Uploads | Validate mime and size; store outside public root or under `storage` with a generated name |
| Secrets | KHQR credentials in `.env`, never in the repository |
| Passwords | Never returned in responses or logs |
| Audit | `activity_logs` for refund, price change, stock adjustment, role change |

## 7. Performance

- Index the columns listed in 04 §5.
- Eager load relations on list pages (`with()`), avoid N+1.
- Paginate all lists (default 15–25 rows).
- Product search for the POS screen uses a debounced endpoint returning at most 20 rows.
- Cache settings and permissions per user for the request lifecycle (or Redis later).
- Heavy reports run through queued jobs when the range is large.

## 8. Deployment

| Item | Recommendation |
|------|----------------|
| Server | Single VPS (2 vCPU / 4 GB is enough for one store) running Nginx, PHP-FPM, PostgreSQL |
| Environments | `local`, `staging`, `production` |
| Process | `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache` on deploy |
| Queue | `database` driver first; Redis when needed |
| Scheduler | One cron entry for `schedule:run` (daily backup, stock drift check) |
| Backups | Daily `pg_dump`, keep 14 days, copy off the server |
| Logs | Daily log files, errors to an external tracker (e.g. Sentry) |
| Time zone | App `UTC`, display `Asia/Phnom_Penh` |

## 9. Multi-Store Readiness

Not in the MVP. To keep the door open: avoid global singletons for store data, read store-wide values from the settings service, and keep new tables easy to extend with a nullable `store_id` (see 04 §7 item 8).
