        -- =========================================
        -- DATABASE: jel_ims
        -- PHASE 2 — Final schema (RBAC, bookings, notifications, RFM)
        -- Compatible with phpMyAdmin / XAMPP (MySQL / MariaDB)
        -- =========================================

        CREATE DATABASE IF NOT EXISTS jel_ims;
        USE jel_ims;

        -- =========================================
        -- 1. ROLES (RBAC)
        -- =========================================

        CREATE TABLE roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(50) NOT NULL
        );

        INSERT INTO roles (role_name) VALUES
        ('Admin'),
        ('Staff'),
        ('Technician'),
        ('Customer');

        -- =========================================
        -- 2. USERS
        -- =========================================

        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role_id INT NOT NULL,
            status VARCHAR(20) DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            UNIQUE KEY uq_users_email (email),

            FOREIGN KEY (role_id)
                REFERENCES roles (id)
                ON DELETE RESTRICT
        );

        -- =========================================
        -- 3. CUSTOMERS (includes no_show_count for No-Show tracking)
        -- =========================================

        CREATE TABLE customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            contact_number VARCHAR(20),
            address TEXT,
            no_show_count INT NOT NULL DEFAULT 0,

            FOREIGN KEY (user_id)
                REFERENCES users (id)
                ON DELETE CASCADE
        );

        -- =========================================
        -- 4. TECHNICIANS
        -- =========================================

        CREATE TABLE technicians (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            availability_status VARCHAR(50) DEFAULT 'Available',

            FOREIGN KEY (user_id)
                REFERENCES users (id)
                ON DELETE CASCADE
        );

        -- =========================================
        -- 5. SERVICES
        -- =========================================

        CREATE TABLE services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_name VARCHAR(100) NOT NULL,
            estimated_duration_minutes INT NOT NULL
        );

        INSERT INTO services (service_name, estimated_duration_minutes) VALUES
        ('General Cleaning', 60),
        ('Deep Cleaning', 180),
        ('Repair', 120),
        ('Installation', 240),
        ('Maintenance', 90);

        -- =========================================
        -- 6. TECHNICIAN SKILLS (service ↔ technician)
        -- =========================================

        CREATE TABLE technician_skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            technician_id INT NOT NULL,
            service_id INT NOT NULL,

            FOREIGN KEY (technician_id)
                REFERENCES technicians (id)
                ON DELETE CASCADE,

            FOREIGN KEY (service_id)
                REFERENCES services (id)
                ON DELETE RESTRICT
        );

        -- =========================================
        -- 7. TIME SLOTS
        -- =========================================

        CREATE TABLE time_slots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            time_value VARCHAR(20) NOT NULL
        );

        INSERT INTO time_slots (time_value) VALUES
        ('8:00 AM'),
        ('10:00 AM'),
        ('1:00 PM'),
        ('3:00 PM');

        -- =========================================
        -- 8. BOOKINGS
        -- Status (application): Unassigned, Assigned, Ongoing, Completed, Cancelled, No-Show
        -- Audit: created_by, updated_by, updated_at
        -- =========================================

        CREATE TABLE bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,

            user_id INT NOT NULL,
            technician_id INT NULL,

            service_id INT NOT NULL,
            booking_date DATE NOT NULL,
            time_slot_id INT NOT NULL,

            status VARCHAR(20) DEFAULT 'Unassigned',

            cancellation_reason TEXT,
            rescheduled_from_booking_id INT NULL,

            created_by INT NULL,
            updated_by INT NULL,
            updated_at TIMESTAMP NULL,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            FOREIGN KEY (user_id)
                REFERENCES users (id)
                ON DELETE RESTRICT,

            FOREIGN KEY (technician_id)
                REFERENCES technicians (id)
                ON DELETE SET NULL,

            FOREIGN KEY (service_id)
                REFERENCES services (id)
                ON DELETE RESTRICT,

            FOREIGN KEY (time_slot_id)
                REFERENCES time_slots (id)
                ON DELETE RESTRICT
        );

        CREATE INDEX idx_bookings_status ON bookings (status);
        CREATE INDEX idx_bookings_date ON bookings (booking_date);
        CREATE INDEX idx_bookings_technician ON bookings (technician_id);
        CREATE INDEX idx_bookings_user ON bookings (user_id);

        -- One assignment per technician per date/slot (NULL technician_id = multiple unassigned OK)
        ALTER TABLE bookings
            ADD UNIQUE KEY unique_technician_slot (technician_id, booking_date, time_slot_id);

        -- =========================================
        -- 9. SERVICE HISTORY
        -- =========================================

        CREATE TABLE service_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            user_id INT NOT NULL,
            service_notes TEXT,
            completion_date DATE,

            FOREIGN KEY (booking_id)
                REFERENCES bookings (id)
                ON DELETE CASCADE,

            FOREIGN KEY (user_id)
                REFERENCES users (id)
                ON DELETE RESTRICT
        );

        -- =========================================
        -- 10. NOTIFICATIONS
        -- =========================================

        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,

            user_id INT NOT NULL,
            booking_id INT NULL,

            message TEXT NOT NULL,
            type VARCHAR(50),
            status VARCHAR(20) DEFAULT 'Unread',

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            FOREIGN KEY (user_id)
                REFERENCES users (id)
                ON DELETE CASCADE,

            FOREIGN KEY (booking_id)
                REFERENCES bookings (id)
                ON DELETE SET NULL
        );

        CREATE INDEX idx_notifications_user_status ON notifications (user_id, status);

        -- =========================================
        -- 11. NOTIFICATION TRIGGERS (reference / seed events)
        -- =========================================

        CREATE TABLE notification_triggers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_name VARCHAR(100),
            description TEXT
        );

        INSERT INTO notification_triggers (event_name, description) VALUES
        ('Booking Created', 'Notify admin and technician'),
        ('Technician Assigned', 'Notify technician'),
        ('Service Completed', 'Notify customer'),
        ('Booking Cancelled', 'Notify customer');

        -- =========================================
        -- DATA MINING — RFM-style customer metrics (completed bookings only)
        -- =========================================

        DROP VIEW IF EXISTS rfm_customer_analysis;

        CREATE VIEW rfm_customer_analysis AS
        SELECT
            b.user_id,
            u.full_name,
            COUNT(b.id) AS frequency,
            DATEDIFF(CURDATE(), MAX(b.booking_date)) AS recency_days,
            SUM(s.estimated_duration_minutes) AS total_service_minutes
        FROM bookings b
        INNER JOIN services s ON b.service_id = s.id
        INNER JOIN users u ON b.user_id = u.id
        WHERE b.status = 'Completed'
        GROUP BY b.user_id, u.full_name;

        -- =========================================
        -- DEFAULT ADMIN (replace password via password_hash() in PHP before production)
        -- =========================================

        INSERT INTO users (
            full_name,
            email,
            password,
            role_id
        ) VALUES (
            'System Administrator',
            'admin@jelims.com',
            'admin123',
            1
        );
