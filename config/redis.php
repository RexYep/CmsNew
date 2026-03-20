<?php
// ============================================
// config/redis.php
// ============================================
//
// SETUP (isang beses lang):
// 1. Pumunta sa https://upstash.com → sign up (libre)
// 2. "Create Database" → name: cms-cache → region: Global
// 3. Sa Render dashboard → iyong service → Environment tab → dagdag:
//       UPSTASH_REDIS_REST_URL   = https://xxx.upstash.io
//       UPSTASH_REDIS_REST_TOKEN = AXxx...
// ============================================

define('REDIS_URL',   getenv('UPSTASH_REDIS_REST_URL')   ?: '');
define('REDIS_TOKEN', getenv('UPSTASH_REDIS_REST_TOKEN') ?: '');

// TTL values (seconds)
define('CACHE_TTL_DASHBOARD',   300);  // 5 min  — counts, stats
define('CACHE_TTL_NOTIF',        60);  // 1 min  — unread count, recent notifs
define('CACHE_TTL_LISTS',       180);  // 3 min  — complaint/user lists
define('CACHE_TTL_CATEGORIES', 3600);  // 1 hour — categories (bihirang mag-change)

// Auto-disable kung walang credentials para hindi masira ang app
define('CACHE_ENABLED', !empty(REDIS_URL) && !empty(REDIS_TOKEN));