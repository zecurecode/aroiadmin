<?php
/**
 * Department Cards Shortcode
 *
 * @package Multiside_Aroi_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Department cards display class
 */
class Multiside_Aroi_Department_Cards {

    /**
     * Render department cards shortcode
     *
     * Usage: [aroi_department_cards]
     * Usage with specific sites: [aroi_department_cards sites="4,5,6,7"]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'sites' => 'all',  // 'all' or comma-separated site IDs
            'columns' => '3',   // Number of columns (2, 3, or 4)
            'show_hours' => 'yes',
            'show_status' => 'yes',
        ), $atts);

        // Get departments from database
        $departments = self::get_departments($atts['sites']);

        if (empty($departments)) {
            return '<p>Ingen avdelinger funnet.</p>';
        }

        // Start output
        ob_start();
        ?>
        <div class="aroi-department-cards columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php foreach ($departments as $dept): ?>
                <?php
                $hours = Multiside_Aroi_Opening_Hours::get_hours($dept['site_id']);
                $is_open = Multiside_Aroi_Opening_Hours::is_open_now($dept['site_id']);
                $status_class = $is_open ? 'open' : 'closed';
                $status_text = $is_open ? 'Åpen nå' : 'Stengt';
                ?>
                <div class="department-card <?php echo esc_attr($status_class); ?>">
                    <div class="card-header">
                        <h3 class="department-name"><?php echo esc_html($dept['name']); ?></h3>
                        <?php if ($atts['show_status'] === 'yes'): ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <?php if ($atts['show_hours'] === 'yes' && $hours): ?>
                            <div class="opening-hours">
                                <i class="fas fa-clock"></i>
                                <strong>I dag:</strong>
                                <?php if ($hours['is_closed']): ?>
                                    <span class="closed-text">Stengt</span>
                                <?php else: ?>
                                    <span class="hours-text">
                                        <?php echo esc_html($hours['open_time']); ?> - <?php echo esc_html($hours['close_time']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($dept['phone'])): ?>
                            <div class="phone-info">
                                <i class="fas fa-phone-volume"></i>
                                <a href="tel:<?php echo esc_attr(str_replace(' ', '', $dept['phone'])); ?>" class="phone-link">
                                    <?php echo esc_html($dept['phone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($dept['address'])): ?>
                            <div class="map-container">
                                <?php
                                $address_encoded = urlencode($dept['address']);
                                $map_embed_url = "https://www.google.com/maps/embed/v1/place?key=AIzaSyDummy&q={$address_encoded}";
                                $directions_url = "https://www.google.com/maps/dir/?api=1&destination={$address_encoded}";
                                ?>
                                <div class="map-wrapper">
                                    <iframe
                                        src="https://www.google.com/maps?q=<?php echo $address_encoded; ?>&output=embed"
                                        width="100%"
                                        height="200"
                                        style="border:0;"
                                        allowfullscreen=""
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade">
                                    </iframe>
                                </div>
                                <a href="<?php echo esc_url($directions_url); ?>"
                                   class="btn-directions"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="fas fa-directions"></i> Få veibeskrivelse
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get departments from database
     *
     * @param string $sites 'all' or comma-separated site IDs
     * @return array Departments data
     */
    private static function get_departments($sites) {
        // Get base data from _apningstid table
        $sql = "SELECT AvdID as site_id, Navn as name, url, Telefon as phone FROM _apningstid";

        // Filter by specific sites if requested
        if ($sites !== 'all') {
            $site_ids = array_map('intval', explode(',', $sites));
            $site_ids_str = implode(',', $site_ids);
            $sql .= " WHERE AvdID IN ($site_ids_str)";
        }

        $sql .= " ORDER BY Navn ASC";

        $result = Multiside_Aroi_Database::query($sql);

        if (!$result) {
            error_log('MultiSide Aroi: Failed to fetch departments - SQL error');
            return array();
        }

        $departments = array();
        while ($row = mysqli_fetch_assoc($result)) {
            // Enhance with location data from locations table if available
            $location_data = self::get_location_data($row['site_id']);
            if ($location_data) {
                // Use location phone if available, otherwise use _apningstid phone
                if (!empty($location_data['phone'])) {
                    $row['phone'] = $location_data['phone'];
                }
                $row['address'] = $location_data['address'];
            }

            $departments[] = $row;
        }

        return $departments;
    }

    /**
     * Get location data from locations table
     *
     * @param int $site_id Site ID
     * @return array|false Location data or false
     */
    private static function get_location_data($site_id) {
        $sql = sprintf(
            "SELECT phone, address FROM locations WHERE site_id = %d LIMIT 1",
            intval($site_id)
        );

        $result = Multiside_Aroi_Database::query($sql);

        if (!$result || mysqli_num_rows($result) === 0) {
            return false;
        }

        return mysqli_fetch_assoc($result);
    }
}
