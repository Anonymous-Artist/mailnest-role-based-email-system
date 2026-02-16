CREATE DATABASE IF NOT EXISTS mailnest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mailnest;

-- -------------------------------------------------------
-- Users Table
-- -------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('admin','user') NOT NULL DEFAULT 'user',
    reminder_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Email Templates
-- -------------------------------------------------------
CREATE TABLE email_templates (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(150) NOT NULL,
    subject       VARCHAR(255) NOT NULL,
    body          TEXT NOT NULL,
    created_by    INT UNSIGNED NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Email Logs
-- -------------------------------------------------------
CREATE TABLE email_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT UNSIGNED NOT NULL,
    recipient_email VARCHAR(150) NOT NULL,
    subject         VARCHAR(255) NOT NULL,
    status          ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    error_message   TEXT DEFAULT NULL,
    sent_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Subscriptions
-- -------------------------------------------------------
CREATE TABLE subscriptions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    service_name  VARCHAR(150) NOT NULL,
    amount        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    billing_date  DATE NOT NULL,
    status        ENUM('active','inactive','cancelled') NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Default Admin User  (password: Admin@123)
-- Change this after first login!
-- -------------------------------------------------------
INSERT INTO users (name, email, password, role) VALUES (
    'System Admin',
    'admin@mailnest.local',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

CREATE INDEX idx_logs_status    ON email_logs(status);
CREATE INDEX idx_logs_admin     ON email_logs(admin_id);
CREATE INDEX idx_subs_user      ON subscriptions(user_id);
CREATE INDEX idx_subs_status    ON subscriptions(status);
CREATE INDEX idx_subs_billing   ON subscriptions(billing_date);
