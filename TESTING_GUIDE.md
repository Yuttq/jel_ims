# JEL-IMS — Full system testing guide

Use this document to verify the application end-to-end after installation. Work through the sections in order unless you are only regression-testing one area.

---

## 1. Before you test

### 1.1 Environment

- MySQL/MariaDB is running (e.g. XAMPP MySQL started).
- PHP can connect using `app/config/Database.php` (default: host `localhost`, database `jel_ims`, user `root`, empty password unless you changed it).
- The **document root is the `jel_ims` project folder**, not only `public/`. See `README.md` if layout or CSS looks broken.

### 1.2 Database setup

1. Import **`database/jel_ims.sql`** (schema, services, time slots, default admin).
2. **Recommended:** Import **`database/sample_staff_technicians.sql`** so Staff and Technician logins exist and every seeded service has at least one skilled technician.
3. **Assign / cancel QA data:** Import **`database/sample_testing_bookings.sql`** (after step 2) for demo customers and **Unassigned** bookings. Customer-created bookings often auto-assign, which leaves Admin “Assign Technicians” empty — this seed fixes that.

### 1.3 Base URL examples

Replace with your actual host/path:

| Setup | Example login URL |
|--------|-------------------|
| XAMPP (`htdocs/jel_ims`) | `http://localhost/jel_ims/app/views/auth/login.php` |
| PHP built-in server from project root | `http://localhost:8080/app/views/auth/login.php` |

All steps below assume you open pages relative to that base (same pattern as the login URL).

**Admin Manage Users:** `{base}/app/views/admin/manage_users.php`  
**Staff Manage Technicians:** `{base}/app/views/staff/manage_technicians.php`  
**Staff Assign Technicians:** `{base}/app/views/staff/assign_technicians.php`  
**Staff View Bookings:** `{base}/app/views/staff/view_bookings.php`  
**Staff Customer Details:** `{base}/app/views/staff/customer_details.php`

### 1.4 Test accounts

| Role | Email | Password | Source |
|------|--------|----------|--------|
| Admin | `admin@jelims.com` | `admin123` | `jel_ims.sql` |
| Staff | `staff1@jelims.test` | `testpass123` | `sample_staff_technicians.sql` |
| Staff | `staff2@jelims.test` | `testpass123` | `sample_staff_technicians.sql` |
| Technician | `tech1@jelims.test` | `testpass123` | `sample_staff_technicians.sql` |
| Technician | `tech2@jelims.test` | `testpass123` | `sample_staff_technicians.sql` |
| Technician | `tech3@jelims.test` | `testpass123` | `sample_staff_technicians.sql` |
| Customer | *(register yourself)* | *(your choice)* | Register on login page |
| Customer | `customer.ava@jelims.test` | `testpass123` | `sample_testing_bookings.sql` |
| Customer | `customer.ben@jelims.test` | `testpass123` | `sample_testing_bookings.sql` |

Seed passwords may be plain text in SQL; the app accepts them and can upgrade them to a hash on first successful login.

---

## 2. Understanding booking assignment

After a customer submits **Create Booking**, the app runs **automatic assignment**: it tries to assign a technician who has a **technician_skills** row for that service, has **no conflicting booking** on the same date and time slot, and prefers lower current workload.

**What this means for testing**

- With **`sample_staff_technicians.sql`** loaded, many new bookings become **`Assigned`** immediately (not `Unassigned`).
- **Admin → Assign Technicians** is easiest to exercise when there is at least one **`Unassigned`** booking. That happens if no skilled technician is free for that slot, or if you test against a database **without** technician rows/skills (schema + admin only), then create bookings and assign manually.

You can still validate **manual assignment**, **technician busy**, and **status updates** using the flows below; adjust expectations if auto-assignment already fired.

---

## 3. Phase A — Smoke test (environment + auth)

| Step | Action | Expected result |
|------|--------|------------------|
| A1 | Open the login page | Page loads; header/sidebar styling looks correct (CSS from `/public/css/style.css`). |
| A2 | Log in as **Admin** | Redirect to Admin dashboard; no database errors. |
| A3 | Click **Logout** (or open `/public/logout.php`) | Session ends; opening an admin URL redirects to login. |
| A4 | Open **Register**, create a customer | Redirect to login with success indicator; new user can log in. |
| A5 | Log in as the new **customer** | Redirect to Customer dashboard. |

