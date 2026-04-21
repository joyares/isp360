# ISP360 ERP - Project Instructions & Standards

## 1. Project Identity & Architecture

- Project Name: ISP360
- Author: Mostafa Joy
- Author-URL: https://www.linkedin.com/in/joyoares
- Standards Name: ispts (ISP Technical Standard)
- Framework: Pure PHP 8.1+ MVC (No external frameworks like Laravel/CodeIgniter).
- Database: MySQL using PDO (Prepared Statements only).
- Structure:
	- `app/Core/`: Base classes (Router, Database, BaseController, BaseModel).
	- `app/Modules/`: Feature logic (Support, Inventory, HR, Finance, ACL/RBAC).
	- `app/Helpers/`: Utility classes (ispts_ImageHelper, SMS, Pagination).
	- `Template/`: Root UI source folder containing reusable view elements and components.
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
- Flash Notifications: Page-level save/update success or error alerts MUST render in the top navbar on desktop, immediately to the right of the global search box. Do not leave duplicate inline page alerts when the shared topnav notification is shown.
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

## 9. Template Reuse Policy

- Source of UI Truth: Reuse and adapt view elements/components from the root `Template/` folder.
- Implementation Rule: When building new screens, copy needed UI sections from `Template/` into project views/layouts and then integrate dynamic PHP data.
- Consistency Rule: Keep structure/classes/components aligned with `Template/` to preserve a consistent design system across modules.

## 10. GitHub Copilot Prompt List (Phase-wise)

Use the prompts below in order to implement the ISP360 project according to these standards.

### Phase 1: Core Architecture & Rendering

Base Controller & Breadcrumbs:

"Referring to instructions.md, create a BaseController class in app/Core/. It must have a render() method that accepts $viewName and $data array. It should automatically include views/layouts/header.php, views/layouts/sidenav.php, and views/layouts/footer.php. Implement the breadcrumb logic to handle a Menu > Sub Menu > Page format passed via $data."

DI Container (PSR-11):

"Generate a simple PSR-11 compliant Dependency Injection Container class in app/Core/ that uses PHP Reflection API to automatically resolve dependencies for my Controllers and Services, following the standards in instructions.md."

### Phase 2: RBAC & Security

RBAC Helper & Session Check:

"Write a global helper function has_permission(string $slug): bool that checks if a permission slug exists in $_SESSION['user_permissions']. Then, show an example of gating a sidebar menu link in views/layouts/sidenav.php using this function."

RBAC Schema & Soft Delete:

"Create the MySQL schema for users, roles, permissions, and role_permission tables. Every table must include a status TINYINT column (1 for On, 0 for Off) as per the no-delete policy in instructions.md."

### Phase 3: Utilities & Performance

ispts Image Compressor Helper:

"Create a ispts_ImageHelper class in app/Helpers/. Implement a method ispts_compress using the PHP GD library to resize images to a max width of 1200px and compress to 70% JPEG quality, ensuring the file stays under 200KB. Save the file to public/assets/uploads/."

Pagination Logic:

"In app/Core/BaseModel.php, implement a reusable ispts_paginate method that accepts $tableName, $limit (10/20/50), and $page. It must calculate the SQL OFFSET and return results where status = 1."
