<?php
// ============================================
// reCAPTCHA v3 HELPER FUNCTIONS
// includes/recaptcha_helper.php
// ============================================
// Google reCAPTCHA v3 - Invisible CAPTCHA with score-based validation

/**
 * Generate reCAPTCHA v3 JavaScript
 * Call this in the <head> section of your page
 */
function loadRecaptchaScript() {
    $site_key = getenv('RECAPTCHA_SITE_KEY');
    
    if (empty($site_key)) {
        error_log("reCAPTCHA: RECAPTCHA_SITE_KEY not set in environment variables");
        return '<!-- reCAPTCHA disabled: RECAPTCHA_SITE_KEY not configured -->';
    }
    
    return '
    <script src="https://www.google.com/recaptcha/api.js?render=' . htmlspecialchars($site_key) . '"></script>
    <script>
        // reCAPTCHA v3 - Generate token on form submit
        function executeRecaptcha(action) {
            return new Promise((resolve, reject) => {
                grecaptcha.ready(function() {
                    grecaptcha.execute("' . htmlspecialchars($site_key) . '", {action: action})
                        .then(function(token) {
                            resolve(token);
                        })
                        .catch(function(error) {
                            console.error("reCAPTCHA error:", error);
                            reject(error);
                        });
                });
            });
        }
        
        // Auto-inject token into forms before submit
        document.addEventListener("DOMContentLoaded", function() {
            const forms = document.querySelectorAll("form[data-recaptcha]");
            
            forms.forEach(function(form) {
                form.addEventListener("submit", function(e) {
                    e.preventDefault();
                    
                    const action = form.getAttribute("data-recaptcha") || "submit";
                    const submitBtn = form.querySelector("button[type=submit]");
                    
                    // Disable submit button
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = "<span class=\\"spinner-border spinner-border-sm me-2\\"></span>Verifying...";
                    }
                    
                    executeRecaptcha(action)
                        .then(function(token) {
                            // Add token to form
                            let tokenInput = form.querySelector("input[name=recaptcha_token]");
                            if (!tokenInput) {
                                tokenInput = document.createElement("input");
                                tokenInput.type = "hidden";
                                tokenInput.name = "recaptcha_token";
                                form.appendChild(tokenInput);
                            }
                            tokenInput.value = token;
                            
                            // Add action to form
                            let actionInput = form.querySelector("input[name=recaptcha_action]");
                            if (!actionInput) {
                                actionInput = document.createElement("input");
                                actionInput.type = "hidden";
                                actionInput.name = "recaptcha_action";
                                form.appendChild(actionInput);
                            }
                            actionInput.value = action;
                            
                            // Submit form
                            form.submit();
                        })
                        .catch(function(error) {
                            // Re-enable submit button
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = submitBtn.getAttribute("data-original-text") || "Submit";
                            }
                            alert("reCAPTCHA verification failed. Please refresh and try again.");
                        });
                });
                
                // Save original button text
                const submitBtn = form.querySelector("button[type=submit]");
                if (submitBtn) {
                    submitBtn.setAttribute("data-original-text", submitBtn.innerHTML);
                }
            });
        });
    </script>';
}

/**
 * Verify reCAPTCHA v3 token with Google
 */
function verifyRecaptcha($token, $action = '', $min_score = 0.5) {
    $secret_key = getenv('RECAPTCHA_SECRET_KEY');
    
    if (empty($secret_key)) {
        error_log("reCAPTCHA: RECAPTCHA_SECRET_KEY not set");
        return ['success' => false, 'score' => 0.0, 'message' => 'reCAPTCHA not configured.'];
    }
    
    if (empty($token)) {
        return ['success' => false, 'score' => 0.0, 'message' => 'reCAPTCHA token missing.'];
    }
    
    // Verify with Google
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret_key,
        'response' => $token,
        'remoteip' => getClientIP()
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($result === false || $http_code !== 200) {
        // Fail open if service down
        return ['success' => true, 'score' => 0.5, 'message' => 'Verification unavailable'];
    }
    
    $response = json_decode($result, true);
    
    if (!$response || !isset($response['success'])) {
        return ['success' => false, 'score' => 0.0, 'message' => 'Invalid response'];
    }
    
    if (!$response['success']) {
        return ['success' => false, 'score' => 0.0, 'message' => 'Verification failed'];
    }
    
    // Check action
    if (!empty($action) && isset($response['action']) && $response['action'] !== $action) {
        return ['success' => false, 'score' => 0.0, 'message' => 'Invalid action'];
    }
    
    // Check score
    $score = isset($response['score']) ? (float)$response['score'] : 0.0;
    
    if ($score < $min_score) {
        error_log("reCAPTCHA: Low score ($score) - " . getClientIP());
        return ['success' => false, 'score' => $score, 'message' => 'Suspicious activity detected'];
    }
    
    return ['success' => true, 'score' => $score, 'message' => 'Verified'];
}

/**
 * Validate from POST
 */
function validateRecaptchaFromPost($min_score = 0.5) {
    if (!isset($_POST['recaptcha_token'])) {
        return ['success' => false, 'score' => 0.0, 'message' => 'reCAPTCHA required'];
    }
    
    $token = $_POST['recaptcha_token'];
    $action = isset($_POST['recaptcha_action']) ? $_POST['recaptcha_action'] : '';
    
    return verifyRecaptcha($token, $action, $min_score);
}

function getRecommendedMinScore($action) {
    $scores = [
        'login' => 0.5,
        'register' => 0.6,
        'complaint_submit' => 0.5,
        'forgot_password' => 0.5,
        'comment' => 0.4
    ];
    return isset($scores[$action]) ? $scores[$action] : 0.5;
}

function isRecaptchaConfigured() {
    return !empty(getenv('RECAPTCHA_SITE_KEY')) && !empty(getenv('RECAPTCHA_SECRET_KEY'));
}

function displayRecaptchaBadge() {
    return '<div class="text-muted small mt-2"><i class="bi bi-shield-check"></i> Protected by reCAPTCHA</div>';
}