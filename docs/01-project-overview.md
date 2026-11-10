# 01 · Project Overview

## 1. Purpose

vPOS lets a small Cambodian mini-mart sell products quickly at a counter, accept cash and KHQR payments, track stock and cash drawers, and see daily business numbers without needing an accountant on site.

## 2. Problem Statement

Mini-mart owners typically rely on notebooks or spreadsheets. This causes:

- Stock counts that drift from reality
- No record of which cashier handled which sale
- Cash drawer shortages that cannot be traced
- Slow checkout and manual change calculation
- No simple view of profit (sales minus cost minus expenses)

## 3. Target Users

| User | Description | Primary needs |
|------|-------------|---------------|
| **Admin** (owner) | Owns the store, full control | Reports, settings, user management, profit visibility |
| **Manager** | Runs daily operations | Inventory, purchasing, refunds, expenses, shift review |
| **Cashier** | Works the counter | Fast checkout, open/close shift, print receipt |

## 4. Scope

### In scope (MVP)

- Authentication, roles and permissions
- Products, categories, inventory with stock movement history
- POS checkout: barcode scan, cart, discount, VAT, cash / KHQR / card, change, receipt
- Orders list with refund and return
- Customers
- Suppliers and purchase orders (receive stock)
- Expenses with categories
- Cash registers, cash sessions (shifts), cash in/out
- Reports with PDF/Excel export
- Settings: business profile, currency, VAT, receipt, payment methods

### Out of scope (MVP)

- Multi-store inventory transfer
- Loyalty points, promotions engine, coupons
- Offline mode / PWA sync
- Accounting ledger, payroll
- Online storefront (a separate e-commerce project exists)
- Hardware drivers beyond browser printing and USB barcode scanners that act as a keyboard

## 5. Technology Stack

| Layer | Choice | Notes |
|-------|--------|-------|
| Backend | Laravel 13 (PHP) | Eloquent ORM, Form Requests, Policies |
| Database | PostgreSQL | `decimal(14,2)` for money |
| Frontend | Blade + Tailwind CSS | Black & white theme, reusable Blade components |
| Interactivity | Alpine.js (recommended) | Cart behaviour on the POS screen |
| Payments | Bakong KHQR | Behind a payment gateway interface |
| Exports | PDF + Excel | e.g. `barryvdh/laravel-dompdf`, `maatwebsite/excel` |

## 6. Success Criteria

- A cashier completes a typical 5-item sale in under 30 seconds.
- Stock on hand always equals the sum of its stock movements.
- Closing a shift shows the exact variance between expected and counted cash.
- Every sale, refund, stock change and cash movement is traceable to a user.
- A new store can be configured (profile, VAT, products) in under one hour.

## 7. Assumptions

- One store and one currency per sale for the MVP (USD default, KHR display/payment supported).
- Internet connection is available at the counter.
- Product barcodes are scanned by a keyboard-emulating scanner.

## 8. Glossary

| Term | Meaning |
|------|---------|
| KHQR | Cambodia's unified QR payment standard (Bakong) |
| Cash session | A cashier's shift on one cash register, from opening float to closing count |
| Float | Starting cash placed in the drawer at shift open |
| SKU | Internal product code, unique |
| Stock movement | A single signed change to a product's quantity |
| Line total | `quantity × unit_price` for one order item |
