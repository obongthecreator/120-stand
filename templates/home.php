<?php
/**
 * Home Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = 'Home - 120 Stand Inventory';
$current_user = Stand120_Auth::get_current_user_data();
$is_admin = Stand120_Auth::is_admin();

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

<h1 class="page-title">
    <iconify-icon icon="solar:widget-2-linear"></iconify-icon>
    Dashboard
</h1>

<!-- Home Cards Grid -->
<div class="home-cards-grid">
    <!-- Take Order -->
    <a href="<?php echo home_url('/120-stand/take-order/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:cart-plus-linear"></iconify-icon>
        </div>
        <h3>Take Order</h3>
        <p>Create new customer orders, select items, set quantities, and process payments quickly.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Order Preparation -->
    <a href="<?php echo home_url('/120-stand/order-preparation/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:chef-hat-linear"></iconify-icon>
        </div>
        <h3>Order Preparation</h3>
        <p>Track daily fruit preparation including opening stock, additions, sales, and closing quantities.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Stock Inventory -->
    <a href="<?php echo home_url('/120-stand/stock-inventory/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:box-linear"></iconify-icon>
        </div>
        <h3>Stock Inventory</h3>
        <p>Monitor pack levels for fruits and non-fruit items with real-time opening, added, and closing stock.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Chopping Inventory -->
    <a href="<?php echo home_url('/120-stand/chopping-inventory/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:scissors-linear"></iconify-icon>
        </div>
        <h3>Chopping Inventory</h3>
        <p>Record whole fruit processing - track opening, imports, prepared quantities, and packs obtained.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Import Record -->
    <a href="<?php echo home_url('/120-stand/import-record/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:delivery-linear"></iconify-icon>
        </div>
        <h3>Import Record</h3>
        <p>Log daily product imports which automatically update stock and chopping inventory forms.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Product Summary -->
    <a href="<?php echo home_url('/120-stand/product-summary/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:chart-2-linear"></iconify-icon>
        </div>
        <h3>Product Summary</h3>
        <p>View total products sold, revenue generated, active staff, and detailed sales breakdown.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Market Expense -->
    <a href="<?php echo home_url('/120-stand/expense-record/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:document-text-linear"></iconify-icon>
        </div>
        <h3>Market Expense</h3>
        <p>Record daily market expenses with description, amount, and quantity. View expense history and totals.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Financial Summary -->
    <a href="<?php echo home_url('/120-stand/financial-summary/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:wallet-linear"></iconify-icon>
        </div>
        <h3>Financial Summary</h3>
        <p>Track daily finances including sales, cash/transfer breakdown, expenses, and cash reconciliation.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <?php if ($is_admin): ?>
    <!-- Reconciliation Calendar -->
    <a href="<?php echo home_url('/120-stand/reconciliation/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:calendar-check-linear"></iconify-icon>
        </div>
        <h3>Reconciliation</h3>
        <p>Review and confirm daily records. Two admins must verify each day's records for accountability.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Admin Panel -->
    <a href="<?php echo home_url('/120-stand/admin-panel/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:settings-linear"></iconify-icon>
        </div>
        <h3>Admin Panel</h3>
        <p>Manage products, prices, staff accounts, opening values, and system settings.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    
    <!-- Analytics -->
    <a href="<?php echo home_url('/120-stand/analytics/'); ?>" class="home-card">
        <div class="home-card-icon">
            <iconify-icon icon="solar:graph-up-linear"></iconify-icon>
        </div>
        <h3>Analytics</h3>
        <p>View performance analytics, sales trends, staff performance, and business insights.</p>
        <span class="home-card-btn">
            <iconify-icon icon="solar:arrow-right-linear"></iconify-icon> Open
        </span>
    </a>
    <?php endif; ?>
</div>

<?php include STAND120_PLUGIN_DIR . 'templates/partials/footer.php'; ?>
