<?php
/**
 * Location Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Location_Functions {
    
    /**
     * Get delivery time for a location
     */
    public static function get_delivery_time($site_id) {
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}/delivery-time");
        
        return $response ? $response['delivery_time'] : 30;
    }
    
    /**
     * Shortcode for delivery time
     */
    public static function shortcode_delivery_time($atts) {
        $site_id = Aroi_Laravel_Integration::get_site_id();
        $delivery_time = self::get_delivery_time($site_id);
        
        return sprintf(
            '<span class="aroi-delivery-time">%d %s</span>',
            $delivery_time,
            __('minutter', 'aroi-laravel')
        );
    }
    
    /**
     * Shortcode for opening hours
     */
    public static function shortcode_opening_hours($atts) {
        $atts = shortcode_atts(array(
            'site' => Aroi_Laravel_Integration::get_site_id()
        ), $atts);
        
        $site_id = intval($atts['site']);
        
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}/opening-hours");
        
        if (!$response) {
            return '<span style="color:red;">' . __('Kunne ikke hente åpningstider', 'aroi-laravel') . '</span>';
        }
        
        $open_time = $response['open_time'] ?? '';
        $close_time = $response['close_time'] ?? '';
        $status = $response['status'] ?? 0;
        $is_open = $response['is_open'] ?? false;
        
        ob_start();
        ?>
        <div class="aroi-opening-hours">
            <p class="hours"><?php echo esc_html($open_time); ?> til <?php echo esc_html($close_time); ?></p>
            <?php if ($status == 0 || !$is_open): ?>
                <p class="status closed">
                    <span style="color:red;">
                        <?php printf(__('Åpner klokken %s i dag. Du kan fortsatt bestille for henting innen åpningstiden.', 'aroi-laravel'), $open_time); ?>
                    </span>
                </p>
            <?php else: ?>
                <p class="status open">
                    <span style="color:green;">
                        <?php _e('Åpen for henting i dag', 'aroi-laravel'); ?>
                    </span>
                </p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode for location status
     */
    public static function shortcode_location_status($atts) {
        $atts = shortcode_atts(array(
            'site' => Aroi_Laravel_Integration::get_site_id(),
            'format' => 'simple' // simple, detailed, widget
        ), $atts);
        
        $site_id = intval($atts['site']);
        
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}/is-open");
        
        if (!$response) {
            return '<span class="aroi-status unknown">' . __('Status ukjent', 'aroi-laravel') . '</span>';
        }
        
        $is_open = $response['is_open'] ?? false;
        $message = $response['message'] ?? '';
        
        if ($atts['format'] === 'simple') {
            $class = $is_open ? 'open' : 'closed';
            return sprintf('<span class="aroi-status %s">%s</span>', $class, esc_html($message));
        }
        
        // Detailed format
        ob_start();
        ?>
        <div class="aroi-location-status <?php echo $is_open ? 'is-open' : 'is-closed'; ?>">
            <div class="status-indicator">
                <span class="status-dot"></span>
                <span class="status-text"><?php echo esc_html($message); ?></span>
            </div>
            <?php if ($response['open_time'] && $response['close_time']): ?>
                <div class="hours-info">
                    <i class="far fa-clock"></i>
                    <?php echo esc_html($response['open_time']); ?> - <?php echo esc_html($response['close_time']); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode for weekly hours
     */
    public static function shortcode_weekly_hours($atts) {
        $atts = shortcode_atts(array(
            'site' => Aroi_Laravel_Integration::get_site_id(),
            'highlight_today' => 'yes'
        ), $atts);
        
        $site_id = intval($atts['site']);
        
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}/all-hours");
        
        if (!$response || !isset($response['schedule'])) {
            return '<p>' . __('Kunne ikke hente åpningstider', 'aroi-laravel') . '</p>';
        }
        
        $today = strtolower(date('l'));
        $day_translations = array(
            'monday' => __('Mandag', 'aroi-laravel'),
            'tuesday' => __('Tirsdag', 'aroi-laravel'),
            'wednesday' => __('Onsdag', 'aroi-laravel'),
            'thursday' => __('Torsdag', 'aroi-laravel'),
            'friday' => __('Fredag', 'aroi-laravel'),
            'saturday' => __('Lørdag', 'aroi-laravel'),
            'sunday' => __('Søndag', 'aroi-laravel')
        );
        
        ob_start();
        ?>
        <div class="aroi-weekly-hours">
            <table>
                <tbody>
                    <?php foreach ($response['schedule'] as $day_info): 
                        $day_key = strtolower($day_info['day']);
                        $is_today = ($atts['highlight_today'] === 'yes' && $day_key === $today);
                        $day_name = $day_translations[$day_key] ?? $day_info['day'];
                    ?>
                        <tr class="<?php echo $is_today ? 'today' : ''; ?>">
                            <td class="day"><?php echo esc_html($day_name); ?></td>
                            <td class="hours">
                                <?php if ($day_info['status'] == 0 || !$day_info['open_time']): ?>
                                    <span class="closed"><?php _e('Stengt', 'aroi-laravel'); ?></span>
                                <?php else: ?>
                                    <?php echo esc_html($day_info['open_time']); ?> - <?php echo esc_html($day_info['close_time']); ?>
                                <?php endif; ?>
                                <?php if (!empty($day_info['notes'])): ?>
                                    <span class="notes">(<?php echo esc_html($day_info['notes']); ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get site name by ID
     */
    public static function get_site_name($site_id) {
        $mapping = array(
            4 => 'lade',
            5 => 'gramyra',
            6 => 'moan',
            7 => 'namsos',
            10 => 'frosta',
            11 => 'hell',
            12 => 'steinkjer'
        );
        
        return $mapping[$site_id] ?? 'unknown';
    }
    
    /**
     * Get license by site ID
     */
    public static function get_license($site_id) {
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}");
        
        return $response ? $response['license'] : 0;
    }
    
    /**
     * Check if location is open
     */
    public static function is_open($site_id) {
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}/is-open", false); // Don't cache this
        
        return $response ? $response['is_open'] : false;
    }
    
    /**
     * Get opening time
     */
    public static function get_open_time($site_id) {
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}/opening-hours");
        
        return $response ? $response['open_time'] : null;
    }
    
    /**
     * Get closing time
     */
    public static function get_close_time($site_id) {
        $api = Aroi_Laravel_Integration::get_instance()->get_api_client();
        $response = $api->get("/wordpress/location/{$site_id}/opening-hours");
        
        return $response ? $response['close_time'] : null;
    }
}