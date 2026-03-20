<?php
// ============================================
// redis_test.php v2 — I-REPLACE ang lumang version
// I-DELETE pagkatapos ng testing!
// ============================================

// Show ALL errors para makita natin ang problema
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/redis.php';
require_once 'includes/cache_helper.php';
require_once 'includes/functions.php';

$test_password = 'test1234';
if (!isset($_GET['key']) || $_GET['key'] !== $test_password) {
    die("<h3 style='color:red'>❌ Access denied.</h3>");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redis Cache Test v2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:700px">
<h3 class="mb-4">🔴 Redis Cache Status Check <small class="text-muted fs-6">v2</small></h3>

<?php
// ============================================
// TEST 1-3 (same as before — condensed)
// ============================================
echo "<div class='card mb-3'><div class='card-header fw-bold'>Tests 1-3 — Quick Check</div><div class='card-body'>";
$url   = REDIS_URL;
$token = REDIS_TOKEN;
echo CACHE_ENABLED
    ? "<span class='text-success'>✅ Credentials OK — CACHE_ENABLED: TRUE</span><br>"
    : "<span class='text-danger'>❌ CACHE_ENABLED: FALSE — missing env vars</span><br>";

$ping = Cache::ping();
echo $ping
    ? "<span class='text-success'>✅ PING → PONG — Redis alive!</span><br>"
    : "<span class='text-danger'>❌ PING failed</span><br>";

$k = 'test:' . time();
Cache::set($k, 'ok', 30);
$v = Cache::get($k);
Cache::del($k);
echo ($v === 'ok')
    ? "<span class='text-success'>✅ SET/GET working</span>"
    : "<span class='text-danger'>❌ SET/GET failed</span>";
echo "</div></div>";

// ============================================
// TEST 4 — Debug version
// ============================================
echo "<div class='card mb-3'><div class='card-header fw-bold'>Test 4 — Session & App Functions Debug</div><div class='card-body'>";

// Step 1: Check session
echo "<strong>Session status:</strong><br>";
echo "session_status(): <code>" . session_status() . "</code> ";
echo session_status() === PHP_SESSION_ACTIVE
    ? "<span class='text-success'>(Active ✅)</span><br>"
    : "<span class='text-danger'>(Not active ❌)</span><br>";

echo "session_id(): <code>" . (session_id() ?: 'EMPTY') . "</code><br>";
echo "\$_SESSION contents: <code>" . htmlspecialchars(json_encode($_SESSION)) . "</code><br><br>";

// Step 2: Check user_id
if (empty($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>❌ Walang session['user_id']!</strong><br>";
    echo "Posibleng dahilan:<br>";
    echo "1. Hindi ka naka-login — mag-login muna sa <a href='/auth/login.php' target='_blank'>auth/login.php</a><br>";
    echo "2. Session expired — mag-login ulit<br>";
    echo "3. Session config issue sa Render<br>";
    echo "</div>";
} else {
    $user_id = $_SESSION['user_id'];
    echo "<span class='text-success'>✅ user_id = <strong>{$user_id}</strong> ({$_SESSION['role']})</span><br><br>";

    // Step 3: Check if functions exist
    echo "<strong>Function availability:</strong><br>";
    $funcs = ['getUnreadNotificationCount', 'cacheInvalidateNotifications', 'Cache'];
    foreach ($funcs as $f) {
        $exists = function_exists($f) || class_exists($f);
        echo $exists
            ? "<span class='text-success'>✅ {$f}()</span><br>"
            : "<span class='text-danger'>❌ {$f}() — NOT FOUND! Hindi pa na-apply ang functions_cache_patch.php</span><br>";
    }
    echo "<br>";

    // Step 4: Actual speed test
    if (function_exists('getUnreadNotificationCount')) {
        echo "<strong>Speed test — getUnreadNotificationCount():</strong><br>";

        // Clear cache first para fair ang test
        if (class_exists('Cache')) {
            Cache::del("notif:unread:{$user_id}");
        }

        $start  = microtime(true);
        $count1 = getUnreadNotificationCount($user_id);
        $first  = round((microtime(true) - $start) * 1000, 2);
        echo "1st call (DB query): <strong class='text-warning'>{$first}ms</strong> → count = {$count1}<br>";

        $start  = microtime(true);
        $count2 = getUnreadNotificationCount($user_id);
        $second = round((microtime(true) - $start) * 1000, 2);
        echo "2nd call (cache hit?): <strong class='text-success'>{$second}ms</strong> → count = {$count2}<br><br>";

        if ($second < $first * 0.8) {
            echo "<div class='alert alert-success mb-0'>";
            echo "🎉 <strong>Cache is working!</strong> 2nd call mas mabilis ng " . round($first - $second, 2) . "ms<br>";
            echo "({$first}ms DB → {$second}ms Redis)";
            echo "</div>";
        } elseif ($second < $first) {
            echo "<div class='alert alert-warning mb-0'>";
            echo "⚠️ Medyo mas mabilis ang 2nd call pero hindi dramatic.<br>";
            echo "Normal ito sa Render free tier — pareho lang ang latency ng DB at Redis.<br>";
            echo "Mas magiging obvious ang difference kapag maraming concurrent users.";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning mb-0'>";
            echo "⚠️ Pareho ang speed — baka hindi gumagana ang caching sa getUnreadNotificationCount().<br>";
            echo "Check kung na-apply na ang modified version sa includes/functions.php.";
            echo "</div>";
        }

        // Cleanup
        if (class_exists('Cache')) {
            cacheInvalidateNotifications($user_id);
        }
    } else {
        echo "<div class='alert alert-danger'>";
        echo "❌ <strong>getUnreadNotificationCount() not found!</strong><br>";
        echo "Kailangan i-apply ang <code>functions_cache_patch.php</code> sa <code>includes/functions.php</code>.";
        echo "</div>";
    }
}
echo "</div></div>";
?>

<div class="alert alert-warning">
    <strong>⚠️ REMINDER:</strong> I-delete ang <code>redis_test.php</code> pagkatapos ng testing!
</div>
</div>
</body>
</html>