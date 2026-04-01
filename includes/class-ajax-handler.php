<?php
/**
 * AJAX Handler Class
 * Handles all AJAX requests for the inventory system
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stand120_Ajax_Handler {
    
    private const NONCE_EXEMPT_ACTIONS = array('login', 'check_login_status');

    /**
     * Actions that do not require nonce validation.
     */
    private static function is_nonce_exempt_action($action) {
        return in_array($action, self::NONCE_EXEMPT_ACTIONS, true);
    }

    /**
     * Handle AJAX requests
     */
    public static function handle() {
        $action = sanitize_text_field($_POST['stand120_action'] ?? '');
        
        // Login action doesn't require nonce verification (user isn't logged in yet)
        // But still verify the nonce is properly formatted
        if (!self::is_nonce_exempt_action($action)) {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'stand120_nonce')) {
                wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
                return;
            }
        }
        
        switch ($action) {
            // Auth actions
            case 'login':
                self::handle_login();
                break;
            case 'check_login_status':
                self::check_login_status();
                break;
            case 'logout':
                self::handle_logout();
                break;
            
            // Product actions
            case 'get_products':
                self::get_products();
                break;
            case 'get_menu_items':
                self::get_menu_items();
                break;
            case 'add_product':
                self::add_product();
                break;
            case 'update_product':
                self::update_product();
                break;
            case 'delete_product':
                self::delete_product();
                break;
            
            // Take Order actions
            case 'submit_order':
                self::submit_order();
                break;
            case 'get_orders':
                self::get_orders();
                break;
            case 'get_order':
                self::get_order();
                break;
            
            // Order Preparation actions
            case 'save_order_preparation':
                self::save_order_preparation();
                break;
            case 'get_order_preparation':
                self::get_order_preparation();
                break;
            case 'get_order_preparation_history':
                self::get_order_preparation_history();
                break;
            case 'update_opening_values':
                self::update_opening_values();
                break;
            case 'update_all_opening_values':
                self::update_all_opening_values();
                break;
            
            // Stock Inventory actions
            case 'save_stock_inventory':
                self::save_stock_inventory();
                break;
            case 'get_stock_inventory':
                self::get_stock_inventory();
                break;
            case 'get_stock_inventory_history':
                self::get_stock_inventory_history();
                break;
            
            // Chopping Inventory actions
            case 'save_chopping_inventory':
                self::save_chopping_inventory();
                break;
            case 'get_chopping_inventory':
                self::get_chopping_inventory();
                break;
            case 'get_chopping_inventory_history':
                self::get_chopping_inventory_history();
                break;
            
            // Import Record actions
            case 'save_import_record':
                self::save_import_record();
                break;
            case 'get_import_records':
                self::get_import_records();
                break;
            case 'get_import_history':
                self::get_import_history();
                break;
            
            // Financial Summary actions
            case 'save_financial_summary':
                self::save_financial_summary();
                break;
            case 'get_financial_summary':
                self::get_financial_summary();
                break;
            case 'get_financial_summary_history':
                self::get_financial_summary_history();
                break;
            
            // Product Summary actions
            case 'get_product_summary':
                self::get_product_summary();
                break;
            
            // Sync actions
            case 'sync_offline_data':
                self::sync_offline_data();
                break;
            
            // Staff actions
            case 'get_staff':
                self::get_staff();
                break;
            case 'create_staff':
                self::create_staff();
                break;
            case 'update_staff':
                self::update_staff_action();
                break;
            case 'delete_staff':
                self::delete_staff();
                break;
            
            // Analytics
            case 'get_analytics':
                self::get_analytics();
                break;
            case 'get_target_recommendation':
                self::get_target_recommendation();
                break;
            case 'get_sales_insights':
                self::get_sales_insights();
                break;
            case 'export_data':
                self::export_data();
                break;
            case 'clear_all_records':
                self::clear_all_records();
                break;
            case 'get_system_diagnostics':
                self::get_system_diagnostics();
                break;
            
            // Expense actions
            case 'submit_expenses':
                self::submit_expenses();
                break;
            case 'get_expenses':
                self::get_expenses();
                break;
            case 'get_expense_history':
                self::get_expense_history();
                break;
            case 'delete_expense':
                self::delete_expense();
                break;
            
            // Order management actions
            case 'delete_order':
                self::delete_order();
                break;
            case 'get_all_orders':
                self::get_all_orders();
                break;
            case 'update_financial_field':
                self::update_financial_field();
                break;
            
            // Reconciliation actions
            case 'save_reconciliation_check':
                self::save_reconciliation_check();
                break;
            case 'get_reconciliation_data':
                self::get_reconciliation_data();
                break;
            
            default:
                wp_send_json_error(array('message' => 'Invalid action'));
        }
    }
    
    /**
     * Handle login
     */
    private static function handle_login() {
        $raw_username = trim($_POST['username'] ?? '');
        $username = is_email($raw_username) ? sanitize_email($raw_username) : sanitize_text_field($raw_username);
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => 'Please enter username and password'));
        }
        
        $result = Stand120_Auth::login($username, $password);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle logout
     */
    private static function handle_logout() {
        $result = Stand120_Auth::logout();
        wp_send_json_success($result);
    }
    
    /**
     * Get products
     */
    private static function get_products() {
        $type = sanitize_text_field($_POST['type'] ?? '');
        $products = Stand120_Database::get_products($type ?: null);
        wp_send_json_success(array('products' => $products));
    }
    
    /**
     * Get menu items
     */
    private static function get_menu_items() {
        $items = Stand120_Database::get_menu_items();
        wp_send_json_success(array('items' => $items));
    }
    
    /**
     * Add product
     */
    private static function add_product() {
        // Check admin using WordPress directly to avoid any issues
        if (!current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'type' => sanitize_text_field($_POST['type'] ?? 'menu'),
            'unit' => sanitize_text_field($_POST['unit'] ?? 'piece'),
            'status' => 'active'
        );
        
        if (empty($data['name'])) {
            wp_send_json_error(array('message' => 'Product name is required'));
            return;
        }
        
        // Check if an active product with the same name already exists
        global $wpdb;
        $table = $wpdb->prefix . 'stand120_products';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE name = %s AND status = 'active'",
            $data['name']
        ));
        
        if ($existing) {
            // Update existing product instead of duplicating
            $update_data = $data;
            unset($update_data['status']); // Don't change status
            Stand120_Database::update_product($existing->id, $update_data);
            Stand120_Database::log_activity('update_product', 'stand120_products', $existing->id, null, $update_data);
            wp_send_json_success(array('message' => 'Product updated successfully', 'product_id' => $existing->id));
            return;
        }
        
        $result = Stand120_Database::add_product($data);
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to add product: ' . $wpdb->last_error));
            return;
        }
        
        Stand120_Database::log_activity('add_product', 'stand120_products', null, null, $data);
        
        wp_send_json_success(array('message' => 'Product added successfully', 'product_id' => $result));
    }
    
    /**
     * Update product
     */
    private static function update_product() {
        // Check admin using WordPress directly
        if (!current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            wp_send_json_error(array('message' => 'Product ID is required'));
            return;
        }
        
        $data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'type' => sanitize_text_field($_POST['type'] ?? 'menu')
        );
        
        $old = Stand120_Database::get_product($id);
        $result = Stand120_Database::update_product($id, $data);
        Stand120_Database::log_activity('update_product', 'stand120_products', $id, $old, $data);
        
        wp_send_json_success(array('message' => 'Product updated successfully'));
    }
    
    /**
     * Delete product
     */
    private static function delete_product() {
        // Check admin using WordPress directly
        if (!current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            wp_send_json_error(array('message' => 'Product ID is required'));
            return;
        }
        
        Stand120_Database::delete_product($id);
        Stand120_Database::log_activity('delete_product', 'stand120_products', $id);
        
        wp_send_json_success(array('message' => 'Product deleted successfully'));
    }
    
    /**
     * Submit order
     */
    private static function submit_order() {
        // Check if user is logged into WordPress at all
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Session expired. Please refresh the page and login again.'));
            return;
        }
        
        // Skip the Stand120 logged in check - just use WordPress login status
        // This ensures the system works even if the staff record doesn't exist yet
        
        $result = Stand120_Take_Order::submit_order($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get orders
     */
    private static function get_orders() {
        $filters = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'staff_id' => intval($_POST['staff_id'] ?? 0),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20)
        );
        
        $result = Stand120_Take_Order::get_orders($filters);
        wp_send_json_success($result);
    }
    
    /**
     * Get single order
     */
    private static function get_order() {
        $id = intval($_POST['order_id'] ?? 0);
        $order = Stand120_Take_Order::get_order($id);
        
        if ($order) {
            wp_send_json_success(array('order' => $order));
        } else {
            wp_send_json_error(array('message' => 'Order not found'));
        }
    }
    
    /**
     * Save order preparation
     */
    private static function save_order_preparation() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to continue'));
            return;
        }
        
        $result = Stand120_Order_Preparation::save($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get order preparation
     */
    private static function get_order_preparation() {
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        $result = Stand120_Order_Preparation::get_for_date($date);
        wp_send_json_success($result);
    }
    
    /**
     * Get order preparation history
     */
    private static function get_order_preparation_history() {
        $filters = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'product_id' => intval($_POST['product_id'] ?? 0),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20)
        );
        
        $result = Stand120_Order_Preparation::get_history($filters);
        wp_send_json_success($result);
    }
    
    /**
     * Update opening values (admin only)
     */
    private static function update_opening_values() {
        // Check admin using WordPress directly
        if (!current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $table = sanitize_text_field($_POST['table'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        $value = floatval($_POST['value'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        
        switch ($table) {
            case 'order_preparation':
                $result = Stand120_Order_Preparation::update_opening($product_id, $value, $date);
                break;
            case 'stock_inventory':
                $result = Stand120_Stock_Inventory::update_opening($product_id, $value, $date);
                break;
            case 'chopping_inventory':
                $result = Stand120_Chopping_Inventory::update_opening($product_id, $value, $date);
                break;
            default:
                wp_send_json_error(array('message' => 'Invalid table'));
                return;
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Check login status
     */
    private static function check_login_status() {
        wp_send_json_success(array(
            'is_logged_in' => Stand120_Auth::is_logged_in()
        ));
    }

    /**
     * Update opening values in bulk (admin only)
     */
    private static function update_all_opening_values() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $result = Stand120_Admin_Panel::update_all_opening_values($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Save stock inventory
     */
    private static function save_stock_inventory() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to continue'));
            return;
        }
        
        $result = Stand120_Stock_Inventory::save($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get stock inventory
     */
    private static function get_stock_inventory() {
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        $result = Stand120_Stock_Inventory::get_for_date($date);
        wp_send_json_success($result);
    }
    
    /**
     * Get stock inventory history
     */
    private static function get_stock_inventory_history() {
        $filters = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'product_id' => intval($_POST['product_id'] ?? 0),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20)
        );
        
        $result = Stand120_Stock_Inventory::get_history($filters);
        wp_send_json_success($result);
    }
    
    /**
     * Save chopping inventory
     */
    private static function save_chopping_inventory() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to continue'));
            return;
        }
        
        $result = Stand120_Chopping_Inventory::save($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get chopping inventory
     */
    private static function get_chopping_inventory() {
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        $result = Stand120_Chopping_Inventory::get_for_date($date);
        wp_send_json_success($result);
    }
    
    /**
     * Get chopping inventory history
     */
    private static function get_chopping_inventory_history() {
        $filters = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'product_id' => intval($_POST['product_id'] ?? 0),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20)
        );
        
        $result = Stand120_Chopping_Inventory::get_history($filters);
        wp_send_json_success($result);
    }
    
    /**
     * Save import record
     */
    private static function save_import_record() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to continue'));
            return;
        }
        
        $result = Stand120_Import_Record::save($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get import records
     */
    private static function get_import_records() {
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        $result = Stand120_Import_Record::get_for_date($date);
        wp_send_json_success($result);
    }
    
    /**
     * Get import history
     */
    private static function get_import_history() {
        $filters = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'product_id' => intval($_POST['product_id'] ?? 0),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20)
        );
        
        $result = Stand120_Import_Record::get_history($filters);
        wp_send_json_success($result);
    }
    
    /**
     * Save financial summary
     */
    private static function save_financial_summary() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to continue'));
            return;
        }
        
        $result = Stand120_Financial_Summary::save($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get financial summary
     */
    private static function get_financial_summary() {
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        $result = Stand120_Financial_Summary::get_for_date($date);
        wp_send_json_success($result);
    }
    
    /**
     * Get financial summary history
     */
    private static function get_financial_summary_history() {
        $filters = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20)
        );
        
        $result = Stand120_Financial_Summary::get_history($filters);
        wp_send_json_success($result);
    }
    
    /**
     * Get product summary
     */
    private static function get_product_summary() {
        $filters = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? date('Y-m-d')),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? date('Y-m-d')),
            'product_id' => intval($_POST['product_id'] ?? 0),
            'staff_id' => intval($_POST['staff_id'] ?? 0),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 50)
        );
        
        $result = Stand120_Product_Summary::get_summary($filters);
        wp_send_json_success($result);
    }
    
    /**
     * Sync offline data
     */
    private static function sync_offline_data() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to continue'));
            return;
        }
        
        $data = json_decode(stripslashes($_POST['offline_data'] ?? '[]'), true);
        
        if (!is_array($data)) {
            wp_send_json_error(array('message' => 'Invalid data format'));
        }
        
        $results = array();
        
        foreach ($data as $item) {
            $type = $item['type'] ?? '';
            $record = $item['data'] ?? array();
            
            switch ($type) {
                case 'order':
                    $result = Stand120_Take_Order::submit_order($record);
                    break;
                case 'order_preparation':
                    $result = Stand120_Order_Preparation::save($record);
                    break;
                case 'stock_inventory':
                    $result = Stand120_Stock_Inventory::save($record);
                    break;
                case 'chopping_inventory':
                    $result = Stand120_Chopping_Inventory::save($record);
                    break;
                case 'import_record':
                    $result = Stand120_Import_Record::save($record);
                    break;
                case 'financial_summary':
                    $result = Stand120_Financial_Summary::save($record);
                    break;
                default:
                    $result = array('success' => false, 'message' => 'Unknown type');
            }
            
            $results[] = array(
                'type' => $type,
                'local_id' => $item['local_id'] ?? '',
                'result' => $result
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Sync completed',
            'results' => $results
        ));
    }
    
    /**
     * Get staff
     */
    private static function get_staff() {
        // Check admin using WordPress directly
        if (!current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $staff = Stand120_Auth::get_all_staff();
        wp_send_json_success(array('staff' => $staff));
    }
    
    /**
     * Create staff
     */
    private static function create_staff() {
        // Check admin using WordPress directly
        if (!current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $data = array(
            'username' => sanitize_user($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'email' => sanitize_email($_POST['email'] ?? ''),
            'full_name' => sanitize_text_field($_POST['full_name'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? '')
        );
        
        $result = Stand120_Auth::create_staff($data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Update staff
     */
    private static function update_staff_action() {
        // Check admin using WordPress directly
        if (!current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $staff_id = intval($_POST['staff_id'] ?? 0);
        $data = array(
            'full_name' => sanitize_text_field($_POST['full_name'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        );
        
        $result = Stand120_Auth::update_staff($staff_id, $data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Delete staff
     */
    private static function delete_staff() {
        // Check admin using WordPress directly
        if (!current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $staff_id = intval($_POST['staff_id'] ?? 0);
        $result = Stand120_Auth::delete_staff($staff_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get analytics
     */
    private static function get_analytics() {
        $type = sanitize_text_field($_POST['type'] ?? 'overview');
        $date_from = sanitize_text_field($_POST['date_from'] ?? date('Y-m-01'));
        $date_to = sanitize_text_field($_POST['date_to'] ?? date('Y-m-d'));
        $period = sanitize_text_field($_POST['period'] ?? '');
        $reference_date = sanitize_text_field($_POST['date'] ?? $date_to);
        
        if ($period) {
            $reference_timestamp = strtotime($reference_date);
            if (!$reference_timestamp) {
                $reference_timestamp = time();
            }
            switch ($period) {
                case 'daily':
                    $date_from = $reference_date;
                    $date_to = $reference_date;
                    break;
                case 'weekly':
                    $date_from = date('Y-m-d', strtotime('monday this week', $reference_timestamp));
                    $date_to = date('Y-m-d', strtotime('sunday this week', $reference_timestamp));
                    break;
                case 'monthly':
                    $date_from = date('Y-m-01', $reference_timestamp);
                    $date_to = date('Y-m-t', $reference_timestamp);
                    break;
                case 'yearly':
                    $date_from = date('Y-01-01', $reference_timestamp);
                    $date_to = date('Y-12-31', $reference_timestamp);
                    break;
            }
        }
        
        global $wpdb;
        
        $analytics = array();
        
        switch ($type) {
            case 'overview':
                // Total sales
                $orders_table = $wpdb->prefix . 'stand120_orders';
                $analytics['total_sales'] = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(grand_total) FROM $orders_table WHERE order_date BETWEEN %s AND %s",
                    $date_from, $date_to
                )) ?: 0;
                
                $analytics['total_orders'] = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $orders_table WHERE order_date BETWEEN %s AND %s",
                    $date_from, $date_to
                )) ?: 0;
                
                // Staff performance
                $staff_table = $wpdb->prefix . 'stand120_staff';
                $analytics['staff_performance'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.full_name, COUNT(o.id) as order_count, SUM(o.grand_total) as total_sales
                    FROM $staff_table s
                    LEFT JOIN $orders_table o ON s.id = o.staff_id AND o.order_date BETWEEN %s AND %s
                    WHERE s.status = 'active'
                    GROUP BY s.id
                    ORDER BY total_sales DESC",
                    $date_from, $date_to
                ));
                
                // Daily sales trend
                $analytics['daily_sales'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT order_date, SUM(grand_total) as total
                    FROM $orders_table
                    WHERE order_date BETWEEN %s AND %s
                    GROUP BY order_date
                    ORDER BY order_date ASC",
                    $date_from, $date_to
                ));
                
                // Day-of-week sales distribution
                $analytics['dow_sales'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT DAYOFWEEK(order_date) as dow, DAYNAME(order_date) as day_name,
                        COUNT(*) as order_count, SUM(grand_total) as total
                    FROM $orders_table
                    WHERE order_date BETWEEN %s AND %s
                    GROUP BY DAYOFWEEK(order_date), DAYNAME(order_date)
                    ORDER BY dow ASC",
                    $date_from, $date_to
                ));
                
                // Top products
                $items_table = $wpdb->prefix . 'stand120_order_items';
                $analytics['top_products'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT oi.product_name, SUM(oi.quantity) as qty_sold, SUM(oi.total) as revenue
                    FROM $items_table oi
                    JOIN $orders_table o ON oi.order_id = o.id
                    WHERE o.order_date BETWEEN %s AND %s
                    GROUP BY oi.product_id
                    ORDER BY qty_sold DESC
                    LIMIT 10",
                    $date_from, $date_to
                ));
                
                $products_table = $wpdb->prefix . 'stand120_products';
                $prep_table = $wpdb->prefix . 'stand120_order_preparation';
                $stock_table = $wpdb->prefix . 'stand120_stock_inventory';
                $chop_table = $wpdb->prefix . 'stand120_chopping_inventory';
                $import_table = $wpdb->prefix . 'stand120_import_records';
                $financial_table = $wpdb->prefix . 'stand120_financial_summary';
                
                $analytics['preparation'] = array(
                    'summary' => $wpdb->get_row($wpdb->prepare(
                        "SELECT 
                            SUM(total_added) as total_added,
                            SUM(total_sold) as total_sold,
                            SUM(closing_value) as closing_value
                        FROM $prep_table
                        WHERE prep_date BETWEEN %s AND %s",
                        $date_from, $date_to
                    )),
                    'records' => $wpdb->get_results($wpdb->prepare(
                        "SELECT p.name as product_name,
                            SUM(op.total_added) as total_added,
                            SUM(op.total_sold) as total_sold,
                            SUM(op.closing_value) as closing_value
                        FROM $prep_table op
                        JOIN $products_table p ON op.product_id = p.id
                        WHERE op.prep_date BETWEEN %s AND %s
                        GROUP BY op.product_id
                        ORDER BY p.name ASC",
                        $date_from, $date_to
                    ))
                );
                
                $analytics['stock'] = array(
                    'summary' => $wpdb->get_row($wpdb->prepare(
                        "SELECT 
                            SUM(added_packs) as added_packs,
                            SUM(used_packs) as used_packs,
                            SUM(closing_packs) as closing_packs
                        FROM $stock_table
                        WHERE stock_date BETWEEN %s AND %s",
                        $date_from, $date_to
                    )),
                    'records' => $wpdb->get_results($wpdb->prepare(
                        "SELECT p.name as product_name, p.type as product_type,
                            SUM(si.added_packs) as added_packs,
                            SUM(si.used_packs) as used_packs,
                            SUM(si.closing_packs) as closing_packs
                        FROM $stock_table si
                        JOIN $products_table p ON si.product_id = p.id
                        WHERE si.stock_date BETWEEN %s AND %s
                        GROUP BY si.product_id
                        ORDER BY p.name ASC",
                        $date_from, $date_to
                    ))
                );
                
                $analytics['chopping'] = array(
                    'summary' => $wpdb->get_row($wpdb->prepare(
                        "SELECT 
                            SUM(import_whole) as import_whole,
                            SUM(prepared_whole) as prepared_whole,
                            SUM(packs_gotten) as packs_gotten
                        FROM $chop_table
                        WHERE chop_date BETWEEN %s AND %s",
                        $date_from, $date_to
                    )),
                    'records' => $wpdb->get_results($wpdb->prepare(
                        "SELECT p.name as product_name,
                            SUM(ci.import_whole) as import_whole,
                            SUM(ci.prepared_whole) as prepared_whole,
                            SUM(ci.packs_gotten) as packs_gotten
                        FROM $chop_table ci
                        JOIN $products_table p ON ci.product_id = p.id
                        WHERE ci.chop_date BETWEEN %s AND %s
                        GROUP BY ci.product_id
                        ORDER BY p.name ASC",
                        $date_from, $date_to
                    ))
                );
                
                $analytics['imports'] = array(
                    'summary' => $wpdb->get_row($wpdb->prepare(
                        "SELECT SUM(quantity_imported) as quantity_imported
                        FROM $import_table
                        WHERE import_date BETWEEN %s AND %s",
                        $date_from, $date_to
                    )),
                    'records' => $wpdb->get_results($wpdb->prepare(
                        "SELECT p.name as product_name, p.type as product_type,
                            SUM(ir.quantity_imported) as quantity_imported
                        FROM $import_table ir
                        JOIN $products_table p ON ir.product_id = p.id
                        WHERE ir.import_date BETWEEN %s AND %s
                        GROUP BY ir.product_id
                        ORDER BY p.name ASC",
                        $date_from, $date_to
                    ))
                );
                
                $analytics['financials'] = $wpdb->get_row($wpdb->prepare(
                    "SELECT 
                        SUM(total_sales) as total_sales,
                        SUM(cash_sales) as cash_sales,
                        SUM(transfer_sales) as transfer_sales,
                        SUM(delivery_fees) as delivery_fees,
                        SUM(extras_amount) as extras_amount,
                        SUM(expenses_amount) as expenses_amount
                    FROM $financial_table
                    WHERE summary_date BETWEEN %s AND %s",
                    $date_from, $date_to
                ));
                
                // Expenses from the expenses table
                $expenses_table = $wpdb->prefix . 'stand120_expenses';
                $analytics['detailed_expenses'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT e.description, SUM(e.total) as total_amount, SUM(e.quantity) as total_qty
                    FROM $expenses_table e
                    WHERE e.expense_date BETWEEN %s AND %s
                    GROUP BY e.description
                    ORDER BY total_amount DESC",
                    $date_from, $date_to
                ));

                $analytics['total_expenses'] = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(total) FROM $expenses_table WHERE expense_date BETWEEN %s AND %s",
                    $date_from, $date_to
                )) ?: 0;

                // Revenue = total sales
                $analytics['revenue'] = $analytics['total_sales'];

                // Total expenses from both financial_summary and expenses table
                $fin_expenses = 0;
                if ($analytics['financials']) {
                    $fin_expenses = floatval($analytics['financials']->expenses_amount ?? 0);
                }
                $analytics['total_all_expenses'] = $fin_expenses + floatval($analytics['total_expenses']);

                // Profit = Revenue - Total Expenses
                $analytics['profit'] = floatval($analytics['revenue']) - floatval($analytics['total_all_expenses']);

                // Loss (if profit is negative)
                $analytics['loss'] = $analytics['profit'] < 0 ? abs($analytics['profit']) : 0;

                // Product profitability
                $analytics['product_revenue'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT oi.product_name, SUM(oi.quantity) as qty_sold, SUM(oi.total) as revenue,
                        p.price as unit_price
                    FROM $items_table oi
                    JOIN $orders_table o ON oi.order_id = o.id
                    LEFT JOIN {$products_table} p ON oi.product_id = p.id AND p.status = 'active'
                    WHERE o.order_date BETWEEN %s AND %s
                    GROUP BY oi.product_name, p.price
                    ORDER BY revenue DESC",
                    $date_from, $date_to
                ));
                
                $analytics['period'] = array(
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'period' => $period ?: 'custom'
                );
                break;
                
            case 'inventory':
                // Stock levels
                $stock_table = $wpdb->prefix . 'stand120_stock_inventory';
                $products_table = $wpdb->prefix . 'stand120_products';
                $analytics['current_stock'] = $wpdb->get_results(
                    "SELECT p.name, s.closing_packs
                    FROM $stock_table s
                    JOIN $products_table p ON s.product_id = p.id
                    WHERE s.stock_date = CURDATE()
                    ORDER BY p.name"
                );
                break;
        }
        
        wp_send_json_success(array('analytics' => $analytics));
    }

    /**
     * Export data (admin only)
     */
    private static function export_data() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $type = sanitize_text_field($_POST['type'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        $allowed_types = array('orders', 'financial', 'stock', 'preparation', 'chopping', 'imports');
        if (!in_array($type, $allowed_types, true)) {
            wp_send_json_error(array('message' => 'Invalid export type'));
            return;
        }
        
        $from_date = $date_from ? DateTime::createFromFormat('Y-m-d', $date_from) : null;
        $to_date = $date_to ? DateTime::createFromFormat('Y-m-d', $date_to) : null;
        $from_valid = !$date_from || ($from_date instanceof DateTime && $from_date->format('Y-m-d') === $date_from);
        $to_valid = !$date_to || ($to_date instanceof DateTime && $to_date->format('Y-m-d') === $date_to);
        
        if (!$from_valid || !$to_valid) {
            wp_send_json_error(array('message' => 'Invalid date range'));
            return;
        }
        
        $result = Stand120_Admin_Panel::export_data($type, $date_from, $date_to);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Clear all records and histories (admin only)
     */
    /**
     * Submit expenses
     */
    private static function submit_expenses() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Session expired. Please refresh the page and login again.'));
            return;
        }
        
        $result = Stand120_Expense::submit_expenses($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get expenses for a date
     */
    private static function get_expenses() {
        $date = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));
        $result = Stand120_Expense::get_expenses($date);
        wp_send_json_success($result);
    }
    
    /**
     * Get expense history
     */
    private static function get_expense_history() {
        $filters = array(
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20)
        );
        
        $result = Stand120_Expense::get_expense_history($filters);
        wp_send_json_success($result);
    }
    
    /**
     * Delete an expense
     */
    private static function delete_expense() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $expense_id = intval($_POST['expense_id'] ?? 0);
        if ($expense_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid expense ID'));
            return;
        }
        
        // Check date-based permission: admin can only delete today's expenses, super admin can delete any
        if (!Stand120_Auth::is_super_admin()) {
            global $wpdb;
            $table = $wpdb->prefix . 'stand120_expenses';
            $expense = $wpdb->get_row($wpdb->prepare("SELECT expense_date FROM $table WHERE id = %d", $expense_id));
            
            if ($expense && $expense->expense_date !== date('Y-m-d')) {
                wp_send_json_error(array('message' => 'You can only delete expenses from today. Contact a super admin for older records.'));
                return;
            }
        }
        
        $result = Stand120_Expense::delete_expense($expense_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get target recommendation
     */
    private static function get_target_recommendation() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Admin access required'));
            return;
        }
        
        $target = floatval($_POST['target_amount'] ?? 0);
        if ($target <= 0) {
            wp_send_json_error(array('message' => 'Please enter a valid target amount'));
            return;
        }
        
        global $wpdb;
        $products_table = $wpdb->prefix . 'stand120_products';
        $items_table = $wpdb->prefix . 'stand120_order_items';
        $orders_table = $wpdb->prefix . 'stand120_orders';
        
        // Get menu products with their prices and recent sales data
        $products = $wpdb->get_results(
            "SELECT p.id, p.name, p.price, p.type,
                COALESCE((SELECT AVG(oi.quantity) FROM $items_table oi JOIN $orders_table o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0) as avg_daily_sales
            FROM $products_table p
            WHERE p.status = 'active' AND p.price > 0
            ORDER BY p.price DESC"
        );
        
        if (!$products || !is_array($products) || count($products) === 0) {
            wp_send_json_success(array(
                'target' => $target,
                'recommendations' => array(),
                'total_projected' => 0,
                'achievable' => false
            ));
            return;
        }
        
        // Suggest 20% above average daily sales, or at least 1
        $target_sales_multiplier = 1.2;
        
        // Calculate recommendation: distribute target across products based on avg sales
        $recommendations = array();
        $remaining = $target;
        
        foreach ($products as $product) {
            if ($remaining <= 0) break;
            $price = floatval($product->price);
            if ($price <= 0) continue;
            
            $avg = floatval($product->avg_daily_sales);
            $suggested_qty = max(1, round($avg > 0 ? $avg * $target_sales_multiplier : 1));
            $product_total = $price * $suggested_qty;
            
            if ($product_total > $remaining) {
                $suggested_qty = max(1, ceil($remaining / $price));
                $product_total = $price * $suggested_qty;
            }
            
            $recommendations[] = array(
                'product_name' => $product->name,
                'price' => $price,
                'suggested_qty' => $suggested_qty,
                'projected_revenue' => $product_total,
                'avg_daily_sales' => round($avg, 1)
            );
            
            $remaining -= $product_total;
        }
        
        $total_projected = array_sum(array_column($recommendations, 'projected_revenue'));
        
        wp_send_json_success(array(
            'target' => $target,
            'recommendations' => $recommendations,
            'total_projected' => $total_projected,
            'achievable' => $total_projected >= $target
        ));
    }
    
    /**
     * Get sales insights: hourly patterns, day-of-week patterns, product timing recommendations
     */
    private static function get_sales_insights() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        global $wpdb;
        $orders_table = $wpdb->prefix . 'stand120_orders';
        $items_table = $wpdb->prefix . 'stand120_order_items';
        
        // Get hourly sales distribution (last 30 days)
        $hourly_sales = $wpdb->get_results(
            "SELECT HOUR(created_at) as hour, COUNT(*) as order_count, SUM(grand_total) as total_sales
            FROM $orders_table
            WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY HOUR(created_at)
            ORDER BY hour ASC"
        );
        
        // Get day-of-week sales distribution (last 90 days)
        $dow_sales = $wpdb->get_results(
            "SELECT DAYOFWEEK(order_date) as dow, DAYNAME(order_date) as day_name, 
                COUNT(*) as order_count, SUM(grand_total) as total_sales
            FROM $orders_table
            WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            GROUP BY DAYOFWEEK(order_date), DAYNAME(order_date)
            ORDER BY dow ASC"
        );
        
        // Get product-level hourly patterns (which products sell at which times)
        $product_timing = $wpdb->get_results(
            "SELECT oi.product_name, 
                CASE 
                    WHEN HOUR(o.created_at) BETWEEN 6 AND 11 THEN 'Morning'
                    WHEN HOUR(o.created_at) BETWEEN 12 AND 16 THEN 'Afternoon'
                    WHEN HOUR(o.created_at) BETWEEN 17 AND 21 THEN 'Evening'
                    ELSE 'Night'
                END as time_period,
                SUM(oi.quantity) as qty_sold,
                SUM(oi.total) as revenue
            FROM $items_table oi
            JOIN $orders_table o ON oi.order_id = o.id
            WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY oi.product_name, time_period
            ORDER BY qty_sold DESC"
        );
        
        // Get product-level day-of-week patterns
        $product_dow = $wpdb->get_results(
            "SELECT oi.product_name, DAYNAME(o.order_date) as day_name,
                SUM(oi.quantity) as qty_sold, SUM(oi.total) as revenue
            FROM $items_table oi
            JOIN $orders_table o ON oi.order_id = o.id
            WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            GROUP BY oi.product_name, DAYNAME(o.order_date)
            ORDER BY oi.product_name, qty_sold DESC"
        );
        
        // Build best time per product
        $product_best_times = array();
        foreach ($product_timing as $row) {
            $name = $row->product_name;
            if (!isset($product_best_times[$name]) || floatval($row->qty_sold) > floatval($product_best_times[$name]->qty_sold)) {
                $product_best_times[$name] = $row;
            }
        }
        
        // Build best day per product
        $product_best_days = array();
        foreach ($product_dow as $row) {
            $name = $row->product_name;
            if (!isset($product_best_days[$name]) || floatval($row->qty_sold) > floatval($product_best_days[$name]->qty_sold)) {
                $product_best_days[$name] = $row;
            }
        }
        
        // Generate recommendations
        $recommendations = array();
        foreach ($product_best_times as $name => $time_data) {
            $day_data = $product_best_days[$name] ?? null;
            $recommendations[] = array(
                'product_name' => $name,
                'best_time' => $time_data->time_period,
                'best_time_qty' => intval($time_data->qty_sold),
                'best_day' => $day_data ? $day_data->day_name : 'N/A',
                'best_day_qty' => $day_data ? intval($day_data->qty_sold) : 0
            );
        }
        
        // Sort by total qty
        usort($recommendations, function($a, $b) {
            return ($b['best_time_qty'] + $b['best_day_qty']) - ($a['best_time_qty'] + $a['best_day_qty']);
        });
        
        wp_send_json_success(array(
            'hourly_sales' => $hourly_sales,
            'dow_sales' => $dow_sales,
            'product_timing' => $product_timing,
            'recommendations' => $recommendations
        ));
    }
    
    /**
     * Delete an order and recalculate financial summary
     */
    private static function delete_order() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $order_id = intval($_POST['order_id'] ?? 0);
        if ($order_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
            return;
        }
        
        global $wpdb;
        $orders_table = $wpdb->prefix . 'stand120_orders';
        $items_table = $wpdb->prefix . 'stand120_order_items';
        $summary_table = $wpdb->prefix . 'stand120_financial_summary';
        $prep_table = $wpdb->prefix . 'stand120_order_preparation';
        
        // Get the order data first
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
            return;
        }
        
        $order_date = $order->order_date;
        $today = current_time('Y-m-d');
        
        // Non-super-admin can only delete orders from today
        if (!Stand120_Auth::is_super_admin() && $order_date !== $today) {
            wp_send_json_error(array('message' => 'You can only delete orders from today. Contact a super admin to delete older orders.'));
            return;
        }
        
        // Get order items before deletion (for recalculating preparation sold values)
        $order_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $items_table WHERE order_id = %d",
            $order_id
        ));
        
        // Delete order items
        $wpdb->delete($items_table, array('order_id' => $order_id), array('%d'));
        
        // Delete the order
        $wpdb->delete($orders_table, array('id' => $order_id), array('%d'));
        
        // Recalculate order preparation sold values for that date
        foreach ($order_items as $item) {
            $total_sold = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(oi.quantity), 0) 
                 FROM $items_table oi 
                 JOIN $orders_table o ON oi.order_id = o.id 
                 WHERE oi.product_id = %d AND o.order_date = %s",
                $item->product_id,
                $order_date
            ));
            
            $wpdb->update(
                $prep_table,
                array('total_sold' => $total_sold),
                array('product_id' => $item->product_id, 'prep_date' => $order_date),
                array('%d'),
                array('%d', '%s')
            );
        }
        
        // Recalculate financial summary for that date
        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COALESCE(SUM(cash_amount), 0) as total_cash,
                COALESCE(SUM(transfer_amount), 0) as total_transfer,
                COALESCE(SUM(grand_total), 0) as total_sales,
                COALESCE(SUM(delivery_fee), 0) as total_delivery,
                COUNT(*) as order_count
            FROM $orders_table WHERE order_date = %s",
            $order_date
        ));
        
        $wpdb->update(
            $summary_table,
            array(
                'cash_sales' => $totals->total_cash,
                'transfer_sales' => $totals->total_transfer,
                'total_sales' => $totals->total_sales,
                'delivery_fees' => $totals->total_delivery
            ),
            array('summary_date' => $order_date),
            array('%f', '%f', '%f', '%f'),
            array('%s')
        );
        
        // Log activity
        Stand120_Database::log_activity(
            'delete_order',
            'stand120_orders',
            $order_id,
            array('grand_total' => $order->grand_total, 'order_date' => $order_date),
            null
        );
        
        wp_send_json_success(array(
            'message' => sprintf('Order #%d deleted successfully. Financial summary for %s has been recalculated.', $order_id, $order_date)
        ));
    }
    
    /**
     * Get all orders with pagination and filters
     */
    private static function get_all_orders() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        global $wpdb;
        $orders_table = $wpdb->prefix . 'stand120_orders';
        $items_table = $wpdb->prefix . 'stand120_order_items';
        
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = min(100, max(1, intval($_POST['per_page'] ?? 20)));
        $offset = ($page - 1) * $per_page;
        
        $where = "1=1";
        $params = array();
        
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        if ($date_from) {
            $where .= " AND o.order_date >= %s";
            $params[] = $date_from;
        }
        if ($date_to) {
            $where .= " AND o.order_date <= %s";
            $params[] = $date_to;
        }
        
        // Count total
        $count_query = "SELECT COUNT(*) FROM $orders_table o WHERE $where";
        if (!empty($params)) {
            $total = $wpdb->get_var($wpdb->prepare($count_query, $params));
        } else {
            $total = $wpdb->get_var($count_query);
        }
        
        $total_pages = max(1, ceil($total / $per_page));
        
        // Get orders
        $staff_table = $wpdb->prefix . 'stand120_staff';
        $query = "SELECT o.*, 
                    s.full_name as staff_name,
                    (SELECT COUNT(*) FROM $items_table WHERE order_id = o.id) as item_count
                  FROM $orders_table o 
                  LEFT JOIN $staff_table s ON o.staff_id = s.id
                  WHERE $where 
                  ORDER BY o.order_date DESC, o.id DESC 
                  LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        $orders = $wpdb->get_results($wpdb->prepare($query, $params));
        
        wp_send_json_success(array(
            'orders' => $orders,
            'total' => intval($total),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => $total_pages
        ));
    }
    
    /**
     * Update a financial summary field (super admin only)
     */
    private static function update_financial_field() {
        if (!Stand120_Auth::is_super_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Super admin access required'));
            return;
        }
        
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $field_value = sanitize_text_field($_POST['field_value'] ?? '');
        $date = sanitize_text_field($_POST['date'] ?? '');
        
        $allowed_fields = array('extras_amount', 'extras_remark', 'expenses_amount', 'expenses_remark', 'old_cash', 'cash_left');
        
        if (!in_array($field_name, $allowed_fields)) {
            wp_send_json_error(array('message' => 'Invalid field name'));
            return;
        }
        
        if (empty($date)) {
            wp_send_json_error(array('message' => 'Date is required'));
            return;
        }
        
        global $wpdb;
        $summary_table = $wpdb->prefix . 'stand120_financial_summary';
        
        // Determine format based on field type
        $format = in_array($field_name, array('extras_remark', 'expenses_remark')) ? '%s' : '%f';
        if (!in_array($field_name, array('extras_remark', 'expenses_remark'))) {
            $field_value = floatval($field_value);
        }
        
        $updated = $wpdb->update(
            $summary_table,
            array($field_name => $field_value),
            array('summary_date' => $date),
            array($format),
            array('%s')
        );
        
        if ($updated === false) {
            wp_send_json_error(array('message' => 'Failed to update field'));
            return;
        }
        
        // Log activity
        Stand120_Database::log_activity(
            'update_financial_field',
            'stand120_financial_summary',
            null,
            array('field' => $field_name, 'date' => $date),
            array('field' => $field_name, 'value' => $field_value, 'date' => $date)
        );
        
        wp_send_json_success(array(
            'message' => sprintf('Field "%s" updated successfully for %s', $field_name, $date)
        ));
    }
    
    private static function clear_all_records() {
        if (!Stand120_Auth::is_super_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Super Admin access required'));
            return;
        }
        
        $result = Stand120_Admin_Panel::clear_all_records();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Save reconciliation check
     */
    private static function save_reconciliation_check() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $result = Stand120_Reconciliation::save_check($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get reconciliation data for a month
     */
    private static function get_reconciliation_data() {
        if (!Stand120_Auth::is_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }
        
        $year = intval($_POST['year'] ?? date('Y'));
        $month = intval($_POST['month'] ?? date('n'));
        
        $result = Stand120_Reconciliation::get_month_data($year, $month);
        wp_send_json_success($result);
    }

    /**
     * Get system diagnostics (Super Admin only)
     */
    private static function get_system_diagnostics() {
        if (!Stand120_Auth::is_super_admin()) {
            wp_send_json_error(array('message' => 'Unauthorized - Super Admin access required'));
            return;
        }

        $result = Stand120_Admin_Panel::get_system_diagnostics();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
