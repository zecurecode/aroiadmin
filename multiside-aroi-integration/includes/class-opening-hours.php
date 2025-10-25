<?php
/**
 * Opening Hours Service
 *
 * @package Multiside_Aroi_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Opening hours class - Uses _apningstid table
 */
class Multiside_Aroi_Opening_Hours {

    /**
     * Site ID to location mapping for _apningstid table
     */
    private static $site_to_avd_map = array(
        7  => 1,  // Namsos
        4  => 2,  // Lade
        6  => 3,  // Moan
        5  => 4,  // Gramyra
        10 => 5,  // Frosta
        11 => 6,  // Hell
        12 => 7,  // Steinkjer
    );

    /**
     * Day name mapping (Norwegian abbreviations to full names)
     */
    private static $day_map = array(
        'Man' => 'Monday',
        'Tir' => 'Tuesday',
        'Ons' => 'Wednesday',
        'Tor' => 'Thursday',
        'Fre' => 'Friday',
        'Lor' => 'Saturday',
        'Son' => 'Sunday',
    );

    /**
     * Get opening hours for a specific site/day from _apningstid table
     *
     * @param int $site_id Site ID (4, 5, 6, 7, 10, 11, 12)
     * @param string|null $day_abbr Day abbreviation (Man, Tir, etc.) - defaults to today
     * @return array|false Opening hours data or false
     */
    public static function get_hours($site_id, $day_abbr = null) {
        if (!isset(self::$site_to_avd_map[$site_id])) {
            return false;
        }

        $avd_id = self::$site_to_avd_map[$site_id];

        // Default to today if no day specified
        if ($day_abbr === null) {
            $day_abbr = self::get_current_day_abbr();
        }

        // Build column names based on day
        $start_col = $day_abbr . 'Start';
        $stop_col = $day_abbr . 'Stopp';
        $closed_col = $day_abbr . 'Stengt';

        $sql = sprintf(
            "SELECT %s as open_time, %s as close_time, %s as is_closed, Navn as location_name
             FROM _apningstid
             WHERE AvdID = %d
             LIMIT 1",
            $start_col,
            $stop_col,
            $closed_col,
            intval($avd_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if (!$result || mysqli_num_rows($result) === 0) {
            return false;
        }

        $row = mysqli_fetch_assoc($result);

        return array(
            'site_id' => $site_id,
            'location_name' => $row['location_name'],
            'day' => isset(self::$day_map[$day_abbr]) ? self::$day_map[$day_abbr] : $day_abbr,
            'open_time' => $row['open_time'],
            'close_time' => $row['close_time'],
            'is_closed' => (bool) $row['is_closed'],
            'is_open' => !$row['is_closed']
        );
    }

    /**
     * Get full week schedule for a site
     *
     * @param int $site_id Site ID
     * @return array Week schedule
     */
    public static function get_week_schedule($site_id) {
        $days = array('Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lor', 'Son');
        $schedule = array();

        foreach ($days as $day) {
            $hours = self::get_hours($site_id, $day);
            if ($hours) {
                $schedule[] = $hours;
            }
        }

        return $schedule;
    }

    /**
     * Check if location is currently open
     *
     * @param int $site_id Site ID
     * @return bool True if open, false if closed
     */
    public static function is_open_now($site_id) {
        $hours = self::get_hours($site_id);

        if (!$hours) {
            return false;
        }

        // Check if manually closed
        if ($hours['is_closed']) {
            return false;
        }

        // Get current time in Norway timezone
        $current_time = self::get_norway_time();
        $open_time = strtotime($hours['open_time']);
        $close_time = strtotime($hours['close_time']);

        return ($current_time >= $open_time && $current_time <= $close_time);
    }

    /**
     * Get next opening time
     *
     * @param int $site_id Site ID
     * @return string|null Next opening time or null
     */
    public static function get_next_opening_time($site_id) {
        $hours = self::get_hours($site_id);

        if (!$hours || !$hours['is_closed']) {
            return $hours['open_time'];
        }

        // Check tomorrow
        $tomorrow = self::get_tomorrow_day_abbr();
        $tomorrow_hours = self::get_hours($site_id, $tomorrow);

        return $tomorrow_hours ? $tomorrow_hours['open_time'] : null;
    }

    /**
     * Get delivery time for location
     *
     * @param int $site_id Site ID
     * @return int Delivery time in minutes (default 30)
     */
    public static function get_delivery_time($site_id) {
        // Try to get from leveringstid table
        $sql = sprintf(
            "SELECT tid FROM leveringstid WHERE id = %d LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            return intval($row['tid']);
        }

        // Default to 30 minutes
        return 30;
    }

    /**
     * Get current day abbreviation (Norwegian)
     *
     * @return string Day abbreviation (Man, Tir, etc.)
     */
    private static function get_current_day_abbr() {
        $day_num = date('N'); // 1 (Monday) to 7 (Sunday)
        $days = array('Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lor', 'Son');
        return $days[$day_num - 1];
    }

    /**
     * Get tomorrow's day abbreviation
     *
     * @return string Day abbreviation
     */
    private static function get_tomorrow_day_abbr() {
        $day_num = date('N'); // 1-7
        $days = array('Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lor', 'Son');
        $tomorrow_num = ($day_num % 7); // Wrap around to Monday after Sunday
        return $days[$tomorrow_num];
    }

    /**
     * Get current time in Norway timezone
     *
     * @return int Unix timestamp
     */
    private static function get_norway_time() {
        $tz = new DateTimeZone('Europe/Oslo');
        $dt = new DateTime('now', $tz);
        return strtotime($dt->format('H:i:s'));
    }

    /**
     * Render opening hours shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'site' => get_current_blog_id(),
        ), $atts);

        $site_id = intval($atts['site']);
        $hours = self::get_hours($site_id);

        if (!$hours) {
            return '<p>Åpningstider ikke tilgjengelig</p>';
        }

        $is_open = self::is_open_now($site_id);
        $status_class = $is_open ? 'open' : 'closed';
        $status_text = $is_open
            ? sprintf('Åpen til %s', $hours['close_time'])
            : sprintf('Stengt - Åpner %s', self::get_next_opening_time($site_id));

        ob_start();
        ?>
        <div class="aroi-opening-hours <?php echo esc_attr($status_class); ?>">
            <div class="hours-display">
                <strong><?php echo esc_html($hours['open_time']); ?></strong>
                til
                <strong><?php echo esc_html($hours['close_time']); ?></strong>
            </div>
            <div class="status-message">
                <?php echo esc_html($status_text); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render delivery time shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render_delivery_time($atts) {
        $atts = shortcode_atts(array(
            'site' => get_current_blog_id(),
        ), $atts);

        $site_id = intval($atts['site']);
        $delivery_time = self::get_delivery_time($site_id);

        return sprintf('<span class="aroi-delivery-time">%d</span>', $delivery_time);
    }
}
