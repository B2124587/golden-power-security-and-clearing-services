-- GPS access control and audit foundation. Additive and safe to re-run on MariaDB 10.4+.
USE gps_website;

ALTER TABLE admins
    ADD COLUMN IF NOT EXISTS role ENUM('super_admin','administrator','manager','security_supervisor','security_guard','cleaning_supervisor','cleaner') NOT NULL DEFAULT 'administrator',
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;

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