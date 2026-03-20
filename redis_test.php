<?php
// ============================================
// redis_test.php
// Ilagay sa ROOT ng project (same level ng config/)
// I-access sa browser: http://localhost/cms3/redis_test.php
//
// IMPORTANT: I-DELETE ang file na ito pagkatapos
// ng testing — huwag iwan sa production!
// ============================================

require_once 'config/config.php';
require_once 'config/redis.php';
require_once 'includes/cache_helper.php';

// Simple password protection — baguhin mo ito!
$test_password = 'test1234';
if (!isset($_GET['key']) || $_GET['key'] !== $test_password) {
    die("<h3 style='color:red'>❌ Access denied. Add ?key=test1234 sa URL</h3>");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redis Cache Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:700px">

<h3 class="mb-4">🔴 Redis Cache Status Check</h3>

<?php
// ============================================
// TEST 1: Credentials check
// ============================================
echo "<div class='card mb-3'>";
echo "<div class='card-header fw-bold'>Test 1 — Environment Variables</div>";
echo "<div class='card-body'>";

$url   = REDIS_URL;
$token = REDIS_TOKEN;

if (empty($url)) {
    echo "<span class='text-danger'>❌ UPSTASH_REDIS_REST_URL — <strong>WALA!</strong> Hindi pa naka-set sa Render environment.</span><br>";
} else {
    // Show partial URL for security
    $safe_url = substr($url, 0, 30) . '...';
    echo "<span class='text-success'>✅ UPSTASH_REDIS_REST_URL — <strong>{$safe_url}</strong></span><br>";
}

if (empty($token)) {
    echo "<span class='text-danger'>❌ UPSTASH_REDIS_REST_TOKEN — <strong>WALA!</strong> Hindi pa naka-set sa Render environment.</span><br>";
} else {
    $safe_token = substr($token, 0, 10) . '...' . substr($token, -5);
    echo "<span class='text-success'>✅ UPSTASH_REDIS_REST_TOKEN — <strong>{$safe_token}</strong></span><br>";
}

echo "<br><strong>CACHE_ENABLED:</strong> " . (CACHE_ENABLED ? "<span class='text-success'>TRUE ✅</span>" : "<span class='text-danger'>FALSE ❌</span>");
echo "</div></div>";

if (!CACHE_ENABLED) {
    echo "<div class='alert alert-danger'>⚠️ Caching is DISABLED. I-set muna ang environment variables sa Render/local .env bago mag-proceed.</div>";
}

// ============================================
// TEST 2: PING — buhay ba ang connection?
// ============================================
echo "<div class='card mb-3'>";
echo "<div class='card-header fw-bold'>Test 2 — Connection (PING)</div>";
echo "<div class='card-body'>";

$start = microtime(true);
$ping  = Cache::ping();
$ms    = round((microtime(true) - $start) * 1000, 2);

if ($ping) {
    echo "<span class='text-success'>✅ PONG received — Redis is alive!</span><br>";
    echo "<small class='text-muted'>Response time: <strong>{$ms}ms</strong></small>";
    if ($ms > 500) {
        echo "<br><small class='text-warning'>⚠️ Mabagal ang response ({$ms}ms). Check kung tama ang region ng Upstash (dapat Singapore para sa Render Singapore).</small>";
    }
} else {
    echo "<span class='text-danger'>❌ No response — Hindi ma-connect sa Redis!</span><br>";
    echo "<small class='text-muted'>Possible reasons: maling credentials, network issue, o Upstash is down.</small>";
}
echo "</div></div>";

// ============================================
// TEST 3: SET at GET — gumagana ba ang caching?
// ============================================
echo "<div class='card mb-3'>";
echo "<div class='card-header fw-bold'>Test 3 — Write & Read (SET / GET)</div>";
echo "<div class='card-body'>";

$test_key = 'redis_test:' . time();
$test_val = ['message' => 'Hello Redis!', 'time' => date('Y-m-d H:i:s'), 'system' => 'CMS'];

$start  = microtime(true);
$set_ok = Cache::set($test_key, $test_val, 60); // 60 seconds TTL
$set_ms = round((microtime(true) - $start) * 1000, 2);

if ($set_ok) {
    echo "<span class='text-success'>✅ SET — Nagawa ang write sa Redis ({$set_ms}ms)</span><br>";
} else {
    echo "<span class='text-danger'>❌ SET — Hindi ma-write sa Redis!</span><br>";
}

$start  = microtime(true);
$get_ok = Cache::get($test_key);
$get_ms = round((microtime(true) - $start) * 1000, 2);

if ($get_ok && $get_ok['message'] === 'Hello Redis!') {
    echo "<span class='text-success'>✅ GET — Nababasa ang data mula Redis ({$get_ms}ms)</span><br>";
    echo "<small class='text-muted'>Value: <code>" . json_encode($get_ok) . "</code></small>";
} else {
    echo "<span class='text-danger'>❌ GET — Hindi mabasa ang data!</span><br>";
}

// Cleanup
Cache::del($test_key);
echo "</div></div>";

// ============================================
// TEST 4: Actual app caching — gamitin ang functions mo
// ============================================
echo "<div class='card mb-3'>";
echo "<div class='card-header fw-bold'>Test 4 — App Functions (Live Cache Test)</div>";
echo "<div class='card-body'>";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Test notification count caching
    $start   = microtime(true);
    $count1  = getUnreadNotificationCount($user_id); // 1st call — DB query
    $first   = round((microtime(true) - $start) * 1000, 2);

    $start   = microtime(true);
    $count2  = getUnreadNotificationCount($user_id); // 2nd call — should hit cache
    $second  = round((microtime(true) - $start) * 1000, 2);

    echo "<strong>getUnreadNotificationCount() test:</strong><br>";
    echo "1st call (DB query): <strong>{$first}ms</strong><br>";
    echo "2nd call (cache): <strong>{$second}ms</strong><br>";

    if ($second < $first) {
        echo "<span class='text-success'>✅ Cache is working! 2nd call mas mabilis ng " . round($first - $second, 2) . "ms</span><br>";
    } else {
        echo "<span class='text-warning'>⚠️ Pareho pa rin ang speed — baka may issue sa cache.</span><br>";
    }

    // Cleanup test cache
    cacheInvalidateNotifications($user_id);
    echo "<small class='text-muted'>Test cache cleared.</small>";

} else {
    echo "<span class='text-warning'>⚠️ Hindi ka naka-login — mag-login muna para ma-test ang app functions.</span><br>";
    echo "<a href='auth/login.php' class='btn btn-sm btn-primary mt-2'>Go to Login</a>";
}

