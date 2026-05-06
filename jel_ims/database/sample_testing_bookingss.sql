-- =========================================
-- Demo bookings for Admin "Assign Technicians" / Cancel testing
-- Run AFTER database/jel_ims.sql AND database/sample_staff_technicians.sql
--   (technicians seed is optional but adds Assigned/Ongoing demo rows.)
--
-- Why: bookings created via the customer UI trigger auto-assignment when a
-- skilled technician is free — the admin queue often stays empty. These rows
-- are inserted as Unassigned with technician_id NULL so you can assign manually.
--
-- Password for demo customers (plain until first login): testpass123
-- Run ONCE per database; duplicate emails will cause errors if re-run as-is.
-- =========================================

USE jel_ims;

-- ----- Demo customers (role 4) -----
INSERT INTO users (full_name, email, password, role_id, status)
VALUES ('Demo Customer Ava', 'customer.ava@jelims.test', 'testpass123', 4, 'Active');
SET @cust_user_1 = LAST_INSERT_ID();

INSERT INTO customers (user_id, contact_number, address)
VALUES (@cust_user_1, '555-0300', '10 Sample Street, Demo City');

INSERT INTO users (full_name, email, password, role_id, status)
VALUES ('Demo Customer Ben', 'customer.ben@jelims.test', 'testpass123', 4, 'Active');
SET @cust_user_2 = LAST_INSERT_ID();

INSERT INTO customers (user_id, contact_number, address)
VALUES (@cust_user_2, '555-0301', '20 Sample Ave, Demo City');

-- ----- Unassigned — admin can assign any technician -----
INSERT INTO bookings (
    user_id, technician_id, service_id, booking_date, time_slot_id,
    status, cancellation_reason, rescheduled_from_booking_id, created_by, updated_by
) VALUES
(@cust_user_1, NULL, 1, '2026-06-15', 1, 'Unassigned', NULL, NULL, @cust_user_1, NULL),
(@cust_user_2, NULL, 2, '2026-06-15', 2, 'Unassigned', NULL, NULL, @cust_user_2, NULL),
(@cust_user_1, NULL, 4, '2026-06-16', 1, 'Unassigned', NULL, NULL, @cust_user_1, NULL),
(@cust_user_2, NULL, 5, '2026-06-17', 3, 'Unassigned', NULL, NULL, @cust_user_2, NULL);

-- ----- Assigned — appears under Cancel Booking (Admin) -----
INSERT INTO bookings (
    user_id, technician_id, service_id, booking_date, time_slot_id,
    status, cancellation_reason, rescheduled_from_booking_id, created_by, updated_by
)
SELECT
    @cust_user_1, t.id, 3, '2026-06-22', 2,
    'Assigned', NULL, NULL, @cust_user_1, NULL
FROM technicians AS t
ORDER BY t.id
LIMIT 1;

-- ----- Ongoing — also cancellable by admin -----
INSERT INTO bookings (
    user_id, technician_id, service_id, booking_date, time_slot_id,
    status, cancellation_reason, rescheduled_from_booking_id, created_by, updated_by
)
SELECT
    @cust_user_2, t.id, 2, '2026-06-23', 3,
    'Ongoing', NULL, NULL, @cust_user_2, NULL
FROM technicians AS t
ORDER BY t.id
LIMIT 1;
