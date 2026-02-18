<?php
// ============================================
// ROOT INDEX - Landing Page
// index.php (root directory)
// ============================================

session_start();

if (isset($_SESSION['user_id'])) {
    require_once 'includes/functions.php';
    if (isAdmin()) {
        header("Location: admin/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS — Complaint Management System</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            --navy:    #0d1b2a;
            --navy-2:  #132236;
            --navy-3:  #1a2f48;
            --cyan:    #00c2e0;
            --cyan-2:  #00e5ff;
            --rose:    #f72585;
            --white:   #ffffff;
            --muted:   #8fa3b8;
            --card-bg: #111e2e;
            --border:  rgba(0, 194, 224, 0.12);
            --shadow:  0 20px 60px rgba(0,0,0,0.4);
        }

        /* ===== BASE ===== */
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            color: var(--white);
            overflow-x: hidden;
        }

        h1,h2,h3,h4,h5,h6 { font-family: 'Sora', sans-serif; }

        /* ===== NOISE TEXTURE OVERLAY ===== */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 9999;
            opacity: 0.5;
        }

        /* ===== NAVBAR ===== */
        .navbar-custom {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 18px 0;
            transition: all 0.4s ease;
        }

        .navbar-custom.scrolled {
            background: rgba(13, 27, 42, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 12px 0;
            box-shadow: 0 4px 30px rgba(0,0,0,0.3);
        }

        .navbar-custom .nav-brand {
            font-family: 'Sora', sans-serif;
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-custom .nav-brand .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            color: var(--navy);
        }

        .navbar-custom .nav-brand span.accent { color: var(--cyan); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
        }

        .nav-links a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-links a:hover { color: var(--white); background: rgba(255,255,255,0.06); }

        .btn-nav-login {
            background: transparent;
            color: var(--cyan) !important;
            border: 1px solid var(--cyan) !important;
            padding: 8px 20px !important;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s !important;
        }

        .btn-nav-login:hover {
            background: var(--cyan) !important;
            color: var(--navy) !important;
        }

        .btn-nav-register {
            background: var(--cyan);
            color: var(--navy) !important;
            padding: 8px 20px !important;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.2s !important;
        }

        .btn-nav-register:hover {
            background: var(--cyan-2) !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 194, 224, 0.35);
        }

        /* Mobile nav toggle */
        .nav-toggler {
            display: none;
            background: none;
            border: 1px solid var(--border);
            color: var(--white);
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
        }

        /* ===== HERO SECTION ===== */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 120px 0 80px;
        }

        /* Grid background */
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,194,224,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,194,224,0.06) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
        }

        /* Glow orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(0,194,224,0.18) 0%, transparent 70%);
            top: -100px; right: -100px;
            animation: drift 12s ease-in-out infinite;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(247,37,133,0.1) 0%, transparent 70%);
            bottom: -150px; left: -100px;
            animation: drift 15s ease-in-out infinite reverse;
        }

        @keyframes drift {
            0%, 100% { transform: translate(0,0); }
            33% { transform: translate(30px, -20px); }
            66% { transform: translate(-20px, 30px); }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,194,224,0.1);
            border: 1px solid rgba(0,194,224,0.25);
            border-radius: 50px;
            padding: 6px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--cyan);
            letter-spacing: 0.5px;
            margin-bottom: 28px;
            animation: fadeUp 0.6s ease both;
        }

        .badge-dot {
            width: 6px; height: 6px;
            background: var(--cyan);
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.7); }
        }

        .hero-title {
            font-size: clamp(2.6rem, 5vw, 4.2rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1.4rem;
            animation: fadeUp 0.7s ease 0.1s both;
        }

        .hero-title .highlight {
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--muted);
            line-height: 1.8;
            max-width: 500px;
            margin-bottom: 2.5rem;
            animation: fadeUp 0.8s ease 0.2s both;
        }

        .hero-cta {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            animation: fadeUp 0.9s ease 0.3s both;
        }

        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            background: var(--cyan);
            color: var(--navy);
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.25s;
            border: 2px solid var(--cyan);
        }

        .btn-primary-custom:hover {
            background: var(--cyan-2);
            border-color: var(--cyan-2);
            color: var(--navy);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 194, 224, 0.4);
        }

        .btn-secondary-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            background: transparent;
            color: var(--white);
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.25s;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .btn-secondary-custom:hover {
            border-color: rgba(255,255,255,0.5);
            color: var(--white);
            background: rgba(255,255,255,0.06);
            transform: translateY(-2px);
        }

        /* Hero visual */
        .hero-visual {
            position: relative;
            animation: fadeLeft 1s ease 0.3s both;
        }

        @keyframes fadeLeft {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .dashboard-mockup {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow), 0 0 0 1px rgba(0,194,224,0.05);
            position: relative;
            overflow: hidden;
        }

        .mockup-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .mockup-dot { width: 10px; height: 10px; border-radius: 50%; }
        .mockup-dot.red { background: #ff5f56; }
        .mockup-dot.yellow { background: #ffbd2e; }
        .mockup-dot.green { background: #27c93f; }

        .mockup-title {
            font-family: 'Sora', sans-serif;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--muted);
            margin-left: 6px;
        }

        .mockup-stat-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }

        .mockup-stat {
            background: var(--navy-2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }

        .mockup-stat-num {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--cyan);
        }

        .mockup-stat-lbl {
            font-size: 0.68rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .mockup-list { display: flex; flex-direction: column; gap: 8px; }

        .mockup-item {
            background: var(--navy-2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.78rem;
        }

        .mockup-item-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .mockup-item-label { color: var(--white); font-weight: 500; flex: 1; }
        .mockup-item-badge {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .status-pending   { background: rgba(255,193,7,0.15);  color: #ffc107; }
        .status-review    { background: rgba(0,194,224,0.15);  color: var(--cyan); }
        .status-resolved  { background: rgba(39,201,63,0.15);  color: #27c93f; }

        .dot-pending  { background: #ffc107; }
        .dot-review   { background: var(--cyan); }
        .dot-resolved { background: #27c93f; }

        /* Floating badge */
        .floating-badge {
            position: absolute;
            background: var(--navy-3);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            animation: floatBadge 3s ease-in-out infinite;
        }

        .floating-badge-1 {
            top: -20px; right: -20px;
            color: #27c93f;
        }

        .floating-badge-2 {
            bottom: -20px; left: -20px;
            color: var(--cyan);
        }

        @keyframes floatBadge {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        /* ===== SECTION COMMON ===== */
        section { position: relative; }

        .section-label {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--cyan);
            margin-bottom: 14px;
        }

        .section-title {
            font-size: clamp(1.8rem, 3vw, 2.6rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 14px;
        }

        .section-sub {
            font-size: 1rem;
            color: var(--muted);
            line-height: 1.7;
            max-width: 550px;
            margin: 0 auto;
        }

        /* ===== FEATURES SECTION ===== */
        .features-section {
            padding: 120px 0;
            background: var(--navy-2);
        }

        .features-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            opacity: 0.3;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px 28px;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            border-color: rgba(0,194,224,0.3);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3), 0 0 0 1px rgba(0,194,224,0.1);
        }

        .feature-card:hover::before { opacity: 1; }

        .feature-icon-wrap {
            width: 56px; height: 56px;
            background: rgba(0,194,224,0.1);
            border: 1px solid rgba(0,194,224,0.2);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            color: var(--cyan);
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .feature-card:hover .feature-icon-wrap {
            background: rgba(0,194,224,0.18);
            transform: scale(1.05);
        }

        .feature-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--white);
        }

        .feature-desc {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.7;
        }

        /* ===== STATS SECTION ===== */
        .stats-section {
            padding: 80px 0;
            background: var(--navy);
        }

        .stat-item {
            text-align: center;
            padding: 30px 20px;
        }

        .stat-number {
            font-family: 'Sora', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--muted);
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .stat-divider {
            width: 1px;
            background: var(--border);
            height: 80px;
            align-self: center;
        }

        /* ===== HOW IT WORKS ===== */
        .steps-section {
            padding: 120px 0;
            background: var(--navy-2);
        }

        .steps-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,194,224,0.3), transparent);
        }

        .step-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px 28px;
            height: 100%;
            position: relative;
            transition: all 0.3s;
        }

        .step-card:hover {
            border-color: rgba(0,194,224,0.3);
            transform: translateY(-4px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .step-number {
            font-family: 'Sora', sans-serif;
            font-size: 3.5rem;
            font-weight: 800;
            color: rgba(0,194,224,0.08);
            line-height: 1;
            margin-bottom: 16px;
            position: absolute;
            top: 20px; right: 24px;
        }

        .step-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            color: var(--navy);
            margin-bottom: 20px;
        }

        .step-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .step-desc {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.7;
        }

        /* ===== TESTIMONIALS ===== */
        .testimonials-section {
            padding: 120px 0;
            background: var(--navy);
        }

        .testimonial-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px 28px;
            height: 100%;
            transition: all 0.3s;
        }

        .testimonial-card:hover {
            border-color: rgba(0,194,224,0.25);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .testimonial-stars {
            color: #ffc107;
            font-size: 0.9rem;
            margin-bottom: 16px;
            letter-spacing: 2px;
        }

        .testimonial-text {
            font-size: 0.95rem;
            color: #cdd5df;
            line-height: 1.8;
            margin-bottom: 24px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--rose));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--white);
            flex-shrink: 0;
        }

        .author-name {
            font-family: 'Sora', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .author-role {
            font-size: 0.78rem;
            color: var(--muted);
        }

        /* ===== FAQ ===== */
        .faq-section {
            padding: 120px 0;
            background: var(--navy-2);
        }

        .faq-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,194,224,0.3), transparent);
        }

        .faq-accordion .accordion-item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px !important;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .faq-accordion .accordion-button {
            background: var(--card-bg);
            color: var(--white);
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 20px 24px;
            box-shadow: none;
        }

        .faq-accordion .accordion-button:not(.collapsed) {
            background: rgba(0,194,224,0.06);
            color: var(--cyan);
        }

        .faq-accordion .accordion-button::after {
            filter: invert(1) brightness(0.7);
        }

        .faq-accordion .accordion-button:not(.collapsed)::after {
            filter: none;
        }

        .faq-accordion .accordion-body {
            background: rgba(0,194,224,0.03);
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.8;
            padding: 0 24px 20px;
        }

        /* ===== CTA SECTION ===== */
        .cta-section {
            padding: 120px 0;
            background: var(--navy);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section .orb-cta {
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(0,194,224,0.08) 0%, transparent 70%);
            border-radius: 50%;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .cta-inner {
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            margin-bottom: 16px;
        }

        .cta-title .highlight {
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cta-sub {
            font-size: 1rem;
            color: var(--muted);
            margin-bottom: 40px;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: #080f1a;
            border-top: 1px solid var(--border);
            padding: 60px 0 30px;
        }

        .footer-brand {
            font-family: 'Sora', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .footer-brand .brand-icon {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
            color: var(--navy);
        }

        .footer-brand span.accent { color: var(--cyan); }

        .footer-desc {
            font-size: 0.88rem;
            color: var(--muted);
            line-height: 1.7;
            max-width: 280px;
        }

        .footer-heading {
            font-family: 'Sora', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .footer-links { list-style: none; }

        .footer-links li { margin-bottom: 10px; }

        .footer-links a {
            color: #8fa3b8;
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.2s;
        }

        .footer-links a:hover { color: var(--cyan); }

        .footer-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 40px 0 24px;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .footer-bottom-links {
            display: flex;
            gap: 20px;
        }

        .footer-bottom-links a {
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-bottom-links a:hover { color: var(--cyan); }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.7s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .hero-visual { margin-top: 60px; }
            .floating-badge { display: none; }
            .stat-divider { display: none; }
        }

        @media (max-width: 768px) {
            .nav-links { display: none; flex-direction: column; padding: 20px 0; }
            .nav-links.open { display: flex; }
            .nav-toggler { display: block; }
            .hero-title { font-size: 2.2rem; }
            .stat-item { padding: 20px 10px; }
            .stat-number { font-size: 2.2rem; }
            .mockup-stat-row { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar-custom" id="navbar">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="#" class="nav-brand">
            <div class="brand-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
            CMS<span class="accent">.</span>
        </a>

        <button class="nav-toggler" id="navToggler" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="#features">Features</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#testimonials">Reviews</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="auth/login.php" class="btn-nav-login">Login</a></li>
            <li><a href="auth/register.php" class="btn-nav-register">Get Started</a></li>
        </ul>
    </div>
</nav>

<!-- ===== HERO SECTION ===== -->
<section class="hero-section" id="home">
    <div class="hero-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="container">
        <div class="row align-items-center">
            <!-- Left Content -->
            <div class="col-lg-6">
                <div class="hero-badge">
                    <span class="badge-dot"></span>
                    Now with real-time notifications
                </div>
                <h1 class="hero-title">
                    Resolve Complaints<br>
                    <span class="highlight">Faster Than Ever</span>
                </h1>
                <p class="hero-subtitle">
                    A powerful, transparent complaint management platform. Submit issues, track progress in real-time, and get resolutions — all in one place.
                </p>
                <div class="hero-cta">
                    <a href="auth/register.php" class="btn-primary-custom">
                        <i class="bi bi-rocket-takeoff-fill"></i> Get Started Free
                    </a>
                    <a href="auth/login.php" class="btn-secondary-custom">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                </div>
            </div>

            <!-- Right Visual -->
            <div class="col-lg-6 hero-visual">
                <div style="position: relative; padding: 20px;">
                    <div class="dashboard-mockup">
                        <div class="mockup-header">
                            <span class="mockup-dot red"></span>
                            <span class="mockup-dot yellow"></span>
                            <span class="mockup-dot green"></span>
                            <span class="mockup-title">Complaint Dashboard</span>
                        </div>

                        <div class="mockup-stat-row">
                            <div class="mockup-stat">
                                <div class="mockup-stat-num">24</div>
                                <div class="mockup-stat-lbl">Total</div>
                            </div>
                            <div class="mockup-stat">
                                <div class="mockup-stat-num" style="color: #ffc107;">7</div>
                                <div class="mockup-stat-lbl">Pending</div>
                            </div>
                            <div class="mockup-stat">
                                <div class="mockup-stat-num" style="color: #27c93f;">15</div>
                                <div class="mockup-stat-lbl">Resolved</div>
                            </div>
                        </div>

                        <div class="mockup-list">
                            <div class="mockup-item">
                                <span class="mockup-item-dot dot-pending"></span>
                                <span class="mockup-item-label">Billing issue with account #2041</span>
                                <span class="mockup-item-badge status-pending">Pending</span>
                            </div>
                            <div class="mockup-item">
                                <span class="mockup-item-dot dot-review"></span>
                                <span class="mockup-item-label">Service outage report — Zone 4</span>
                                <span class="mockup-item-badge status-review">In Review</span>
                            </div>
                            <div class="mockup-item">
                                <span class="mockup-item-dot dot-resolved"></span>
                                <span class="mockup-item-label">Delivery not received — Order #804</span>
                                <span class="mockup-item-badge status-resolved">Resolved</span>
                            </div>
                            <div class="mockup-item">
                                <span class="mockup-item-dot dot-pending"></span>
                                <span class="mockup-item-label">Account access problem reported</span>
                                <span class="mockup-item-badge status-pending">Pending</span>
                            </div>
                        </div>
                    </div>

                    <!-- Floating badges -->
                    <div class="floating-badge floating-badge-1" style="animation-delay: 0s;">
                        <i class="bi bi-check-circle-fill"></i> Complaint resolved in 2h
                    </div>
                    <div class="floating-badge floating-badge-2" style="animation-delay: 1.5s; color: var(--cyan);">
                        <i class="bi bi-bell-fill"></i> 3 new updates
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== STATS SECTION ===== -->
<section class="stats-section">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-md-3 col-6">
                <div class="stat-item reveal">
                    <div class="stat-number" data-target="1000">0</div>
                    <div class="stat-label">Complaints Resolved</div>
                </div>
            </div>
            <div class="col-md-1 d-none d-md-flex justify-content-center">
                <div class="stat-divider"></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item reveal" style="transition-delay: 0.1s">
                    <div class="stat-number" data-target="500">0</div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
            <div class="col-md-1 d-none d-md-flex justify-content-center">
                <div class="stat-divider"></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item reveal" style="transition-delay: 0.2s">
                    <div class="stat-number" data-target="95" data-suffix="%">0</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FEATURES SECTION ===== -->
<section class="features-section" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label reveal">Features</span>
            <h2 class="section-title reveal">Everything You Need</h2>
            <p class="section-sub reveal">Built to make complaint handling effortless for users and admins alike.</p>
        </div>

        <div class="row g-4">
            <?php
            $features = [
                ['bi-lightning-charge-fill', 'Fast & Efficient', 'Submit complaints in seconds. Our streamlined process ensures quick resolution times with zero friction.'],
                ['bi-shield-check-fill',     'Secure & Reliable', 'Your data is protected with enterprise-grade security. Every complaint is tracked and safely stored.'],
                ['bi-bell-fill',             'Real-time Notifications', 'Get instant updates about your complaint status. Stay informed at every step with smart notifications.'],
                ['bi-speedometer2',          'Admin Dashboard', 'Powerful admin tools for managing, assigning, and resolving complaints efficiently with full visibility.'],
                ['bi-graph-up-arrow',        'Analytics & Reports', 'Track performance metrics, resolution times, and satisfaction ratings with detailed visual reports.'],
                ['bi-star-fill',             'Rating System', 'Rate resolved complaints and provide feedback that helps continuously improve service quality.'],
            ];
            foreach ($features as $i => $f): ?>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card reveal" style="transition-delay: <?= $i * 0.08 ?>s">
                    <div class="feature-icon-wrap">
                        <i class="bi <?= $f[0] ?>"></i>
                    </div>
                    <div class="feature-title"><?= $f[1] ?></div>
                    <p class="feature-desc"><?= $f[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="steps-section" id="how-it-works">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label reveal">Process</span>
            <h2 class="section-title reveal">How It Works</h2>
            <p class="section-sub reveal">Four simple steps to get your complaints heard and resolved.</p>
        </div>

        <div class="row g-4">
            <?php
            $steps = [
                ['bi-person-plus-fill',        'Create Account',    'Sign up in seconds with just your email and basic information. Verification is instant.'],
                ['bi-pencil-square',           'Submit Complaint',  'Fill out a simple form describing your issue. Attach photos or documents if needed.'],
                ['bi-radar',                   'Track Progress',    'Monitor your complaint status in real-time through your personal dashboard.'],
                ['bi-patch-check-fill',        'Get Resolution',    'Receive a resolution and rate your experience to help us serve you better.'],
            ];
            foreach ($steps as $i => $s): ?>
            <div class="col-md-6 col-lg-3">
                <div class="step-card reveal" style="transition-delay: <?= $i * 0.1 ?>s">
                    <div class="step-number"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="step-icon">
                        <i class="bi <?= $s[0] ?>"></i>
                    </div>
                    <div class="step-title"><?= $s[1] ?></div>
                    <p class="step-desc"><?= $s[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section class="testimonials-section" id="testimonials">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label reveal">Reviews</span>
            <h2 class="section-title reveal">What Users Say</h2>
            <p class="section-sub reveal">Real feedback from real users who trust our platform.</p>
        </div>

        <div class="row g-4">
            <?php
            $testimonials = [
                ['★★★★★', '"The system is incredibly easy to use. I submitted my complaint and got a response within the same day. Highly recommended!"', 'MR', 'Maria R.', 'Regular User'],
                ['★★★★★', '"As an admin, managing hundreds of complaints is now so much easier. The dashboard gives me everything I need at a glance."', 'JC', 'Juan C.', 'System Admin'],
                ['★★★★☆', '"Real-time notifications are a game changer. I always know the status of my complaint without having to check manually."', 'AL', 'Ana L.', 'Regular User'],
            ];
            foreach ($testimonials as $i => $t): ?>
            <div class="col-md-4">
                <div class="testimonial-card reveal" style="transition-delay: <?= $i * 0.1 ?>s">
                    <div class="testimonial-stars"><?= $t[0] ?></div>
                    <p class="testimonial-text"><?= $t[1] ?></p>
                    <div class="testimonial-author">
                        <div class="author-avatar"><?= $t[2] ?></div>
                        <div>
                            <div class="author-name"><?= $t[3] ?></div>
                            <div class="author-role"><?= $t[4] ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== FAQ ===== -->
<section class="faq-section" id="faq">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <span class="section-label reveal">FAQ</span>
                    <h2 class="section-title reveal">Common Questions</h2>
                    <p class="section-sub reveal">Everything you need to know before getting started.</p>
                </div>

                <div class="accordion faq-accordion reveal" id="faqAccordion">
                    <?php
                    $faqs = [
                        ['Is it free to create an account?', 'Yes! Creating an account and submitting complaints is completely free. No hidden fees.'],
                        ['How long does it take to resolve a complaint?', 'Resolution times vary by complaint type, but our admins aim to respond within 24 hours and resolve within 3–5 business days.'],
                        ['Can I attach photos or documents to my complaint?', 'Absolutely. You can attach images and supporting documents when submitting your complaint to provide better context.'],
                        ['Will I be notified of any updates?', 'Yes. You\'ll receive real-time in-app notifications and optional email alerts whenever your complaint status changes.'],
                        ['Can I edit my complaint after submission?', 'You can edit complaints that are still in Pending status. Once an admin starts reviewing it, editing will be locked.'],
                    ];
                    foreach ($faqs as $i => $faq): ?>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq<?= $i ?>"
                                    aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                                <?= $faq[0] ?>
                            </button>
                        </h3>
                        <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body"><?= $faq[1] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section class="cta-section">
    <div class="orb-cta"></div>
    <div class="container cta-inner">
        <span class="section-label reveal">Get Started</span>
        <h2 class="cta-title reveal">
            Ready to <span class="highlight">Get Heard?</span>
        </h2>
        <p class="cta-sub reveal">Join hundreds of users who trust our complaint management system every day.</p>
        <div class="reveal">
            <a href="auth/register.php" class="btn-primary-custom" style="font-size: 1rem; padding: 16px 40px;">
                <i class="bi bi-rocket-takeoff-fill"></i> Create Free Account
            </a>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a href="#" class="footer-brand">
                    <div class="brand-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
                    CMS<span class="accent">.</span>
                </a>
                <p class="footer-desc">A transparent, fast, and reliable complaint management platform for users and organizations.</p>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <p class="footer-heading">Platform</p>
                <ul class="footer-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#testimonials">Reviews</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <p class="footer-heading">Account</p>
                <ul class="footer-links">
                    <li><a href="auth/login.php">Login</a></li>
                    <li><a href="auth/register.php">Register</a></li>
                    <li><a href="auth/forgot_password.php">Forgot Password</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <p class="footer-heading">Legal</p>
                <ul class="footer-links">
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                </ul>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="footer-bottom">
            <span>&copy; 2026 Complaint Management System. All rights reserved.</span>
            <div class="footer-bottom-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ===== NAVBAR SCROLL EFFECT =====
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 30);
});

// ===== MOBILE NAV TOGGLE =====
const toggler = document.getElementById('navToggler');
const navLinks = document.getElementById('navLinks');
toggler.addEventListener('click', () => {
    navLinks.classList.toggle('open');
});

// Close nav on link click (mobile)
navLinks.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => navLinks.classList.remove('open'));
});

// ===== SCROLL REVEAL =====
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ===== ANIMATED COUNTER =====
function animateCounter(el) {
    const target = parseInt(el.dataset.target);
    const suffix = el.dataset.suffix || '+';
    const duration = 2000;
    const steps = 60;
    const increment = target / steps;
    let current = 0;
    const timer = setInterval(() => {
        current = Math.min(current + increment, target);
        el.textContent = Math.floor(current) + suffix;
        if (current >= target) clearInterval(timer);
    }, duration / steps);
}

const statObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounter(entry.target);
            statObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.stat-number[data-target]').forEach(el => statObserver.observe(el));

// ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            const offset = 80;
            window.scrollTo({ top: target.offsetTop - offset, behavior: 'smooth' });
        }
    });
});
</script>
</body>
</html>