**Failure cues:** blank page → check PHP error log; wrong layout → document root; login fails → user `status` must be `Active`, credentials exact.

---

## 4. Phase B — Customer (role 4)

Use a **future** booking date (`YYYY-MM-DD` strictly after today).

| Step | Action | Expected result |
|------|--------|------------------|
| B1 | **Create Booking** — pick service, date, time slot, submit | Redirect to booking history with success; booking appears. |
| B2 | **Booking History** | New row visible; status is `Unassigned` or `Assigned` (see §2). |
| B3 | **Past date** | Set date before today → error path (`?error=past_date`), booking not created. |
| B4 | **Duplicate slot** | Same customer, same date, same time slot again → `?error=duplicate`. |
| B5 | **Cancel booking** (customer form on booking history, where shown) | Requires a reason; success redirect; booking cancelled per app rules. |
| B6 | **Notifications** | Page opens; after creating a booking you may see customer/admin notifications depending on flow. |

---

## 5. Phase C — Admin (role 1, oversight)

| Step | Action | Expected result |
|------|--------|------------------|
| C1 | **Dashboard** | Loads without errors. |
| C2 | **Assign Technicians page (read-only)** | Page opens and shows monitoring data; assignment/cancel actions are Staff-managed. |
| C3 | **Operational oversight pages** | Staff pages (`view_bookings`, `customer_details`, `manage_technicians`) open under Admin in read-only mode. |
| C4 | **Manage Users** | Page opens; Admin can create Admin/Staff and Technician accounts via forms. |
| C5 | **Reports** | Opens; open **Technician workload** and **RFM-style report** (and any links from Reports hub) — pages render; empty tables are OK on a fresh DB. |
| C6 | **Notifications** | Opens. |
| C7 | **Charts and summary cards** | Reports charts are visible, compact, and responsive (not full-screen stretched). |

### 5.1 Manage Users (Admin governance checks)

Use an **Admin** session. Exact status values used by the app are **`Active`** and **`Inactive`** (case-sensitive).

| Step | Action | Expected result |
|------|--------|------------------|
| MU1 | Sidebar → **Manage Users** | Page loads; **All users** table shows **ID, Name, Email, Role, Status, Created**, and **Actions**; **no password column** (view page source → no bcrypt-style hashes exposed). |
| MU2 | **Add Admin or Staff** — create a Staff user | Success message; new row **`Active`** appears in the list. |
| MU3 | **Add Admin or Staff** — reuse an existing email | Error path; no duplicate row. |
| MU4 | **Add Technician** — pick **≥ one** skill and submit | Success message; row appears as Technician with **`technician_skills`**. |
| MU5 | **Add Technician** — zero skills checked | Validation error; nothing committed. |
| MU6 | **Edit** a non-technician user (`?edit=id`) — change name or email → **Save changes** | **User was updated successfully** message; table reflects changes; password unchanged if password fields left blank. |
| MU7 | **Edit** a user that has a **technician** profile (`technicians.user_id`) | Role is **Technician** and locked (hidden `edit_role_id=3`); you can still update name/email/optional password. |
| MU8 | **Deactivate** (**Inactive**) on any **Active** user except guarded cases below | **Account status was updated.** user shows **`Inactive`**; that account **cannot log in** (inactive message on login page). |
| MU9 | **Restore** (**Active**) on that user | User can log in again. |
| MU10 | **Deactivate** yourself (current Admin viewing the row for your signed-in account) | Error — **cannot deactivate your own account** (recommended safety rule). |
| MU11 | **Deactivate** when this user is the **only** **`Active`** user with Admin role (`role_id = 1`) | Error — cannot remove the **last Active Administrator**. |
| MU12 | **Deactivate** a **Technician** who has **`Assigned`** or **`Ongoing`** bookings | Error — unblock only after bookings are **`Completed`**, **`Cancelled`**, **`No-Show`**, or otherwise no longer Assigned/Ongoing on that technician. |

