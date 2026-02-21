<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — Barangay Complaint Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:   #0d1b2a;
            --navy-2: #132236;
            --navy-3: #1a2f48;
            --cyan:   #00c2e0;
            --cyan-2: #00e5ff;
            --muted:  #8fa3b8;
            --border: rgba(0,194,224,0.15);
            --card:   #111e2e;
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            color: #d0dde8;
            line-height: 1.8;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(0,194,224,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,194,224,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none; z-index: 0;
        }

        .orb {
            position: fixed; border-radius: 50%;
            filter: blur(100px); pointer-events: none; z-index: 0;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(0,194,224,0.08) 0%, transparent 70%);
            top: -150px; left: -150px;
        }

        .top-bar {
            position: sticky; top: 0; z-index: 100;
            background: rgba(13,27,42,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 14px 0;
        }

        .top-bar-inner {
            max-width: 860px; margin: 0 auto; padding: 0 24px;
            display: flex; align-items: center;
            justify-content: space-between; gap: 16px; flex-wrap: wrap;
        }

        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .brand-icon {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; color: var(--navy);
        }
        .brand-name { font-family:'Sora',sans-serif; font-weight:800; font-size:1rem; color:#fff; }
        .brand-name span { color: var(--cyan); }

        .btn-close-tab {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: 0.82rem; color: var(--muted);
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 50px; padding: 7px 16px;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-close-tab:hover { color: var(--cyan); border-color: rgba(0,194,224,0.3); }

        .doc-wrapper {
            max-width: 860px; margin: 0 auto;
            padding: 60px 24px 100px;
            position: relative; z-index: 1;
        }

        .doc-header { text-align: center; margin-bottom: 60px; padding-bottom: 40px; border-bottom: 1px solid var(--border); }

        .doc-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(0,194,224,0.08);
            border: 1px solid rgba(0,194,224,0.2);
            border-radius: 50px; padding: 6px 16px;
            font-size: 0.75rem; font-weight: 700;
            color: var(--cyan); letter-spacing: 1px;
            text-transform: uppercase; margin-bottom: 20px;
        }

        .doc-title {
            font-family: 'Sora', sans-serif;
            font-weight: 800; font-size: clamp(1.8rem, 4vw, 2.6rem);
            color: #fff; margin-bottom: 14px; line-height: 1.2;
        }
        .doc-title span {
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .doc-meta {
            font-size: 0.85rem; color: var(--muted);
            display: flex; align-items: center; justify-content: center;
            gap: 20px; flex-wrap: wrap;
        }
        .doc-meta span { display: flex; align-items: center; gap: 6px; }

        /* Data collected summary cards */
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin: 20px 0;
        }

        .data-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            display: flex; align-items: center; gap: 12px;
            font-size: 0.87rem; color: #c5d3e0;
        }

        .data-card i {
            width: 36px; height: 36px;
            background: rgba(0,194,224,0.1);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            color: var(--cyan); font-size: 1rem; flex-shrink: 0;
        }

        /* Rights grid */
        .rights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 14px; margin: 20px 0;
        }

        .right-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 18px;
            transition: border-color 0.2s;
        }
        .right-card:hover { border-color: rgba(0,194,224,0.3); }

        .right-icon {
            width: 40px; height: 40px;
            background: rgba(0,194,224,0.1);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--cyan); font-size: 1.1rem;
            margin-bottom: 12px;
        }
        .right-title {
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.88rem;
            color: #fff; margin-bottom: 6px;
        }
        .right-desc { font-size: 0.8rem; color: var(--muted); line-height: 1.6; }

        /* TOC */
        .toc-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 16px; padding: 28px 32px; margin-bottom: 50px;
        }
        .toc-title {
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.85rem;
            text-transform: uppercase; letter-spacing: 1.5px;
            color: var(--cyan); margin-bottom: 16px;
        }
        .toc-list { list-style: none; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px; }
        .toc-list a {
            color: var(--muted); text-decoration: none; font-size: 0.87rem;
            display: flex; align-items: center; gap: 8px;
            padding: 6px 10px; border-radius: 8px; transition: all 0.2s;
        }
        .toc-list a:hover { color: var(--cyan); background: rgba(0,194,224,0.06); }
        .toc-num { font-family:'Sora',sans-serif; font-weight:700; font-size:0.72rem; color:var(--cyan); opacity:0.7; min-width:20px; }

        /* Sections */
        .doc-section { margin-bottom: 48px; scroll-margin-top: 80px; }
        .section-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
        .section-num {
            width: 38px; height: 38px;
            background: rgba(0,194,224,0.1);
            border: 1px solid rgba(0,194,224,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.85rem; color: var(--cyan); flex-shrink: 0;
        }
        .section-title { font-family:'Sora',sans-serif; font-weight:700; font-size:1.15rem; color:#fff; }

        .doc-section p { font-size: 0.93rem; color: #b0c4d8; margin-bottom: 14px; }

        .doc-section ul { padding-left: 0; list-style: none; margin-bottom: 14px; }
        .doc-section ul li {
            font-size: 0.93rem; color: #b0c4d8;
            padding: 6px 0 6px 28px; position: relative;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .doc-section ul li::before {
            content: ''; position: absolute; left: 8px; top: 50%;
            transform: translateY(-50%);
            width: 6px; height: 6px;
            background: var(--cyan); border-radius: 50%; opacity: 0.6;
        }

        .highlight-box {
            background: rgba(0,194,224,0.06);
            border: 1px solid rgba(0,194,224,0.15);
            border-left: 3px solid var(--cyan);
            border-radius: 0 12px 12px 0;
            padding: 16px 20px; margin: 18px 0;
            font-size: 0.9rem; color: #a8c5d8;
        }

        .success-box {
            background: rgba(39,201,63,0.06);
            border: 1px solid rgba(39,201,63,0.15);
            border-left: 3px solid #27c93f;
            border-radius: 0 12px 12px 0;
            padding: 16px 20px; margin: 18px 0;
            font-size: 0.9rem; color: #86efac;
            display: flex; gap: 12px; align-items: flex-start;
        }
        .success-box i { color: #27c93f; flex-shrink: 0; margin-top: 2px; }

        .section-divider { border: none; border-top: 1px solid var(--border); margin: 48px 0; }

        /* Agree bar */
        .agree-bar {
            position: sticky; bottom: 0; z-index: 100;
            background: rgba(13,27,42,0.92);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            padding: 16px 24px;
        }
        .agree-inner {
            max-width: 860px; margin: 0 auto;
            display: flex; align-items: center;
            justify-content: space-between; gap: 16px; flex-wrap: wrap;
        }
        .agree-text { font-size: 0.83rem; color: var(--muted); }

        .btn-agree {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 28px;
            background: var(--cyan); color: var(--navy);
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.88rem;
            border-radius: 10px; border: none;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
        }
        .btn-agree:hover {
            background: var(--cyan-2); transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,194,224,0.3); color: var(--navy);
        }

        @media (max-width: 600px) {
            .doc-wrapper { padding: 40px 16px 100px; }
            .toc-card { padding: 20px; }
            .toc-list { grid-template-columns: 1fr; }
            .data-grid { grid-template-columns: 1fr 1fr; }
            .rights-grid { grid-template-columns: 1fr; }
            .agree-inner { flex-direction: column; text-align: center; }
            .btn-agree { width: 100%; justify-content: center; }
        }

        @media (max-width: 380px) {
            .data-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-inner">
            <a href="../index.php" class="brand">
                <div class="brand-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
                <span class="brand-name">CMS<span>.</span></span>
            </a>
            <button class="btn-close-tab" onclick="window.close(); history.back();">
                <i class="bi bi-x-lg"></i> Close
            </button>
        </div>
    </div>

    <!-- Content -->
    <div class="doc-wrapper">
        <!-- Header -->
        <div class="doc-header">
            <div class="doc-badge"><i class="bi bi-shield-lock"></i> Privacy Document</div>
            <h1 class="doc-title">Privacy <span>Policy</span></h1>
            <div class="doc-meta">
                <span><i class="bi bi-calendar3"></i> Effective: January 1, 2026</span>
                <span><i class="bi bi-arrow-clockwise"></i> Last Updated: February 2026</span>
                <span><i class="bi bi-geo-alt"></i> Barangay Complaint Management System</span>
            </div>
        </div>

        <!-- Intro -->
        <div class="doc-section">
            <p>The <strong style="color:#fff;">Barangay Complaint Management System (BCMS)</strong> is committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, store, and protect your personal data in compliance with the <strong style="color:#fff;">Republic Act No. 10173 — Data Privacy Act of 2012</strong> of the Philippines.</p>

            <div class="success-box">
                <i class="bi bi-shield-check-fill"></i>
                <span>We take your privacy seriously. Your personal information is collected only for legitimate barangay complaint management purposes and is never sold to third parties.</span>
            </div>
        </div>

        <!-- TOC -->
        <div class="toc-card">
            <div class="toc-title"><i class="bi bi-list-ul"></i> &nbsp;Table of Contents</div>
            <ul class="toc-list">
                <li><a href="#collected"><span class="toc-num">01</span> Data We Collect</a></li>
                <li><a href="#purpose"><span class="toc-num">02</span> Purpose of Collection</a></li>
                <li><a href="#sharing"><span class="toc-num">03</span> Data Sharing</a></li>
                <li><a href="#storage"><span class="toc-num">04</span> Data Storage & Security</a></li>
                <li><a href="#retention"><span class="toc-num">05</span> Data Retention</a></li>
                <li><a href="#rights"><span class="toc-num">06</span> Your Rights</a></li>
                <li><a href="#cookies"><span class="toc-num">07</span> Cookies & Sessions</a></li>
                <li><a href="#children"><span class="toc-num">08</span> Minors' Privacy</a></li>
                <li><a href="#changes"><span class="toc-num">09</span> Policy Changes</a></li>
                <li><a href="#contact"><span class="toc-num">10</span> Contact Us</a></li>
            </ul>
        </div>

        <hr class="section-divider">

        <!-- Section 1 -->
        <div class="doc-section" id="collected">
            <div class="section-header">
                <div class="section-num">01</div>
                <h2 class="section-title">Personal Data We Collect</h2>
            </div>
            <p>When you register and use the Barangay Complaint Management System, we collect the following personal information:</p>

            <div class="data-grid">
                <div class="data-card">
                    <i class="bi bi-person-fill"></i>
                    <span>Full Name</span>
                </div>
                <div class="data-card">
                    <i class="bi bi-envelope-fill"></i>
                    <span>Email Address</span>
                </div>
                <div class="data-card">
                    <i class="bi bi-telephone-fill"></i>
                    <span>Phone Number</span>
                </div>
                <div class="data-card">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Home Address</span>
                </div>
                <div class="data-card">
                    <i class="bi bi-image-fill"></i>
                    <span>Profile Photo</span>
                </div>
                <div class="data-card">
                    <i class="bi bi-pc-display"></i>
                    <span>IP Address & Device Info</span>
                </div>
            </div>

            <p>Additionally, we collect data related to complaints you file, including complaint descriptions, attached photos or documents, and any communication or updates related to your complaint.</p>
            <div class="highlight-box">
                <i class="bi bi-info-circle"></i> &nbsp;Some information (such as phone number and profile photo) is optional. Only your Full Name and Email are required to create an account.
            </div>
        </div>

        <!-- Section 2 -->
        <div class="doc-section" id="purpose">
            <div class="section-header">
                <div class="section-num">02</div>
                <h2 class="section-title">Purpose of Data Collection</h2>
            </div>
            <p>We collect your personal information solely for the following legitimate purposes:</p>
            <ul>
                <li>To create and manage your account in the Barangay Complaint Management System</li>
                <li>To verify your identity and eligibility as a resident or authorized user</li>
                <li>To receive, process, and respond to complaints you submit</li>
                <li>To send notifications about complaint status updates and resolutions</li>
                <li>To communicate with you regarding your account or submitted complaints</li>
                <li>To maintain accurate records for barangay administrative purposes</li>
                <li>To ensure system security and prevent fraudulent or abusive use</li>
                <li>To generate anonymized statistical reports for barangay service improvement</li>
            </ul>
        </div>

        <!-- Section 3 -->
        <div class="doc-section" id="sharing">
            <div class="section-header">
                <div class="section-num">03</div>
                <h2 class="section-title">Data Sharing & Disclosure</h2>
            </div>
            <p>Your personal information will <strong style="color:#fff;">NOT</strong> be sold, rented, or shared with any commercial third party. Your data may only be accessed or disclosed in the following limited circumstances:</p>
            <ul>
                <li><strong style="color:#e2eaf3;">Authorized Barangay Personnel</strong> — Only designated barangay officials and staff directly handling complaints will have access to your information</li>
                <li><strong style="color:#e2eaf3;">Higher Government Authorities</strong> — If a complaint requires escalation to higher government offices (e.g., municipal, city, or national agencies), relevant information may be shared as required</li>
                <li><strong style="color:#e2eaf3;">Legal Requirements</strong> — We may disclose your information when required by law, court order, or legitimate government request</li>
                <li><strong style="color:#e2eaf3;">System Administrators</strong> — Technical staff responsible for maintaining system security may access logs containing your data strictly for maintenance purposes</li>
            </ul>
        </div>

        <!-- Section 4 -->
        <div class="doc-section" id="storage">
            <div class="section-header">
                <div class="section-num">04</div>
                <h2 class="section-title">Data Storage & Security</h2>
            </div>
            <p>We take the security of your personal information seriously. The following measures are in place to protect your data:</p>
            <ul>
                <li>All passwords are encrypted using industry-standard hashing algorithms before storage</li>
                <li>Access to the system and its database is restricted to authorized personnel only</li>
                <li>The system uses secure server configurations to prevent unauthorized access</li>
                <li>Regular security assessments are conducted to identify and address vulnerabilities</li>
                <li>File uploads (photos, documents) are stored in secured directories with restricted access</li>
            </ul>
            <div class="highlight-box">
                <i class="bi bi-lock-fill"></i> &nbsp;While we implement strong security measures, no digital system is 100% immune to breaches. In the event of a data breach, affected users will be notified promptly in accordance with the Data Privacy Act of 2012.
            </div>
        </div>

        <!-- Section 5 -->
        <div class="doc-section" id="retention">
            <div class="section-header">
                <div class="section-num">05</div>
                <h2 class="section-title">Data Retention</h2>
            </div>
            <p>We retain your personal data only for as long as necessary to fulfill the purposes outlined in this Privacy Policy:</p>
            <ul>
                <li>Active account data is retained for as long as your account remains active in the system</li>
                <li>Complaint records are retained for a minimum of five (5) years for administrative and legal reference purposes</li>
                <li>When you delete your account, personal identifiers are removed; however, anonymized complaint records may be retained for statistical purposes</li>
                <li>System access logs (IP addresses, activity logs) are retained for up to one (1) year for security purposes</li>
            </ul>
        </div>

        <!-- Section 6 -->
        <div class="doc-section" id="rights">
            <div class="section-header">
                <div class="section-num">06</div>
                <h2 class="section-title">Your Rights as a Data Subject</h2>
            </div>
            <p>Under the Data Privacy Act of 2012 (Republic Act No. 10173), you have the following rights regarding your personal data:</p>

            <div class="rights-grid">
                <div class="right-card">
                    <div class="right-icon"><i class="bi bi-eye"></i></div>
                    <div class="right-title">Right to Access</div>
                    <div class="right-desc">You may request a copy of the personal data we hold about you at any time.</div>
                </div>
                <div class="right-card">
                    <div class="right-icon"><i class="bi bi-pencil-square"></i></div>
                    <div class="right-title">Right to Correction</div>
                    <div class="right-desc">You may request corrections to inaccurate or outdated personal information.</div>
                </div>
                <div class="right-card">
                    <div class="right-icon"><i class="bi bi-trash3"></i></div>
                    <div class="right-title">Right to Erasure</div>
                    <div class="right-desc">You may request deletion of your personal data, subject to legal retention requirements.</div>
                </div>
                <div class="right-card">
                    <div class="right-icon"><i class="bi bi-slash-circle"></i></div>
                    <div class="right-title">Right to Object</div>
                    <div class="right-desc">You may object to the processing of your data for certain purposes.</div>
                </div>
                <div class="right-card">
                    <div class="right-icon"><i class="bi bi-shield-exclamation"></i></div>
                    <div class="right-title">Right to File Complaint</div>
                    <div class="right-desc">You may file a complaint with the National Privacy Commission (NPC) if you believe your rights have been violated.</div>
                </div>
                <div class="right-card">
                    <div class="right-icon"><i class="bi bi-download"></i></div>
                    <div class="right-title">Right to Portability</div>
                    <div class="right-desc">You may request a copy of your data in a structured, readable format.</div>
                </div>
            </div>

            <p>To exercise any of these rights, please contact the Barangay Administration directly at the Barangay Hall or through the system.</p>
        </div>

        <!-- Section 7 -->
        <div class="doc-section" id="cookies">
            <div class="section-header">
                <div class="section-num">07</div>
                <h2 class="section-title">Cookies & Sessions</h2>
            </div>
            <p>The Barangay Complaint Management System uses the following browser-based technologies:</p>
            <ul>
                <li><strong style="color:#e2eaf3;">Session Cookies</strong> — Used to maintain your login state while you navigate the system. These are deleted when you close your browser</li>
                <li><strong style="color:#e2eaf3;">LocalStorage</strong> — Used to remember your preferred display settings (e.g., light or dark mode) across visits</li>
                <li><strong style="color:#e2eaf3;">No Third-Party Tracking</strong> — We do not use advertising cookies or third-party analytics trackers</li>
            </ul>
        </div>

        <!-- Section 8 -->
        <div class="doc-section" id="children">
            <div class="section-header">
                <div class="section-num">08</div>
                <h2 class="section-title">Privacy of Minors</h2>
            </div>
            <p>The Barangay Complaint Management System is not intended for use by individuals under 18 years of age without the express consent and supervision of a parent or legal guardian. If we become aware that a minor has registered without proper consent, the account will be reviewed and may be removed. Complaints involving or filed on behalf of minors will be handled with additional confidentiality and sensitivity.</p>
        </div>

        <!-- Section 9 -->
        <div class="doc-section" id="changes">
            <div class="section-header">
                <div class="section-num">09</div>
                <h2 class="section-title">Changes to This Privacy Policy</h2>
            </div>
            <p>The Barangay Administration reserves the right to update this Privacy Policy at any time to reflect changes in our practices, legal requirements, or operational needs. The updated policy will be posted in the system with the effective date clearly stated. Continued use of the system after any update constitutes your acceptance of the revised policy.</p>
            <p>We encourage you to review this Privacy Policy periodically to stay informed about how we protect your information.</p>
        </div>

        <!-- Section 10 -->
        <div class="doc-section" id="contact">
            <div class="section-header">
                <div class="section-num">10</div>
                <h2 class="section-title">Contact Information</h2>
            </div>
            <p>For any questions, concerns, or requests regarding your personal data and this Privacy Policy, please contact us:</p>
            <ul>
                <li>Visit the <strong style="color:#e2eaf3;">Barangay Hall</strong> during office hours (Monday–Friday, 8:00 AM – 5:00 PM)</li>
                <li>Submit a concern through the <strong style="color:#e2eaf3;">Barangay Complaint Management System</strong></li>
                <li>Contact the designated <strong style="color:#e2eaf3;">Barangay Data Protection Officer (DPO)</strong> or IT Administrator</li>
            </ul>
            <p>You may also file a complaint directly with the <strong style="color:#e2eaf3;">National Privacy Commission (NPC)</strong> at <a href="https://www.privacy.gov.ph" target="_blank" style="color:var(--cyan);">www.privacy.gov.ph</a> if you believe your data privacy rights have been violated.</p>
        </div>

        <hr class="section-divider">

        <p style="font-size:0.85rem; color:var(--muted); text-align:center;">
            This Privacy Policy is compliant with Republic Act No. 10173 (Data Privacy Act of 2012) of the Philippines.<br>
            By using the Barangay Complaint Management System, you acknowledge that you have read and understood this policy.
        </p>
    </div>

   

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</body>
</html>