-- ============================================
-- COMPLAINT MANAGEMENT SYSTEM - DATABASE SETUP
-- Phase 1: Database Design and Creation
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS complaint_management_system;
USE complaint_management_system;

-- ============================================
-- Table 1: Users Table
-- Stores both regular users and administrators
-- ============================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- ============================================
-- Table 2: Categories Table
-- Stores complaint categories for classification
-- ============================================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_status (status)
);

-- ============================================
-- Table 3: Complaints Table
-- Main table for storing all complaints
-- ============================================
CREATE TABLE complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Pending',
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    assigned_to INT NULL,
    admin_response TEXT,
    submitted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_category (category_id),
    INDEX idx_submitted_date (submitted_date)
);

-- ============================================
-- Table 4: Complaint History Table
-- Tracks all changes made to complaints
-- ============================================
CREATE TABLE complaint_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    changed_by INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    comment TEXT,
    changed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_complaint (complaint_id),
    INDEX idx_changed_date (changed_date)
);

-- ============================================
-- Insert Default Admin Account
-- Username: admin@cms.com
-- Password: Admin@123 (hashed using PASSWORD function)
-- NOTE: Change this password after first login!
-- ============================================
INSERT INTO users (full_name, email, phone, password, role, status) VALUES
('System Administrator', 'admin@cms.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
-- Default password is: Admin@123
-- You should hash passwords properly in PHP using password_hash()

-- ============================================
-- Insert Sample Categories
-- ============================================
INSERT INTO categories (category_name, description, status) VALUES
('Technical Support', 'Issues related to technical problems, system errors, or software bugs', 'active'),
('Billing & Payment', 'Complaints about billing, invoices, payment issues, or refunds', 'active'),
('Service Quality', 'Issues regarding service delivery, quality, or customer experience', 'active'),
('Account Management', 'Problems with account access, profile settings, or account security', 'active'),
('General Inquiry', 'General questions, suggestions, or feedback', 'active'),
('Facility Issues', 'Problems related to physical facilities, infrastructure, or equipment', 'active'),
('Staff Behavior', 'Complaints about staff conduct, communication, or professionalism', 'active'),
('Other', 'Complaints that do not fit into other categories', 'active');

-- ============================================
-- Insert Sample Test User
-- Email: user@test.com
-- Password: User@123
-- ============================================
INSERT INTO users (full_name, email, phone, password, role, status) VALUES
('Test User', 'user@test.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active');

-- ============================================
-- Insert Sample Complaints for Testing
-- ============================================
INSERT INTO complaints (user_id, category_id, subject, description, status, priority) VALUES
(2, 1, 'Cannot login to my account', 'I have been trying to login for the past hour but keep getting an error message', 'Pending', 'High'),
(2, 3, 'Poor customer service experience', 'The staff was not helpful when I called for assistance yesterday', 'In Progress', 'Medium');

-- ============================================
-- Insert Sample History Records
-- ============================================
INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, comment) VALUES
(2, 1, 'Pending', 'In Progress', 'Complaint assigned to support team for investigation');

-- ============================================
-- Useful Views for Reporting
-- ============================================

-- View: Active Complaints Summary
CREATE VIEW active_complaints_summary AS
SELECT 
    c.complaint_id,
    c.subject,
    u.full_name AS submitted_by,
    cat.category_name,
    c.status,
    c.priority,
    c.submitted_date,
    DATEDIFF(NOW(), c.submitted_date) AS days_pending
FROM complaints c
JOIN users u ON c.user_id = u.user_id
LEFT JOIN categories cat ON c.category_id = cat.category_id
WHERE c.status != 'Closed';

-- View: Complaint Statistics by Category
CREATE VIEW complaints_by_category AS
SELECT 
    cat.category_name,
    COUNT(*) AS total_complaints,
    SUM(CASE WHEN c.status = 'Pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN c.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
    SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
    SUM(CASE WHEN c.status = 'Closed' THEN 1 ELSE 0 END) AS closed
FROM complaints c
LEFT JOIN categories cat ON c.category_id = cat.category_id
GROUP BY cat.category_name;


-- this an update of mysql since my system add some features the db also update

USE complaint_management_system;

-- Add admin_level column to users table
ALTER TABLE users 
ADD COLUMN admin_level ENUM('super_admin', 'admin') DEFAULT 'admin' AFTER role;

-- Make your current admin account a Super Admin
-- Replace 'YOUR_ADMIN_EMAIL' with your admin email
UPDATE users 
SET admin_level = 'super_admin' 
WHERE email = 'YOUR_ADMIN_EMAIL' AND role = 'admin';

-- You can also update all existing admins to super_admin
-- UPDATE users SET admin_level = 'super_admin' WHERE role = 'admin';

USE complaint_management_system;

-- Create table for complaint attachments
CREATE TABLE IF NOT EXISTS complaint_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    uploaded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    INDEX idx_complaint (complaint_id)
)

USE complaint_management_system;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    complaint_id INT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

USE complaint_management_system;

-- Create complaint comments table
CREATE TABLE IF NOT EXISTS complaint_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_complaint (complaint_id),
    INDEX idx_created (created_at)
);

