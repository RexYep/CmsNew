<?php
// ============================================
// SUBMIT COMPLAINT PAGE
// user/submit_complaint.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security_helper.php';
require_once '../includes/recaptcha_helper.php';

requireLogin();

if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$page_title = "Submit Complaint";

$error = '';
$success = '';

$user_id = $_SESSION['user_id'];

// Check daily limit
$limit_check = checkDailyComplaintLimit($user_id);
$can_submit = $limit_check['can_submit'];
$complaints_today = $limit_check['count'];
$remaining = $limit_check['remaining'];

// Get all active categories
$categories = getAllCategories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validation = validateFormProtection('complaint_submit', 3, 60); // 3 per minute

    if (!$validation['valid']) {
        $error = implode('<br>', $validation['errors']);
    } else {
        // Validate reCAPTCHA
        if (isRecaptchaConfigured()) {
            $recaptcha = validateRecaptchaFromPost(0.25);
            if (!$recaptcha['success']) {
                $error = $recaptcha['message'];
            }
        }

        // If validation passed, proceed
        if (empty($error)) {


            $limit_check = checkDailyComplaintLimit($user_id);
            if (!$limit_check['can_submit']) {
                $error = 'Daily complaint limit reached! You have submitted ' . $limit_check['count'] . ' complaint(s) today. Please try again tomorrow.';
            } else {

                $user_id = $_SESSION['user_id'];
                $category_id = sanitizeInput($_POST['category_id']);
                $subject = sanitizeInput($_POST['subject']);
                $description = sanitizeInput($_POST['description']);
                $priority = sanitizeInput($_POST['priority']);

                // Validate inputs
                if (empty($subject) || empty($description) || empty($category_id)) {
                    $error = 'Please fill in all required fields';
                } elseif (strlen($subject) < 5) {
                    $error = 'Subject must be at least 5 characters long';
                } elseif (strlen($description) < 20) {
                    $error = 'Description must be at least 20 characters long';
                } else {
                    // ─────────────────────────────────────────────────────────────
                    // STEP 1: Pre-validate ALL files BEFORE inserting the complaint.
                    // This prevents saving a complaint when an attachment is invalid.
                    // ─────────────────────────────────────────────────────────────
                    $allowed_types = [
                        'image/jpeg', 'image/png', 'image/gif',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'
                    ];
                    $video_types      = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'];
                    $max_size_default = 5  * 1024 * 1024; // 5MB  for images & documents
                    $max_size_video   = 50 * 1024 * 1024; // 50MB for videos

                    if (!empty($_FILES['attachments']['name'][0])) {
                        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                            $err_code  = $_FILES['attachments']['error'][$key];
                            $file_name = $_FILES['attachments']['name'][$key];

                            // BUG FIX #3: Properly catch PHP server-level size errors
                            // (triggered when file exceeds upload_max_filesize / post_max_size in php.ini)
                            if ($err_code === UPLOAD_ERR_INI_SIZE || $err_code === UPLOAD_ERR_FORM_SIZE) {
                                $error = "File <strong>$file_name</strong> exceeds the server's maximum allowed upload size. Please choose a smaller file.";
                                break;
                            }

                            if ($err_code !== UPLOAD_ERR_OK) {
                                $error = "An unexpected upload error occurred for <strong>$file_name</strong>. Please try again.";
                                break;
                            }

                            $file_type = $_FILES['attachments']['type'][$key];
                            $file_size = $_FILES['attachments']['size'][$key];

                            // Validate file type
                            if (!in_array($file_type, $allowed_types)) {
                                $error = "File type not allowed: <strong>$file_name</strong>. Please upload images, PDF, Word documents, or videos only.";
                                break;
                            }

                            // Per-type size validation
                            $is_video    = in_array($file_type, $video_types);
                            $max_allowed = $is_video ? $max_size_video : $max_size_default;
                            $max_label   = $is_video ? '50MB' : '5MB';

                            if ($file_size > $max_allowed) {
                                $size_mb = number_format($file_size / 1024 / 1024, 2);
                                $error = "File <strong>$file_name</strong> is too large ({$size_mb}MB). Maximum allowed size for " . ($is_video ? 'videos' : 'images/documents') . " is <strong>$max_label</strong>.";
                                break;
                            }
                        }
                    }

                    // ─────────────────────────────────────────────────────────────
                    // STEP 2: Only insert the complaint if file pre-validation passed.
                    // BUG FIX #2: Complaint is no longer saved when a file is invalid.
                    // ─────────────────────────────────────────────────────────────
                    if (empty($error)) {
                    // Insert complaint
                    $stmt = $conn->prepare("INSERT INTO complaints (user_id, category_id, subject, description, priority, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
                    $stmt->bind_param("iisss", $user_id, $category_id, $subject, $description, $priority);

                    if ($stmt->execute()) {
                        $complaint_id = $conn->insert_id;

                        // ─────────────────────────────────────────────────────────
                        // STEP 3: Upload validated files to Cloudinary.
                        // BUG FIX #1: Upload code is now in the correct place —
                        // inside the UPLOAD_ERR_OK branch, after validation passes.
                        // (Previously it was dead code after a `break` in the elseif.)
                        // ─────────────────────────────────────────────────────────
                        $upload_success = true;
                        $uploaded_files = [];

                        if (!empty($_FILES['attachments']['name'][0])) {
                            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                                if ($_FILES['attachments']['error'][$key] !== UPLOAD_ERR_OK) {
                                    continue; // already caught in pre-validation; skip
                                }

                                $file_name = $_FILES['attachments']['name'][$key];
                                $file_type = $_FILES['attachments']['type'][$key];
                                $file_size = $_FILES['attachments']['size'][$key];

                                // Prepare file array for Cloudinary upload
                                $file_array = [
                                    'name'     => $file_name,
                                    'type'     => $file_type,
                                    'tmp_name' => $tmp_name,
                                    'error'    => $_FILES['attachments']['error'][$key],
                                    'size'     => $file_size
                                ];

                                // Upload to Cloudinary
                                $upload_result = uploadToCloudinary($file_array, 'complaints');

                                if ($upload_result['success']) {
                                    // Save to database with Cloudinary URLs
                                    $cloudinary_url           = $upload_result['url'];
                                    $cloudinary_public_id     = $upload_result['public_id'];
                                    $cloudinary_resource_type = $upload_result['resource_type'];

                                    $stmt = $conn->prepare("INSERT INTO complaint_attachments (complaint_id, file_name, file_path, cloudinary_url, cloudinary_public_id, cloudinary_resource_type, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                    $stmt->bind_param(
                                        "issssssi",
                                        $complaint_id,
                                        $file_name,
                                        $cloudinary_url, // Store cloudinary URL as file_path for backward compatibility
                                        $cloudinary_url,
                                        $cloudinary_public_id,
                                        $cloudinary_resource_type,
                                        $file_type,
                                        $file_size
                                    );
                                    $stmt->execute();

                                    $uploaded_files[] = $file_name;
                                } else {
                                    $error = "Failed to upload file: $file_name - " . $upload_result['error'];
                                    $upload_success = false;
                                    break;
                                }
                            }
                        }

                        // Log to history
                        $stmt = $conn->prepare("INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, comment) VALUES (?, ?, NULL, 'Pending', 'Complaint submitted')");
                        $stmt->bind_param("ii", $complaint_id, $user_id);
                        $stmt->execute();

                        // Notify only super admins about new complaint (regular admins get notified when assigned)
                        $super_admins = $conn->query("SELECT user_id FROM users WHERE role = 'admin' AND admin_level = 'super_admin' AND status = 'active'");
                        while ($admin = $super_admins->fetch_assoc()) {
                            $notif_title = "New Complaint Submitted";
                            $notif_message = "Complaint #$complaint_id: " . substr($subject, 0, 50) . "... (Priority: $priority)";
                            $notif_type = ($priority == 'High') ? 'danger' : 'info';
                            createNotification($admin['user_id'], $notif_title, $notif_message, $notif_type, $complaint_id);
                        }

                        // ↓ CACHE INVALIDATION: i-clear ang dashboard at complaint stats
                        cacheInvalidateComplaint(0, $user_id);

                        if ($upload_success) {
                            logActivity('complaint_submitted', 'Submitted complaint #' . $complaint_id);
                            $success = 'Complaint submitted successfully! Tracking ID: #' . $complaint_id;
                            if (!empty($uploaded_files)) {
                                $success .= '<br>Files uploaded: ' . implode(', ', $uploaded_files);
                            }
                        } else {
                            $success = 'Complaint submitted (ID: #' . $complaint_id . '), but some files failed to upload.';
                        }

                        // Clear form
                        $subject = $description = '';
                    } else {
                        $error = 'Failed to submit complaint. Please try again.';
                    }
                    } // End file pre-validation check
                } // Close input validation else
            } // Close the daily limit check

        } // End empty($error) check
    } // End validation check

}


