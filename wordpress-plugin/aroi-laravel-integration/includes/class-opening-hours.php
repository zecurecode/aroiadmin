<?php
/**
 * Opening Hours Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Opening_Hours {
    
    /**
     * AJAX handler to check open status
     */
    public static function ajax_check_open_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'aroi_ajax_nonce')) {
            wp_die('Security check failed');
        }
        
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : Aroi_Laravel_Integration::get_site_id();
        
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}/is-open", false); // Don't cache
        
        if ($response) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error(array('message' => __('Kunne ikke hente status', 'aroi-laravel')));
        }
    }
    
    /**
     * Get Norwegian day name
     */
    public static function get_norwegian_day($english_day = null) {
        if (!$english_day) {
            $english_day = date('l');
        }
        
        $days = array(
            'Monday' => 'Mandag',
            'Tuesday' => 'Tirsdag',
            'Wednesday' => 'Onsdag',
            'Thursday' => 'Torsdag',
            'Friday' => 'Fredag',
            'Saturday' => 'Lørdag',
            'Sunday' => 'Søndag'
        );
        
        return $days[$english_day] ?? $english_day;
    }
    
    /**
     * Get next day Norwegian
     */
    public static function get_next_norwegian_day() {
        $tomorrow = date('l', strtotime('tomorrow'));
        return self::get_norwegian_day($tomorrow);
    }
    
    /**
     * Format time slots for display
     */
    public static function format_time_slots($start_time, $end_time, $interval = 15, $format = 'H:i') {
        $slots = array();
        
        $current = strtotime($start_time);
        $end = strtotime($end_time);
        
        while ($current <= $end) {
            $slots[] = date($format, $current);
            $current = strtotime("+{$interval} minutes", $current);
        }
        
        return $slots;
    }
    
    /**
     * Check if time is within opening hours
     */
    public static function is_within_hours($time, $open_time, $close_time) {
        $time_stamp = is_numeric($time) ? $time : strtotime($time);
        $open_stamp = is_numeric($open_time) ? $open_time : strtotime($open_time);
        $close_stamp = is_numeric($close_time) ? $close_time : strtotime($close_time);
        
        return $time_stamp >= $open_stamp && $time_stamp <= $close_stamp;
    }
    
    /**
     * Get next opening time
     */
    public static function get_next_opening($site_id) {
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        
        // Get current status
        $current_status = $api->get("/wordpress/location/{$site_id}/is-open");
        
        if ($current_status && $current_status['is_open']) {
            // Already open
            return array(
                'is_open_now' => true,
                'next_open' => null,
                'message' => $current_status['message']
            );
        }
        
        // Get weekly schedule
        $schedule = $api->get("/wordpress/location/{$site_id}/all-hours");
        
        if (!$schedule || !isset($schedule['schedule'])) {
            return null;
        }
        
        // Find next opening time
        $current_day = date('N'); // 1 (Monday) to 7 (Sunday)
        $current_time = date('H:i');
        
        for ($i = 0; $i < 7; $i++) {
            $check_day = (($current_day - 1 + $i) % 7) + 1;
            $day_name = self::get_day_name_by_number($check_day);
            
            foreach ($schedule['schedule'] as $day_hours) {
                if (strtolower($day_hours['day']) === strtolower($day_name)) {
                    if ($day_hours['status'] == 1 && $day_hours['open_time']) {
                        // Check if this is today and we haven't passed opening time
                        if ($i === 0 && $current_time < $day_hours['open_time']) {
                            return array(
                                'is_open_now' => false,
                                'next_open' => $day_hours['open_time'],
                                'next_open_day' => 'i dag',
                                'message' => sprintf(__('Åpner kl %s i dag', 'aroi-laravel'), $day_hours['open_time'])
                            );
                        }
                        // Future day
                        elseif ($i > 0) {
                            $norwegian_day = self::get_norwegian_day($day_name);
                            return array(
                                'is_open_now' => false,
                                'next_open' => $day_hours['open_time'],
                                'next_open_day' => $norwegian_day,
                                'message' => sprintf(__('Åpner %s kl %s', 'aroi-laravel'), $norwegian_day, $day_hours['open_time'])
                            );
                        }
                    }
                    break;
                }
            }
        }
        
        return array(
            'is_open_now' => false,
            'next_open' => null,
            'message' => __('Ingen åpningstider funnet', 'aroi-laravel')
        );
    }
    
    /**
     * Get day name by number
     */
    private static function get_day_name_by_number($number) {
        $days = array(
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        );
        
        return $days[$number] ?? 'Monday';
    }
    
    /**
     * Update opening status
     */
    public static function update_status($site_id, $status) {
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        
        $data = array(
            'status' => intval($status)
        );
        
        return $api->post("/wordpress/location/{$site_id}/update-status", $data);
    }
    
    /**
     * Get special hours or holidays
     */
    public static function get_special_hours($site_id, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        // This would need a new API endpoint in Laravel
        // For now, return null
        return null;
    }
}