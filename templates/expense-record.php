<?php
/**
 * Expense Record Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = 'Market Expense - 120 Stand Inventory';
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
        <iconify-icon icon="solar:document-text-linear"></iconify-icon>
        Market Expense
    </h1>
    <div style="display: flex; gap: 12px; align-items: center;">
        <input type="date" id="expenseDate" class="form-control" value="<?php echo $today; ?>" style="max-width: 200px;">
        <a href="<?php echo home_url('/120-stand/expense-history/'); ?>" class="history-btn">
            <iconify-icon icon="solar:history-linear"></iconify-icon> View History
        </a>
    </div>
</div>

<!-- Expense Form -->
<form id="expenseForm">
    <div class="glass-card">
        <h3 style="margin-bottom: 16px; color: var(--primary-color);">
            <iconify-icon icon="solar:list-linear"></iconify-icon> Market Expense Items
        </h3>
        
        <div class="table-responsive">
            <table class="table" id="expenseTable">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount (₦)</th>
                        <th>Quantity</th>
                        <th>Total (₦)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="expenseBody">
                    <!-- Rows added via JavaScript -->
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 16px;">
            <button type="button" id="addExpenseRow" class="btn btn-primary">
                <iconify-icon icon="solar:add-circle-linear"></iconify-icon> Add Item
            </button>
        </div>
    </div>
    
    <!-- Grand Total -->
    <div class="grand-total-section">
        <span class="grand-total-label">
            <iconify-icon icon="solar:calculator-linear"></iconify-icon> Grand Total
        </span>
        <span id="grandTotal" class="grand-total-value"><span class="naira">₦</span>0</span>
    </div>
    
    <!-- Submit Button -->
    <div style="margin-top: 24px; text-align: center;">
        <button type="button" id="submitExpenses" class="btn btn-primary btn-lg">
            <iconify-icon icon="solar:check-circle-linear"></iconify-icon> Submit Market Expenses
        </button>
    </div>
</form>

<script>
    jQuery(document).ready(function($) {
        let rowCounter = 0;
        let isSubmitting = false;
        
        function addRow() {
            rowCounter++;
            const row = `
                <tr data-row="${rowCounter}">
                    <td>
                        <input type="text" class="table-input expense-desc" placeholder="Enter description" required>
                    </td>
                    <td>
                        <input type="text" class="table-input expense-amount number-input" placeholder="0" value="0">
                    </td>
                    <td>
                        <input type="text" class="table-input expense-qty" placeholder="0" value="0" style="max-width: 80px;">
                    </td>
                    <td class="row-total formatted-number"><span class="naira">₦</span>0</td>
                    <td>
                        <button type="button" class="btn remove-row-btn" style="background: var(--danger-color); color: #fff; padding: 6px 12px; border-radius: 8px; font-size: 0.85rem;">
                            <iconify-icon icon="solar:trash-bin-trash-linear"></iconify-icon>
                        </button>
                    </td>
                </tr>
            `;
            $('#expenseBody').append(row);
            recalculate();
        }
        
        function recalculate() {
            let grandTotal = 0;
            $('#expenseBody tr').each(function() {
                const amount = Stand120.parseNumber($(this).find('.expense-amount').val());
                const qty = parseInt($(this).find('.expense-qty').val().toString().replace(/,/g, '')) || 0;
                const total = amount * qty;
                grandTotal += total;
                $(this).find('.row-total').html('<span class="naira">₦</span>' + Stand120.formatNumber(total));
            });
            $('#grandTotal').html('<span class="naira">₦</span>' + Stand120.formatNumber(grandTotal));
        }
        
        // Add first row by default
        addRow();
        
        // Prevent Enter key from submitting the form (which causes page reload)
        $('#expenseForm').on('submit', function(e) {
            e.preventDefault();
            return false;
        });
        
        $('#addExpenseRow').on('click', function() {
            addRow();
        });
        
        // Remove row
        $('#expenseBody').on('click', '.remove-row-btn', function() {
            if ($('#expenseBody tr').length > 1) {
                $(this).closest('tr').remove();
                recalculate();
            } else {
                Stand120.showAlert('danger', 'At least one item row is required.');
            }
        });
        
        // Recalculate on input change
        $('#expenseBody').on('input', '.expense-amount, .expense-qty', function() {
            recalculate();
        });
        
        // Smart field behavior for expense qty fields (clear 0 on focus, restore on blur)
        $('#expenseBody').on('focus', '.expense-qty', function() {
            const val = $(this).val().toString().replace(/,/g, '');
            if (val === '0') {
                $(this).val('');
            }
        });
        $('#expenseBody').on('blur', '.expense-qty', function() {
            const val = $(this).val().toString().replace(/,/g, '').trim();
            if (val === '') {
                $(this).val('0');
            }
        });
        
        // Submit expenses
        $('#submitExpenses').on('click', async function() {
            if (isSubmitting) return;
            
            const items = [];
            let valid = true;
            
            $('#expenseBody tr').each(function() {
                const desc = $(this).find('.expense-desc').val().trim();
                const amount = Stand120.parseNumber($(this).find('.expense-amount').val());
                const qty = parseInt($(this).find('.expense-qty').val().toString().replace(/,/g, '')) || 0;
                
                if (desc === '' && amount === 0) {
                    return; // skip empty rows
                }
                
                if (desc === '') {
                    valid = false;
                    Stand120.showAlert('danger', 'Please enter a description for all expense items.');
                    return false;
                }
                
                if (amount <= 0) {
                    valid = false;
                    Stand120.showAlert('danger', 'Please enter a valid amount for "' + desc + '".');
                    return false;
                }
                
                items.push({
                    description: desc,
                    amount: amount,
                    quantity: qty,
                    total: amount * qty
                });
            });
            
            if (!valid) return;
            
            if (items.length === 0) {
                Stand120.showAlert('danger', 'Please add at least one expense item.');
                return;
            }
            
            const date = $('#expenseDate').val();
            if (!date) {
                Stand120.showAlert('danger', 'Please select a date.');
                return;
            }
            
            isSubmitting = true;
            $('#submitExpenses').prop('disabled', true).html('<span class="loading-spinner"></span> Submitting...');
            
            Stand120.ajax('submit_expenses', {
                expenses: JSON.stringify(items),
                date: date
            }).then(response => {
                if (response.success) {
                    Stand120.showAlert('success', response.data.message || 'Market expenses submitted successfully!');
                    // Reset form
                    $('#expenseBody').empty();
                    rowCounter = 0;
                    addRow();
                    $('#grandTotal').html('<span class="naira">₦</span>0');
                } else {
                    Stand120.showAlert('danger', response.data?.message || 'Failed to submit market expenses.');
                }
            }).catch(() => {
                Stand120.showAlert('danger', 'An error occurred. Please try again.');
            }).finally(() => {
                isSubmitting = false;
                $('#submitExpenses').prop('disabled', false).html('<iconify-icon icon="solar:check-circle-linear"></iconify-icon> Submit Market Expenses');
            });
        });
    });
</script>

<?php include STAND120_PLUGIN_DIR . 'templates/partials/footer.php'; ?>
