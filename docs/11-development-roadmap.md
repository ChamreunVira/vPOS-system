# 11 · Development Roadmap

Estimates assume one full-stack developer working full time. Adjust if work is part time: multiply durations accordingly.

## 1. Phases

| Phase | Goal | Duration | Exit criteria |
|-------|------|----------|---------------|
| 0 · Foundation | Project skeleton, tooling, design system | 1 week | App boots, CI runs, layouts and base components exist |
| 1 · Core data | Auth, roles, catalog, customers, suppliers | 2 weeks | Admin can manage users and the full catalog under permissions |
| 2 · Inventory & purchasing | Stock movements, adjustments, purchase orders | 1.5 weeks | Receiving a PO updates stock with correct history |
| 3 · Cash & POS | Cash sessions, cart, checkout, payments, receipt | 3 weeks | A cashier completes a cash sale end to end inside a shift |
| 4 · KHQR & refunds | KHQR integration, refunds/returns, order management | 2 weeks | KHQR sale and partial refund work with correct stock and cash effects |
| 5 · Finance & reports | Expenses, dashboard, reports, PDF/Excel export | 2 weeks | Daily summary and profit estimate match hand calculation |
| 6 · Settings & hardening | Settings, activity log, security review, performance | 1.5 weeks | All NFRs verified; no known critical bugs |
| 7 · UAT & launch | Pilot in one real store, fixes, training, go-live | 2 weeks | Store runs a full week on vPOS |

Total: about 15 weeks including buffer.

## 2. Phase Detail

### Phase 0 · Foundation

- [ ] Laravel 13 project, PostgreSQL connection, `.env.example`
- [ ] Tailwind B&W theme tokens (08 §2), fonts, three layouts
- [ ] Base Blade components (button, input, card, table, modal, badge, toast)
- [ ] Pint, Larastan, Pest, GitHub Actions CI
- [ ] Enums and Action/Service folder conventions (05)

### Phase 1 · Core data

- [ ] Migrations and models for roles, permissions, role_permissions, users
- [ ] Seeders: default roles, permission set, first Admin
- [ ] Login, logout, password change, active-user middleware
- [ ] Permission gate and `can:` middleware (06)
- [ ] CRUD: users, roles (permission matrix UI), categories, products (image upload), customers, suppliers
- [ ] Payment methods seeded (cash, khqr, card)

### Phase 2 · Inventory & purchasing

- [ ] `stock_movements` and `RecordStockMovementAction`
- [ ] Stock adjustment UI and movement history per product
- [ ] Low-stock indicators and report
- [ ] Purchase orders: draft, ordered, received, cancelled
- [ ] Stock drift check command (04 §7 item 10)

### Phase 3 · Cash & POS

- [ ] Cash registers, open/close session, cash in/out, expected-vs-counted close screen
- [ ] `OrderTotalsCalculator` with full unit tests
- [ ] POS screen: search, barcode, grid, cart (Alpine.js), discount selector, customer picker
- [ ] Create order and pay with cash, change calculation
- [ ] `CompleteOrderAction` with locking and transaction
- [ ] Receipt view and print CSS
- [ ] Orders list and detail

### Phase 4 · KHQR & refunds

- [ ] `PaymentGatewayInterface`, Bakong implementation, fake for tests
- [ ] KHQR flow: generate, poll or webhook, manual confirm fallback
- [ ] Split payments
- [ ] Refund and return flow with restock choice and cash-out movement
- [ ] Add refund tables from 04 §7 item 3

### Phase 5 · Finance & reports

- [ ] Expense categories and expenses
- [ ] Dashboard (role-aware)
- [ ] Reports: sales, top products, by category, by cashier, inventory valuation, cash sessions, expenses
- [ ] Profit estimate (sales − cost of goods − expenses)
- [ ] PDF and Excel export

### Phase 6 · Settings & hardening

- [ ] Settings table and screen: profile, logo, VAT, exchange rate, receipt text
- [ ] Activity log for sensitive actions
- [ ] Security review (10 §5), rate limiting, backup job and restore test
- [ ] Performance pass with 10k products and 100k orders
- [ ] Khmer translations file and font check on receipts

### Phase 7 · UAT & launch

- [ ] Run the UAT script (10 §7)
- [ ] Seed or import the real product list
- [ ] Hardware check: barcode scanner, receipt printer, tablet
- [ ] Train staff; one-page cheat sheets per role
- [ ] Go-live with a rollback plan; first-week daily check-ins

## 3. Milestones

| Milestone | Target (weeks from start) | Demo |
|-----------|---------------------------|------|
| M1 · Admin can set up the store | 3 | Users, roles, catalog |
| M2 · Stock is trustworthy | 4.5 | Purchase, receive, adjust, history |
| M3 · First sale | 7.5 | Cash sale inside a shift, printed receipt |
| M4 · Full payments | 9.5 | KHQR sale, refund, order management |
| M5 · Owner insight | 11.5 | Dashboard, reports, exports |
| M6 · Launch ready | 15 | Pilot store live |

## 4. Risks and Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| KHQR integration delays (merchant account, API access) | Blocks M4 | Start the Bakong merchant application in Phase 0; build behind an interface with a fake gateway so the rest proceeds |
| Overselling from concurrent checkouts | Wrong stock | Row locks and a concurrency test (T-POS-04) |
| Stock or cash drifting from reality | Loss of trust | Movement-only stock changes, drift check, mandatory close-shift variance |
| Schema gaps discovered late (04 §7) | Rework | Decide on the proposed additions (order-to-session link, currency on payments, refunds tables, settings) before Phase 3 |
| Receipt printing differences across printers | Poor UX | Test on target printers in Phase 3, keep print CSS simple |
| Scope creep (loyalty, promotions, multi-store) | Delay | Keep the out-of-scope list in 01 §4; new ideas go to a backlog |
| Khmer text rendering problems | Unreadable receipts | Choose a Khmer-capable font early; test on the receipt printer |
| Single developer availability | Schedule slip | Keep documentation current; small, frequent releases |

## 5. Decisions to Make Early

1. Which proposed schema additions go into the first migration set (04 §7).
2. Whether to allow negative stock (BR-STK-09 default: no).
3. Whether refunds above an amount require manager approval (PR-04).
4. KHR handling depth: display-only conversion, or full KHR cash payments with tendered and change in riel.
5. Receipt paper width (58 mm or 80 mm) for the pilot store.

## 6. Definition of Done (per feature)

- Matches the requirement ID in 02 and the rules in 03
- Authorisation enforced on server (06)
- Form Request validation in place (09)
- Unit and feature tests written and passing (10)
- Reusable components used; no one-off styles (08)
- Empty, loading, and error states handled
- Documentation updated if behaviour or schema changed

## 7. Post-MVP Backlog

Promotions and coupons · loyalty points · multi-store and stock transfers · offline mode (PWA) · supplier payments and accounts payable · customer credit (sell on account) · public API and mobile app · e-commerce sync · automated low-stock reorder suggestions · Telegram daily sales summary.
