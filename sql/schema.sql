-- ============================================================
-- Golden Power Security (GPS) — Database Schema (MySQL 5.7+/8)
-- ============================================================

CREATE DATABASE IF NOT EXISTS gps_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gps_website;

-- Admin users (the standalone admin panel logs in against this table)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','administrator','manager','security_supervisor','security_guard','cleaning_supervisor','cleaner') NOT NULL DEFAULT 'administrator',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Failed admin login attempts, used for brute-force rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(150) NOT NULL,   -- ip + username combo
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (identifier, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(80) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    role VARCHAR(40) NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role, permission_id),
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    actor_admin_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(80),
    entity_id VARCHAR(80),
    previous_value JSON NULL,
    new_value JSON NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_actor (actor_admin_id),
    CONSTRAINT fk_audit_admin FOREIGN KEY (actor_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO permissions (permission_key, label) VALUES
    ('dashboard.view', 'View dashboard'),
    ('customers.manage', 'Manage customers'),
    ('employees.manage', 'Manage employees'),
    ('operations.manage', 'Manage security and cleaning operations'),
    ('incidents.manage', 'Manage incidents'),
    ('finance.manage', 'Manage finance records'),
    ('content.manage', 'Manage website content'),
    ('reports.view', 'View reports'),
    ('settings.manage', 'Manage system settings'),
    ('audit.view', 'View audit logs')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'super_admin', id FROM permissions;
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'administrator', id FROM permissions WHERE permission_key NOT IN ('audit.view');
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'manager', id FROM permissions WHERE permission_key IN ('dashboard.view','customers.manage','employees.manage','operations.manage','incidents.manage','reports.view','content.manage');
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'security_supervisor', id FROM permissions WHERE permission_key IN ('dashboard.view','operations.manage','incidents.manage','reports.view');
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'cleaning_supervisor', id FROM permissions WHERE permission_key IN ('dashboard.view','operations.manage','reports.view');
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'security_guard', id FROM permissions WHERE permission_key IN ('dashboard.view','incidents.manage');
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'cleaner', id FROM permissions WHERE permission_key IN ('dashboard.view');

-- Client testimonials shown on the homepage
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(120) NOT NULL,
    client_title VARCHAR(150),          -- role / company, e.g. "Operations Manager, Acme Logistics"
    quote TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO testimonials (client_name, client_title, quote, is_active, sort_order) VALUES
('Chanda M.', 'Operations Manager, Kitwe Logistics', 'GPS has escorted our cross-border shipments for two years without a single incident. Professional, punctual, and always reachable.', 1, 1),
('Mwansa K.', 'Farm Owner, Copperbelt', 'Their armed patrol response is genuinely fast. Having a security partner we can trust has changed how we operate at night.', 1, 2),
('Bwalya T.', 'Facilities Lead, Ndola', 'From the cleaning crew to the site guards, everyone from GPS shows up on time and does the job properly.', 1, 3);

-- Global, editable site content: logo, hero background, site name, tagline, etc.
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(60) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('site_name', 'Golden Power Security'),
    ('tagline', 'Proudly protecting Southern Africa''s proudest possessions'),
    ('logo_path', ''),
    ('hero_bg_path', ''),
    ('about_text', 'Established in 2018, Golden Power Security (GPS) is a private armed security corporation delivering broad-based protection for business, transport, farming and game-farming clients across Southern Africa.'),
    ('stat1_value', '7'),   ('stat1_label', 'Years in Operation'),
    ('stat2_value', '500'), ('stat2_label', 'Clients Protected'),
    ('stat3_value', '24'),  ('stat3_label', 'Hour Response'),
    ('stat4_value', '60'),  ('stat4_label', 'Personnel Deployed'),
    ('whatsapp_number', '260971181142')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Services — fully admin-manageable (add / edit / delete / reorder / toggle live)
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,           -- e.g. SVC-01, shown as a manifest tag
    title VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(40) DEFAULT 'shield',   -- icon key used by the front-end
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO services (code, title, description, icon, sort_order) VALUES
('SVC-01', 'Business Security', '24/7 protection for enterprise premises, hardware, endpoints and on-site assets.', 'building', 1),
('SVC-02', 'Transport & Truck Escort', 'Specialised armed convoy escorting for cross-border cargo transit across Southern Africa.', 'truck', 2),
('SVC-03', 'Farming & Game Farming Security', 'Dedicated protection for agricultural land, livestock and game farm assets.', 'leaf', 3),
('SVC-04', 'Armed Patrol & Guarding', 'Highly trained, armed personnel delivering superior on-site safety solutions.', 'shield', 4);

-- Countries serviced for cross-border escorting (feeds the interactive route map)
CREATE TABLE IF NOT EXISTS escort_countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(60) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO escort_countries (country_name, sort_order) VALUES
('DRC', 1), ('Zambia', 2), ('Zimbabwe', 3), ('Mozambique', 4),
('Namibia', 5), ('Tanzania', 6), ('South Africa', 7);

-- Regional offices / contact details — fully editable by the admin
CREATE TABLE IF NOT EXISTS contact_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_label VARCHAR(80) NOT NULL,   -- e.g. "South Africa Office"
    address TEXT,
    phone1 VARCHAR(40),
    phone2 VARCHAR(40),
    email VARCHAR(120),
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO contact_info (region_label, address, phone1, phone2, email, sort_order) VALUES
('South Africa Office', 'N11, Plaas Doornplaatz, Tomburke, 0621', '+27 74 574 1187', '+27 78 519 9099', 'goldenpowersecut@gmail.com', 1),
('Zambia Office', 'Plot 14861, Topaz Road, Kamwala South, Lusaka', '+260 971 181 1427', '+260 966 588 611', 'goldpowsec@gmail.com', 2);

-- Daily quotes shown on the public home page
CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_text VARCHAR(500) NOT NULL,
    is_active TINYINT(1) DEFAULT 0,      -- only one should be active at a time
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO quotes (quote_text, is_active) VALUES
('Proudly protecting Southern Africa''s proudest possessions.', 1),
('Proudly protecting those who risk their lives to provide for Southern Africa daily.', 0);

-- Achievement / operation videos for the public gallery
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(400),
    video_url VARCHAR(500) NOT NULL,   -- YouTube/Vimeo embed URL, or uploads/videos/xxx.mp4
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Photo gallery — images uploaded via the admin panel
CREATE TABLE IF NOT EXISTS gallery_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    caption VARCHAR(400),
    image_path VARCHAR(500) NOT NULL,  -- relative path e.g. uploads/gallery/xxx.jpg
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Daily achievement posts with route details and journey photos
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    achievement_date DATE NOT NULL,
    start_point VARCHAR(150) NOT NULL,
    destination VARCHAR(150) NOT NULL,
    summary TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_achievements_active_date (is_active, achievement_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS achievement_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption VARCHAR(300),
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_achievement_photos_achievement
        FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Public inquiry / contact form submissions
CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(40),
    service_requested VARCHAR(120),
    message TEXT,
    status ENUM('Pending','Contacted','Closed') DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Quote calculator data for the public estimate form
CREATE TABLE IF NOT EXISTS service_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NULL,
    pricing_key VARCHAR(80) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL,
    category ENUM('security', 'cleaning', 'general') NOT NULL DEFAULT 'general',
    unit VARCHAR(40) NOT NULL DEFAULT 'flat',
    base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'ZMW',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_pricing_active_category (is_active, category),
    CONSTRAINT fk_service_pricing_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quote_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(32) NOT NULL UNIQUE,
    customer_id INT NULL,
    inquiry_id INT NULL,
    service_id INT NULL,
    pricing_key VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(40),
    location VARCHAR(180) NOT NULL,
    requirements JSON NULL,
    estimated_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'ZMW',
    status ENUM('Pending','Reviewed','Sent','Approved','Rejected','Expired') NOT NULL DEFAULT 'Pending',
    estimate_disclaimer VARCHAR(255) NOT NULL DEFAULT 'Estimate subject to site assessment and confirmation.',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quote_requests_status_created (status, created_at),
    INDEX idx_quote_requests_email (email),
    CONSTRAINT fk_quote_requests_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    CONSTRAINT fk_quote_requests_inquiry FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO service_pricing (service_id, pricing_key, label, category, unit, base_price, unit_price)
SELECT s.id, 'security_guard', 'Security Guard', 'security', 'guard_hour', 0, 85
FROM services s WHERE s.title LIKE '%Guard%' OR s.title LIKE '%Security%'
LIMIT 1;

INSERT INTO service_pricing (pricing_key, label, category, unit, base_price, unit_price)
VALUES
    ('mobile_patrol', 'Mobile Patrol', 'security', 'visit', 450, 0),
    ('cctv_monitoring', 'CCTV Monitoring', 'security', 'month', 1200, 0),
    ('event_security', 'Event Security', 'security', 'guard_hour', 0, 95),
    ('office_cleaning', 'Office Cleaning', 'cleaning', 'room', 350, 120),
    ('house_cleaning', 'House Cleaning', 'cleaning', 'room', 250, 100),
    ('deep_cleaning', 'Deep Cleaning', 'cleaning', 'room', 600, 180),
    ('window_cleaning', 'Window Cleaning', 'cleaning', 'room', 300, 80),
    ('carpet_cleaning', 'Carpet Cleaning', 'cleaning', 'room', 400, 110),
    ('other', 'Other Service', 'general', 'flat', 0, 0)
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Security and cleaning operations tables used by the protected admin workspace
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(40),
    email VARCHAR(150),
    position ENUM('Security Guard','Security Supervisor','Cleaner','Cleaning Supervisor','Manager','Administrator') NOT NULL,
    department ENUM('Security','Cleaning','Administration') NOT NULL,
    location VARCHAR(120),
    employment_status ENUM('Active','Inactive','On Leave') NOT NULL DEFAULT 'Active',
    date_joined DATE,
    training_status ENUM('Not Started','In Progress','Current','Expired') NOT NULL DEFAULT 'Not Started',
    profile_photo VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employees_status_department (employment_status, department),
    INDEX idx_employees_name (full_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS security_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(150) NOT NULL,
    client_name VARCHAR(150),
    address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    site_status ENUM('Operational','Attention','Critical','Inactive') NOT NULL DEFAULT 'Operational',
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_security_sites_status (site_status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employee_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    employee_id INT NOT NULL,
    shift_date DATE NOT NULL,
    shift_type ENUM('Day','Night','Custom') NOT NULL DEFAULT 'Day',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    supervisor_id INT NULL,
    status ENUM('Planned','Published','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_shifts_date_status (shift_date, status),
    CONSTRAINT fk_shifts_site FOREIGN KEY (site_id) REFERENCES security_sites(id) ON DELETE RESTRICT,
    CONSTRAINT fk_shifts_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    CONSTRAINT fk_shifts_supervisor FOREIGN KEY (supervisor_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employee_attendance (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT NOT NULL,
    employee_id INT NOT NULL,
    site_id INT NOT NULL,
    clocked_in_at DATETIME NULL,
    clocked_out_at DATETIME NULL,
    attendance_status ENUM('Not Started','On Duty','Completed','Absent') NOT NULL DEFAULT 'Not Started',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    location_consent TINYINT(1) NOT NULL DEFAULT 0,
    notes VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attendance_shift_employee (shift_id, employee_id),
    INDEX idx_attendance_status (attendance_status),
    CONSTRAINT fk_attendance_shift FOREIGN KEY (shift_id) REFERENCES employee_shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    CONSTRAINT fk_attendance_site FOREIGN KEY (site_id) REFERENCES security_sites(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS incident_reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    incident_number VARCHAR(32) NOT NULL UNIQUE,
    site_id INT NULL,
    employee_id INT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME NOT NULL,
    category ENUM('Unauthorized Access','Theft','Suspicious Activity','Property Damage','Fire','Medical Emergency','Workplace Incident','Security Breach','Other') NOT NULL,
    severity ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    description TEXT NOT NULL,
    ai_suggested_category VARCHAR(100) NULL,
    ai_suggested_severity VARCHAR(20) NULL,
    status ENUM('Reported','Under Investigation','Escalated','Resolved','Closed') NOT NULL DEFAULT 'Reported',
    assigned_investigator_id INT NULL,
    investigation_notes TEXT,
    resolution TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incidents_severity_status (severity, status),
    INDEX idx_incidents_date (incident_date),
    CONSTRAINT fk_incidents_site FOREIGN KEY (site_id) REFERENCES security_sites(id) ON DELETE SET NULL,
    CONSTRAINT fk_incidents_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    CONSTRAINT fk_incidents_investigator FOREIGN KEY (assigned_investigator_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS incident_timeline (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    incident_id BIGINT NOT NULL,
    actor_admin_id INT NULL,
    status VARCHAR(40),
    note TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_incident_timeline_incident (incident_id, created_at),
    CONSTRAINT fk_incident_timeline_incident FOREIGN KEY (incident_id) REFERENCES incident_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_incident_timeline_admin FOREIGN KEY (actor_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cleaning_jobs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_number VARCHAR(32) NOT NULL UNIQUE,
    client_name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NULL,
    team_size INT NOT NULL DEFAULT 1,
    supervisor_id INT NULL,
    status ENUM('Planned','In Progress','Inspection','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
    quality_rating TINYINT NULL,
    customer_feedback TEXT,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cleaning_jobs_status_start (status, start_at),
    CONSTRAINT fk_cleaning_jobs_supervisor FOREIGN KEY (supervisor_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cleaning_tasks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    cleaning_job_id BIGINT NOT NULL,
    task_name VARCHAR(150) NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    completed_by INT NULL,
    INDEX idx_cleaning_tasks_job (cleaning_job_id),
    CONSTRAINT fk_cleaning_tasks_job FOREIGN KEY (cleaning_job_id) REFERENCES cleaning_jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_cleaning_tasks_employee FOREIGN KEY (completed_by) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Default admin login: username "admin" / password "GoldenPower2026!"
-- (hash generated with PHP password_hash — change this after first login)
INSERT INTO admins (username, password_hash, role, is_active) VALUES
('admin', '$2y$10$TiqrplDAPVe3P0dakSu13OHBEZMhNzpZ9u9.nepYGXPwTc5o4KhK.', 'super_admin', 1);