include '../includes/header.php';
include '../includes/navbar.php';
?>
<?php if (function_exists('loadRecaptchaScript')) {
    echo loadRecaptchaScript();
} ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Submit New Complaint
            </div>
            <div class="card-body">
                <!-- Daily Limit Warning -->
        <?php if ($can_submit): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                <strong>Daily Limit:</strong> You can submit <strong><?php echo $remaining; ?></strong> more complaint(s) today. 
                (<?php echo $complaints_today; ?>/<?php echo DAILY_COMPLAINT_LIMIT; ?> used)
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>Daily Limit Reached!</strong> You have submitted <?php echo $complaints_today; ?> complaint(s) today. 
                Please try again tomorrow.
            </div>
        <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" data-recaptcha="complaint_submit" <?php echo !$can_submit ? 'style="pointer-events: none; opacity: 0.6;"' : ''; ?>>
                <?php formProtection(); ?>    
                <div class="mb-3">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_id" name="category_id" required <?php echo !$can_submit ? 'disabled' : ''; ?>>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select the category that best describes your complaint</small>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               placeholder="Brief summary of your complaint" 
                               value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" 
                               minlength="5" required <?php echo !$can_submit ? 'disabled' : ''; ?>>
                        <small class="text-muted">Minimum 5 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="6" 
                                  placeholder="Provide detailed information about your complaint..." 
                                  minlength="20" required <?php echo !$can_submit ? 'disabled' : ''; ?>><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Minimum 20 characters</small>
                            <small id="charCount" class="text-muted">0</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                        <select class="form-select" id="priority" name="priority" required <?php echo !$can_submit ? 'disabled' : ''; ?>>
                            <option value="Low" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'Low') ? 'selected' : ''; ?>>
                                Low - Not urgent
                            </option>
                            <option value="Medium" <?php echo (!isset($_POST['priority']) || $_POST['priority'] == 'Medium') ? 'selected' : ''; ?>>
                                Medium - Normal priority
                            </option>
                            <option value="High" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'High') ? 'selected' : ''; ?>>
                                High - Urgent
                            </option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="attachments" class="form-label">
                            <i class="bi bi-paperclip"></i> Attachments (Optional)
                        </label>
                        <input type="file" class="form-control" id="attachments" name="attachments[]" 
                               multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.mp4,.mpeg,.mov,.avi,.webm"
                               <?php echo !$can_submit ? 'disabled' : ''; ?>>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> You can upload multiple files:<br>
                            • <strong>Images:</strong> JPG, PNG, GIF (Max 5MB)<br>
                            • <strong>Videos:</strong> MP4, MPEG, MOV, AVI, WEBM (Max 50MB)<br>
                            • <strong>Documents:</strong> PDF, DOC, DOCX (Max 5MB)
                        </small>
                        <div id="fileList"></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" id="submitBtn" class="btn btn-primary" <?php echo !$can_submit ? 'disabled' : ''; ?>>
                            <i class="bi bi-send"></i> Submit Complaint
                        </button>
                        <button type="reset" class="btn btn-outline-secondary" <?php echo !$can_submit ? 'disabled' : ''; ?>>
                            <i class="bi bi-x-circle"></i> Clear Form
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary ms-auto">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                    <?php if (isRecaptchaConfigured()) {
                        echo displayRecaptchaBadge();
                    } ?>
                </form>
            </div>
        </div>

        <!-- Tips Card -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <i class="bi bi-lightbulb"></i> Tips for Submitting a Complaint
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Be specific and clear about your issue</li>
                    <li>Include relevant dates, times, and reference numbers if applicable</li>
                    <li>Choose the correct category for faster processing</li>
                    <li>Set appropriate priority level</li>
                    <li>Attach screenshots or documents as evidence if available</li>
                    <li>Provide contact information if additional details are needed</li>
                </ul>
            </div>
        </div>
    </div>