**Quick SQL (optional)**

```sql
-- After MU4/MU5 technician tests
SELECT t.id AS technician_pk, u.email, COUNT(ts.id) AS skill_rows
FROM technicians t
JOIN users u ON u.id = t.user_id
LEFT JOIN technician_skills ts ON ts.technician_id = t.id
WHERE u.email = 'you@your-test-email.test'
GROUP BY t.id, u.email;
```

---

## 6. Phase D — Technician (role 3)

Use an account that has a row in **`technicians`** linked to `users.id` (sample file provides this).

| Step | Action | Expected result |
|------|--------|------------------|
| D1 | Log in as **tech1@jelims.test** | Technician dashboard; counts reflect DB (no “no technician profile” message). |
| D2 | **Update Status** | Lists bookings assigned to this technician in **Assigned** / **Ongoing** (as implemented). |
| D3 | Valid transitions | From **Assigned** → **Ongoing** or **Completed** or **No-Show** where allowed; success feedback. |
| D4 | Invalid transition | Wrong jump (e.g. skipping allowed states) → error / no silent success. |
| D5 | **No-Show** | When applied from an allowed state, booking updates; customer **no_show_count** can increment (verify in DB if needed). |
| D6 | **Notifications** | Opens; booking-linked notifications can open technician booking details page. |
| D7 | **Dashboard booking links** | "View booking" opens the same technician booking details page. |

**Optional direct URL:**

- Technician booking details:  
  `{base}/app/views/technician/booking_details.php?booking_id=<id>`

---

## 7. Phase E — Staff (role 2, operational center)

| Step | Action | Expected result |
|------|--------|------------------|
| E1 | Log in as **staff1@jelims.test** | Staff dashboard; metrics (today’s bookings, ongoing, unassigned) show numbers. |
| E2 | **Manage Technicians** | Create/edit/deactivate/reactivate technician accounts works. |
| E3 | **Assign Technicians** | Assignment and cancellation operations work for Staff. |
| E4 | **View Bookings** | Booking list renders with customer/technician/status details. |
| E5 | **Customer Details** | Customer list/search and per-customer booking history work. |
| E6 | **Notifications** | Opens. |

---

## 8. Phase F — Visibility checks

| Check | How | Expected |
|--------|-----|----------|
| Customer status label clarity | View customer booking with DB status `Unassigned` | UI label shows **Pending Technician Assignment**. |
| Customer technician visibility | Open assigned booking in customer history | Technician name/email/role are shown. |
| Staff booking/customer status label clarity | Open Staff booking/customer pages for unassigned rows | Label shows **Pending Technician Assignment**. |

---

## 9. Phase G — Regression bundle (quick)

Run these anytime after changes:

| Check | How | Expected |
|--------|-----|----------|
| Session security | Open customer URL while logged out | Redirect to login. |
| Role isolation | Open Admin dashboard URL as customer | Redirect / denied. |
| Register duplicate | Register same email twice | Duplicate error path. |
| Inactive user | In **Manage Users**, **Deactivate** a test user (`status = 'Inactive'`), or run `UPDATE users SET status='Inactive' WHERE id=…`) | Login rejected (**inactive account** style message); restore with **Restore** clears it when allowed. |

---

## 10. Optional database checks

If something looks wrong, confirm in MySQL (phpMyAdmin or CLI):

- `SELECT id, full_name, email, role_id, status FROM users;`
- `SELECT * FROM bookings ORDER BY id DESC LIMIT 10;`
- `SELECT * FROM notifications ORDER BY id DESC LIMIT 10;`
- For No-Show tests: `customers.no_show_count` for the booking’s customer user.

---

## 11. Pass/fail log (template)

| Phase | Pass? | Notes |
|-------|-------|-------|
| A — Smoke | ☐ | |
| B — Customer | ☐ | |
| C — Admin | ☐ | |
| D — Technician | ☐ | |
| E — Staff | ☐ | |
| F — Visibility | ☐ | |
| G — Regression | ☐ | |

Date tested: _______________  
Tester: _______________  
Git commit / DB dumps used: _______________

---

For installation details and troubleshooting, see **`README.md`**.
