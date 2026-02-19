<?php
// ============================================
// GLOBAL AUTO-REFRESH CHECKER (For All Pages)
// includes/global_auto_refresh.php
// ============================================

if (!isset($_SESSION['user_id'])) {
    return; // Not logged in, skip
}

$current_user_id = $_SESSION['user_id'];
$is_user = !isAdmin();
$is_admin = isAdmin();
?>

<!-- Global Auto-Refresh Script -->
<script>
const GLOBAL_AUTO_REFRESH = {
    userId: <?php echo $current_user_id; ?>,
    isUser: <?php echo $is_user ? 'true' : 'false'; ?>,
    isAdmin: <?php echo $is_admin ? 'true' : 'false'; ?>,
    checkInterval: null,
    lastNotificationCount: 0,
    lastComplaintCounts: {},
    isChecking: false,
    
    // Initialize
    init() {
        this.createIndicator();
        this.getInitialCounts();
        this.startChecking();
        this.setupDropdownTrigger();
        this.startActivityTracking();
        this.startOnlineUserTracking();
        
        // Check when tab becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkForUpdates();
                this.updateActivity();
            }
        });

        
    },

    // Quick check when notification dropdown is clicked
setupDropdownTrigger() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        dropdown.addEventListener('show.bs.dropdown', () => {
            // Immediately check for updates when dropdown opens
            this.checkForUpdates();
        });
    }
},
    
    // Create floating indicator
    createIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'globalAutoRefreshIndicator';
        indicator.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(102, 126, 234, 0.95);
            color: white;
            padding: 10px 16px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: none;
            align-items: center;
            gap: 8px;
            z-index: 9999;
            font-size: 13px;
            font-weight: 500;
        `;
        indicator.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Checking...';
        document.body.appendChild(indicator);
        
        // Add animations
        const styles = document.createElement('style');
        styles.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            @keyframes slideInUp {
                from { transform: translateY(100px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        `;
        document.head.appendChild(styles);
    },
    
    // Show/hide indicator
    showIndicator(show = true) {
        const indicator = document.getElementById('globalAutoRefreshIndicator');
        if (indicator) {
            indicator.style.display = show ? 'flex' : 'none';
        }
    },
    
    // Get initial counts
    async getInitialCounts() {
        try {
            const endpoint = this.isUser ? '../user/global_check.php' : '../admin/global_check.php';
            const response = await fetch(endpoint);
            const data = await response.json();
            
            if (data.success) {
                this.lastNotificationCount = data.notification_count || 0;
                this.lastComplaintCounts = data.complaint_counts || {};
            }
        } catch (error) {
            console.error('Error getting initial counts:', error);
        }
    },
    
    // Start periodic checking
    startChecking() {
        // Check every 60 seconds specifically for notifications
this.checkInterval = setInterval(() => {
    this.checkForUpdates();
}, 60000); // 60 seconds = 60000 milliseconds

// Also check immediately when dropdown is opened
const notificationDropdown = document.getElementById('notificationDropdown');
if (notificationDropdown) {
    notificationDropdown.addEventListener('click', () => {
        // Quick check when user opens notification dropdown
        setTimeout(() => {
            this.checkForUpdates();
        }, 500);
    });
}
    },
    
    // Check for updates
    async checkForUpdates() {
        if (this.isChecking) return;
        
        this.isChecking = true;
        this.showIndicator(true);
        
        try {
            const endpoint = this.isUser ? '../user/global_check.php' : '../admin/global_check.php';
            const response = await fetch(endpoint);
            const data = await response.json();
            
            if (data.success) {
                // Check notifications
                if (data.notification_count > this.lastNotificationCount) {
                    this.handleNewNotifications(data.notification_count - this.lastNotificationCount);
                    this.lastNotificationCount = data.notification_count;
                }
                
                // Check complaint status changes
                if (data.complaint_counts) {
                    this.checkComplaintChanges(data.complaint_counts);
                }
                
                // Update notification badge in navbar
                this.updateNotificationBadge(data.notification_count);
            }
        } catch (error) {
            console.error('Error checking updates:', error);
        } finally {
            this.isChecking = false;
            this.showIndicator(false);
        }
    },
    
    // Handle new notifications
    handleNewNotifications(count) {
        this.showToast(`You have ${count} new notification(s)!`, 'info');
        
        // Play sound (optional)
        // this.playNotificationSound();
    },
    
    // Check complaint changes
    checkComplaintChanges(newCounts) {
        const oldCounts = this.lastComplaintCounts;
        
        // For users: check if any complaint status changed
        if (this.isUser) {
            if (newCounts.in_progress > (oldCounts.in_progress || 0)) {
                this.showToast('A complaint status was updated to In Progress!', 'info');
            }
            if (newCounts.resolved > (oldCounts.resolved || 0)) {
                this.showToast('A complaint was resolved!', 'success');
            }
        }
        
        // For admins: check new complaints
        if (this.isAdmin) {
            if (newCounts.pending > (oldCounts.pending || 0)) {
                const newCount = newCounts.pending - (oldCounts.pending || 0);
                this.showToast(`${newCount} new complaint(s) submitted!`, 'warning');
            }
        }
        
        this.lastComplaintCounts = newCounts;
    },
    
  // Update notification badge (ENHANCED for your navbar structure)
