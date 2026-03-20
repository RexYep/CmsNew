<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="<?php echo SITE_NAME; ?> - Efficient complaint management system">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>

    <!-- ============================================ -->
    <!-- PERFORMANCE: Preconnect sa CDN para mas mabilis mag-download -->
    <!-- ============================================ -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    
    <!-- Favicon (optional) -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/images/favicon.ico">

    <!-- ============================================ -->
    <!-- PAGE LOADING BAR CSS                         -->
    <!-- ============================================ -->
    <style>
        /* Top loading progress bar */
        #page-loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% 100%;
            z-index: 99999;
            transition: width 0.25s ease;
            animation: shimmer 1.5s infinite linear;
            display: none;
        }
        #page-loading-bar.loading {
            display: block;
        }
        #page-loading-bar.done {
            width: 100% !important;
            opacity: 0;
            transition: width 0.1s ease, opacity 0.4s ease 0.1s;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Subtle page transition fade */
        body {
            animation: pageFadeIn 0.18s ease-out;
        }
        @keyframes pageFadeIn {
            from { opacity: 0.6; transform: translateY(4px); }
            to   { opacity: 1;   transform: translateY(0); }
        }

        /* Nav link hover prefetch indicator */
        .sidebar-menu a[data-prefetched="true"] {
            position: relative;
        }
    </style>
</head>

<body>

<!-- ============================================ -->
<!-- PAGE LOADING BAR (top ng screen)             -->
<!-- ============================================ -->
<div id="page-loading-bar"></div>

<!-- Dark Mode Initialization - Must run immediately -->
<script>
(function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();
</script>

<!-- ============================================ -->
<!-- SERVICE WORKER REGISTRATION                  -->
<!-- ============================================ -->
<script>
(function() {
    // I-register ang Service Worker para mag-cache ng static files
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?php echo SITE_URL; ?>sw.js')
                .then(function(reg) {
                    console.log('[SW] Registered:', reg.scope);
                })
                .catch(function(err) {
                    console.warn('[SW] Registration failed:', err);
                });
        });
    }
})();
</script>