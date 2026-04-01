<?php
/**
 * Reconciliation Calendar Page Template
 * Admin-only page where 2 admins verify daily records
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = 'Reconciliation Calendar - 120 Stand Inventory';
$current_user = Stand120_Auth::get_current_user_data();
$is_admin = Stand120_Auth::is_admin();

if (!$is_admin) {
    wp_redirect(home_url('/120-stand/'));
    exit;
}

include STAND120_PLUGIN_DIR . 'templates/partials/header.php';
?>

<!-- Staff Info Bar -->
<div class="staff-info-bar">
    <div class="staff-info">
        <div class="staff-avatar">
            <?php echo strtoupper(substr($current_user['display_name'], 0, 1)); ?>
        </div>
        <div class="staff-details">
            <h4><?php echo esc_html($current_user['display_name']); ?></h4>
            <span><?php echo ucfirst($current_user['role']); ?></span>
        </div>
    </div>
    <div class="datetime-display">
        <div class="date-display">
            <iconify-icon icon="solar:calendar-linear"></iconify-icon>
            <span class="date-text"><?php echo date_i18n('l, F j, Y'); ?></span>
        </div>
        <div class="time-display">
            <iconify-icon icon="solar:clock-circle-linear"></iconify-icon>
            <span class="digital-clock">--:--:--</span>
        </div>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <h1 class="page-title" style="margin-bottom: 0;">
        <iconify-icon icon="solar:calendar-check-linear"></iconify-icon>
        Reconciliation Calendar
    </h1>
</div>

<p style="color: var(--text-muted); margin-bottom: 24px; font-size: 0.9rem;">
    <iconify-icon icon="solar:info-circle-linear"></iconify-icon>
    Two admins must verify each day's records. Click on a date to mark it as reviewed. Once both admins have checked, the date is locked.
</p>

<!-- Calendar Navigation -->
<div class="glass-card" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <button id="prevMonth" class="btn btn-primary" style="padding: 8px 16px;">
            <iconify-icon icon="solar:alt-arrow-left-linear"></iconify-icon> Previous
        </button>
        <h3 id="calendarTitle" style="color: var(--primary-color); margin: 0;"></h3>
        <button id="nextMonth" class="btn btn-primary" style="padding: 8px 16px;">
            Next <iconify-icon icon="solar:alt-arrow-right-linear"></iconify-icon>
        </button>
    </div>
</div>

<!-- Legend -->
<div style="display: flex; gap: 24px; margin-bottom: 16px; flex-wrap: wrap; font-size: 0.85rem; color: var(--text-muted);">
    <span><span class="recon-legend-dot" style="background: var(--text-muted);"></span> Not checked</span>
    <span><span class="recon-legend-dot" style="background: #f0ad4e;"></span> 1 admin checked</span>
    <span><span class="recon-legend-dot" style="background: var(--success-color);"></span> Fully verified (2 admins)</span>
</div>

<!-- Calendar Grid -->
<div class="glass-card">
    <div class="recon-calendar-grid">
        <div class="recon-calendar-header">Sun</div>
        <div class="recon-calendar-header">Mon</div>
        <div class="recon-calendar-header">Tue</div>
        <div class="recon-calendar-header">Wed</div>
        <div class="recon-calendar-header">Thu</div>
        <div class="recon-calendar-header">Fri</div>
        <div class="recon-calendar-header">Sat</div>
    </div>
    <div id="calendarBody" class="recon-calendar-grid">
        <!-- Calendar days rendered via JavaScript -->
    </div>
</div>

<!-- Reconciliation Modal -->
<div id="reconModal" class="recon-modal-overlay" style="display: none;">
    <div class="recon-modal glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="color: var(--primary-color); margin: 0;">
                <iconify-icon icon="solar:calendar-check-linear"></iconify-icon>
                Verify Date: <span id="reconModalDate"></span>
            </h3>
            <button id="closeReconModal" class="btn" style="background: transparent; font-size: 1.5rem; padding: 0 8px; color: var(--text-muted);">&times;</button>
        </div>
        
        <!-- Current Status -->
        <div id="reconStatus" style="margin-bottom: 16px;"></div>
        
        <!-- Remarks Field -->
        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label">
                <iconify-icon icon="solar:document-text-linear"></iconify-icon> Remarks (optional)
            </label>
            <textarea id="reconRemarks" class="form-control" rows="3" placeholder="Add any remarks about the records for this date..." style="resize: vertical;"></textarea>
        </div>
        
        <!-- Submit Button -->
        <div style="text-align: center;">
            <button id="submitReconCheck" class="btn btn-primary btn-lg">
                <iconify-icon icon="solar:check-circle-linear"></iconify-icon> Confirm Verification
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth() + 1; // 1-indexed
    let reconData = {};
    let selectedDate = null;
    const currentStaffId = <?php echo json_encode($current_user['staff_id']); ?>;
    const todayStr = '<?php echo date('Y-m-d'); ?>';
    
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                         'July', 'August', 'September', 'October', 'November', 'December'];
    
    function loadCalendar() {
        $('#calendarTitle').text(monthNames[currentMonth - 1] + ' ' + currentYear);
        
        Stand120.ajax('get_reconciliation_data', {
            year: currentYear,
            month: currentMonth
        }).then(response => {
            if (response.success) {
                reconData = response.data.data || {};
                renderCalendar();
            }
        });
    }
    
    function renderCalendar() {
        const $body = $('#calendarBody').empty();
        
        // Get first day of month and total days
        const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
        
        // Add empty cells for days before the 1st
        for (let i = 0; i < firstDay; i++) {
            $body.append('<div class="recon-calendar-day recon-day-empty"></div>');
        }
        
        // Add days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = currentYear + '-' + String(currentMonth).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            const record = reconData[dateStr];
            const isFuture = dateStr > todayStr;
            
            let statusClass = 'recon-day-unchecked';
            let checkmarks = '';
            let isClickable = !isFuture;
            
            if (record) {
                const hasAdmin1 = !!record.admin1_staff_id;
                const hasAdmin2 = !!record.admin2_staff_id;
                const currentAdminChecked = (record.admin1_staff_id == currentStaffId) || (record.admin2_staff_id == currentStaffId);
                
                if (hasAdmin1 && hasAdmin2) {
                    statusClass = 'recon-day-full';
                    isClickable = false; // Fully verified - locked
                    checkmarks = '<div class="recon-checks"><iconify-icon icon="solar:check-circle-bold" style="color: var(--success-color);"></iconify-icon><iconify-icon icon="solar:check-circle-bold" style="color: var(--success-color);"></iconify-icon></div>';
                } else if (hasAdmin1) {
                    statusClass = 'recon-day-partial';
                    if (currentAdminChecked) {
                        isClickable = false; // This admin already checked
                    }
                    checkmarks = '<div class="recon-checks"><iconify-icon icon="solar:check-circle-bold" style="color: #f0ad4e;"></iconify-icon></div>';
                }
            }
            
            if (isFuture) {
                statusClass = 'recon-day-future';
            }
            
            const dataAttr = isClickable ? `data-date="${dateStr}"` : '';
            const cursorClass = isClickable ? 'recon-day-clickable' : '';
            
            $body.append(`
                <div class="recon-calendar-day ${statusClass} ${cursorClass}" ${dataAttr}>
                    <span class="recon-day-number">${day}</span>
                    ${checkmarks}
                </div>
            `);
        }
    }
    
    function openReconModal(dateStr) {
        selectedDate = dateStr;
        const record = reconData[dateStr];
        
        $('#reconModalDate').text(dateStr);
        $('#reconRemarks').val('');
        
        // Show current status
        let statusHtml = '';
        if (record) {
            if (record.admin1_name) {
                statusHtml += '<div style="padding: 8px 12px; background: rgba(40, 167, 69, 0.1); border-radius: 8px; margin-bottom: 8px;">';
                statusHtml += '<strong style="color: var(--success-color);"><iconify-icon icon="solar:check-circle-bold"></iconify-icon> ' + record.admin1_name + '</strong>';
                statusHtml += '<br><small style="color: var(--text-muted);">Checked: ' + record.admin1_checked_at + '</small>';
                if (record.admin1_remarks) {
                    statusHtml += '<br><small>Remarks: ' + record.admin1_remarks + '</small>';
                }
                statusHtml += '</div>';
            }
            if (record.admin2_name) {
                statusHtml += '<div style="padding: 8px 12px; background: rgba(40, 167, 69, 0.1); border-radius: 8px; margin-bottom: 8px;">';
                statusHtml += '<strong style="color: var(--success-color);"><iconify-icon icon="solar:check-circle-bold"></iconify-icon> ' + record.admin2_name + '</strong>';
                statusHtml += '<br><small style="color: var(--text-muted);">Checked: ' + record.admin2_checked_at + '</small>';
                if (record.admin2_remarks) {
                    statusHtml += '<br><small>Remarks: ' + record.admin2_remarks + '</small>';
                }
                statusHtml += '</div>';
            }
        }
        
        if (!statusHtml) {
            statusHtml = '<p style="color: var(--text-muted); font-size: 0.9rem;">No admin has verified this date yet. You will be the first.</p>';
        }
        
        $('#reconStatus').html(statusHtml);
        $('#reconModal').fadeIn(200);
    }
    
    // Event delegation for clickable calendar days
    $('#calendarBody').on('click', '.recon-day-clickable[data-date]', function() {
        const dateStr = $(this).data('date');
        if (dateStr) {
            openReconModal(dateStr);
        }
    });
    
    // Close modal
    $('#closeReconModal').on('click', function() {
        $('#reconModal').fadeOut(200);
        selectedDate = null;
    });
    
    // Close modal on overlay click
    $('#reconModal').on('click', function(e) {
        if ($(e.target).hasClass('recon-modal-overlay')) {
            $('#reconModal').fadeOut(200);
            selectedDate = null;
        }
    });
    
    // Submit verification
    $('#submitReconCheck').on('click', function() {
        if (!selectedDate) return;
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Verifying...');
        
        Stand120.ajax('save_reconciliation_check', {
            date: selectedDate,
            remarks: $('#reconRemarks').val()
        }).then(response => {
            if (response.success) {
                Stand120.showAlert('success', response.data.message || 'Date verified successfully!');
                $('#reconModal').fadeOut(200);
                selectedDate = null;
                loadCalendar(); // Reload data
            } else {
                Stand120.showAlert('danger', response.data?.message || 'Failed to verify date.');
            }
        }).catch(() => {
            Stand120.showAlert('danger', 'An error occurred. Please try again.');
        }).finally(() => {
            $btn.prop('disabled', false).html('<iconify-icon icon="solar:check-circle-linear"></iconify-icon> Confirm Verification');
        });
    });
    
    // Navigation
    $('#prevMonth').on('click', function() {
        currentMonth--;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }
        loadCalendar();
    });
    
    $('#nextMonth').on('click', function() {
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        loadCalendar();
    });
    
    // Initial load
    loadCalendar();
});
</script>

<?php include STAND120_PLUGIN_DIR . 'templates/partials/footer.php'; ?>
