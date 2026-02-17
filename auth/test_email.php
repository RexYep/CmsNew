<?php
// ============================================
// TEMPORARY EMAIL DEBUG PAGE
// auth/test_email.php
// ============================================
// PURPOSE: Check kung bakit hindi gumagana ang email
//
// HOW TO USE:
// 1. Upload to your project at: auth/test_email.php
// 2. Visit: https://cmsnew-5ocg.onrender.com/auth/test_email.php
// 3. Check the output
//
// ⚠️ DELETE THIS FILE after debugging!
// ============================================

require_once '../config/config.php';

 require_once '../vendor/autoload.php';
                use PHPMailer\PHPMailer\PHPMailer;
                use PHPMailer\PHPMailer\Exception;
                use PHPMailer\PHPMailer\SMTP;
// Security: only allow if logged in as admin OR via secret key
$secret = $_GET['key'] ?? '';
if ($secret !== 'debug2026') {
    die("❌ Access denied. Add ?key=debug2026 to URL");
}

// Get email settings from environment
$mail_host     = getenv('MAIL_HOST')     ?: 'smtp.gmail.com';
$mail_port     = getenv('MAIL_PORT')     ?: '587';
$mail_username = getenv('MAIL_USERNAME') ?: '(not set)';
$mail_password = getenv('MAIL_PASSWORD') ?: '(not set)';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:700px;">
    <h2>📧 Email Configuration Debug</h2>
    <hr>

    <!-- Show current env var values -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <strong>Environment Variables Check</strong>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <td><strong>MAIL_HOST</strong></td>
                    <td><?php echo htmlspecialchars($mail_host); ?></td>
                    <td><?php echo $mail_host === 'smtp.gmail.com' ? '✅' : '⚠️ Expected: smtp.gmail.com'; ?></td>
                </tr>
                <tr>
                    <td><strong>MAIL_PORT</strong></td>
                    <td><?php echo htmlspecialchars($mail_port); ?></td>
                    <td><?php echo $mail_port == '587' ? '✅' : '⚠️ Expected: 587'; ?></td>
                </tr>
                <tr>
                    <td><strong>MAIL_USERNAME</strong></td>
                    <td><?php echo htmlspecialchars($mail_username); ?></td>
                    <td><?php echo filter_var($mail_username, FILTER_VALIDATE_EMAIL) ? '✅' : '❌ Invalid email'; ?></td>
                </tr>
                <tr>
                    <td><strong>MAIL_PASSWORD</strong></td>
                    <td>
                        <?php
                        $pw = getenv('MAIL_PASSWORD') ?: '';
                        if (empty($pw)) {
                            echo '<span class="text-danger">❌ NOT SET!</span>';
                        } else {
                            // Show length and first/last char only
                            echo '✅ Set (' . strlen($pw) . ' chars) - ' .
                                 substr($pw, 0, 2) . '****' . substr($pw, -2);
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        $pw_len = strlen(getenv('MAIL_PASSWORD') ?: '');
                        if ($pw_len === 0) echo '❌ Empty!';
                        elseif ($pw_len === 16) echo '✅ Correct length (16)';
                        elseif ($pw_len === 19) echo '✅ Correct length with spaces (xxxx xxxx xxxx xxxx)';
                        else echo '⚠️ Length: ' . $pw_len . ' (Gmail App Password should be 16 or 19 chars)';
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Test SMTP Connection -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <strong>SMTP Connection Test</strong>
        </div>
        <div class="card-body">
            <?php
            if (!file_exists('../vendor/autoload.php')) {
                echo '<div class="alert alert-danger">❌ vendor/autoload.php not found! Run: composer install</div>';

            } else {
               

              $mail = new PHPMailer(true);
                $debugOutput = '';

                try {
                    $mail->isSMTP();
                    $mail->Host       = $mail_host;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getenv('MAIL_USERNAME') ?: '';
                    $mail->Password   = getenv('MAIL_PASSWORD') ?: '';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = (int)$mail_port;
                    $mail->Timeout    = 15;

                    // Capture debug output
                    $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
                    $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
                        $debugOutput .= htmlspecialchars($str) . "\n";
                    };

                    // Test recipients
                    $test_to = $_GET['email'] ?? getenv('MAIL_USERNAME');
                    $mail->setFrom(getenv('MAIL_USERNAME') ?: '', 'CMS Test');
                    $mail->addAddress($test_to);
                    $mail->Subject = 'Test Email from CMS - ' . date('Y-m-d H:i:s');
                    $mail->isHTML(true);
                    $mail->Body    = '<h2>✅ Test Email</h2><p>If you receive this, email is working!</p>';
                    $mail->AltBody = 'Test email - email is working!';

                    $mail->send();

                    echo '<div class="alert alert-success">';
                    echo '<strong>✅ EMAIL SENT SUCCESSFULLY!</strong><br>';
                    echo 'Test email sent to: ' . htmlspecialchars($test_to);
                    echo '</div>';

                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">';
                    echo '<strong>❌ EMAIL FAILED!</strong><br>';
                    echo '<strong>Error:</strong> ' . htmlspecialchars($mail->ErrorInfo);
                    echo '</div>';
                }

                if ($debugOutput) {
                    echo '<div class="mt-3">';
                    echo '<strong>SMTP Debug Log:</strong>';
                    echo '<pre class="bg-dark text-light p-3 mt-2" style="font-size:0.75rem; max-height:300px; overflow-y:auto;">';
                    echo $debugOutput;
                    echo '</pre>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>

    <div class="alert alert-warning">
        <strong>⚠️ IMPORTANT:</strong> Delete this file after debugging!<br>
        <code>auth/test_email.php</code>
    </div>

    <a href="?key=debug2026&email=<?php echo urlencode($_GET['email'] ?? ''); ?>" class="btn btn-primary">
        🔄 Retest
    </a>
    &nbsp;
    <a href="?key=debug2026&email=<?php echo urlencode($mail_username); ?>" class="btn btn-secondary">
        Send to <?php echo htmlspecialchars($mail_username); ?>
    </a>
</div>
</body>
</html>