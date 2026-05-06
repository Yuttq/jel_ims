    -- =========================================
    -- Sample Staff + Technicians for local testing
    -- Run AFTER database/jel_ims.sql (requires roles, services).
    --
    -- Password for every account below: testpass123
    -- (Stored plain in SQL; first login hashes via AuthController.)
    -- =========================================

    USE jel_ims;

    -- ----- Staff (role_id = 2) -----
    INSERT INTO users (full_name, email, password, role_id, status) VALUES
    ('Morgan Chen', 'staff1@jelims.test', 'testpass123', 2, 'Active'),
    ('Riley Santos', 'staff2@jelims.test', 'testpass123', 2, 'Active');

    -- ----- Technician 1: cleaning-focused -----
    INSERT INTO users (full_name, email, password, role_id, status)
    VALUES ('Alex Rivera', 'tech1@jelims.test', 'testpass123', 3, 'Active');
    SET @u_tech1 = LAST_INSERT_ID();
    INSERT INTO technicians (user_id, availability_status) VALUES (@u_tech1, 'Available');
    SET @t1 = LAST_INSERT_ID();
    INSERT INTO technician_skills (technician_id, service_id) VALUES
    (@t1, 1),  -- General Cleaning
    (@t1, 2);  -- Deep Cleaning

    -- ----- Technician 2: repair / install -----
    INSERT INTO users (full_name, email, password, role_id, status)
    VALUES ('Jordan Blake', 'tech2@jelims.test', 'testpass123', 3, 'Active');
    SET @u_tech2 = LAST_INSERT_ID();
    INSERT INTO technicians (user_id, availability_status) VALUES (@u_tech2, 'Busy');
    SET @t2 = LAST_INSERT_ID();
    INSERT INTO technician_skills (technician_id, service_id) VALUES
    (@t2, 3),  -- Repair
    (@t2, 4);  -- Installation

    -- ----- Technician 3: general + maintenance (overlap with tech1 on service 1) -----
    INSERT INTO users (full_name, email, password, role_id, status)
    VALUES ('Sam Okonkwo', 'tech3@jelims.test', 'testpass123', 3, 'Active');
    SET @u_tech3 = LAST_INSERT_ID();
    INSERT INTO technicians (user_id, availability_status) VALUES (@u_tech3, 'Available');
    SET @t3 = LAST_INSERT_ID();
    INSERT INTO technician_skills (technician_id, service_id) VALUES
    (@t3, 1),  -- General Cleaning
    (@t3, 5);  -- Maintenance
