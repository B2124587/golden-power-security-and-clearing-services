-- GPS security and cleaning operations foundation.
USE gps_website;

INSERT INTO permissions (permission_key, label) VALUES
    ('operations.manage', 'Manage security and cleaning operations')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'super_admin', id FROM permissions WHERE permission_key = 'operations.manage';
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'administrator', id FROM permissions WHERE permission_key = 'operations.manage';
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'manager', id FROM permissions WHERE permission_key = 'operations.manage';
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'security_supervisor', id FROM permissions WHERE permission_key = 'operations.manage';
INSERT IGNORE INTO role_permissions (role, permission_id)
SELECT 'cleaning_supervisor', id FROM permissions WHERE permission_key = 'operations.manage';

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