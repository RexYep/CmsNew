<?php
// ============================================
// SECURITY HELPER FUNCTIONS
// includes/security_helper.php
// ============================================
// Anti-spam protection: Honeypot, Rate Limiting, CSRF

/**
 * Generate honeypot field HTML
 * Hidden field that bots will fill but humans won't see
 */
function generateHoneypot($field_name = 'website_url') {
    $timestamp = time();
    $token = hash('sha256', $field_name . $timestamp . $_SERVER['REMOTE_ADDR']);
    
    // Store token in session for validation
    $_SESSION['honeypot_token'] = $token;
    $_SESSION['honeypot_time'] = $timestamp;
    
    // Hidden field with deceptive label (bots think it's legitimate)
    return '
    <div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
        <label for="' . htmlspecialchars($field_name) . '">Website (leave blank)</label>
        <input type="text" 
               id="' . htmlspecialchars($field_name) . '" 
               name="' . htmlspecialchars($field_name) . '" 
               value="" 
               tabindex="-1" 
               autocomplete="off">
        <input type="hidden" name="honeypot_token" value="' . htmlspecialchars($token) . '">
        <input type="hidden" name="form_timestamp" value="' . $timestamp . '">
    </div>';
}

/**
 * Validate honeypot field
 * Returns array with 'valid' (bool) and 'message' (string)
 */
function validateHoneypot($field_name = 'website_url') {
    // Check if honeypot field was filled (bot behavior)
    if (!empty($_POST[$field_name])) {
        error_log("Honeypot triggered - Field filled: " . $_SERVER['REMOTE_ADDR']);
        return [
            'valid' => false,
            'message' => 'Spam detected. Please try again.'
        ];
    }
    
    // Check if form was submitted too quickly (bot behavior)
    if (isset($_POST['form_timestamp'])) {
        $form_time = (int)$_POST['form_timestamp'];
        $current_time = time();
        $time_diff = $current_time - $form_time;
        
        // If submitted in less than 2 seconds, likely a bot
        if ($time_diff < 2) {
            error_log("Form submitted too quickly ({$time_diff}s): " . $_SERVER['REMOTE_ADDR']);
            return [
                'valid' => false,
                'message' => 'Please take your time filling the form.'
            ];
        }
        
        // If form is older than 1 hour, token expired
        if ($time_diff > 3600) {
            return [
                'valid' => false,
                'message' => 'Form expired. Please refresh and try again.'
            ];
        }
    }
    
    // Validate token
    if (isset($_POST['honeypot_token']) && isset($_SESSION['honeypot_token'])) {
        if ($_POST['honeypot_token'] !== $_SESSION['honeypot_token']) {
            error_log("Invalid honeypot token: " . $_SERVER['REMOTE_ADDR']);
            return [
                'valid' => false,
                'message' => 'Invalid form submission. Please try again.'
            ];
        }
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Rate limiting - check if user/IP exceeded submission limit
 * 
 * @param string $action - Action type (e.g., 'complaint_submit', 'login', 'register')
 * @param int $limit - Max attempts allowed
 * @param int $time_window - Time window in seconds (default: 60 = 1 minute)
 * @return array ['allowed' => bool, 'remaining' => int, 'reset_in' => int]
 */
function checkRateLimit($action, $limit = 3, $time_window = 60) {
    global $conn;
    
    $ip_address = getClientIP();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $current_time = time();
    $window_start = $current_time - $time_window;
    
    // Create rate_limit table if not exists
    createRateLimitTable();
    
    // Clean old entries (older than time window)
    $stmt = $conn->prepare("DELETE FROM rate_limit WHERE timestamp < ?");
    $stmt->bind_param("i", $window_start);
    $stmt->execute();
    
    // Count recent attempts
    if ($user_id) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count, MIN(timestamp) as oldest
            FROM rate_limit 
            WHERE action = ? 
            AND (user_id = ? OR ip_address = ?) 
            AND timestamp >= ?
        ");
        $stmt->bind_param("sisi", $action, $user_id, $ip_address, $window_start);
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count, MIN(timestamp) as oldest
            FROM rate_limit 
            WHERE action = ? 
            AND ip_address = ? 
            AND timestamp >= ?
        ");
        $stmt->bind_param("ssi", $action, $ip_address, $window_start);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $attempt_count = (int)$result['count'];
    $oldest_attempt = (int)$result['oldest'];
    
    $remaining = max(0, $limit - $attempt_count);
    $reset_in = $oldest_attempt > 0 ? ($oldest_attempt + $time_window - $current_time) : $time_window;
    
    if ($attempt_count >= $limit) {
        error_log("Rate limit exceeded for $action: $ip_address (User: $user_id)");
        return [
            'allowed' => false,
            'remaining' => 0,
            'reset_in' => $reset_in,
            'message' => "Too many attempts. Please wait " . ceil($reset_in / 60) . " minute(s) before trying again."
        ];
    }
    
    return [
        'allowed' => true,
        'remaining' => $remaining,
        'reset_in' => $reset_in,
        'message' => ''
    ];
}

/**
 * Record rate limit attempt
 */
function recordRateLimitAttempt($action) {
    global $conn;
    
    $ip_address = getClientIP();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $timestamp = time();
    
    createRateLimitTable();
    
    $stmt = $conn->prepare("INSERT INTO rate_limit (action, user_id, ip_address, timestamp) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sisi", $action, $user_id, $ip_address, $timestamp);
    $stmt->execute();
}

/**
 * Create rate_limit table if not exists
 */
function createRateLimitTable() {
    global $conn;
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS rate_limit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            user_id INT DEFAULT NULL,
            ip_address VARCHAR(45) NOT NULL,
            timestamp INT NOT NULL,
            INDEX idx_action_ip (action, ip_address),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/**
 * Enhanced CSRF token generation with timestamp
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Regenerate token if older than 1 hour
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Check if token expired (1 hour)
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time'] > 3600)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate complete form protection (honeypot + CSRF)
 * Call this in your forms
 */
function formProtection() {
    $csrf_token = generateCSRFToken();
    $honeypot = generateHoneypot();
    
    echo $honeypot;
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">';
}

/**
 * Validate all form protections
 * Call this at the start of form processing
 * 
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateFormProtection($action, $rate_limit = 3, $time_window = 60) {
    $errors = [];
    
    // 1. Validate Honeypot
    $honeypot = validateHoneypot();
    if (!$honeypot['valid']) {
        $errors[] = $honeypot['message'];
    }
    
    // 2. Validate CSRF Token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
        error_log("CSRF validation failed: " . getClientIP());
    }
    
    // 3. Check Rate Limit
    $rate_check = checkRateLimit($action, $rate_limit, $time_window);
    if (!$rate_check['allowed']) {
        $errors[] = $rate_check['message'];
    } else {
        // Record attempt if allowed
        recordRateLimitAttempt($action);
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Display security error message
 */
function showSecurityError($errors) {
    if (is_array($errors)) {
        foreach ($errors as $error) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo '<i class="bi bi-shield-x"></i> ' . htmlspecialchars($error);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
    } else {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="bi bi-shield-x"></i> ' . htmlspecialchars($errors);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}