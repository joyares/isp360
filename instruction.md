# ISP360 ERP - Project Instructions & Standards

## 1. Project Identity & Architecture

- Project Name: ISP360
- Author: Mostafa Joy
- Author-URL: https://www.linkedin.com/in/joyoares
- Standards Name: ispts (ISP Technical Standard)
- Framework: Pure PHP 8.2+ MVC (No external frameworks like Laravel/CodeIgniter).
- Database: MySQL using PDO (Prepared Statements only).
- Structure:
	- `app/Core/`: Base classes (Router, Database, BaseController, BaseModel).
	- `app/Modules/`: Feature logic (Support, Inventory, HR, Finance, ACL/RBAC).
	- `app/Helpers/`: Utility classes (ispts_ImageHelper, SMS, Pagination).
	- `views/layouts/`: UI fragments (header, footer, sidenav).
	- `public/`: Entry point (index.php) and assets (CSS, JS, Images).

## 2. Naming Conventions (ispts)

- Core Logic: All business logic methods MUST use the `ispts_` prefix (e.g., `ispts_calculate_billing()`, `ispts_process_payment()`).
- Service Classes: MUST use the `Ispts` suffix (e.g., `FinanceIspts.php`, `InventoryIspts.php`).
- Standard Code:
	- Classes: PascalCase (e.g., `ClientController`).
	- Methods: camelCase (e.g., `getSubscriberList`).
	- Variables/Columns: snake_case (e.g., `client_id`, `package_price`).
	- View Files: kebab-case (e.g., `edit-subscriber.php`).

## 3. Persistent Data (No-Delete Policy)

- STRICT RULE: Physical `DELETE` SQL queries are strictly FORBIDDEN to maintain a complete audit trail.
- Soft Delete: Every table MUST have a `status` (TINYINT) column.
- Operations: To "remove" an item, update `status = 0` (Off/Inactive).
- Retrieval: All default list queries MUST include `WHERE status = 1` unless specifically fetching history or logs.

## 4. Role-Based Access Control (RBAC)

- Granularity: Permissions are slug-based (e.g., `inventory_add`, `finance_edit_invoice`).
- UI Control:
	- Menus: Wrap all sidebar menus and sub-menus in a `has_permission('slug')` check.
	- Features: Action buttons (Edit/Update/Off) MUST be hidden if the user lacks the specific permission slug.
	- Forms: Use `has_permission()` to toggle `disabled` or `readonly` attributes on sensitive fields (e.g., salary, plan prices).
- Backend: Every Controller method MUST call `$this->requirePermission('slug')` before execution.

## 5. UI & Navigation Standards

- Breadcrumbs: MUST render on every page in format: `Menu > Sub Menu > Current Page Name (active link)`.
- Pagination:
	- Required for all data tables.
	- User selection options: 10, 20, 50 rows per page.
	- `BaseModel` handles the calculation.

## 6. Image Handling (ispts_compress)

- Requirement: Every transaction or expense entry with an invoice/receipt image MUST be compressed.
- Spec: Maximum 200KB, 70% JPEG quality, maximum width 1200px.
- Storage: Images MUST be saved to `public/assets/uploads/` using unique naming conventions (UUID or timestamp).

## 7. Git Commit Convention

- Format: `verb:`
- Verbs:
	- `Add`: New feature, module, or file.
	- `Update`: Modifications to existing logic (including soft-delete implementation).
	- `Delete`: Removal of obsolete code logic (not DB records).
	- `Fix`: Bug fixes.
	- `Refactor`: Code improvements without changing behavior.
- Example: `Add: ispts_compress logic to Finance Module`
- Example: `Update: soft-delete status check for Inventory module`

## 8. Financial Integrity (ACID Transactions)

- Requirement: All billing cycles and ledger movements MUST be wrapped in PDO transactions.
- Logic: Use `beginTransaction()`, `commit()`, and `rollBack()` to ensure Atomicity.