</div>

</div> <!-- End page-content -->

<script>
    // ─── Size limits (must match PHP) ────────────────────────────────────────
    const MAX_VIDEO_MB    = 50;   // videos
    const MAX_DEFAULT_MB  = 5;    // images & documents
    const MAX_VIDEO_BYTES = MAX_VIDEO_MB   * 1024 * 1024;
    const MAX_DEFAULT_BYTES = MAX_DEFAULT_MB * 1024 * 1024;

    // ─── Character counter ────────────────────────────────────────────────────
    const description = document.getElementById('description');
    const charCount   = document.getElementById('charCount');

    description.addEventListener('input', function () {
        charCount.textContent = this.value.length;
        charCount.classList.toggle('text-danger',  this.value.length < 20);
        charCount.classList.toggle('text-success', this.value.length >= 20);
    });
    description.dispatchEvent(new Event('input'));

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function getFileLimitBytes(file) {
        return file.type.startsWith('video/') ? MAX_VIDEO_BYTES : MAX_DEFAULT_BYTES;
    }
    function getFileLimitLabel(file) {
        return file.type.startsWith('video/') ? `${MAX_VIDEO_MB}MB` : `${MAX_DEFAULT_MB}MB`;
    }
    function isFileOversized(file) {
        return file.size > getFileLimitBytes(file);
    }

    // ─── File input change handler ────────────────────────────────────────────
    document.getElementById('attachments').addEventListener('change', function () {
        const fileList = document.getElementById('fileList');
        fileList.innerHTML = '';

        if (!this.files.length) return;

      

        const container = document.createElement('div');
        container.className = 'mt-3';

        Array.from(this.files).forEach((file, index) => {
            const fileSizeMB  = (file.size / 1024 / 1024).toFixed(2);
            const isVideo     = file.type.startsWith('video/');
            const isImage     = file.type.startsWith('image/');
            const oversizeFile = isFileOversized(file);
            const limitLabel  = getFileLimitLabel(file);

            const fileCard = document.createElement('div');
            fileCard.className = `card mb-3 ${oversizeFile ? 'border-danger' : ''}`;
            fileCard.dataset.oversized = oversizeFile ? '1' : '0';
            fileCard.innerHTML = `
                <div class="card-body ${oversizeFile ? 'bg-danger bg-opacity-10' : ''}">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-${isVideo ? 'camera-video' : isImage ? 'image' : 'file-earmark'} fs-3 me-3
                                   text-${oversizeFile ? 'danger' : 'primary'}"></i>
                                <div>
                                    <strong>${file.name}</strong><br>
                                    <small class="text-${oversizeFile ? 'danger fw-semibold' : 'muted'}">
                                        ${fileSizeMB} MB &nbsp;|&nbsp; ${file.type || 'Unknown type'}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5 text-end">
                            ${oversizeFile
                                ? `<span class="badge bg-danger fs-6">
                                       <i class="bi bi-x-circle me-1"></i>Too Large (max ${limitLabel})
                                   </span>
                                  `
                                : `<span class="badge bg-success">
                                       <i class="bi bi-check-circle me-1"></i>Ready to Upload
                                   </span>`
                            }
                        </div>
                    </div>

                    ${!oversizeFile ? `
                    <!-- Preview Area -->
                    <div class="preview-area mt-3" id="preview-${index}"></div>
                    <!-- Progress Bar (hidden until submit) -->
                    <div class="progress mt-3" style="display:none;" id="progress-${index}">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width:0%">0%</div>
                    </div>` : ''}
                </div>`;

            container.appendChild(fileCard);

            // Preview (only for valid-size files)
            if (!oversizeFile) {
                const previewArea = fileCard.querySelector(`#preview-${index}`);
                if (isImage || isVideo) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewArea.innerHTML = isImage
                            ? `<div class="text-center">
                                   <img src="${e.target.result}" class="img-fluid rounded"
                                        style="max-height:200px;max-width:100%;" alt="Preview">
                               </div>`
                            : `<div class="text-center">
                                   <video controls class="rounded" style="max-height:300px;max-width:100%;">
                                       <source src="${e.target.result}" type="${file.type}">
                                       Your browser does not support video preview.
                                   </video>
                                   <div class="mt-1">
                                       <small class="text-muted">
                                           <i class="bi bi-play-circle"></i> Preview before submitting
                                       </small>
                                   </div>
                               </div>`;
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        fileList.appendChild(container);
    });

    // ─── Shared file-check logic (used by both click and submit handlers) ───────
    function checkOversizedFiles(e) {
        const form  = document.querySelector('form[enctype="multipart/form-data"]');
        const input = document.getElementById('attachments');
        if (!input || !input.files.length) return true; // no files – allow

        const files      = Array.from(input.files);
        const validFiles = files.filter(f => !isFileOversized(f));
        const badFiles   = files.filter(f =>  isFileOversized(f));

        // Remove any previous client-side error
        const prev = document.getElementById('clientSizeError');
        if (prev) prev.remove();

        // Block if ALL files are oversized
        if (validFiles.length === 0) {
            if (e) e.preventDefault();
            const alertEl = document.createElement('div');
            alertEl.id        = 'clientSizeError';
            alertEl.className = 'alert alert-danger mt-3';
            alertEl.innerHTML = `
                <i class="bi bi-x-octagon-fill me-1"></i>
                <strong>Upload blocked!</strong> All selected files exceed the allowed size limit.
                Please remove them or choose smaller files before submitting.`;
            form.prepend(alertEl);
            alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false; // blocked
        }

        // Warn if SOME files are oversized
        if (badFiles.length) {
            const confirmed = confirm(
                `⚠️ ${badFiles.length} file(s) exceed the size limit and will NOT be uploaded:\n\n` +
                badFiles.map(f => `• ${f.name} (${(f.size/1024/1024).toFixed(2)} MB)`).join('\n') +
                `\n\nThe remaining ${validFiles.length} file(s) will still be submitted. Continue?`
            );
            if (!confirmed) { if (e) e.preventDefault(); return false; }
        }

        return true; // passed
    }

    // ─── Button CLICK handler — fires BEFORE reCAPTCHA intercepts the submit ──
    // This is the key fix: reCAPTCHA listens on the button click and submits the
    // form programmatically, bypassing the form 'submit' event. We must intercept
    // at the click level first.
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            if (!checkOversizedFiles(e)) {
                e.stopImmediatePropagation(); // prevent reCAPTCHA from firing
                return;
            }

            // Animate progress bars for valid files only
            document.querySelectorAll('.progress').forEach(bar => {
                bar.style.display = 'block';
                const inner = bar.querySelector('.progress-bar');
                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 95) progress = 95;
                    inner.style.width = progress + '%';
                    inner.textContent = Math.round(progress) + '%';
                    if (progress >= 95) clearInterval(interval);
                }, 200);
            });
        }, true); // useCapture=true so this runs before reCAPTCHA's listener
    }

    // ─── Form submit fallback (catches programmatic submits) ─────────────────
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    if (form) {
        form.addEventListener('submit', function (e) {
            checkOversizedFiles(e);
        });
    }
</script>
<?php include '../includes/footer.php'; ?>