-- Add approval_status column to users table
ALTER TABLE users 
ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER status;

-- Set existing users as approved (so current users aren't locked out)
UPDATE users SET approval_status = 'approved';

USE complaint_management_system;

-- Add failed login tracking columns to users table
ALTER TABLE users 
ADD COLUMN failed_login_attempts INT DEFAULT 0 AFTER approval_status,
ADD COLUMN last_failed_login DATETIME NULL AFTER failed_login_attempts,
ADD COLUMN account_locked_until DATETIME NULL AFTER last_failed_login;

-- Create login attempts log table (optional, for tracking)
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempt_time)
);

USE complaint_management_system;

-- Create password reset tokens table
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_otp (otp_code),
    INDEX idx_expires (expires_at)
);


-- Add profile_picture column to users table
ALTER TABLE users 
ADD COLUMN profile_picture VARCHAR(255) NULL AFTER phone;

-- Set default avatar path (optional)
-- UPDATE users SET profile_picture = 'uploads/avatars/default-avatar.png' WHERE profile_picture IS NULL;


USE complaint_management_system;

-- Add online activity tracking columns
ALTER TABLE users 
ADD COLUMN last_activity TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER profile_picture,
ADD COLUMN is_online TINYINT(1) DEFAULT 0 AFTER last_activity;

-- Create index for faster queries
CREATE INDEX idx_online_status ON users(is_online, last_activity);

USE complaint_management_system;

-- ============================================
-- PHASE 1: DATABASE SCHEMA UPDATES
-- ============================================

-- 1. Update status enum to include 'Assigned' and 'On Hold'
ALTER TABLE complaints 
MODIFY COLUMN status ENUM('Pending', 'Assigned', 'In Progress', 'On Hold', 'Resolved', 'Closed') DEFAULT 'Pending';

-- 2. Add new columns for assignment tracking
ALTER TABLE complaints 
ADD COLUMN assigned_at TIMESTAMP NULL AFTER assigned_to,
ADD COLUMN assigned_by INT NULL AFTER assigned_at,
ADD COLUMN assignment_note TEXT NULL AFTER assigned_by,
ADD COLUMN can_be_reassigned TINYINT(1) DEFAULT 1 AFTER assignment_note;

-- 3. Add foreign key for assigned_by
ALTER TABLE complaints
ADD FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL;

-- 4. Create assignment history table
CREATE TABLE IF NOT EXISTS assignment_history (
    assignment_history_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    assigned_by INT NOT NULL COMMENT 'Super admin who made the assignment',
    assigned_from INT NULL COMMENT 'Previous admin (for reassignments)',
    assigned_to INT NOT NULL COMMENT 'Admin who received the assignment',
    assignment_note TEXT COMMENT 'Note from super admin to assigned admin',
    action_type ENUM('assigned', 'reassigned', 'unassigned') DEFAULT 'assigned',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_from) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_complaint (complaint_id),
    INDEX idx_assigned_to (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks complaint assignment history';

-- 5. Create status progression rules table (for validation)
CREATE TABLE IF NOT EXISTS status_progression_rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    current_status VARCHAR(50) NOT NULL,
    allowed_next_status VARCHAR(50) NOT NULL,
    can_reverse TINYINT(1) DEFAULT 0 COMMENT '1 = Super admin can reverse, 0 = No one can reverse',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progression (current_status, allowed_next_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Defines allowed status transitions';

-- 6. Insert status progression rules
INSERT INTO status_progression_rules (current_status, allowed_next_status, can_reverse) VALUES
-- From Pending
('Pending', 'Assigned', 0),
-- From Assigned
('Assigned', 'In Progress', 0),
('Assigned', 'On Hold', 0),
-- From In Progress
('In Progress', 'On Hold', 1),
('In Progress', 'Resolved', 0),
-- From On Hold
('On Hold', 'In Progress', 1),
('On Hold', 'Assigned', 1),
-- From Resolved
('Resolved', 'Closed', 0),
('Resolved', 'In Progress', 1); -- Can reopen if issue persists

-- 7. Update existing complaints to have consistent status
-- Set unassigned complaints to 'Pending'
UPDATE complaints SET status = 'Pending' WHERE assigned_to IS NULL AND status != 'Closed';

-- Set assigned complaints without progress to 'Assigned'
UPDATE complaints SET status = 'Assigned' WHERE assigned_to IS NOT NULL AND status = 'Pending';

-- 8. Add index for better performance
CREATE INDEX idx_status_assigned ON complaints(status, assigned_to);
CREATE INDEX idx_assigned_at ON complaints(assigned_at);

-- ============================================
-- VERIFICATION QUERIES (Run to check)
-- ============================================

-- Check new columns exist
DESCRIBE complaints;

-- Check assignment history table
DESCRIBE assignment_history;

-- Check status rules
SELECT * FROM status_progression_rules ORDER BY current_status;

-- Check complaints with new structure
SELECT 
    complaint_id, 
    subject, 
    status, 
    assigned_to, 
    assigned_at, 
    assigned_by 
FROM complaints 
LIMIT 5;