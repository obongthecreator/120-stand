<?php
/**
 * Financial Summary Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = 'Financial Summary - 120 Stand Inventory';
$current_user = Stand120_Auth::get_current_user_data();
$is_admin = Stand120_Auth::is_admin();
$today = date('Y-m-d');

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
        <iconify-icon icon="solar:wallet-linear"></iconify-icon>
        Financial Summary
    </h1>
    <div style="display: flex; gap: 12px; align-items: center;">
        <input type="date" id="finDate" class="form-control" value="<?php echo $today; ?>" style="max-width: 200px;">
        <a href="<?php echo home_url('/120-stand/financial-summary-history/'); ?>" class="history-btn">
            <iconify-icon icon="solar:history-linear"></iconify-icon> View History
        </a>
    </div>
</div>

<div class="glass-card">
    <h3 style="margin-bottom: 20px; color: var(--primary-color);">
        <iconify-icon icon="solar:calculator-linear"></iconify-icon> Daily Financial Report
    </h3>
    
    <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.9rem;">
        <iconify-icon icon="solar:info-circle-linear"></iconify-icon> 
        Cash Left = (Cash Sales + Old Cash + Extras + Market Card Cash) - Expenses. 
        Only Extras, Expenses, and Market Card Cash fields are editable. Values auto-save.
    </p>
    
    <div class="table-responsive">
        <table class="table">
            <tbody>
                <tr>
                    <td style="font-weight: 600; width: 40%;">
                        <iconify-icon icon="solar:graph-up-linear" style="color: var(--primary-color);"></iconify-icon>
                        Total Sales
                    </td>
                    <td id="totalSales" class="formatted-number" style="font-size: 1.2rem;"><span class="naira">₦</span>0</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:transfer-horizontal-linear" style="color: var(--info-color);"></iconify-icon>
                        Transfer/Card Sales
                    </td>
                    <td id="transferSales" class="formatted-number"><span class="naira">₦</span>0</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:banknote-2-linear" style="color: var(--success-color);"></iconify-icon>
                        Cash Sales
                    </td>
                    <td id="cashSales" class="formatted-number"><span class="naira">₦</span>0</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:scooter-linear" style="color: var(--warning-color);"></iconify-icon>
                        Delivery Fees
                    </td>
                    <td id="deliveryFees" class="formatted-number"><span class="naira">₦</span>0</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:add-circle-linear" style="color: var(--success-color);"></iconify-icon>
                        Extras Amount (₦)
                    </td>
                    <td>
                        <input type="text" id="extrasAmount" class="table-input number-input" placeholder="0" style="max-width: 150px;">
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:chat-dots-linear" style="color: var(--text-muted);"></iconify-icon>
                        Extras Remark
                    </td>
                    <td>
                        <input type="text" id="extrasRemark" class="table-input" placeholder="Enter extras description..." style="max-width: 300px;">
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:card-linear" style="color: var(--info-color);"></iconify-icon>
                        Market Card Cash (₦)
                    </td>
                    <td>
                        <input type="text" id="marketCardCash" class="table-input number-input" placeholder="0" style="max-width: 150px;">
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:minus-circle-linear" style="color: var(--danger-color);"></iconify-icon>
                        Expenses Amount (₦)
                    </td>
                    <td>
                        <input type="text" id="expensesAmount" class="table-input number-input" placeholder="0" style="max-width: 150px;">
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:chat-dots-linear" style="color: var(--text-muted);"></iconify-icon>
                        Expenses Remark
                    </td>
                    <td>
                        <input type="text" id="expensesRemark" class="table-input" placeholder="Enter expenses description..." style="max-width: 300px;">
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">
                        <iconify-icon icon="solar:clock-circle-linear" style="color: var(--text-muted);"></iconify-icon>
                        Old Cash (Yesterday's Cash Left)
                    </td>
                    <td id="oldCash" class="formatted-number"><span class="naira">₦</span>0</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Cash Left Highlight -->
    <div class="grand-total-section" style="margin-top: 24px;">
        <span class="grand-total-label">
            <iconify-icon icon="solar:cash-out-linear"></iconify-icon> Cash Left
        </span>
        <span id="cashLeft" class="grand-total-value"><span class="naira">₦</span>0</span>
    </div>
    
    <p style="color: var(--text-muted); margin-top: 16px; font-size: 0.85rem; text-align: center;">
        <iconify-icon icon="solar:info-circle-linear"></iconify-icon> 
        Formula: Cash Left = (Cash Sales + Old Cash + Extras + Market Card Cash) - Expenses
    </p>
</div>

<script>
    $(document).ready(function() {
        if (typeof FinancialSummary !== 'undefined') {
            FinancialSummary.init();
            
            // Reload data when date changes
            $('#finDate').on('change', function() {
                FinancialSummary.loadData();
            });
        }
    });
</script>

<?php include STAND120_PLUGIN_DIR . 'templates/partials/footer.php'; ?>
