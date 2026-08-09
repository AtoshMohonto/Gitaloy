# Gitaloy — Village Education Program

A PHP/MySQL management system for a village-based free education program in Bangladesh.
Students from villages gather at local study centers (Mondir, madrasa, school halls) for
weekly Friday sessions. This app tracks everything in one place: students, attendance,
fees, syllabus, tasks, progress, and materials distribution.

## Features

- **Role-based access** — Admin, Divisional Manager, District Manager, Accountant, Teacher, Student
- **Geography hierarchy** — Division → District → Upazila → Village, which scopes every zone filter
- **Study centers** — Mosques, madrasas, schools, and community halls
- **Students** — registration with auto-generated student IDs, guardian contact, documents, quick search
- **Attendance & sessions** — Friday/weekly sessions with per-student Present/Absent + instant fee capture
- **Fees & expenses** — per-head fees (Friday or monthly), payments, receipts, and center expenses
- **Syllabus & tasks** — per class/subject/year syllabuses, task assignments, and marks
- **Progress** — combined attendance + performance views per student
- **Distribution** — zone-wise plans for books, notebooks, pens, bags; per-student records
- **Reports** — printable attendance sheets, report cards, fee receipts, distribution reports
- **Student portal** — students log in with their student ID to see their own progress
- **Landing page** — a public home page at `index.php` showing the program's features and roles, with sign-in / account options and an admin-controlled hero, picture, and notice board
- **My Account** — any user can update their profile and change their password
- **Site Settings** — admin-configurable app name, tagline, and contact details
- **Frontend content** — admin controls the landing page (hero text, picture, notice) plus the login screen text and dashboard announcements
- **Modern UI** — Tailwind CSS + Lucide icons with an emerald theme (matching the `sms-dashboard` style)

## Requirements

- XAMPP (or any Apache + PHP 8.0+ + MySQL/MariaDB stack)
- No Composer dependencies — pure PHP with Tailwind CSS and Lucide icons via CDN

## Installation

1. Copy this folder into your web root (e.g. `C:\xampp\htdocs\Gitaloy`).
2. Make sure Apache and MySQL are running in the XAMPP control panel.
3. Open `http://localhost/Gitaloy/`.
4. The first page load creates the `gitaloy` database automatically from `database_schema.sql`.

The database and tables are created automatically. Default admin account:

- **Admin** — `admin@gitaloy.com` / `admin123`

> Change the admin password after first login. Other passwords default to `gitaloy123`
> (shown when creating accounts).

## Setup checklist

1. Log in as admin.
2. **Admin panel → Geography** — add divisions, districts, upazilas, villages.
3. **Admin panel → Centers** — add study centers and link each to a village.
4. **Admin panel → Classes / Subjects / Years** — ensure the academic structure exists.
5. **Admin panel → Fee heads / Items** — set up fee categories and distribution items.
6. **Admin panel → Users** — create divisional managers, district managers, accountants, teachers.
7. **Students** — register students at their centers.
8. **Attendance** — create a session, mark attendance, collect Friday fees in the same step.
9. **Distribution** — create a plan per zone, assign items to students.

## Default passwords

- Admin: `admin123`
- All created staff accounts: `gitaloy123`
- Student login: username = student ID (e.g. `GIT-00001`), password `gitaloy123`

## Database

- `database_schema.sql` — full schema (auto-applied on every connection, idempotent)
- `config/database.php` — connection settings (overridable with `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`)

## Layout

```
Gitaloy/
├── index.php                      # Public landing page (features, roles, sign-in)
├── database_schema.sql
├── config/database.php
├── includes/                      # auth, helpers, layout (header/sidebar/footer)
├── admin/                         # admin panel (geography, centers, users, setup)
└── modules/
    ├── auth/                      # login
    ├── dashboard/                 # role-aware home
    ├── students/                  # CRUD, search API, profile, progress
    ├── attendance/                # sessions + marking
    ├── fees/                      # fees + expenses + receipts
    ├── syllabus/                  # syllabus records
    ├── tasks/                     # tasks + marks
    ├── progress/                  # aggregate views
    ├── distribution/              # plans + per-student distribution
    ├── reports/                   # printable documents
    ├── users/                     # account management
    ├── settings/                  # site settings (admin)
    ├── content/                   # frontend content (admin)
    └── account/                   # my account (profile + password)
```
