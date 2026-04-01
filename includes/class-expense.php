<?php
if (!defined('ABSPATH')) exit;

class Stand120_Expense {
    
    /**
     * Submit expenses
     */
    public static function submit_expenses($data) {
        global $wpdb;
        
        if (!is_user_logged_in()) {
            return array('success' => false, 'message' => 'User is not logged in.');
        }
        
        $user_id = get_current_user_id();
        $staff_table = $wpdb->prefix . 'stand120_staff';
        
        $staff = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $staff_table WHERE user_id = %d",
            $user_id
        ));
        
        if (!$staff) {
            return array('success' => false, 'message' => 'Staff record not found.');
        }
        
        $staff_id = $staff->id;
        
        // Parse expenses
        $expenses = array();
        if (isset($data['expenses'])) {
            if (is_array($data['expenses'])) {
                $expenses = $data['expenses'];
            } elseif (is_string($data['expenses'])) {
                $decoded = json_decode(stripslashes($data['expenses']), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $expenses = $decoded;
                }
            }
        }
        
        if (empty($expenses)) {
            return array('success' => false, 'message' => 'No expense items provided');
        }
        
        $expense_date = sanitize_text_field($data['date'] ?? date('Y-m-d'));
        $table = $wpdb->prefix . 'stand120_expenses';
        $total_amount = 0;
        $inserted = 0;
        
        foreach ($expenses as $expense) {
            $description = sanitize_text_field($expense['description'] ?? '');
            $amount = floatval($expense['amount'] ?? 0);
            $quantity = intval($expense['quantity'] ?? 1);
            $total = floatval($expense['total'] ?? ($amount * $quantity));
            
            if (empty($description) || $amount <= 0) {
                continue;
            }
            
            $result = $wpdb->insert($table, array(
                'staff_id' => $staff_id,
                'expense_date' => $expense_date,
                'description' => $description,
                'amount' => $amount,
                'quantity' => $quantity,
                'total' => $total
            ), array('%d', '%s', '%s', '%f', '%d', '%f'));
            
            if ($result !== false) {
                $total_amount += $total;
                $inserted++;
            }
        }
        
        if ($inserted === 0) {
            return array('success' => false, 'message' => 'No valid expenses to submit');
        }
        
        return array(
            'success' => true,
            'message' => $inserted . ' expense(s) recorded successfully',
            'total_amount' => $total_amount,
            'count' => $inserted
        );
    }
    
    /**
     * Get expenses for a given date
     */
    public static function get_expenses($date) {
        global $wpdb;
        $table = $wpdb->prefix . 'stand120_expenses';
        
        $expenses = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE expense_date = %s ORDER BY created_at DESC",
            $date
        ));
        
        return array('expenses' => $expenses);
    }
    
    /**
     * Get expense history with filters and pagination
     */
    public static function get_expense_history($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'stand120_expenses';
        $staff_table = $wpdb->prefix . 'stand120_staff';
        
        $sql = "SELECT e.*, s.full_name as staff_name
                FROM $table e
                LEFT JOIN $staff_table s ON e.staff_id = s.id
                WHERE 1=1";
        $params = array();
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND e.expense_date >= %s";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND e.expense_date <= %s";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY e.expense_date DESC, e.created_at DESC";
        
        // Pagination
        $page = max(1, intval($filters['page'] ?? 1));
        $per_page = max(1, min(100, intval($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $per_page;
        
        // Get total count
        $count_sql = str_replace("SELECT e.*, s.full_name as staff_name", "SELECT COUNT(*)", $sql);
        if (!empty($params)) {
            $total = $wpdb->get_var($wpdb->prepare($count_sql, $params));
        } else {
            $total = $wpdb->get_var($count_sql);
        }
        
        $sql .= " LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        if (!empty($params)) {
            $records = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $records = $wpdb->get_results($sql);
        }
        
        return array(
            'records' => $records,
            'total_records' => intval($total),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }
    
    /**
     * Delete a single expense by id
     */
    public static function delete_expense($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'stand120_expenses';
        
        $id = intval($id);
        if ($id <= 0) {
            return array('success' => false, 'message' => 'Invalid expense ID');
        }
        
        $result = $wpdb->delete($table, array('id' => $id), array('%d'));
        
        if ($result !== false) {
            return array('success' => true, 'message' => 'Expense deleted successfully');
        } else {
            return array('success' => false, 'message' => 'Failed to delete expense');
        }
    }
}
