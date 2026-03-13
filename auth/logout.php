<?php

// ============================================
// LOGOUT HANDLER
// auth/logout.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

// Logout the user
logActivity('logout', 'Logging out');
logoutUser();
