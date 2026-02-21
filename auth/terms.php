<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions — Barangay Complaint Management System</title>
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

        /* Grid background */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(0,194,224,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,194,224,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        /* Glow orb */
        .orb {
            position: fixed; border-radius: 50%;
            filter: blur(100px); pointer-events: none; z-index: 0;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(0,194,224,0.08) 0%, transparent 70%);
            top: -150px; right: -150px;
        }

        /* Top bar */
        .top-bar {
            position: sticky; top: 0; z-index: 100;
            background: rgba(13,27,42,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 14px 0;
        }

        .top-bar-inner {
            max-width: 860px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .brand-icon {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; color: var(--navy);
        }
        .brand-name {
            font-family: 'Sora', sans-serif;
            font-weight: 800; font-size: 1rem; color: #fff;
        }
        .brand-name span { color: var(--cyan); }

        .btn-close-tab {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: 0.82rem; color: var(--muted);
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 50px; padding: 7px 16px;
            cursor: pointer; text-decoration: none;
            transition: all 0.2s; font-family: 'DM Sans', sans-serif;
        }
        .btn-close-tab:hover { color: var(--cyan); border-color: rgba(0,194,224,0.3); }

        /* Main content */
        .doc-wrapper {
            max-width: 860px;
            margin: 0 auto;
            padding: 60px 24px 80px;
            position: relative; z-index: 1;
        }

        /* Doc header */
        .doc-header {
            text-align: center;
            margin-bottom: 60px;
            padding-bottom: 40px;
            border-bottom: 1px solid var(--border);
        }

        .doc-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(0,194,224,0.08);
            border: 1px solid rgba(0,194,224,0.2);
            border-radius: 50px;
            padding: 6px 16px;
            font-size: 0.75rem; font-weight: 700;
            color: var(--cyan); letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .doc-title {
            font-family: 'Sora', sans-serif;
            font-weight: 800; font-size: clamp(1.8rem, 4vw, 2.6rem);
            color: #fff; margin-bottom: 14px; line-height: 1.2;
        }

        .doc-title span {
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .doc-meta {
            font-size: 0.85rem; color: var(--muted);
            display: flex; align-items: center; justify-content: center;
            gap: 20px; flex-wrap: wrap;
        }

        .doc-meta span { display: flex; align-items: center; gap: 6px; }

        /* Table of contents */
        .toc-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 50px;
        }

        .toc-title {
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.85rem;
            text-transform: uppercase; letter-spacing: 1.5px;
            color: var(--cyan); margin-bottom: 16px;
        }

        .toc-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 8px;
        }

        .toc-list a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.87rem;
            display: flex; align-items: center; gap: 8px;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .toc-list a:hover {
            color: var(--cyan);
            background: rgba(0,194,224,0.06);
        }

        .toc-num {
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.72rem;
            color: var(--cyan); opacity: 0.7;
            min-width: 20px;
        }

        /* Sections */
        .doc-section {
            margin-bottom: 48px;
            scroll-margin-top: 80px;
        }

        .section-header {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 20px;
        }

        .section-num {
            width: 38px; height: 38px;
            background: rgba(0,194,224,0.1);
            border: 1px solid rgba(0,194,224,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.85rem;
            color: var(--cyan); flex-shrink: 0;
        }

        .section-title {
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 1.15rem;
            color: #fff;
        }

        .doc-section p {
            font-size: 0.93rem;
            color: #b0c4d8;
            margin-bottom: 14px;
        }

        .doc-section ul, .doc-section ol {
            padding-left: 0;
            list-style: none;
            margin-bottom: 14px;
        }

        .doc-section ul li, .doc-section ol li {
            font-size: 0.93rem;
            color: #b0c4d8;
            padding: 6px 0 6px 28px;
            position: relative;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }

        .doc-section ul li::before {
            content: '';
            position: absolute; left: 8px; top: 50%;
            transform: translateY(-50%);
            width: 6px; height: 6px;
            background: var(--cyan); border-radius: 50%;
            opacity: 0.6;
        }

        .doc-section ol { counter-reset: item; }
        .doc-section ol li { counter-increment: item; }
        .doc-section ol li::before {
            content: counter(item) ".";
            position: absolute; left: 4px; top: 6px;
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.78rem;
            color: var(--cyan); opacity: 0.7;
        }

        /* Highlight box */
        .highlight-box {
            background: rgba(0,194,224,0.06);
            border: 1px solid rgba(0,194,224,0.15);
            border-left: 3px solid var(--cyan);
            border-radius: 0 12px 12px 0;
            padding: 16px 20px;
            margin: 18px 0;
            font-size: 0.9rem;
            color: #a8c5d8;
        }

        .warning-box {
            background: rgba(255,193,7,0.06);
            border: 1px solid rgba(255,193,7,0.15);
            border-left: 3px solid #ffc107;
            border-radius: 0 12px 12px 0;
            padding: 16px 20px;
            margin: 18px 0;
            font-size: 0.9rem;
            color: #d4b87a;
            display: flex; gap: 12px; align-items: flex-start;
        }

        .warning-box i { color: #ffc107; flex-shrink: 0; margin-top: 2px; }

        /* Section divider */
        .section-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 48px 0;
        }

        /* Bottom agree section */
        .agree-bar {
            position: sticky; bottom: 0; z-index: 100;
            background: rgba(13,27,42,0.92);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            padding: 16px 24px;
            text-align: center;
        }

        .agree-inner {
            max-width: 860px; margin: 0 auto;
            display: flex; align-items: center;
            justify-content: space-between; gap: 16px;
            flex-wrap: wrap;
        }

        .agree-text {
            font-size: 0.83rem; color: var(--muted);
        }

        .btn-agree {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 28px;
            background: var(--cyan); color: var(--navy);
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.88rem;
            border-radius: 10px; border: none;
            cursor: pointer; text-decoration: none;
            transition: all 0.2s;
        }
        .btn-agree:hover {
            background: var(--cyan-2);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,194,224,0.3);
            color: var(--navy);
        }

        @media (max-width: 600px) {
            .doc-wrapper { padding: 40px 16px 100px; }
            .toc-card { padding: 20px; }
            .toc-list { grid-template-columns: 1fr; }
            .agree-inner { flex-direction: column; text-align: center; }
            .btn-agree { width: 100%; justify-content: center; }
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
            <div class="doc-badge"><i class="bi bi-file-text"></i> Legal Document</div>
            <h1 class="doc-title">Terms and <span>Conditions</span></h1>
            <div class="doc-meta">
                <span><i class="bi bi-calendar3"></i> Effective: January 1, 2026</span>
                <span><i class="bi bi-arrow-clockwise"></i> Last Updated: February 2026</span>
                <span><i class="bi bi-geo-alt"></i> Barangay Complaint Management System</span>
            </div>
        </div>

        <!-- Intro -->
        <div class="doc-section">
            <p>Welcome to the <strong style="color:#fff;">Barangay Complaint Management System (BCMS)</strong>. These Terms and Conditions govern your use of our platform. By registering and using this system, you agree to comply with and be bound by the following terms. Please read them carefully before proceeding.</p>

            <div class="warning-box">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>If you do not agree with any part of these Terms and Conditions, you must not register or use this system. Your registration implies full acceptance of these terms.</span>
            </div>
        </div>

        <!-- Table of Contents -->
        <div class="toc-card">
            <div class="toc-title"><i class="bi bi-list-ul"></i> &nbsp;Table of Contents</div>
            <ul class="toc-list">
                <li><a href="#eligibility"><span class="toc-num">01</span> Eligibility</a></li>
                <li><a href="#account"><span class="toc-num">02</span> Account Registration</a></li>
                <li><a href="#use"><span class="toc-num">03</span> Acceptable Use</a></li>
                <li><a href="#complaints"><span class="toc-num">04</span> Filing Complaints</a></li>
                <li><a href="#prohibited"><span class="toc-num">05</span> Prohibited Activities</a></li>
                <li><a href="#privacy"><span class="toc-num">06</span> Privacy & Data</a></li>
                <li><a href="#account-suspension"><span class="toc-num">07</span> Account Suspension</a></li>
                <li><a href="#liability"><span class="toc-num">08</span> Limitation of Liability</a></li>
                <li><a href="#changes"><span class="toc-num">09</span> Changes to Terms</a></li>
                <li><a href="#contact"><span class="toc-num">10</span> Contact</a></li>
            </ul>
        </div>

        <hr class="section-divider">

        <!-- Section 1 -->
        <div class="doc-section" id="eligibility">
            <div class="section-header">
                <div class="section-num">01</div>
                <h2 class="section-title">Eligibility</h2>
            </div>
            <p>This system is intended for use by:</p>
            <ul>
                <li>Residents of the barangay who wish to file complaints or concerns to the barangay officials</li>
                <li>Authorized barangay officials and staff designated to manage and resolve complaints</li>
                <li>Any individual who has received explicit approval from the Barangay Administration to use the system</li>
            </ul>
            <p>By registering, you confirm that the information you provide is accurate, current, and complete. Accounts found to be using false or misleading information are subject to immediate termination.</p>
        </div>

        <!-- Section 2 -->
        <div class="doc-section" id="account">
            <div class="section-header">
                <div class="section-num">02</div>
                <h2 class="section-title">Account Registration & Approval</h2>
            </div>
            <p>All new registrations are subject to review and approval by a Barangay Administrator before access is granted. The approval process may take 1–3 business days.</p>
            <ul>
                <li>You must provide a valid and accessible email address for verification and notifications</li>
                <li>You are responsible for maintaining the confidentiality of your account credentials</li>
                <li>You must not share your login credentials with any other person</li>
                <li>Any activity that occurs under your account is your sole responsibility</li>
                <li>You must notify the Barangay immediately if you suspect unauthorized access to your account</li>
            </ul>
            <div class="highlight-box">
                <i class="bi bi-info-circle"></i> &nbsp;The Barangay Administration reserves the right to approve, deny, or revoke any account at its discretion, without prior notice.
            </div>
        </div>

        <!-- Section 3 -->
        <div class="doc-section" id="use">
            <div class="section-header">
                <div class="section-num">03</div>
                <h2 class="section-title">Acceptable Use</h2>
            </div>
            <p>You agree to use the Barangay Complaint Management System only for lawful purposes and in a manner that does not infringe the rights of others. You agree to:</p>
            <ul>
                <li>Provide truthful and accurate information in all complaints and communications</li>
                <li>Use the system exclusively for filing legitimate complaints and concerns within the barangay's jurisdiction</li>
                <li>Respect the privacy and dignity of all individuals mentioned in any complaint</li>
                <li>Cooperate with barangay officials during the resolution process</li>
                <li>Keep your contact information updated for proper communication</li>
            </ul>
        </div>

        <!-- Section 4 -->
        <div class="doc-section" id="complaints">
            <div class="section-header">
                <div class="section-num">04</div>
                <h2 class="section-title">Filing Complaints</h2>
            </div>
            <p>When submitting a complaint through the system, you agree to the following:</p>
            <ul>
                <li>All information submitted must be factual and verifiable to the best of your knowledge</li>
                <li>Deliberately filing false or misleading complaints may result in account suspension and possible legal consequences under applicable laws</li>
                <li>You understand that the Barangay has the authority to investigate, forward, escalate, or close any complaint at its discretion</li>
                <li>Complaint resolutions are subject to the barangay's jurisdiction and existing laws</li>
                <li>Attached photos or documents must be relevant and must not violate the privacy or rights of any individual</li>
                <li>You may be contacted by barangay officials for additional information or clarification</li>
            </ul>
            <div class="warning-box">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Filing a knowingly false complaint may be considered a violation of Philippine law, particularly Republic Act No. 3019 (Anti-Graft and Corrupt Practices Act) and the Revised Penal Code provisions on perjury and false testimony.</span>
            </div>
        </div>

        <!-- Section 5 -->
        <div class="doc-section" id="prohibited">
            <div class="section-header">
                <div class="section-num">05</div>
                <h2 class="section-title">Prohibited Activities</h2>
            </div>
            <p>The following activities are strictly prohibited and will result in immediate account termination and may be reported to appropriate authorities:</p>
            <ul>
                <li>Filing false, fabricated, or malicious complaints against any individual or entity</li>
                <li>Uploading offensive, obscene, or illegal content including images or documents</li>
                <li>Harassing, threatening, or intimidating other users or barangay officials through the system</li>
                <li>Attempting to gain unauthorized access to other users' accounts or system data</li>
                <li>Using automated tools, bots, or scripts to interact with the system</li>
                <li>Impersonating another person, barangay official, or any government representative</li>
                <li>Attempting to manipulate, alter, or corrupt system data or records</li>
                <li>Using the system to promote illegal activities or distribute prohibited content</li>
            </ul>
        </div>

        <!-- Section 6 -->
        <div class="doc-section" id="privacy">
            <div class="section-header">
                <div class="section-num">06</div>
                <h2 class="section-title">Privacy & Data</h2>
            </div>
            <p>By using this system, you acknowledge and consent to the collection and processing of your personal information as described in our <strong style="color:#fff;">Privacy Policy</strong>. In summary:</p>
            <ul>
                <li>Your personal data is used solely for complaint management and communication purposes</li>
                <li>Your information will not be sold, rented, or shared with third parties outside the barangay administration without your consent, unless required by law</li>
                <li>All data is stored securely and access is restricted to authorized personnel only</li>
                <li>You have the right to request access to, correction of, or deletion of your personal data</li>
            </ul>
            <p>For full details, please read our <a href="privacy.php" target="_blank" style="color:var(--cyan);">Privacy Policy</a>.</p>
        </div>

        <!-- Section 7 -->
        <div class="doc-section" id="account-suspension">
            <div class="section-header">
                <div class="section-num">07</div>
                <h2 class="section-title">Account Suspension & Termination</h2>
            </div>
            <p>The Barangay Administration reserves the right to suspend or permanently terminate any account for the following reasons, without prior notice:</p>
            <ul>
                <li>Violation of any provision in these Terms and Conditions</li>
                <li>Submission of false, malicious, or fraudulent complaints</li>
                <li>Inappropriate conduct toward other users or barangay personnel</li>
                <li>Inactivity for an extended period at the administration's discretion</li>
                <li>Any activity deemed harmful to the system, the barangay, or its residents</li>
            </ul>
            <p>Suspended users may appeal to the Barangay Administration through official channels. The decision of the Barangay Captain shall be final.</p>
        </div>

        <!-- Section 8 -->
        <div class="doc-section" id="liability">
            <div class="section-header">
                <div class="section-num">08</div>
                <h2 class="section-title">Limitation of Liability</h2>
            </div>
            <p>The Barangay and its officials shall not be held liable for:</p>
            <ul>
                <li>Any technical issues, downtime, or data loss caused by system failures or force majeure events</li>
                <li>Outcomes of complaint resolutions that are beyond the jurisdiction or authority of the barangay</li>
                <li>Decisions made in good faith based on the information provided by the complainant</li>
                <li>Unauthorized access to user accounts resulting from the user's failure to protect their credentials</li>
            </ul>
            <div class="highlight-box">
                <i class="bi bi-info-circle"></i> &nbsp;The system is provided "as is" for the purpose of improving complaint handling within the barangay. The Barangay Administration makes no warranties regarding uninterrupted service availability.
            </div>
        </div>

        <!-- Section 9 -->
        <div class="doc-section" id="changes">
            <div class="section-header">
                <div class="section-num">09</div>
                <h2 class="section-title">Changes to These Terms</h2>
            </div>
            <p>The Barangay Administration reserves the right to update or modify these Terms and Conditions at any time. Changes will be effective immediately upon posting on the system. Continued use of the system after any changes constitutes your acceptance of the new terms.</p>
            <p>It is your responsibility to review these Terms periodically. We recommend checking back at least once every three months.</p>
        </div>

        <!-- Section 10 -->
        <div class="doc-section" id="contact">
            <div class="section-header">
                <div class="section-num">10</div>
                <h2 class="section-title">Contact Information</h2>
            </div>
            <p>If you have questions or concerns about these Terms and Conditions, please contact the Barangay Administration through:</p>
            <ul>
                <li>Visiting the Barangay Hall during official office hours (Monday–Friday, 8:00 AM – 5:00 PM)</li>
                <li>Submitting an inquiry through the Barangay Complaint Management System</li>
                <li>Contacting the Barangay Secretary or designated IT Administrator</li>
            </ul>
        </div>

        <hr class="section-divider">

        <p style="font-size:0.85rem; color:var(--muted); text-align:center;">
            By registering and using the Barangay Complaint Management System, you acknowledge that you have read, understood, and agreed to these Terms and Conditions.
        </p>
    </div>

   

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for TOC links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>