updateNotificationBadge(count) {
    // Target the specific badge in your navbar
    const badge = document.querySelector('#notificationDropdown .badge.bg-danger');
    const bellIcon = document.querySelector('#notificationDropdown i.bi-bell-fill');
    
    if (count > 0) {
        if (badge) {
            // Badge exists, update it
            const oldCount = parseInt(badge.textContent.replace('+', '')) || 0;
            const newCount = parseInt(count) || 0;
            
            badge.textContent = newCount > 9 ? '9+' : newCount;
            badge.style.display = 'inline-block';
            
            // If count increased, animate
            if (newCount > oldCount) {
                // Pulse badge
                badge.style.animation = 'none';
                setTimeout(() => {
                    badge.style.animation = 'notificationPulse 0.6s ease 3';
                }, 10);
                
                // Shake bell
                if (bellIcon) {
                    bellIcon.style.animation = 'bellShake 0.5s ease';
                    setTimeout(() => {
                        bellIcon.style.animation = '';
                    }, 500);
                }
                
                // Play sound
                this.playNotificationSound();
            }
        } else {
            // Badge doesn't exist, create it
            const notifButton = document.querySelector('#notificationDropdown');
            if (notifButton) {
                const newBadge = document.createElement('span');
                newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                newBadge.textContent = count > 9 ? '9+' : count;
                newBadge.style.animation = 'notificationPulse 0.6s ease 3';
                notifButton.appendChild(newBadge);
                
                // Shake bell
                if (bellIcon) {
                    bellIcon.style.animation = 'bellShake 0.5s ease';
                    setTimeout(() => {
                        bellIcon.style.animation = '';
                    }, 500);
                }
                
                // Play sound
                this.playNotificationSound();
            }
        }
    } else {
        // No notifications, hide badge
        if (badge) {
            badge.style.display = 'none';
        }
    }
    
    // Update page title
    this.updatePageTitle(count);
    
    // Update dropdown header count
    this.updateDropdownHeader(count);
},

// Update dropdown header
updateDropdownHeader(count) {
    const dropdownHeader = document.querySelector('.notification-dropdown .dropdown-header strong');
    if (dropdownHeader) {
        if (count > 0) {
            dropdownHeader.innerHTML = `Notifications <span class="badge bg-danger ms-1">${count}</span>`;
        } else {
            dropdownHeader.textContent = 'Notifications';
        }
    }
},

// Update page title with notification count
updatePageTitle(count) {
    const originalTitle = document.title.replace(/^\(\d+\)\s*/, '');
    if (count > 0) {
        document.title = `(${count}) ${originalTitle}`;
    } else {
        document.title = originalTitle;
    }
},

