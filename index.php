<?php
session_start();
if (isset($_SESSION['user_id'])) {
    require_once 'includes/functions.php';
    if (isAdmin()) { header("Location: admin/index.php"); }
    else { header("Location: user/index.php"); }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay San Cristobal — Online Complaint Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --navy:#0d1b2a; --navy-2:#132236; --navy-3:#1a2f48;
            --cyan:#00c2e0; --cyan-2:#00e5ff; --gold:#f4c842;
            --white:#fff; --muted:#8fa3b8; --card:#111e2e;
            --border:rgba(0,194,224,0.12);
        }
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{font-family:'DM Sans',sans-serif;background:var(--navy);color:var(--white);overflow-x:hidden;}
        h1,h2,h3,h4,h5{font-family:'Sora',sans-serif;}

        .grid-bg{position:absolute;inset:0;background-image:linear-gradient(rgba(0,194,224,0.05) 1px,transparent 1px),linear-gradient(90deg,rgba(0,194,224,0.05) 1px,transparent 1px);background-size:60px 60px;mask-image:radial-gradient(ellipse 80% 80% at 50% 50%,black 30%,transparent 100%);pointer-events:none;}

        /* NAVBAR */
        .navbar-custom{position:fixed;top:0;left:0;right:0;z-index:1000;padding:16px 0;transition:all .4s ease;}
        .navbar-custom.scrolled{background:rgba(13,27,42,.95);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:12px 0;box-shadow:0 4px 30px rgba(0,0,0,.3);}
        .nav-brand{font-family:'Sora',sans-serif;font-weight:800;color:var(--white);text-decoration:none;display:flex;align-items:center;gap:10px;}
        .nav-logo-wrap{width:38px;height:38px;background:transparent;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--navy);overflow:hidden;}
        .nav-logo{width:100%;height:100%;object-fit:contain;border-radius:inherit;display:block;}
        .nav-brand-text{line-height:1.1;}
        .nav-brand-top{font-size:.65rem;font-weight:400;color:var(--muted);letter-spacing:.5px;display:block;}
        .nav-brand-name{font-size:.95rem;font-weight:800;color:var(--white);}
        .nav-brand-name span{color:var(--cyan);}
        .nav-links{display:flex;align-items:center;gap:6px;list-style:none;}
        .nav-links a{color:var(--muted);text-decoration:none;font-size:.88rem;font-weight:500;padding:7px 14px;border-radius:8px;transition:all .2s;}
        .nav-links a:hover{color:var(--white);background:rgba(255,255,255,.06);}
        .btn-nav-login{color:var(--cyan)!important;border:1px solid rgba(0,194,224,.35)!important;}
        .btn-nav-login:hover{background:rgba(0,194,224,.08)!important;}
        .btn-nav-register{background:var(--cyan)!important;color:var(--navy)!important;font-weight:700!important;}
        .btn-nav-register:hover{background:var(--cyan-2)!important;transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,194,224,.3);}
        .nav-toggler{display:none;background:none;border:1px solid var(--border);color:var(--white);padding:6px 10px;border-radius:8px;font-size:1.2rem;cursor:pointer;}

        /* ANNOUNCEMENT */
        .ann-bar{padding:14px 0;background:rgba(244,200,66,.05);border-top:1px solid rgba(244,200,66,.1);border-bottom:1px solid rgba(244,200,66,.1);}
        .ann-inner{display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
        .ann-label{background:var(--gold);color:#000;font-family:'Sora',sans-serif;font-weight:800;font-size:.68rem;letter-spacing:1.5px;text-transform:uppercase;padding:4px 12px;border-radius:4px;flex-shrink:0;}
        .ann-text{font-size:.87rem;color:#c9b56e;flex:1;}
        .ann-text strong{color:var(--gold);}

        /* HERO */
        .hero-section{min-height:100vh;display:flex;align-items:center;position:relative;overflow:hidden;padding:120px 0 80px;}
        .orb{position:absolute;border-radius:50%;filter:blur(90px);pointer-events:none;}
        .orb-1{width:500px;height:500px;background:radial-gradient(circle,rgba(0,194,224,.15) 0%,transparent 70%);top:-100px;right:-150px;animation:drift 14s ease-in-out infinite;}
        .orb-2{width:350px;height:350px;background:radial-gradient(circle,rgba(244,200,66,.06) 0%,transparent 70%);bottom:-100px;left:-100px;animation:drift 18s ease-in-out infinite reverse;}
        @keyframes drift{0%,100%{transform:translate(0,0);}33%{transform:translate(20px,-15px);}66%{transform:translate(-15px,20px);}}

        .hero-seal{display:flex;align-items:center;gap:14px;margin-bottom:28px;animation:fadeUp .6s ease both;}
        .seal-icon{width:54px;height:54px;background:transparent;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--navy);box-shadow:0 0 0 4px rgba(0,194,224,.15),0 0 0 8px rgba(0,194,224,.05);flex-shrink:0;overflow:hidden;}
        .seal-icon img{width:100%;height:100%;object-fit:contain;border-radius:50%;}
        .seal-text{line-height:1.3;}
        .seal-office{font-size:.7rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--cyan);display:block;margin-bottom:3px;}
        .seal-name{font-family:'Sora',sans-serif;font-weight:800;font-size:1.1rem;color:var(--white);}

        .hero-title{font-size:clamp(2.4rem,5vw,4rem);font-weight:800;line-height:1.12;margin-bottom:20px;animation:fadeUp .7s ease .1s both;}
        .hero-title .line-muted{color:var(--muted);font-weight:400;font-size:.75em;display:block;margin-bottom:6px;font-family:'DM Sans',sans-serif;letter-spacing:.3px;}
        .hero-title .accent{background:linear-gradient(135deg,var(--cyan),var(--cyan-2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}

        .hero-message{font-size:1rem;color:var(--muted);line-height:1.8;max-width:520px;margin-bottom:2.5rem;animation:fadeUp .8s ease .2s both;font-style:italic;border-left:3px solid rgba(0,194,224,.3);padding-left:18px;}

        .hero-cta{display:flex;gap:14px;flex-wrap:wrap;animation:fadeUp .9s ease .3s both;}
        .btn-primary-cta{display:inline-flex;align-items:center;gap:8px;padding:14px 32px;background:var(--cyan);color:var(--navy);font-family:'Sora',sans-serif;font-weight:700;font-size:.95rem;border-radius:12px;text-decoration:none;transition:all .25s;border:2px solid var(--cyan);}
        .btn-primary-cta:hover{background:var(--cyan-2);border-color:var(--cyan-2);color:var(--navy);transform:translateY(-2px);box-shadow:0 12px 35px rgba(0,194,224,.4);}
        .btn-secondary-cta{display:inline-flex;align-items:center;gap:8px;padding:14px 32px;background:transparent;color:var(--white);font-family:'Sora',sans-serif;font-weight:600;font-size:.95rem;border-radius:12px;text-decoration:none;transition:all .25s;border:2px solid rgba(255,255,255,.18);}
        .btn-secondary-cta:hover{border-color:rgba(255,255,255,.4);color:var(--white);background:rgba(255,255,255,.05);transform:translateY(-2px);}

        .notice-card{background:rgba(244,200,66,.07);border:1px solid rgba(244,200,66,.2);border-radius:14px;padding:18px 20px;margin-top:32px;display:flex;gap:14px;align-items:flex-start;animation:fadeUp 1s ease .4s both;max-width:520px;}
        .notice-icon{color:var(--gold);font-size:1.1rem;flex-shrink:0;margin-top:2px;}
        .notice-text{font-size:.83rem;color:#c9b56e;line-height:1.7;}
        .notice-text strong{color:var(--gold);font-weight:700;}

        /* HERO VISUAL */
        .hero-visual{position:relative;animation:fadeLeft 1s ease .3s both;}
        @keyframes fadeLeft{from{opacity:0;transform:translateX(40px);}to{opacity:1;transform:translateX(0);}}
        .portal-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:24px;box-shadow:0 30px 80px rgba(0,0,0,.5);position:relative;overflow:hidden;}
        .portal-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--cyan),var(--gold),var(--cyan));}
        .portal-header{display:flex;align-items:center;gap:10px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border);}
        .portal-seal{width:36px;height:36px;background:transparent;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--navy);overflow:hidden;}
        .portal-seal img{width:100%;height:100%;object-fit:contain;}
        .portal-title-text{line-height:1.2;}
        .portal-title-main{font-family:'Sora',sans-serif;font-weight:700;font-size:.82rem;color:var(--white);}
        .portal-title-sub{font-size:.68rem;color:var(--muted);}
        .portal-stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px;}
        .portal-stat{background:var(--navy-2);border:1px solid var(--border);border-radius:12px;padding:12px;text-align:center;}
        .portal-stat-num{font-family:'Sora',sans-serif;font-weight:700;font-size:1.1rem;color:var(--cyan);}
        .portal-stat-lbl{font-size:.66rem;color:var(--muted);margin-top:2px;}
        .portal-items{display:flex;flex-direction:column;gap:8px;}
        .portal-item{background:var(--navy-2);border:1px solid var(--border);border-radius:10px;padding:11px 14px;display:flex;align-items:center;gap:10px;font-size:.78rem;}
        .pi-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
        .pi-label{color:var(--white);font-weight:500;flex:1;}
        .pi-badge{font-size:.63rem;font-weight:700;padding:3px 9px;border-radius:20px;}
        .s-pending{background:rgba(255,193,7,.15);color:#ffc107;}
        .s-review{background:rgba(0,194,224,.15);color:var(--cyan);}
        .s-resolved{background:rgba(39,201,63,.15);color:#27c93f;}
        .d-pending{background:#ffc107;}
        .d-review{background:var(--cyan);}
        .d-resolved{background:#27c93f;}
        .chip{position:absolute;background:var(--navy-3);border:1px solid var(--border);border-radius:30px;padding:8px 14px;font-size:.72rem;font-weight:600;display:flex;align-items:center;gap:7px;box-shadow:0 10px 40px rgba(0,0,0,.4);animation:floatChip 3s ease-in-out infinite;white-space:nowrap;}
        .chip-1{top:-18px;right:-16px;color:#27c93f;animation-delay:0s;}
        .chip-2{bottom:-18px;left:-16px;color:var(--cyan);animation-delay:1.5s;}
        @keyframes floatChip{0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);}}

        /* SECTIONS */
        .section-eyebrow{display:inline-block;font-size:.72rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:var(--cyan);margin-bottom:12px;}
        .section-title{font-size:clamp(1.7rem,3vw,2.4rem);font-weight:800;line-height:1.2;margin-bottom:14px;}
        .section-sub{font-size:.97rem;color:var(--muted);line-height:1.8;max-width:560px;margin:0 auto;}

        /* STATS */
        .stats-section{padding:80px 0;background:var(--navy);border-top:1px solid var(--border);border-bottom:1px solid var(--border);}
        .stat-item{text-align:center;padding:20px;}
        .stat-num{font-family:'Sora',sans-serif;font-weight:800;font-size:2.8rem;background:linear-gradient(135deg,var(--cyan),var(--cyan-2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;margin-bottom:8px;}
        .stat-label{font-size:.87rem;color:var(--muted);font-weight:500;}
        .stat-vdivider{width:1px;background:var(--border);height:60px;align-self:center;}

        /* SERVICES */
        .services-section{padding:110px 0;background:var(--navy-2);position:relative;}
        .services-section::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(0,194,224,.3),transparent);}
        .service-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:32px 26px;height:100%;transition:all .3s ease;position:relative;overflow:hidden;}
        .service-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--cyan),var(--cyan-2));opacity:0;transition:opacity .3s;}
        .service-card:hover{transform:translateY(-6px);border-color:rgba(0,194,224,.3);box-shadow:0 20px 60px rgba(0,0,0,.3);}
        .service-card:hover::after{opacity:1;}
        .service-icon{width:52px;height:52px;background:rgba(0,194,224,.08);border:1px solid rgba(0,194,224,.18);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--cyan);margin-bottom:20px;transition:all .3s;}
        .service-card:hover .service-icon{background:rgba(0,194,224,.16);transform:scale(1.05);}
        .service-title{font-family:'Sora',sans-serif;font-weight:700;font-size:1rem;margin-bottom:10px;color:var(--white);}
        .service-desc{font-size:.88rem;color:var(--muted);line-height:1.75;}

        /* HOW TO FILE */
        .how-section{padding:110px 0;background:var(--navy);}
        .step-wrap{display:flex;flex-direction:column;gap:0;position:relative;}
        .step-wrap::before{content:'';position:absolute;left:24px;top:40px;bottom:40px;width:1px;background:linear-gradient(180deg,var(--cyan),transparent);opacity:.2;}
        .how-step{display:flex;gap:24px;align-items:flex-start;padding:28px 0;border-bottom:1px solid rgba(255,255,255,.04);transition:all .2s;}
        .how-step:last-child{border-bottom:none;}
        .how-step:hover{padding-left:6px;}
        .step-circle{width:48px;height:48px;background:linear-gradient(135deg,var(--cyan),var(--cyan-2));border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Sora',sans-serif;font-weight:800;font-size:1rem;color:var(--navy);flex-shrink:0;}
        .step-body{padding-top:6px;}
        .step-title{font-family:'Sora',sans-serif;font-weight:700;font-size:1rem;margin-bottom:6px;color:var(--white);}
        .step-desc{font-size:.88rem;color:var(--muted);line-height:1.75;}

        /* RIGHTS */
        .rights-section{padding:110px 0;background:var(--navy-2);position:relative;}
        .rights-section::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(0,194,224,.3),transparent);}
        .right-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px 24px;height:100%;transition:all .3s;}
        .right-card:hover{border-color:rgba(0,194,224,.3);transform:translateY(-4px);box-shadow:0 20px 50px rgba(0,0,0,.3);}
        .right-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:16px;}
        .right-title{font-family:'Sora',sans-serif;font-weight:700;font-size:.95rem;margin-bottom:8px;color:var(--white);}
        .right-desc{font-size:.85rem;color:var(--muted);line-height:1.7;}

        /* CTA */
        .cta-section{padding:120px 0;background:var(--navy-2);text-align:center;position:relative;overflow:hidden;}
        .cta-section::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(0,194,224,.3),transparent);}
        .cta-glow{position:absolute;width:600px;height:600px;background:radial-gradient(circle,rgba(0,194,224,.07) 0%,transparent 70%);border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;}
        .cta-inner{position:relative;z-index:1;}
        .cta-tag{display:inline-flex;align-items:center;gap:8px;background:rgba(0,194,224,.08);border:1px solid rgba(0,194,224,.2);border-radius:50px;padding:6px 18px;font-size:.73rem;font-weight:700;color:var(--cyan);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:24px;}
        .cta-title{font-size:clamp(1.9rem,4vw,2.8rem);font-weight:800;margin-bottom:16px;line-height:1.2;}
        .cta-title span{background:linear-gradient(135deg,var(--cyan),var(--cyan-2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .cta-sub{font-size:.97rem;color:var(--muted);margin-bottom:40px;line-height:1.7;max-width:520px;margin-left:auto;margin-right:auto;}

        /* FOOTER */
        .footer{background:#060f1a;border-top:1px solid var(--border);padding:60px 0 30px;}
        .footer-brand{display:flex;align-items:center;gap:10px;margin-bottom:14px;text-decoration:none;}
        .footer-seal{width:36px;height:36px;background:transparent;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.95rem;color:var(--navy);overflow:hidden;}
        .footer-seal img{width:100%;height:100%;object-fit:contain;}
        .footer-brand-text{line-height:1.2;}
        .footer-brand-top{font-size:.62rem;color:var(--muted);letter-spacing:.5px;display:block;}
        .footer-brand-name{font-family:'Sora',sans-serif;font-weight:800;font-size:.95rem;color:#fff;}
        .footer-brand-name span{color:var(--cyan);}
        .footer-desc{font-size:.83rem;color:var(--muted);line-height:1.7;max-width:270px;}
        .footer-heading{font-family:'Sora',sans-serif;font-size:.72rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:16px;}
        .footer-links{list-style:none;}
        .footer-links li{margin-bottom:10px;}
        .footer-links a{color:#6b87a0;font-size:.85rem;text-decoration:none;transition:color .2s;}
        .footer-links a:hover{color:var(--cyan);}
        .footer-hr{border:none;border-top:1px solid var(--border);margin:40px 0 24px;}
        .footer-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;font-size:.78rem;color:var(--muted);}
        .footer-bottom-links{display:flex;gap:18px;}
        .footer-bottom-links a{color:var(--muted);text-decoration:none;transition:color .2s;}
        .footer-bottom-links a:hover{color:var(--cyan);}

        /* ANIMATIONS */
        @keyframes fadeUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
        .reveal{opacity:0;transform:translateY(28px);transition:all .7s ease;}
        .reveal.visible{opacity:1;transform:translateY(0);}

        /* RESPONSIVE */
        @media(max-width:992px){.hero-visual{margin-top:60px;}.chip{display:none;}.stat-vdivider{display:none;}}
        @media(max-width:768px){.nav-links{display:none;flex-direction:column;padding:20px 0;background:rgba(13,27,42,.98);position:absolute;top:70px;left:0;right:0;border-bottom:1px solid var(--border);}.nav-links.open{display:flex;}.nav-toggler{display:block;}.step-wrap::before{display:none;}.ann-inner{flex-direction:column;align-items:flex-start;}}
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-custom" id="navbar">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="#" class="nav-brand">
            <div class="nav-logo-wrap"><img src="brgy-logo.jpg" alt="Barangay Logo" class="nav-logo"></div>
            <div class="nav-brand-text">
                <span class="nav-brand-top">Official Portal</span>
                <span class="nav-brand-name">Barangay San Cristobal<span>.</span></span>
            </div>
        </a>
        <button class="nav-toggler" id="navToggler"><i class="bi bi-list"></i></button>
        <ul class="nav-links" id="navLinks">
            <li><a href="#services">Services</a></li>
            <li><a href="#how-to-file">How to File</a></li>
            <li><a href="#your-rights">Your Rights</a></li>
            <li><a href="auth/login.php" class="btn-nav-login">Login</a></li>
            <li><a href="auth/register.php" class="btn-nav-register">Register</a></li>
        </ul>
    </div>
</nav>

<!-- ANNOUNCEMENT -->
<div class="ann-bar" style="margin-top:68px;">
    <div class="container">
        <div class="ann-inner">
            <span class="ann-label">Official Notice</span>
            <p class="ann-text"><strong>Barangay San Cristobal</strong> is now accepting online complaints through this portal. All submissions are reviewed by authorized barangay officials.</p>
        </div>
    </div>
</div>

<!-- HERO -->
<section class="hero-section" id="home">
    <div class="grid-bg"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-seal">
                    <div class="seal-icon"><img src="brgy-logo.jpg" alt="Barangay Logo" class="nav-logo"></div>
                    <div class="seal-text">
                        <span class="seal-office">Official Online Portal</span>
                        <span class="seal-name">Barangay San Cristobal</span>
                    </div>
                </div>
                <h1 class="hero-title">
                    <span class="line-muted">Your voice matters to us.</span>
                    We Are Here to<br><span class="accent">Serve &amp; Listen.</span>
                </h1>
                <p class="hero-message">"To all residents of Barangay San Cristobal — this portal is your direct line to your barangay officials. File your complaints, concerns, and requests online. We are committed to addressing every voice in our community."</p>
                <div class="hero-cta">
                    <a href="auth/register.php" class="btn-primary-cta"><i class="bi bi-person-plus-fill"></i> Register as Resident</a>
                    <a href="auth/login.php" class="btn-secondary-cta"><i class="bi bi-box-arrow-in-right"></i> Login to Portal</a>
                </div>
                <div class="notice-card">
                    <i class="bi bi-info-circle-fill notice-icon"></i>
                    <p class="notice-text"><strong>Account Approval Required.</strong> New registrations are reviewed by the Barangay Administration to ensure only verified residents can access the portal. Approval typically takes 1–3 business days.</p>
                </div>
            </div>
            <div class="col-lg-6 hero-visual">
                <div style="position:relative;padding:20px;">
                    <div class="portal-card">
                        <div class="portal-header">
                            <div class="portal-seal"><img src="brgy-logo.jpg" alt="Barangay Logo"></div>
                            <div class="portal-title-text">
                                <div class="portal-title-main">Barangay San Cristobal — Complaint Portal</div>
                                <div class="portal-title-sub">Official Resident Dashboard</div>
                            </div>
                        </div>
                        <div class="portal-stat-row">
                            <div class="portal-stat"><div class="portal-stat-num">38</div><div class="portal-stat-lbl">Total Filed</div></div>
                            <div class="portal-stat"><div class="portal-stat-num" style="color:#ffc107;">11</div><div class="portal-stat-lbl">In Progress</div></div>
                            <div class="portal-stat"><div class="portal-stat-num" style="color:#27c93f;">24</div><div class="portal-stat-lbl">Resolved</div></div>
                        </div>
                        <div class="portal-items">
                            <div class="portal-item"><span class="pi-dot d-pending"></span><span class="pi-label">Noise complaint — Purok 3</span><span class="pi-badge s-pending">Pending</span></div>
                            <div class="portal-item"><span class="pi-dot d-review"></span><span class="pi-label">Clogged drainage — Rizal St.</span><span class="pi-badge s-review">In Review</span></div>
                            <div class="portal-item"><span class="pi-dot d-resolved"></span><span class="pi-label">Street light outage — Purok 1</span><span class="pi-badge s-resolved">Resolved</span></div>
                            <div class="portal-item"><span class="pi-dot d-pending"></span><span class="pi-label">Illegal dumping near plaza</span><span class="pi-badge s-pending">Pending</span></div>
                        </div>
                    </div>
                    <div class="chip chip-1"><i class="bi bi-check-circle-fill"></i> Resolved within 48 hrs</div>
                    <div class="chip chip-2"><i class="bi bi-bell-fill"></i> Real-time updates</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats-section">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-md-3 col-6"><div class="stat-item reveal"><div class="stat-num" data-target="500" data-suffix="+">0</div><div class="stat-label">Registered Residents</div></div></div>
            <div class="col-md-1 d-none d-md-flex justify-content-center"><div class="stat-vdivider"></div></div>
            <div class="col-md-3 col-6"><div class="stat-item reveal" style="transition-delay:.1s"><div class="stat-num" data-target="1000" data-suffix="+">0</div><div class="stat-label">Complaints Addressed</div></div></div>
            <div class="col-md-1 d-none d-md-flex justify-content-center"><div class="stat-vdivider"></div></div>
            <div class="col-md-3 col-6"><div class="stat-item reveal" style="transition-delay:.2s"><div class="stat-num" data-target="92" data-suffix="%">0</div><div class="stat-label">Resolution Rate</div></div></div>
        </div>
    </div>
</div>

<!-- SERVICES -->
<section class="services-section" id="services">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow reveal">What We Offer</span>
            <h2 class="section-title reveal">Services Available<br>Through This Portal</h2>
            <p class="section-sub reveal">Barangay San Cristobal is committed to providing accessible and responsive services to all residents through this online system.</p>
        </div>
        <div class="row g-4">
            <?php
            $services = [
                ['bi-megaphone-fill','File a Complaint','Report issues affecting you and your community — from neighbor disputes to public disturbances — directly to barangay officials.'],
                ['bi-droplet-fill','Infrastructure Concerns','Report broken street lights, clogged drainage, damaged roads, and other public infrastructure issues within the barangay.'],
                ['bi-shield-exclamation','Peace &amp; Order Reports','Report incidents affecting peace and order. Your report will be reviewed by the appropriate barangay officers promptly.'],
                ['bi-bell-fill','Real-time Status Updates','Track the status of your complaint anytime. Receive notifications whenever barangay officials take action on your concern.'],
                ['bi-folder2-open','Complaint Records','Keep a complete history of all your filed complaints and their resolutions through your personal resident account.'],
                ['bi-person-badge-fill','Direct Communication','Communicate directly with assigned barangay officers handling your complaint through the built-in response feature.'],
            ];
            foreach($services as $i=>$s): ?>
            <div class="col-md-6 col-lg-4">
                <div class="service-card reveal" style="transition-delay:<?=$i*.08?>s">
                    <div class="service-icon"><i class="bi <?=$s[0]?>"></i></div>
                    <div class="service-title"><?=$s[1]?></div>
                    <p class="service-desc"><?=$s[2]?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HOW TO FILE -->
<section class="how-section" id="how-to-file">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="section-eyebrow reveal">Step-by-Step</span>
                <h2 class="section-title reveal">How to File a Complaint Online</h2>
                <p class="section-sub text-start reveal">Filing a complaint is simple. Follow these steps and your concern will be received by the Barangay San Cristobal Administration immediately.</p>
                <div class="mt-4 reveal" style="transition-delay:.3s">
                    <a href="auth/register.php" class="btn-primary-cta d-inline-flex"><i class="bi bi-person-plus-fill"></i> Start Here — Register Now</a>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="step-wrap">
                    <?php
                    $steps = [
                        ['Register as a Resident','Create your account using your full name, email, and contact details. New accounts are reviewed and approved by the Barangay Administration.'],
                        ['Wait for Approval','The Barangay Administration will verify and approve your account. You will be notified via email once your account is activated.'],
                        ['File Your Complaint','Log in and fill out the complaint form. Describe your concern clearly and attach any supporting photos or documents if available.'],
                        ['Track Your Complaint','Monitor the status of your complaint in real-time from your dashboard. You\'ll receive updates at every stage of the process.'],
                        ['Receive a Resolution','Once the barangay officials have addressed your complaint, you will be notified. You may also provide feedback on the resolution.'],
                    ];
                    foreach($steps as $i=>$s): ?>
                    <div class="how-step reveal" style="transition-delay:<?=$i*.1?>s">
                        <div class="step-circle"><?=$i+1?></div>
                        <div class="step-body">
                            <div class="step-title"><?=$s[0]?></div>
                            <p class="step-desc"><?=$s[1]?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- YOUR RIGHTS -->
<section class="rights-section" id="your-rights">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow reveal">Know Your Rights</span>
            <h2 class="section-title reveal">Every Resident Has the Right<br>to Be Heard</h2>
            <p class="section-sub reveal">Barangay San Cristobal upholds the rights of every resident. This portal is a tool to make those rights accessible to everyone.</p>
        </div>
        <div class="row g-4">
            <?php
            $rights = [
                ['bi-megaphone-fill','rgba(0,194,224,.1)','#00c2e0','Right to File a Complaint','Every resident of Barangay San Cristobal has the right to formally file a complaint or concern with the barangay for proper action and resolution.'],
                ['bi-eye-fill','rgba(0,194,224,.1)','#00c2e0','Right to Be Informed','You have the right to receive updates and be informed of the status, actions taken, and outcome of your filed complaint.'],
                ['bi-person-fill-check','rgba(39,201,63,.1)','#27c93f','Right to Privacy','Your personal information and complaint details are kept confidential and accessible only to authorized barangay personnel.'],
                ['bi-shield-fill','rgba(0,194,224,.1)','#00c2e0','Right to Fair Treatment','All complaints are treated equally and fairly. Every concern receives proper attention and due process regardless of who filed it.'],
                ['bi-clock-history','rgba(255,193,7,.1)','#ffc107','Right to Timely Resolution','The Barangay Administration is committed to addressing complaints within a reasonable timeframe and keeping you informed of progress.'],
                ['bi-chat-left-text-fill','rgba(0,194,224,.1)','#00c2e0','Right to Give Feedback','After your complaint is resolved, you have the right to provide feedback and rate the quality of the barangay\'s response and resolution.'],
            ];
            foreach($rights as $i=>$r): ?>
            <div class="col-md-6 col-lg-4">
                <div class="right-card reveal" style="transition-delay:<?=$i*.08?>s">
                    <div class="right-icon" style="background:<?=$r[1]?>"><i class="bi <?=$r[0]?>" style="color:<?=$r[2]?>;font-size:1.2rem;"></i></div>
                    <div class="right-title"><?=$r[3]?></div>
                    <p class="right-desc"><?=$r[4]?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="cta-glow"></div>
    <div class="container cta-inner">
        <div class="cta-tag reveal"><i class="bi bi-buildings-fill"></i> Barangay San Cristobal</div>
        <h2 class="cta-title reveal">Your Concern Deserves<br><span>Official Attention.</span></h2>
        <p class="cta-sub reveal">Register as a resident of Barangay San Cristobal today and gain access to your community's official online complaint portal. Your barangay is here to serve you.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap reveal">
            <a href="auth/register.php" class="btn-primary-cta"><i class="bi bi-person-plus-fill"></i> Register as Resident</a>
            <a href="auth/login.php" class="btn-secondary-cta"><i class="bi bi-box-arrow-in-right"></i> Already Registered? Login</a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a href="#" class="footer-brand">
                    <div class="footer-seal"><img src="brgy-logo.jpg" alt="Barangay Logo"></div>
                    <div class="footer-brand-text">
                        <span class="footer-brand-top">Official Online Portal</span>
                        <span class="footer-brand-name">Barangay San Cristobal<span>.</span></span>
                    </div>
                </a>
                <p class="footer-desc">An official digital service of Barangay San Cristobal for receiving and managing resident complaints and concerns.</p>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <p class="footer-heading">Portal</p>
                <ul class="footer-links">
                    <li><a href="#services">Services</a></li>
                    <li><a href="#how-to-file">How to File</a></li>
                    <li><a href="#your-rights">Your Rights</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <p class="footer-heading">Account</p>
                <ul class="footer-links">
                    <li><a href="auth/register.php">Register</a></li>
                    <li><a href="auth/login.php">Login</a></li>
                    <li><a href="auth/forgot_password.php">Forgot Password</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <p class="footer-heading">Legal</p>
                <ul class="footer-links">
                    <li><a href="auth/privacy.php" target="_blank">Privacy Policy</a></li>
                    <li><a href="auth/terms.php" target="_blank">Terms &amp; Conditions</a></li>
                </ul>
            </div>
        </div>
        <hr class="footer-hr">
        <div class="footer-bottom">
            <span>&copy; <?=date('Y')?> Barangay San Cristobal — Official Online Complaint Portal. All rights reserved.</span>
            <div class="footer-bottom-links">
                <a href="auth/privacy.php" target="_blank">Privacy</a>
                <a href="auth/terms.php" target="_blank">Terms</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Navbar scroll
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => navbar.classList.toggle('scrolled', window.scrollY > 40));

    // Mobile nav
    const toggler = document.getElementById('navToggler');
    const navLinks = document.getElementById('navLinks');
    toggler.addEventListener('click', () => navLinks.classList.toggle('open'));
    navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => navLinks.classList.remove('open')));

    // Scroll reveal
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('visible'); observer.unobserve(e.target); } });
    }, {threshold: 0.1});
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    // Stat counter
    function animateCount(el) {
        const target = parseInt(el.dataset.target);
        const suffix = el.dataset.suffix || '';
        let current = 0;
        const inc = target / 60;
        const timer = setInterval(() => {
            current = Math.min(current + inc, target);
            el.textContent = Math.floor(current) + suffix;
            if(current >= target) clearInterval(timer);
        }, 1800 / 60);
    }
    const statObs = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting){ animateCount(e.target); statObs.unobserve(e.target); } });
    }, {threshold: 0.5});
    document.querySelectorAll('.stat-num[data-target]').forEach(el => statObs.observe(el));

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const t = document.querySelector(a.getAttribute('href'));
            if(t){ e.preventDefault(); window.scrollTo({top: t.offsetTop - 80, behavior: 'smooth'}); }
        });
    });
</script>
</body>
</html>