<?php
/**
 * Reconciliation Class
 * Handles reconciliation calendar where admins verify daily records
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stand120_Reconciliation {
    
    /**
     * Save a reconciliation check for a date
     * Two different admins must check each date
     */
    public static function save_check($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'stand120_reconciliation';
        
        if (!Stand120_Auth::is_admin()) {
            return array('success' => false, 'message' => 'Unauthorized - Admin access required');
        }
        
        $date = sanitize_text_field($data['date'] ?? '');
        $remarks = sanitize_textarea_field($data['remarks'] ?? '');
        $staff_id = Stand120_Auth::get_current_staff_id();
        
        if (empty($date)) {
            return array('success' => false, 'message' => 'Date is required');
        }
        
        // Cannot check future dates
        if ($date > date('Y-m-d')) {
            return array('success' => false, 'message' => 'Cannot reconcile future dates');
        }
        
        // Get existing record for this date
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE reconciliation_date = %s",
            $date
        ));
        
        if ($existing) {
            // Check if this admin has already checked
            if ($existing->admin1_staff_id == $staff_id || $existing->admin2_staff_id == $staff_id) {
                return array('success' => false, 'message' => 'You have already verified this date');
            }
            
            // Both slots are taken
            if ($existing->admin1_staff_id && $existing->admin2_staff_id) {
                return array('success' => false, 'message' => 'This date has already been fully verified by two admins');
            }
            
            // Fill the second slot
            $wpdb->update($table, array(
                'admin2_staff_id' => $staff_id,
                'admin2_checked_at' => current_time('mysql'),
                'admin2_remarks' => $remarks
            ), array('id' => $existing->id));
            
            $record_id = $existing->id;
        } else {
            // Create new record - first admin to check
            $wpdb->insert($table, array(
                'reconciliation_date' => $date,
                'admin1_staff_id' => $staff_id,
                'admin1_checked_at' => current_time('mysql'),
                'admin1_remarks' => $remarks
            ));
            
            $record_id = $wpdb->insert_id;
        }
        
        Stand120_Database::log_activity('reconciliation_check', 'stand120_reconciliation', $record_id, null, array(
            'date' => $date,
            'remarks' => $remarks
        ));
        
        return array(
            'success' => true,
            'message' => 'Date verified successfully'
        );
    }
    
    /**
     * Get reconciliation data for a given month
     */
    public static function get_month_data($year, $month) {
        global $wpdb;
        $table = $wpdb->prefix . 'stand120_reconciliation';
        $staff_table = $wpdb->prefix . 'stand120_staff';
        
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, 
                    s1.full_name as admin1_name,
                    s2.full_name as admin2_name
             FROM $table r
             LEFT JOIN $staff_table s1 ON r.admin1_staff_id = s1.id
             LEFT JOIN $staff_table s2 ON r.admin2_staff_id = s2.id
             WHERE r.reconciliation_date BETWEEN %s AND %s
             ORDER BY r.reconciliation_date ASC",
            $start_date, $end_date
        ));
        
        // Index by date for easy lookup
        $data = array();
        foreach ($records as $record) {
            $data[$record->reconciliation_date] = array(
                'id' => $record->id,
                'date' => $record->reconciliation_date,
                'admin1_staff_id' => $record->admin1_staff_id,
                'admin1_name' => $record->admin1_name,
                'admin1_checked_at' => $record->admin1_checked_at,
                'admin1_remarks' => $record->admin1_remarks,
                'admin2_staff_id' => $record->admin2_staff_id,
                'admin2_name' => $record->admin2_name,
                'admin2_checked_at' => $record->admin2_checked_at,
                'admin2_remarks' => $record->admin2_remarks,
                'fully_checked' => !empty($record->admin1_staff_id) && !empty($record->admin2_staff_id)
            );
        }
        
        return array(
            'data' => $data,
            'year' => intval($year),
            'month' => intval($month)
        );
    }
}
