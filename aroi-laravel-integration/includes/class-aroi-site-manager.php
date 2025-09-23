<?php
/**
 * Site management class - handles site-specific information and mappings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aroi_Site_Manager {

    private $site_mappings;
    private $api;

    public function __construct() {
        $this->api = new Aroi_Laravel_API();

        // Site ID to license mapping (for backward compatibility)
        $this->site_mappings = array(
            7 => array('name' => 'Namsos', 'license' => 6714, 'user_id' => 10),
            4 => array('name' => 'Lade', 'license' => 12381, 'user_id' => 11),
            6 => array('name' => 'Moan', 'license' => 5203, 'user_id' => 12),
            5 => array('name' => 'Gramyra', 'license' => 6715, 'user_id' => 13),
            10 => array('name' => 'Frosta', 'license' => 14780, 'user_id' => 14),
            11 => array('name' => 'Hell', 'license' => null, 'user_id' => 16),
            13 => array('name' => 'Steinkjer', 'license' => 30221, 'user_id' => 17),
            15 => array('name' => 'Malvik', 'license' => 14946, 'user_id' => 18),
        );
    }

    /**
     * Get site information
     */
    public function get_site_info($site_id) {
        // Try to get from Laravel API first
        $api_data = $this->api->get_location($site_id);

        if ($api_data) {
            return array(
                'site_id' => $site_id,
                'name' => $api_data['name'],
                'license' => $api_data['license'],
                'phone' => $api_data['phone'] ?? '',
                'email' => $api_data['email'] ?? '',
                'address' => $api_data['address'] ?? '',
                'url' => $api_data['url'] ?? '',
                'active' => $api_data['active'] ?? true,
            );
        }

        // Fallback to local mapping
        if (isset($this->site_mappings[$site_id])) {
            $mapping = $this->site_mappings[$site_id];
            return array(
                'site_id' => $site_id,
                'name' => $mapping['name'],
                'license' => $mapping['license'],
                'user_id' => $mapping['user_id'],
                'phone' => '',
                'email' => '',
                'address' => '',
                'url' => '',
                'active' => true,
            );
        }

        // Default fallback
        return array(
            'site_id' => $site_id,
            'name' => 'Unknown',
            'license' => null,
            'user_id' => null,
            'phone' => '',
            'email' => '',
            'address' => '',
            'url' => '',
            'active' => false,
        );
    }

    /**
     * Get site license
     */
    public function get_site_license($site_id) {
        $site_info = $this->get_site_info($site_id);
        return $site_info['license'];
    }

    /**
     * Get site name
     */
    public function get_site_name($site_id) {
        $site_info = $this->get_site_info($site_id);
        return $site_info['name'];
    }

    /**
     * Get user ID for site (for backward compatibility)
     */
    public function get_user_id_by_site($site_id) {
        if (isset($this->site_mappings[$site_id])) {
            return $this->site_mappings[$site_id]['user_id'];
        }
        return null;
    }

    /**
     * Check if site is active
     */
    public function is_site_active($site_id) {
        $site_info = $this->get_site_info($site_id);
        return $site_info['active'];
    }

    /**
     * Get all sites
     */
    public function get_all_sites() {
        $sites = array();
        foreach ($this->site_mappings as $site_id => $mapping) {
            $sites[$site_id] = $this->get_site_info($site_id);
        }
        return $sites;
    }

    /**
     * Backward compatibility: getCaller function
     */
    public static function get_caller() {
        return get_current_blog_id();
    }
}