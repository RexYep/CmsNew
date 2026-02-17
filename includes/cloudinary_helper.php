<?php
/**
 * CLOUDINARY HELPER FUNCTIONS
 * includes/cloudinary_helper.php
 *

 */

use Cloudinary\Api\Upload\UploadApi;

/**
 * Smart upload - decides local or Cloudinary based on APP_ENV
 */
function uploadToCloudinary($file, $folder = 'complaints') {
    // Check environment
    $is_production = getenv('APP_ENV') === 'production';

    if ($is_production) {
        return _uploadToCloudinaryCloud($file, $folder);
    } else {
        return _saveToLocal($file, $folder);
    }
}

/**
 * Smart delete - decides local or Cloudinary based on stored path
 */
function deleteFromCloudinary($public_id, $resource_type = 'image') {
    $is_production = getenv('APP_ENV') === 'production';

    if ($is_production) {
        return _deleteFromCloudinaryCloud($public_id, $resource_type);
    } else {
        return _deleteFromLocal($public_id);
    }
}

// ============================================
// LOCAL STORAGE (APP_ENV=local)
// ============================================

function _saveToLocal($file, $folder = 'complaints') {
    try {
        // Determine upload directory based on folder
        $upload_dir = defined('BASE_PATH')
            ? BASE_PATH . 'uploads/' . $folder . '/'
            : '../uploads/' . $folder . '/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $extension   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_name = $folder . '_' . time() . '_' . uniqid() . '.' . $extension;
        $file_path   = $upload_dir . $unique_name;

        // Determine resource type
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv'];

        if (in_array($extension, $imageExtensions)) {
            $resourceType = 'image';
        } elseif (in_array($extension, $videoExtensions)) {
            $resourceType = 'video';
        } else {
            $resourceType = 'raw';
        }

        // Move file to local uploads
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Store relative path as the "url" for local
            $relative_path = 'uploads/' . $folder . '/' . $unique_name;

            return [
                'success'       => true,
                'url'           => $relative_path,  // local relative path
                'public_id'     => $relative_path,  // same, used for deletion
                'resource_type' => $resourceType,
                'storage'       => 'local'
            ];
        } else {
            return [
                'success' => false,
                'error'   => 'Failed to move uploaded file to local storage'
            ];
        }
    } catch (Exception $e) {
        error_log("Local upload error: " . $e->getMessage());
        return [
            'success' => false,
            'error'   => $e->getMessage()
        ];
    }
}

function _deleteFromLocal($file_path) {
    try {
        $full_path = defined('BASE_PATH')
            ? BASE_PATH . $file_path
            : '../' . $file_path;

        if (file_exists($full_path)) {
            unlink($full_path);
        }

        return ['success' => true];
    } catch (Exception $e) {
        error_log("Local delete error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============================================
// CLOUDINARY STORAGE (APP_ENV=production)
// ============================================

function _uploadToCloudinaryCloud($file, $folder = 'complaints') {
    try {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error'   => 'Invalid file upload. Error code: ' . ($file['error'] ?? 'unknown')
            ];
        }

        if (!file_exists($file['tmp_name'])) {
            return [
                'success' => false,
                'error'   => 'Uploaded file not found'
            ];
        }

        $uploadApi = new UploadApi();

        // Determine resource type
        $extension       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', 'mpeg'];

        if (in_array($extension, $imageExtensions)) {
            $resourceType = 'image';
        } elseif (in_array($extension, $videoExtensions)) {
            $resourceType = 'video';
        } else {
            $resourceType = 'raw';
        }

        $result = $uploadApi->upload($file['tmp_name'], [
            'folder'          => $folder,
            'resource_type'   => $resourceType,
            'use_filename'    => true,
            'unique_filename' => true,
            'overwrite'       => false,
        ]);

        return [
            'success'       => true,
            'url'           => $result['secure_url'],
            'public_id'     => $result['public_id'],
            'resource_type' => $resourceType,
            'storage'       => 'cloudinary'
        ];

    } catch (Exception $e) {
        error_log("Cloudinary upload error: " . $e->getMessage());
        return [
            'success' => false,
            'error'   => 'Cloudinary upload failed: ' . $e->getMessage()
        ];
    }
}

function _deleteFromCloudinaryCloud($public_id, $resource_type = 'image') {
    try {
        if (empty($public_id)) {
            return ['success' => false, 'error' => 'No public_id provided'];
        }

        $uploadApi = new UploadApi();
        $result    = $uploadApi->destroy($public_id, [
            'resource_type' => $resource_type,
            'invalidate'    => true
        ]);

        return [
            'success' => ($result['result'] === 'ok' || $result['result'] === 'not found'),
            'result'  => $result
        ];

    } catch (Exception $e) {
        error_log("Cloudinary delete error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============================================
// DISPLAY HELPERS
// Works for both local paths and Cloudinary URLs
// ============================================

/**
 * Get displayable URL from stored path/url
 * Handles both local paths and Cloudinary URLs
 */
function getFileUrl($stored_url) {
    if (empty($stored_url)) return '';

    // Already a full Cloudinary or external URL
    if (strpos($stored_url, 'http') === 0) {
        return $stored_url;
    }

    // Local relative path - prepend SITE_URL
    $site_url = defined('SITE_URL') ? SITE_URL : 'http://localhost/cms1/';
    return rtrim($site_url, '/') . '/' . ltrim($stored_url, '/');
}

/**
 * Get optimized image URL
 * - Cloudinary URLs: applies transformations
 * - Local URLs: returns as-is (no optimization locally)
 */
function getOptimizedImageUrl($url, $width = null, $height = null, $crop = 'fill') {
    if (empty($url)) return '';

    $full_url = getFileUrl($url);

    // Only optimize Cloudinary URLs
    if (strpos($full_url, 'cloudinary.com') === false) {
        return $full_url;
    }

    $parts = explode('/upload/', $full_url);
    if (count($parts) !== 2) return $full_url;

    $params = [];
    if ($width)  $params[] = "w_$width";
    if ($height) $params[] = "h_$height";
    if ($width || $height) $params[] = "c_$crop";
    $params[] = 'q_auto';
    $params[] = 'f_auto';

    $transformation = 'upload/' . implode(',', $params) . '/';
    return $parts[0] . '/' . $transformation . $parts[1];
}

/**
 * Check if stored URL is a local file or Cloudinary
 */
function isCloudinaryUrl($url) {
    return strpos($url, 'cloudinary.com') !== false
        || strpos($url, 'http') === 0;
}

/**
 * Get video thumbnail
 */
function getVideoThumbnail($url, $width = 300, $height = 200) {
    if (empty($url)) return '';

    $full_url = getFileUrl($url);

    // Only works for Cloudinary videos
    if (strpos($full_url, 'cloudinary.com') === false) {
        return ''; // No thumbnail for local videos
    }

    $thumbnail_url = str_replace(
        '/video/upload/',
        "/image/upload/w_{$width},h_{$height},c_fill,q_auto,f_auto/",
        $full_url
    );

    return preg_replace('/\.[^.]+$/', '.jpg', $thumbnail_url);
}