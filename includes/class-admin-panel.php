<?php
/**
 * Admin Panel Class
 * Handles admin management functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stand120_Admin_Panel {
    
    /**
     * Get all data for admin panel
     */
    public static function get_admin_data() {
        return array(
            'products' => Stand120_Database::get_products(null, 'active'),
            'staff' => Stand120_Auth::get_all_staff(),
            'product_types' => array(
                array('value' => 'menu', 'label' => 'Menu Item'),
                array('value' => 'fruit', 'label' => 'Fruit'),
                array('value' => 'non_fruit', 'label' => 'Non-Fruit')
            )
        );
    }
    
    /**
     * Bulk update products
     */
    public static function bulk_update_products($products) {
        if (!Stand120_Auth::is_admin()) {
            return array('success' => false, 'message' => 'Unauthorized');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'stand120_products';
        
        foreach ($products as $product) {
            $id = intval($product['id'] ?? 0);
            $name = sanitize_text_field($product['name'] ?? '');
            $data = array(
                'name' => $name,
                'price' => floatval($product['price'] ?? 0),
                'type' => sanitize_text_field($product['type'] ?? 'menu')
            );
            
            if ($id) {
                Stand120_Database::update_product($id, $data);
            } else {
                // Check if active product with same name already exists
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table WHERE name = %s AND status = 'active'",
                    $name
                ));
                
                if ($existing) {
                    Stand120_Database::update_product($existing->id, $data);
                } else {
                    Stand120_Database::add_product(array_merge($data, array('status' => 'active')));
                }
            }
        }
        
        Stand120_Database::log_activity('bulk_update_products', 'stand120_products');
        
        return array('success' => true, 'message' => 'Products updated successfully');
    }
    
    /**
     * Update opening values for all inventory tables
     */
    public static function update_all_opening_values($data) {
        if (!Stand120_Auth::is_admin()) {
            return array('success' => false, 'message' => 'Unauthorized');
        }
        
        $table = sanitize_text_field($data['table'] ?? '');
        $date = sanitize_text_field($data['date'] ?? date('Y-m-d'));
        $values = $data['values'] ?? array();
        
        foreach ($values as $item) {
            $product_id = intval($item['product_id'] ?? 0);
            $value = floatval($item['value'] ?? 0);
            
            if (!$product_id) continue;
            
            switch ($table) {
                case 'order_preparation':
                    Stand120_Order_Preparation::update_opening($product_id, $value, $date);
                    break;
                case 'stock_inventory':
                    Stand120_Stock_Inventory::update_opening($product_id, $value, $date);
                    break;
                case 'chopping_inventory':
                    Stand120_Chopping_Inventory::update_opening($product_id, $value, $date);
                    break;
            }
        }
        
        return array('success' => true, 'message' => 'Opening values updated');
    }

    /**
     * Clear all records and history tables (Super Admin only)
     */
    public static function clear_all_records() {
        if (!Stand120_Auth::is_super_admin()) {
            return array('success' => false, 'message' => 'Unauthorized - Super Admin access required');
        }
        
        global $wpdb;
        
        $prefix = $wpdb->prefix;
        if (!preg_match('/^[a-z0-9_-]+$/i', $prefix)) {
            return array('success' => false, 'message' => 'Invalid table prefix');
        }
        
        $tables = array(
            'stand120_orders',
            'stand120_order_items',
            'stand120_order_preparation',
            'stand120_stock_inventory',
            'stand120_chopping_inventory',
            'stand120_import_records',
            'stand120_financial_summary',
            'stand120_sync_queue',
            'stand120_activity_log',
            'stand120_expenses'
        );
        
        foreach ($tables as $table) {
            $table_name = $prefix . $table;
            if (!preg_match('/^[a-z0-9_-]+$/i', $table_name)) {
                return array('success' => false, 'message' => 'Invalid table name');
            }
            $table_name = esc_sql($table_name);
            $result = $wpdb->query("TRUNCATE TABLE `$table_name`");
            if ($result === false) {
                return array('success' => false, 'message' => 'Failed to clear records: ' . $wpdb->last_error);
            }
        }
        
        return array('success' => true, 'message' => 'All records cleared');
    }
    
    /**
     * Get activity logs
     */
    public static function get_activity_logs($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'stand120_activity_log';
        $staff_table = $wpdb->prefix . 'stand120_staff';
        
        $sql = "SELECT al.*, s.full_name as staff_name
                FROM $table al
                LEFT JOIN $staff_table s ON al.staff_id = s.id
                WHERE 1=1";
        $params = array();
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(al.created_at) >= %s";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(al.created_at) <= %s";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['staff_id'])) {
            $sql .= " AND al.staff_id = %d";
            $params[] = $filters['staff_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = %s";
            $params[] = $filters['action'];
        }
        
        $sql .= " ORDER BY al.created_at DESC";
        
        // Pagination
        $page = max(1, intval($filters['page'] ?? 1));
        $per_page = max(1, min(100, intval($filters['per_page'] ?? 50)));
        $offset = ($page - 1) * $per_page;
        
        $sql .= " LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        if (!empty($params)) {
            $logs = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $logs = $wpdb->get_results($sql);
        }
        
        return array('logs' => $logs);
    }
    
    /**
     * Export data
     */
    public static function export_data($type, $date_from, $date_to) {
        if (!Stand120_Auth::is_admin()) {
            return array('success' => false, 'message' => 'Unauthorized');
        }
        
        global $wpdb;
        $data = array();
        
        switch ($type) {
            case 'orders':
                $result = Stand120_Take_Order::get_orders(array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'per_page' => 10000
                ));
                $data = $result['orders'];
                break;
                
            case 'financial':
                $result = Stand120_Financial_Summary::get_history(array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'per_page' => 10000
                ));
                $data = $result['records'];
                break;
                
            case 'stock':
                $result = Stand120_Stock_Inventory::get_history(array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'per_page' => 10000
                ));
                $data = $result['records'];
                break;
                
            case 'preparation':
                $result = Stand120_Order_Preparation::get_history(array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'per_page' => 10000
                ));
                $data = $result['records'];
                break;
                
            case 'chopping':
                $result = Stand120_Chopping_Inventory::get_history(array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'per_page' => 10000
                ));
                $data = $result['records'];
                break;
                
            case 'imports':
                $result = Stand120_Import_Record::get_history(array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'per_page' => 10000
                ));
                $data = $result['records'];
                break;
        }
        
        return array('success' => true, 'data' => $data);
    }

    /**
     * Get system diagnostics - checks all forms/features for health status
     * Super Admin only
     */
    public static function get_system_diagnostics() {
        if (!Stand120_Auth::is_super_admin()) {
            return array('success' => false, 'message' => 'Unauthorized - Super Admin access required');
        }

        global $wpdb;
        $prefix = $wpdb->prefix;
        $diagnostics = array();

        // 1. Check Products table & form
        $products_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_products WHERE status = 'active'");
        $menu_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_products WHERE status = 'active' AND type = 'menu'");
        $fruit_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_products WHERE status = 'active' AND type = 'fruit'");
        $products_status = 'good';
        $products_issue = '';
        $products_fix = '';
        if ($products_count === 0) {
            $products_status = 'error';
            $products_issue = 'No active products found in the system.';
            $products_fix = 'Go to Admin Panel > Products tab and add products, or re-activate the plugin to insert defaults.';
        } elseif ($menu_count === 0) {
            $products_status = 'warning';
            $products_issue = 'No menu items found. The Take Order form will not have items to sell.';
            $products_fix = 'Go to Admin Panel > Products tab and add at least one product with type "Menu Item".';
        } elseif ($fruit_count === 0) {
            $products_status = 'warning';
            $products_issue = 'No fruit products found. Chopping and Stock inventory forms will be empty.';
            $products_fix = 'Go to Admin Panel > Products tab and add fruit products.';
        }
        $diagnostics[] = array(
            'feature' => 'Products Management',
            'page' => 'Admin Panel > Products',
            'status' => $products_status,
            'detail' => $products_status === 'good' ? "{$products_count} active products ({$menu_count} menu, {$fruit_count} fruit)" : $products_issue,
            'fix' => $products_fix,
        );

        // 2. Check Staff table & form
        $staff_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_staff WHERE status = 'active'");
        $admin_staff = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_staff WHERE status = 'active' AND role = 'admin'");
        $staff_status = 'good';
        $staff_issue = '';
        $staff_fix = '';
        if ($staff_count === 0) {
            $staff_status = 'error';
            $staff_issue = 'No active staff found. System requires at least one staff member.';
            $staff_fix = 'Go to Admin Panel > Staff tab and add staff members.';
        } elseif ($admin_staff === 0) {
            $staff_status = 'warning';
            $staff_issue = 'No admin-role staff found. Admin features may not be accessible to non-WordPress admins.';
            $staff_fix = 'Ensure at least one staff member has admin role via Admin Panel > Staff tab.';
        }
        $diagnostics[] = array(
            'feature' => 'Staff Management',
            'page' => 'Admin Panel > Staff',
            'status' => $staff_status,
            'detail' => $staff_status === 'good' ? "{$staff_count} active staff ({$admin_staff} admins)" : $staff_issue,
            'fix' => $staff_fix,
        );

        // 3. Check Take Order form
        $orders_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}stand120_orders WHERE order_date = %s",
            date('Y-m-d')
        ));
        $orders_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_orders");
        $order_status = 'good';
        $order_issue = '';
        $order_fix = '';
        if ($menu_count === 0) {
            $order_status = 'error';
            $order_issue = 'Take Order form cannot function without menu items.';
            $order_fix = 'Add menu-type products in Admin Panel > Products tab.';
        }
        $diagnostics[] = array(
            'feature' => 'Take Order',
            'page' => 'Take Order',
            'status' => $order_status,
            'detail' => $order_status === 'good' ? "{$orders_total} total orders ({$orders_today} today)" : $order_issue,
            'fix' => $order_fix,
        );

        // 4. Check Order Preparation form
        $prep_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}stand120_order_preparation WHERE prep_date = %s",
            date('Y-m-d')
        ));
        $prep_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_order_preparation");
        $prep_status = 'good';
        $prep_issue = '';
        $prep_fix = '';
        if ($menu_count === 0) {
            $prep_status = 'error';
            $prep_issue = 'Order Preparation form requires menu items to track.';
            $prep_fix = 'Add menu-type products in Admin Panel > Products tab.';
        }
        $diagnostics[] = array(
            'feature' => 'Order Preparation',
            'page' => 'Order Preparation',
            'status' => $prep_status,
            'detail' => $prep_status === 'good' ? "{$prep_total} total records ({$prep_today} today)" : $prep_issue,
            'fix' => $prep_fix,
        );

        // 5. Check Stock Inventory form
        $stock_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}stand120_stock_inventory WHERE stock_date = %s",
            date('Y-m-d')
        ));
        $stock_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_stock_inventory");
        $inv_products = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_products WHERE status = 'active' AND type IN ('fruit', 'non_fruit')");
        $stock_status = 'good';
        $stock_issue = '';
        $stock_fix = '';
        if ($inv_products === 0) {
            $stock_status = 'error';
            $stock_issue = 'Stock Inventory form requires fruit or non-fruit products.';
            $stock_fix = 'Add fruit or non-fruit products in Admin Panel > Products tab.';
        }
        $diagnostics[] = array(
            'feature' => 'Stock Inventory',
            'page' => 'Stock Inventory',
            'status' => $stock_status,
            'detail' => $stock_status === 'good' ? "{$stock_total} total records ({$stock_today} today)" : $stock_issue,
            'fix' => $stock_fix,
        );

        // 6. Check Chopping Inventory form
        $chop_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}stand120_chopping_inventory WHERE chop_date = %s",
            date('Y-m-d')
        ));
        $chop_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_chopping_inventory");
        $chop_status = 'good';
        $chop_issue = '';
        $chop_fix = '';
        if ($fruit_count === 0) {
            $chop_status = 'error';
            $chop_issue = 'Chopping Inventory requires fruit products to track.';
            $chop_fix = 'Add fruit-type products in Admin Panel > Products tab.';
        }
        $diagnostics[] = array(
            'feature' => 'Chopping Inventory',
            'page' => 'Chopping Inventory',
            'status' => $chop_status,
            'detail' => $chop_status === 'good' ? "{$chop_total} total records ({$chop_today} today)" : $chop_issue,
            'fix' => $chop_fix,
        );

        // 7. Check Import Record form
        $import_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}stand120_import_records WHERE import_date = %s",
            date('Y-m-d')
        ));
        $import_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_import_records");
        $import_status = $fruit_count === 0 ? 'error' : 'good';
        $import_issue = $fruit_count === 0 ? 'Import Record form requires fruit products.' : '';
        $import_fix = $fruit_count === 0 ? 'Add fruit-type products in Admin Panel > Products tab.' : '';
        $diagnostics[] = array(
            'feature' => 'Import Records',
            'page' => 'Import Record',
            'status' => $import_status,
            'detail' => $import_status === 'good' ? "{$import_total} total records ({$import_today} today)" : $import_issue,
            'fix' => $import_fix,
        );

        // 8. Check Financial Summary
        $fin_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}stand120_financial_summary WHERE summary_date = %s",
            date('Y-m-d')
        ));
        $fin_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_financial_summary");
        $fin_status = 'good';
        $fin_issue = '';
        $fin_fix = '';
        if ($orders_total === 0 && $fin_total === 0) {
            $fin_status = 'warning';
            $fin_issue = 'No orders or financial data recorded yet. Financial summary auto-populates from order data.';
            $fin_fix = 'Submit orders via Take Order page to generate financial summary data.';
        }
        $diagnostics[] = array(
            'feature' => 'Financial Summary',
            'page' => 'Financial Summary',
            'status' => $fin_status,
            'detail' => $fin_status === 'good' ? "{$fin_total} total records ({$fin_today} today)" : $fin_issue,
            'fix' => $fin_fix,
        );

        // 9. Check Expense Records
        $exp_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}stand120_expenses WHERE expense_date = %s",
            date('Y-m-d')
        ));
        $exp_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_expenses");
        $diagnostics[] = array(
            'feature' => 'Expense Records',
            'page' => 'Expense Record',
            'status' => 'good',
            'detail' => "{$exp_total} total expenses ({$exp_today} today)",
            'fix' => '',
        );

        // 10. Check Reconciliation
        $recon_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_reconciliation");
        $recon_status = 'good';
        $recon_issue = '';
        $recon_fix = '';
        if ($admin_staff < 2) {
            $recon_status = 'warning';
            $recon_issue = 'Reconciliation requires at least 2 admin-role staff for dual verification.';
            $recon_fix = 'Add more staff with admin roles via Admin Panel > Staff tab.';
        }
        $diagnostics[] = array(
            'feature' => 'Reconciliation',
            'page' => 'Reconciliation',
            'status' => $recon_status,
            'detail' => $recon_status === 'good' ? "{$recon_total} reconciliation records" : $recon_issue,
            'fix' => $recon_fix,
        );

        // 11. Check Database Tables
        $required_tables = array(
            'stand120_products', 'stand120_staff', 'stand120_orders', 'stand120_order_items',
            'stand120_order_preparation', 'stand120_stock_inventory', 'stand120_chopping_inventory',
            'stand120_import_records', 'stand120_financial_summary', 'stand120_sync_queue',
            'stand120_activity_log', 'stand120_expenses', 'stand120_reconciliation'
        );
        $missing_tables = array();
        foreach ($required_tables as $table) {
            $full_name = $prefix . $table;
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full_name));
            if (!$exists) {
                $missing_tables[] = $table;
            }
        }
        $db_status = empty($missing_tables) ? 'good' : 'error';
        $db_issue = empty($missing_tables) ? 'All 13 required tables exist' : 'Missing tables: ' . implode(', ', $missing_tables);
        $db_fix = empty($missing_tables) ? '' : 'Deactivate and reactivate the plugin to recreate missing tables.';
        $diagnostics[] = array(
            'feature' => 'Database Tables',
            'page' => 'System',
            'status' => $db_status,
            'detail' => $db_issue,
            'fix' => $db_fix,
        );

        // 12. Check Sync Queue
        $pending_sync = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}stand120_sync_queue WHERE synced = 0");
        $sync_status = $pending_sync > 10 ? 'warning' : 'good';
        $sync_issue = $pending_sync > 10 ? "{$pending_sync} items pending sync. Offline data may not be saved." : "{$pending_sync} items pending sync";
        $sync_fix = $pending_sync > 10 ? 'Ensure stable internet connection and refresh the page to trigger sync.' : '';
        $diagnostics[] = array(
            'feature' => 'Offline Sync Queue',
            'page' => 'System',
            'status' => $sync_status,
            'detail' => $sync_issue,
            'fix' => $sync_fix,
        );

        // Summary counts
        $good_count = count(array_filter($diagnostics, function($d) { return $d['status'] === 'good'; }));
        $warning_count = count(array_filter($diagnostics, function($d) { return $d['status'] === 'warning'; }));
        $error_count = count(array_filter($diagnostics, function($d) { return $d['status'] === 'error'; }));

        return array(
            'success' => true,
            'diagnostics' => $diagnostics,
            'summary' => array(
                'total' => count($diagnostics),
                'good' => $good_count,
                'warnings' => $warning_count,
                'errors' => $error_count,
            ),
        );
    }
}
