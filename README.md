# JEL Air Conditioning Services Integrated Management System (JEL-IMS)

PHP/MySQL web application for bookings, technician assignment, status workflow, notifications, and admin reporting. It uses a simple MVC-style layout: views under `app/views/`, controllers under `app/controllers/`, models under `app/models/`, and shared assets under `public/`.

---

## Requirements

| Component | Notes |
|-----------|--------|
| **PHP** | 8.0 or newer recommended (7.4+ with PDO usually works). |
| **Extensions** | `pdo`, `pdo_mysql`, `session`, `json`. |
| **Database** | MySQL 5.7+ or MariaDB 10.x. |
| **Web server** | Any server that can serve the **project root** as the document root (see below). |

---

## Installation

### 1. Get the code

Place the project folder (e.g. `jel_ims`) where your web server can read it.

### 2. Create the database

1. Start MySQL/MariaDB (e.g. XAMPP MySQL).
2. Import the schema and seed data:

   - **phpMyAdmin:** Create/import using `database/jel_ims.sql`.
   - **Command line:**

     ```bash
     mysql -u root -p < database/jel_ims.sql
     ```

   This creates the database `jel_ims`, tables, default services and time slots, and a **default Admin** account.

### 3. Configure database credentials

Edit `app/config/Database.php` if your MySQL user, password, host, or database name differ from the defaults:

- Host: `localhost`
- Database: `jel_ims`
- Username: `root`
- Password: *(empty string)*

### 4. Document root (important)

URLs and forms assume the **site root is the `jel_ims` folder**, not only `public/`. Examples:

- Login page: `/app/views/auth/login.php`
- Stylesheet: `/public/css/style.css`
- Controllers: `/app/controllers/AuthController.php`

**Correct setups:**

- **XAMPP:** Copy or symlink `jel_ims` into `htdocs/` and open  
  `http://localhost/jel_ims/app/views/auth/login.php`
- **PHP built-in server** (from inside `jel_ims`):

  ```bash
  cd jel_ims
  php -S localhost:8080
  ```

  Then open  
  `http://localhost:8080/app/views/auth/login.php`

If you point the server only at `public/`, login and other routes will break because `/app/...` paths will not resolve.

### 5. Default administrator account

After import, you can sign in as:

- **Email:** `admin@jelims.com`  
- **Password:** `admin123`

The seed password may be stored as plain text in SQL; the app will verify it and can rehash it on first successful login. **Change this password before any production use.**

---

## How to run

1. Ensure MySQL is running and `jel_ims` database exists.
2. Start your web server with document root = project folder (`jel_ims`).
3. In the browser, open the **login** URL (see above).

Logout: use **Logout** in the sidebar, or visit `/public/logout.php` under your base URL.

---

## Roles and account creation (current workflow)

| Role ID | Role | How to get an account |
|---------|------|------------------------|
| 1 | Admin | Seeded (`admin@jelims.com`). Admin handles oversight, reports, and account governance. |
| 2 | Staff | Not seeded; add first Staff user manually in the database (see below). Staff handles daily operations. |
| 3 | Technician | Created by Staff in **Manage Technicians** (or manual DB setup), with skills. |
| 4 | Customer | **Register** from the login page (`Register` link). |

### Responsibility split

- **Admin:** monitoring, read-only operational oversight, reports, system access governance.
- **Staff:** technician account lifecycle (create/edit/deactivate/reactivate), assignment, booking operations.
- **Technician:** booking detail visibility via notifications/dashboard, service status updates.
- **Customer:** booking creation/history, visibility of assigned technician details.

---

## Testing checklist (verify the system works)

Work through these in order. Use a **future date** for bookings when the app rejects past dates.

### A. Environment

- [ ] Login page loads with stylesheet (dark blue header/sidebar, white content).
- [ ] No PHP errors about database connection (if there are, fix `Database.php` and MySQL).

### B. Authentication

- [ ] **Login:** `admin@jelims.com` / `admin123` → redirects to Admin dashboard.
- [ ] **Logout** → session cleared; restricted pages redirect to login.
- [ ] **Register** a new customer → success message on login page → login as that customer → Customer dashboard.

### C. Customer (role 4)

While logged in as a customer:

