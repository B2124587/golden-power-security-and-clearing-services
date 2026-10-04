-- GPS quote foundation. Additive migration; existing tables and inquiry flow remain intact.
USE gps_website;

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