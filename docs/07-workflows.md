# 07 · Workflows

All diagrams use Mermaid. Every multi-table step runs inside one database transaction.

## 1. Sign-in

```mermaid
flowchart TD
    A[Open login page] --> B[Enter email and password]
    B --> C{Credentials valid?}
    C -- No --> D[Show error, count attempt]
    C -- Yes --> E{User active?}
    E -- No --> F[Reject: account inactive]
    E -- Yes --> G{Role = Cashier and no open session?}
    G -- Yes --> H[Redirect to Open Shift]
    G -- No --> I[Redirect to Dashboard]
```

## 2. Open and Close a Shift

```mermaid
flowchart TD
    A[Cashier chooses register] --> B{Register or cashier already has open session?}
    B -- Yes --> X[Block: close existing session first]
    B -- No --> C[Enter opening float]
    C --> D[Create cash_session, opened_at = now]
    D --> E[Cashier sells, records cash in or out]
    E --> F[Cashier selects Close Shift]
    F --> G{Any pending orders?}
    G -- Yes --> Y[Block: complete or cancel them]
    G -- No --> H[Cashier counts drawer and enters closing_amount]
    H --> I[System computes expected_amount]
    I --> J[Show variance = closing - expected]
    J --> K[Confirm: set closed_at, session becomes read-only]
```

## 3. Checkout (core flow)

```mermaid
sequenceDiagram
    participant C as Cashier
    participant UI as POS Screen
    participant S as Server
    participant DB as PostgreSQL

    C->>UI: Scan barcode / pick product
    UI->>S: GET product lookup
    S-->>UI: Product, price, stock
    C->>UI: Adjust qty, choose discount, customer
    UI->>S: POST create order (items, discount, customer)
    S->>S: Recompute totals (never trust client)
    S->>DB: Insert order (pending) + items with price/cost snapshot
    C->>UI: Choose payment method(s), amount
    UI->>S: POST pay order
    S->>DB: BEGIN
    S->>DB: Lock products, verify stock
    S->>DB: Insert payment(s)
    S->>DB: Insert sale stock_movements, decrement products.stock
    S->>DB: Order status = completed, placed_at = now
    S->>DB: COMMIT
    S-->>UI: Success, change due, receipt URL
    UI->>C: Show receipt, print
```

**Failure handling**

| Situation | Behaviour |
|-----------|-----------|
| Insufficient stock at pay time | Roll back, show which item, keep cart |
| KHQR not confirmed | Order stays `pending`, payment `pending`; cashier can retry or switch method |
| Payment total less than due | Order stays `pending`; remaining balance shown |
| Server error | Roll back whole transaction; nothing is half-saved |

## 4. KHQR Payment

```mermaid
flowchart TD
    A[Cashier selects KHQR] --> B[Server asks gateway for QR with amount and order reference]
    B --> C[Display QR to customer]
    C --> D{Payment confirmed?}
    D -- Webhook or polling says paid --> E[Save payment paid with bank reference]
    D -- Cashier manual confirm --> E
    D -- Timeout or cancel --> F[Mark payment failed, return to method choice]
    E --> G[Continue checkout completion]
```

## 5. Refund / Return

```mermaid
flowchart TD
    A[Find completed order] --> B{User has orders.refund?}
    B -- No --> X[Deny]
    B -- Yes --> C[Select items and quantities to return]
    C --> D[Enter reason]
    D --> E[Per item: restock? yes or no]
    E --> F[System computes refund amount incl. proportional discount and VAT]
    F --> G[Choose refund method, default = original payment method]
    G --> H[BEGIN transaction]
    H --> I[Update payments.refunded_amount]
    I --> J[Create return stock_movements for restocked items]
    J --> K{Refund in cash?}
    K -- Yes --> L[Create cash_out movement in open session]
    K -- No --> M[Skip]
    L --> N[Order status = partially_refunded or refunded]
    M --> N
    N --> O[Write activity log, COMMIT]
```

## 6. Purchase and Receive Stock

```mermaid
flowchart TD
    A[Manager creates purchase order for supplier] --> B[Add products, quantity, unit cost]
    B --> C[Save as draft]
    C --> D[Mark as ordered, set ordered_at]
    D --> E{Goods arrive?}
    E -- Cancel --> F[Status = cancelled]
    E -- Yes --> G[Receive: confirm quantities]
    G --> H[BEGIN transaction]
    H --> I[Create purchase stock_movements, increase products.stock]
    I --> J[Optionally update products.cost]
    J --> K[Status = received]
    K --> L[COMMIT]
```

## 7. Manual Stock Adjustment

1. Manager opens a product, selects **Adjust stock**.
2. Enters the new counted quantity or a signed difference.
3. Chooses type (`adjustment` or `damage`) and writes a required note.
4. Server creates one stock movement with the signed difference and updates `products.stock`.
5. Entry is written to the activity log.

## 8. Record an Expense

1. User with `expenses.create` opens the form.
2. Chooses category, payment method, amount, date, description.
3. Server saves the expense with `created_by`.
4. If paid in cash from an open drawer, the expense is linked to the session (**Proposed**, 04 §7 item 9) and reduces expected cash.

## 9. Daily Closing (Manager)

1. Confirm all sessions for the day are closed.
2. Review variances per session.
3. Review sales summary, payment-method breakdown, refunds, expenses.
4. Export the daily report to PDF or Excel.
