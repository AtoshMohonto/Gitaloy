# GITALOY_PROMPT.md

Project brief for building the **Gitaloy — Village Education Program** management system.
This document captures the product context so the system can be rebuilt or extended faithfully.

## Context

Gitaloy is a village-based free education program in Bangladesh. Children from the village and
surrounding areas come to a local study center every **Friday** for teaching sessions. The program
is run by volunteers across several villages, upazilas, and districts. It also distributes study
materials (books, notebooks, pens, bags) and collects small per-head fees.

The program previously relied on paper records (attendance sheets, Excel files, PDF lists). This
system digitizes and centralizes those records.

## Domain rules

- Geography: **Division → District → Upazila → Village**. Every student and center belongs to a village.
- **Study center**: a physical location (Mondir, madrasa, school hall, community room). A center
  belongs to a village.
- **Class**: student level (Class 1–5, etc.). **Subject**: Bangla, English, Math, Science, etc.
- **Academic year**: a named period (e.g. 2026–2027). Exactly one year is active at a time; all
  sessions, fees, tasks, and distributions link to a year.
- **Session**: a teaching event at a center on a date (`Friday` or `Weekly`). Attendance is recorded
  per student as Present/Absent.
- **Attendance + Friday fee**: when marking attendance, staff can also collect the Friday fee
  per student in the same step, creating a paid fee record.
- **Fee**: billed per head (`Friday` or `Monthly`), with amount, paid amount, status
  (Paid / Partial / Unpaid), and optional linked session/month.
- **Task**: an assignment for a class/subject with total marks; results are per student
  (obtained marks, completed flag).
- **Distribution**: a plan scoped to a division or district for one item with a target quantity.
  Actual per-student distributions are recorded against the plan.
- **Student ID**: auto-generated as `GIT-#####` (e.g. GIT-00001), used as the student's login username.

## Roles

| Role | Scope | Capabilities |
|------|-------|--------------|
| Admin | Global | Everything incl. geography, centers, users, setup |
| Divisional Manager | One division | All modules across the division |
| District Manager | One district | All modules across the district |
| Accountant | One district | Fees, expenses, attendance, students, reports |
| Teacher | One center | Their center's students, sessions, attendance, fees, tasks |
| Student | Self | Own profile, progress, report card |

Zone scoping is enforced in SQL filters: students/centers are filtered through the
village → upazila → district → division chain.

## Default accounts

- Admin: `admin@gitaloy.com` / `admin123`
- Staff created via admin panel: default password `gitaloy123`
- Student login: username = student ID (e.g. `GIT-00001`), default password `gitaloy123`

## Core workflows

1. **Setup**: admin builds geography, centers, classes, subjects, years, fee heads, items, users.
2. **Registration**: staff register students (name, guardian, village, center, class).
3. **Weekly rhythm**: teacher opens a session at a center, marks attendance, and collects Friday fees.
4. **Monitoring**: managers watch progress dashboards, fees, and distribution completion.
5. **Reporting**: print attendance sheets, report cards, receipts, distribution reports.

## Technical notes

- Stack: PHP 8, MySQL/MariaDB, Tailwind CSS + Lucide icons (CDN). No Composer.
- **CSRF protection** (added 2026-08-10): `csrfToken()`, `csrfField()`, `validateCsrf()` in
  `includes/auth.php`. Every `method="post"` form emits `<?= csrfField() ?>` as its first
  line; every POST handler validates `$_POST['csrf_token']` before touching anything else
  (as a `throw new RuntimeException(...)` at the top of the existing try/catch where one
  exists, or as an `if`/`else` guard where it doesn't). Follow this pattern for any new form.
- `config/database.php` auto-creates the DB and applies `database_schema.sql` idempotently.
- Layout: `includes/header.php` + `includes/sidebar.php` + `includes/footer.php`.
  The sidebar groups navigation into clusters (Overview, Operations, My Studies, Administration,
  Account) and is role-aware. Icons are Lucide (CDN); the emerald theme uses `bg-emerald-50/40`
  page background, emerald-900 hero headers, white cards with emerald-100 borders, and emerald
  table headers/hover rows.
- Helpers in `includes/helpers.php` provide zone-scope SQL filters (`getStudentScopeFilter`,
  `getCenterScopeFilter`, `getStudentScopeJoins`, `getCenterScopeJoins`) — keep these consistent
  when adding queries. Center-scoped tables (sessions, expenses) must scope through their center
  join, not a direct village join.
- JSON API in `modules/students/api.php` drives cascading geography selects.
- Key/value site settings live in the `settings` table (`skey`/`svalue`), read/written via
  `getSettings()` and `saveSetting()` in `includes/helpers.php`. Consumed by Site Settings,
  Frontend Content, the login page, and the dashboard announcement banner.
- Repeatable landing-page content (stats/counters, program/cause cards, gallery photos, update
  posts, testimonials) lives in the `content_blocks` table (`section`, `title`, `subtitle`, `body`,
  `icon`, `image`, `stat_value`, `link_url`, `sort_order`, `is_active`), managed via
  `getContentBlocks()`/`createContentBlock()`/`updateContentBlock()`/`deleteContentBlock()`/
  `toggleContentBlock()`/`moveContentBlock()` in `includes/helpers.php` and the admin UI at
  `modules/content/blocks.php` (`?section=stat|program|gallery|update|testimonial`). This is the
  extension point for adding new landing-page items without a schema change — reuse the `section`
  column rather than adding new tables when a future item is "one more of the same shape."
- `index.php` is a public landing page (the main page of the app) showing the program's
  features and the six role descriptions, with Sign in / Create account options. Logged-in
  visitors see a "Go to dashboard" button instead. The hero title/subtitle, hero picture
  (uploaded to `uploads/`), and the notice board are all controlled from the Frontend Content
  module (`hero_title`, `hero_subtitle`, `hero_image`, `notice`, `notice_active`).
- Toasts: `window.showToast(message, type)` in `assets/js/app.js` (success/error/info).
- Admin-only modules: `modules/settings/` (site settings) and `modules/content/` (frontend
  content). `modules/account/` (profile + password change) is available to every logged-in user.
- Print documents use `.report-doc`, `.sig-rule`, and `@media print` rules in `assets/css/style.css`.

## Source documents (in `others/`)

- `30 STUDENTS LIST.pdf`, `gitaloy base.xlsx`, `joypurhatApril25.pptx` — original student lists/presentations
- `daily attendance.xlsx`, `monthly attendence.xlsx` — attendance sheets
- `BRC/Birth Cirtificate_*.pdf`, `Mondir Documents/` — student documents and Mondir legal papers
- `Anukul Chondro 138 dob gita phat.pdf`, `application.jpg` — sample records