echo "</div></div>";

// ============================================
// TEST 5: Speed comparison summary
// ============================================
echo "<div class='card mb-3 border-primary'>";
echo "<div class='card-header fw-bold bg-primary text-white'>Summary</div>";
echo "<div class='card-body'>";

if ($ping && $set_ok && $get_ok) {
    echo "<div class='alert alert-success mb-0'>";
    echo "<h5>🎉 Redis is fully working!</h5>";
    echo "<p class='mb-1'>Ang iyong caching system ay properly configured at gumagana.</p>";
    echo "<p class='mb-0'><strong>Susunod:</strong> I-delete na ang redis_test.php para sa security!</p>";
    echo "</div>";
} elseif (!CACHE_ENABLED) {
    echo "<div class='alert alert-danger mb-0'>";
    echo "<h5>❌ Caching is disabled</h5>";
    echo "<p>I-set ang <code>UPSTASH_REDIS_REST_URL</code> at <code>UPSTASH_REDIS_REST_TOKEN</code> sa:</p>";
    echo "<ul class='mb-0'><li><strong>Render:</strong> Dashboard → Service → Environment tab</li>";
    echo "<li><strong>Local:</strong> .env file sa root ng project</li></ul>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning mb-0'>";
    echo "<h5>⚠️ May issue sa Redis</h5>";
    echo "<p>Tingnan ang mga failed tests sa itaas at i-check ang iyong Upstash credentials.</p>";
    echo "</div>";
}

echo "</div></div>";

echo "<div class='alert alert-warning'>";
echo "<strong>⚠️ REMINDER:</strong> I-delete ang <code>redis_test.php</code> pagkatapos ng testing!";
echo "</div>";
?>

</div>
</body>
</html>