// Play notification sound
playNotificationSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.15);
    } catch (error) {
        // Silently fail if audio not supported
    }
},
    
    // Show toast notification
    showToast(message, type = 'info') {
        const colors = {
            success: '#28a745',
            info: '#17a2b8',
            warning: '#ffc107',
            danger: '#dc3545'
        };
        
        const icons = {
            success: 'check-circle-fill',
            info: 'info-circle-fill',
            warning: 'exclamation-triangle-fill',
            danger: 'x-circle-fill'
        };
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${colors[type]};
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 10000;
            min-width: 320px;
            max-width: 400px;
            animation: slideInUp 0.3s ease;
            cursor: pointer;
        `;
        
        toast.innerHTML = `
            <div style="display: flex; align-items: start; gap: 12px;">
                <i class="bi bi-${icons[type]}" style="font-size: 1.5rem; margin-top: 2px;"></i>
                <div style="flex: 1;">
                    <strong style="display: block; margin-bottom: 4px;">
                        ${type === 'success' ? '✓ Success' : type === 'info' ? 'ℹ Info' : type === 'warning' ? '⚠ Alert' : '✕ Error'}
                    </strong>
                    <div style="font-size: 14px; opacity: 0.95;">${message}</div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer; padding: 0; line-height: 1;">×</button>
            </div>
        `;
        
        toast.onclick = () => toast.remove();
        document.body.appendChild(toast);
        
        // Auto remove after 6 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(400px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 6000);
    },
    
    // Stop checking
    stop() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
    },

    // Track user activity - update every 2 minutes
startActivityTracking() {
    // Update immediately
    this.updateActivity();
    
    // Then update every 2 minutes
    setInterval(() => {
        this.updateActivity();
    }, 120000); // 2 minutes
},

// Update user's last activity
async updateActivity() {
    try {
        const siteUrl = '<?php echo SITE_URL; ?>';
        await fetch(siteUrl + 'includes/update_activity.php');
    } catch (error) {
        console.error('Error updating activity:', error);
    }
},

// Track online users - check every 30 seconds
startOnlineUserTracking() {
    // Get initial online users
    this.getOnlineUsers();
    
    // Update every 30 seconds
    setInterval(() => {
        this.getOnlineUsers();
    }, 30000); // 30 seconds
},

// Get list of online users
async getOnlineUsers() {
    try {
        const siteUrl = '<?php echo SITE_URL; ?>';
        const response = await fetch(siteUrl + 'includes/get_online_users.php');
        const data = await response.json();
        
        if (data.success) {
            this.updateOnlineUserDisplay(data.online_users, data.total_online);
        }
    } catch (error) {
        console.error('Error getting online users:', error);
    }
},

// Update online user display in sidebar
updateOnlineUserDisplay(users, total) {
    // Update count badge
    const onlineBadge = document.getElementById('onlineUserCount');
    if (onlineBadge) {
        onlineBadge.textContent = total;
        onlineBadge.style.animation = total > 0 ? 'pulse 0.5s ease' : '';
    }
    
    // Update user list
    const userList = document.getElementById('onlineUserList');
    if (userList) {
        if (users.length === 0) {
            userList.innerHTML = `
                <div class="text-center py-3">
                    <i class="bi bi-person-slash" style="font-size: 2rem; color: #999;"></i>
                    <p class="text-muted mb-0 mt-2">No one online</p>
                </div>
            `;
        } else {
            userList.innerHTML = users.map(user => this.renderOnlineUser(user)).join('');
        }
    }
},

// Render single online user item
renderOnlineUser(user) {
    const timeAgo = user.minutes_ago === 0 ? 'Just now' : 
                    user.minutes_ago === 1 ? '1 minute ago' : 
                    `${user.minutes_ago} minutes ago`;
    
    const avatar = user.profile_picture 
        ? `<img src="<?php echo SITE_URL; ?>${user.profile_picture}" class="rounded-circle" width="35" height="35" style="object-fit: cover;">`
        : `<div class="user-avatar" style="width: 35px; height: 35px; font-size: 0.9rem;">${user.full_name.charAt(0).toUpperCase()}</div>`;
    
    const roleBadge = user.role === 'admin' 
        ? (user.admin_level === 'super_admin' 
            ? '<span class="badge bg-danger" style="font-size: 0.65rem;">Super Admin</span>'
            : '<span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Admin</span>')
        : '<span class="badge bg-info" style="font-size: 0.65rem;">User</span>';
    
    return `
        <div class="online-user-item d-flex align-items-center gap-2 p-2 mb-2" style="background: rgba(40, 167, 69, 0.1); border-radius: 8px; border-left: 3px solid #28a745;">
            <div class="position-relative">
                ${avatar}
                <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle" 
                      style="width: 12px; height: 12px;"></span>
            </div>
            <div class="flex-grow-1" style="min-width: 0;">
                <div class="d-flex align-items-center gap-2">
                    <strong style="font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${user.full_name}</strong>
                    ${roleBadge}
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">
                    <i class="bi bi-clock"></i> ${timeAgo}
                </small>
            </div>
        </div>
    `;
}


};


// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    GLOBAL_AUTO_REFRESH.init();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    GLOBAL_AUTO_REFRESH.stop();
});


</script>