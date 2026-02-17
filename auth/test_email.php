<?php
// ⚠️ DELETE THIS FILE after debugging!
require_once '../config/config.php';

$secret = $_GET['key'] ?? '';
if ($secret !== 'debug2026') {
    die("Access denied. Add ?key=debug2026 to URL");
}

$brevo_api_key = getenv('BREVO_API_KEY') ?: '';
$mail_username = getenv('MAIL_USERNAME') ?: '';
$app_env       = getenv('APP_ENV') ?: 'not set';
$test_to       = $_GET['email'] ?? $mail_username;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:700px;">
    <h2>Email Debug - Brevo API</h2><hr>

    <div class="card mb-4">
        <div class="card-header bg-info text-white"><strong>Environment Variables</strong></div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <td><strong>APP_ENV</strong></td>
                    <td><?php echo htmlspecialchars($app_env); ?></td>
                    <td><?php echo $app_env === 'production' ? '✅ Will use Brevo API' : '⚠️ Set to: production'; ?></td>
                </tr>
                <tr>
                    <td><strong>BREVO_API_KEY</strong></td>
                    <td><?php echo empty($brevo_api_key) ? '<span class="text-danger">NOT SET</span>' : '✅ Set ('.strlen($brevo_api_key).' chars) - '.substr($brevo_api_key,0,8).'****'; ?></td>
                    <td><?php echo !empty($brevo_api_key) ? '✅' : '❌ Missing!'; ?></td>
                </tr>
                <tr>
                    <td><strong>MAIL_USERNAME</strong></td>
                    <td><?php echo htmlspecialchars($mail_username ?: 'not set'); ?></td>
                    <td><?php echo filter_var($mail_username, FILTER_VALIDATE_EMAIL) ? '✅' : '❌'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-warning"><strong>Brevo API Test - Sending to: <?php echo htmlspecialchars($test_to); ?></strong></div>
        <div class="card-body">
        <?php
        if (empty($brevo_api_key)) {
            echo '<div class="alert alert-danger">❌ BREVO_API_KEY not set! Add it in Render Dashboard Environment tab.</div>';
        } else {
            $data = [
                'sender'      => ['email' => $mail_username ?: 'cmsproperty@gmail.com', 'name' => 'CMS Test'],
                'to'          => [['email' => $test_to]],
                'subject'     => 'Test Email - ' . date('Y-m-d H:i:s'),
                'htmlContent' => '<h2>Test Email</h2><p>Brevo API is working! Sent at: '.date('Y-m-d H:i:s').'</p>',
            ];

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => [
                    'accept: application/json',
                    'api-key: ' . $brevo_api_key,
                    'content-type: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
            ]);

            $response  = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err  = curl_error($ch);
            curl_close($ch);

            if ($curl_err) {
                echo '<div class="alert alert-danger">❌ cURL Error: '.htmlspecialchars($curl_err).'</div>';
            } elseif ($http_code >= 200 && $http_code < 300) {
                echo '<div class="alert alert-success">✅ <strong>EMAIL SENT via Brevo API!</strong><br>Check inbox of: '.htmlspecialchars($test_to).'</div>';
            } else {
                $decoded = json_decode($response, true);
                echo '<div class="alert alert-danger">❌ HTTP '.$http_code.' - '.htmlspecialchars($decoded['message'] ?? $response).'</div>';
            }
            echo '<small>Raw response: <code>'.htmlspecialchars($response).'</code></small>';
        }
        ?>
        </div>
    </div>

    <div class="alert alert-warning">⚠️ DELETE this file after debugging: <code>auth/test_email.php</code></div>

    <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="key" value="debug2026">
        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($test_to); ?>">
        <button type="submit" class="btn btn-primary">Test Again</button>
    </form>
</div>
</body>
</html>