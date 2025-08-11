# STEG Intern Management System

This is a web-based intern management system for Société Tunisienne de l'Electricité et du Gaz (STEG). The system allows administrators, directors, supervisors, and interns to manage the internship process effectively.

## Features

- **User Role Management**
  - Four user types: Admin, Director, Supervisor, Intern
  - Directors: manage only supervisors assigned to them
  - Supervisors: manage only their assigned interns
  - Interns: interact only with their assigned supervisor
  - Role-based dashboards and department-aware scoping

- **Intern Management**
  - Register new interns
  - Assign supervisors to interns
  - Track internship status and progress

- **Report Management**
  - Interns can submit weekly, midway, and final reports
  - Supervisors can review and provide feedback on reports

- **Evaluations**
  - Manage and review supervisor evaluations of interns
  - Filter, sort, and paginate evaluations

- **Analytics Dashboard**
  - High-level stats and department visualizations (Chart.js)
  - Accessible from the Admin dashboard “Analytique” card

- **Attestation Generation**
  - Generate and manage attestations/certificates
  - PDF export functionality

- **Tables: Sorting, Pagination, CSV Export**
  - Reusable table utilities for sorting (with ▲/▼ indicators) and pagination
  - Export current page or all rows to CSV

- **Security Features**
  - Email + password only authentication (no username login)
  - Passwords stored hashed (PHP password_hash)
  - Brute-force protection (account lockout after repeated failures)
  - Session management
  - Staff domain enforcement for internal roles (emails ending with `@steg.tn`)
  - Forgotten password policy: users must contact administration (no self-service reset)

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: SQLite
- **Libraries**: 
  - jsPDF (PDF generation)
  - Chart.js (Statistics visualization)
  - Lightweight in-house table utilities (sorting, pagination, CSV export)

## Setup Instructions

1. Make sure PHP is installed on your system:
   ```
   php --version
   ```

2. Run the setup script to initialize the database:
   ```
   ./setup.sh
   ```

3. Start the development server:
   ```
   php -S localhost:8000
   ```

4. Access the system at http://localhost:8000/login.html

## Seeded Test Accounts (Email / Plaintext Password)

All accounts authenticate by email + password. Usernames remain internal identifiers.

| Role | Email | Password (plaintext for testing) |
|------|-------|----------------------------------|
| Admin | admin@steg.tn | admin123 |
| Director | director@steg.tn | director123 |
| Supervisor (Informatique) | samira.bensalah@steg.tn | supervisor1 |
| Supervisor (Finance) | mohamed.trabelsi@steg.tn | supervisor2 |
| Supervisor (RH) | fatma.khalil@steg.tn | supervisor3 |
| Intern 1 | firas.welhazi@email.com | intern1 |
| Intern 2 | samira.khedher@email.com | intern2 |
| Intern 3 | ahmed.bensalah@email.com | intern3 |

Passwords above are stored hashed in SQLite; values shown are their original plaintext for local testing.

## Directory Structure

- `admin-dashboard.html` - Admin dashboard
- `director-dashboard.html` - Director dashboard (scoped supervisor/intern view)
- `analytics-dashboard.html` - Analytics dashboard (charts and stats)
- `supervisor-dashboard.html` - Supervisor dashboard
- `intern-dashboard.html` - Intern dashboard
- `attestations.html` - Attestation management page
- `evaluations.html` - Evaluations listing and form
- `reports.html` - Simple reports listing
- `login.html` - Unified email-based login page for all user types
*Postponed:* Internship application portal moved to `internship-application (postponed)/` (inactive).
- `common.js` - Shared JavaScript functions
- `table-utils.js` - Sorting, pagination, and CSV export for tables
- `analytics.js` - Analytics dashboard behavior
- `styles.css` - Global stylesheet
- `database/`
  - `db.php` - Database connection, schema, helpers, and seed data
  - `init_db.php` - Database initialization script
  - `api.php` - Backend API endpoints
  - `functions.php` - Shared backend helpers
  - `steg_interns.db` - SQLite database file (created by setup script)

Additional supervisor pages:
- `supervisor-interns.html` - Supervisor’s view of assigned interns
- `supervisor-attestations.html` - Attestations managed by supervisors

## Security Considerations

- All passwords are hashed using PHP's password_hash function
- The system includes protection against brute force attacks (lockout after repeated failures)
- User sessions are managed securely
- Internal roles require staff email domain (e.g., `@steg.tn`)

## License

This project is proprietary software developed for STEG.
