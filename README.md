# vPOS Documentation

Point-of-sale system for mini-marts in Cambodia. Single store for the MVP, with the schema kept friendly to multi-store later.

**Stack:** Laravel 13 · Eloquent · PostgreSQL · Blade + Tailwind CSS (black & white theme)

## Document Index

| #   | Document                                                 | What it answers                                                 |
| --- | -------------------------------------------------------- | --------------------------------------------------------------- |
| 01  | [Project Overview](01-project-overview.md)               | What are we building, for whom, and what is in or out of scope? |
| 02  | [Functional Requirements](02-functional-requirements.md) | What must each module do?                                       |
| 03  | [Business Rules](03-business-rules.md)                   | Which calculations, statuses and constraints must always hold?  |
| 04  | [Database Design](04-database-design.md)                 | Tables, columns, relationships, indexes, known gaps             |
| 05  | [System Architecture](05-system-architecture.md)         | How is the code organised and how do layers talk?               |
| 06  | [User Roles & Permissions](06-user-roles-permissions.md) | Who can do what?                                                |
| 07  | [Workflows](07-workflows.md)                             | Step-by-step flows for checkout, refunds, shifts, purchasing    |
| 08  | [UI/UX Guidelines](08-ui-ux-guidelines.md)               | Design tokens, components, screen rules                         |
| 09  | [API & Validation](09-api-and-validation.md)             | Routes, JSON endpoints, validation rules, error format          |
| 10  | [Testing Strategy](10-testing-strategy.md)               | What we test, how, and the critical test cases                  |
| 11  | [Development Roadmap](11-development-roadmap.md)         | Phases, milestones, risks                                       |

## Conventions

- **Source of truth for data:** the ERD in [04-database-design](04-database-design.md). If another document disagrees, the ERD wins until the schema is changed.
- **Requirement IDs:** `FR-*` functional, `BR-*` business rule, `NFR-*` non-functional.
- **Priority:** `MUST` (MVP), `SHOULD` (MVP if time allows), `COULD` (post-MVP).
- **Proposed** marks anything not in the current ERD that is recommended as an addition.
- **Money:** always `decimal`, never float. Default currency is USD, with KHR support described in 03 and 04.