- [ ] **Create Booking:** choose service, **future** date, time slot → confirmation / redirect without “past date” or duplicate-slot errors.
- [ ] **Booking History:** new booking appears; statuses such as Unassigned / Assigned display as expected.
- [ ] **Cancel booking** (where offered): booking updates or removes appropriately.
- [ ] **Notifications** page opens (may be empty or list items after actions).

### D. Admin (role 1, oversight)

Log back in as admin:

- [ ] **Dashboard** loads.
- [ ] **Operational oversight pages:** assignment/bookings/customer/technician pages load in read-only mode.
- [ ] **Reports** hub opens; open **Technician workload**, **RFM-style report**, or other linked reports — pages render (empty tables are OK on a fresh DB).
- [ ] **Notifications** opens.
- [ ] **Manage Users** opens for account governance (Admin/Staff direct creation is disabled in current workflow).

### E. Staff (role 2)

Create a Staff user in MySQL (see [Manual test users](#manual-test-users)), then:

- [ ] Login → Staff dashboard.
- [ ] **Manage Technicians:** create/edit/deactivate/reactivate technician accounts.
- [ ] **Assign Technicians:** assign pending bookings and process cancellations.
- [ ] **View Bookings:** list loads with customer/technician/status details.
- [ ] **Customer Details:** customer search/list and booking history load.
- [ ] **Notifications** works.

### F. Technician (role 3)

Requires a user with role Technician, a row in `technicians`, and at least one `technician_skills` row for a service you book (see [Manual test users](#manual-test-users)).

- [ ] Login → Technician dashboard.
- [ ] **Dashboard booking list** shows assigned bookings and links to booking details.
- [ ] **Notifications** can open booking details for booking-linked notifications.
- [ ] **Update Status:** valid transitions apply (e.g. toward Completed); invalid transitions should be rejected or messaged appropriately.
- [ ] After marking **No-Show** where applicable, verify behavior matches expectations (e.g. customer no-show tracking in DB).

### G. Regression spots

- [ ] Double booking same customer + same slot → handled (error, not silent duplicate).
- [ ] Assign same technician to two bookings **same date + same time slot** → **technician busy** style error when implemented.
- [ ] Past booking date on create → **past date** error.

---

## Manual test users (Staff / Technician)

Public registration only creates **customers**. To test Staff or Technician flows, insert users manually.

**1. Generate a password hash** (example password `testpass123`):

```bash
php -r "echo password_hash('testpass123', PASSWORD_DEFAULT), PHP_EOL;"
```

**2. Insert a technician** (replace `@hash` with the output; adjust email/name):

```sql
USE jel_ims;

INSERT INTO users (full_name, email, password, role_id, status)
VALUES ('Demo Technician', 'tech@jelims.test', '@hash', 3, 'Active');

SET @user_id = LAST_INSERT_ID();

INSERT INTO technicians (user_id, availability_status)
VALUES (@user_id, 'Available');

SET @tech_id = LAST_INSERT_ID();

-- Skill for service id 1 (e.g. General Cleaning — check `services` table)
INSERT INTO technician_skills (technician_id, service_id)
VALUES (@tech_id, 1);
```

**Staff:** same pattern with `role_id = 2` and **no** `technicians` / `technician_skills` rows unless you need that user to receive assignments.

---

## Project layout (quick reference)

```
jel_ims/
├── app/
│   ├── config/Database.php    # DB connection
│   ├── controllers/           # POST endpoints & orchestration
│   ├── models/                # Data access
│   └── views/                 # PHP templates + layouts
├── database/jel_ims.sql       # Schema + seed
├── public/
│   ├── css/style.css
│   ├── logout.php
│   └── index.php              # placeholder only — use login URL above
└── README.md
```

---

## Troubleshooting

| Issue | What to check |
|-------|----------------|
| Blank page / 500 | PHP error log; `pdo_mysql` enabled; correct PHP version. |
| Database connection error | MySQL running; database name `jel_ims`; credentials in `Database.php`. |
| CSS missing / broken layout | Document root must be **`jel_ims`**, not a subdirectory that drops `/public` or `/app` paths. |
| Login always fails | User `status` must be `Active`; email/password exact; re-import SQL if users table was edited. |
| Cannot assign technician | Technician must exist with a **technician_skills** row for the booked **service_id**. |

---

## Security note

This README describes a **local / demonstration** setup. For production: use strong passwords, HTTPS, restricted DB accounts, move sensitive configuration out of the web root, and replace any plain-text seed passwords in the database.