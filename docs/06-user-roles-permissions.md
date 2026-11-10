# 06 · User Roles & Permissions

## 1. Model

```
users ─► roles ─► role_permissions ─► permissions (module + action)
```

- One role per user.
- A permission is `module.action`, for example `orders.refund`.
- Roles are editable by Admin; the three default roles are seeded.

## 2. Roles

| Role | Purpose |
|------|---------|
| **Admin** | Owner. Everything, including settings, users, roles, and profit reports |
| **Manager** | Operations. Inventory, purchasing, expenses, refunds, reports. No settings, roles or user deletion |
| **Cashier** | Counter. Checkout, own shift, own orders, customers |

## 3. Modules and Actions

| Module | Actions |
|--------|---------|
| dashboard | view |
| pos | use |
| products | view, create, update, delete |
| categories | view, create, update, delete |
| inventory | view, adjust |
| orders | view, view_all, cancel, refund, reprint |
| customers | view, create, update, delete |
| suppliers | view, create, update, delete |
| purchases | view, create, update, receive, cancel |
| expenses | view, create, update, delete |
| cash | open_session, close_session, view_sessions, view_all_sessions, cash_movement, manage_registers |
| payments | view, manage_methods |
| reports | view, export, view_profit |
| users | view, create, update, deactivate |
| roles | view, manage |
| settings | view, update |
| activity_logs | view |

## 4. Default Permission Matrix

✔ allowed · — denied · ◐ limited (see note)

| Module / action | Admin | Manager | Cashier |
|-----------------|:-----:|:-------:|:-------:|
| dashboard.view | ✔ | ✔ | ◐ own shift summary |
| pos.use | ✔ | ✔ | ✔ |
| products.view | ✔ | ✔ | ✔ |
| products.create / update | ✔ | ✔ | — |
| products.delete | ✔ | — | — |
| categories.view | ✔ | ✔ | ✔ |
| categories.create / update / delete | ✔ | ✔ | — |
| inventory.view | ✔ | ✔ | ✔ |
| inventory.adjust | ✔ | ✔ | — |
| orders.view | ✔ | ✔ | ✔ |
| orders.view_all | ✔ | ✔ | — (own orders only) |
| orders.cancel | ✔ | ✔ | ◐ own pending orders |
| orders.refund | ✔ | ✔ | — |
| orders.reprint | ✔ | ✔ | ✔ |
| customers.view / create / update | ✔ | ✔ | ✔ |
| customers.delete | ✔ | ✔ | — |
| suppliers.* | ✔ | ✔ | — |
| purchases.* | ✔ | ✔ | — |
| expenses.view / create | ✔ | ✔ | — |
| expenses.update / delete | ✔ | ◐ own entries | — |
| cash.open_session / close_session | ✔ | ✔ | ✔ |
| cash.cash_movement | ✔ | ✔ | ✔ |
| cash.view_all_sessions | ✔ | ✔ | — |
| cash.manage_registers | ✔ | — | — |
| payments.view | ✔ | ✔ | — |
| payments.manage_methods | ✔ | — | — |
| reports.view | ✔ | ✔ | — |
| reports.export | ✔ | ✔ | — |
| reports.view_profit | ✔ | — | — |
| users.view | ✔ | ✔ | — |
| users.create / update / deactivate | ✔ | — | — |
| roles.* | ✔ | — | — |
| settings.* | ✔ | — | — |
| activity_logs.view | ✔ | — | — |

## 5. Rules

| ID | Rule |
|----|------|
| PR-01 | Deny by default: no permission row means no access |
| PR-02 | Server-side checks are authoritative; hiding a button is a convenience only |
| PR-03 | Cashiers see only orders and sessions they created, unless granted `view_all` |
| PR-04 | Refunds above a configurable amount can require a Manager PIN or approval (**COULD**) |
| PR-05 | Role changes take effect on the user's next request |
| PR-06 | The Admin role always keeps `roles.manage` and `users.update` to prevent lock-out |

## 6. Implementation Notes

- Seed `permissions` from a single config array of modules and actions so the UI and database stay in sync.
- Register one `Gate::before` or `Gate::define` that resolves `module.action` against the user's role permissions.
- Use `->middleware('can:products.create')` on routes and `@can('products.create')` in Blade.
- Eager load `user.role.permissions` once per request.
- Scope queries by ownership with a model scope such as `scopeVisibleTo(User $user)` for the "own records only" cases.
