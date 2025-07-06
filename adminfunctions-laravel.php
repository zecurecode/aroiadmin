<?php
/**
 * Laravel-Compatible Admin Functions for WordPress/WooCommerce Integration
 * 
 * This file replaces the old adminfunctions.php with Laravel API calls
 * All database operations are now handled through Laravel API endpoints
 */

/**
 * Call Laravel API endpoint
 */
function callLaravelAPI($endpoint, $method = 'GET', $data = null) {
    $baseUrl = 'https://aroiasia.no/laravel-admin/api/v1';
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    }
    
    return null;
}

/**
 * Get delivery time for a location
 */
function gettid_function($id) {
    $response = callLaravelAPI("/wordpress/location/{$id}/delivery-time");
    return $response ? $response['delivery_time'] : 30;
}

/**
 * Get opening hours display for shortcode
 */
function gettid_function2($atts) {
    $siteId = $atts['site'] ?? 0;
    
    if (!$siteId) {
        return;
    }
    
    $response = callLaravelAPI("/wordpress/location/{$siteId}/opening-hours");
    
    if (!$response) {
        echo "<span style='color:red;'>Kunne ikke hente åpningstider</span>";
        return;
    }
    
    $openTime = $response['open_time'] ?? '';
    $closeTime = $response['close_time'] ?? '';
    $status = $response['status'] ?? 0;
    $isOpen = $response['is_open'] ?? false;
    
    echo $openTime . " til " . $closeTime . "<br>";
    
    if ($status == 0 || !$isOpen) {
        echo "<span style='color:red;'>Åpner klokken {$openTime} i dag. Du kan fortsatt bestille for henting innen åpningstiden.</span>";
    } else {
        echo "<span style='color:green;'>Åpen for henting i dag</span>";
    }
}

/**
 * Check if location is open now
 */
function isItOpenNow($vogn) {
    $siteId = getSiteIdByVogn($vogn);
    if (!$siteId) return false;
    
    $response = callLaravelAPI("/wordpress/location/{$siteId}/is-open");
    return $response ? $response['is_open'] : false;
}

/**
 * Check if location is closed now
 */
function isItClosedNow($vogn) {
    $siteId = getSiteIdByVogn($vogn);
    if (!$siteId) return true;
    
    $response = callLaravelAPI("/wordpress/location/{$siteId}/is-open");
    if (!$response) return true;
    
    // If not open and past close time, it's closed
    if (!$response['is_open'] && $response['close_time']) {
        $closeTime = strtotime($response['close_time']);
        $now = strtotime('now');
        return $now >= $closeTime;
    }
    
    return !$response['is_open'];
}

/**
 * Get site name by site ID
 */
function getSiteName($site) {
    $sitename = "none";
    switch($site) {
        case 11:
            $sitename = "hell";
            break;    
        case 7:
            $sitename = "namsos";
            break;
        case 4:
            $sitename = "lade";
            break;
        case 6:
            $sitename = "moan";
            break;
        case 5:
            $sitename = "gramyra";
            break;
        case 10:
            $sitename = "frosta";
            break;
        case 12:
            $sitename = "steinkjer";
            break;
        default:
            $sitename = "ingen";
            break;
    }
    return $sitename;
}

/**
 * Get site ID by vogn name
 */
function getSiteIdByVogn($vogn) {
    $mapping = [
        'namsos' => 7,
        'lade' => 4,
        'moan' => 6,
        'gramyra' => 5,
        'frosta' => 10,
        'hell' => 11,
        'steinkjer' => 12
    ];
    
    return $mapping[$vogn] ?? null;
}

/**
 * Get Norwegian day name
 */
function getDay() {
    $converted = "";
    $day = date("D");
    switch($day) {
        case "Mon":
            $converted = "Mandag";
            break;
        case "Tue":
            $converted = "Tirsdag";
            break;
        case "Wed":
            $converted = "Onsdag";
            break;
        case "Thu":
            $converted = "Torsdag";
            break;
        case "Fri":
            $converted = "Fredag";
            break;
        case "Sat":
            $converted = "Lørdag";
            break;
        case "Sun":
            $converted = "Søndag";
            break;
        default:
            $converted = "Ingen";
            break;
    }
    return $converted;
}

/**
 * Get next day name
 */
function nextDay() {
    $converted = "";
    $day = date("N");
    
    if($day == 7) {
        $day = 1;
    } else {
        $day += 1;
    }
    
    switch($day) {
        case 1:
            $converted = "Mandag";
            break;
        case 2:
            $converted = "Tirsdag";
            break;
        case 3:
            $converted = "Onsdag";
            break;
        case 4:
            $converted = "Torsdag";
            break;
        case 5:
            $converted = "Fredag";
            break;
        case 6:
            $converted = "Lørdag";
            break;
        case 7:
            $converted = "Søndag";
            break;
        default:
            $converted = "Ingen";
            break;
    }
    return $converted;
}

/**
 * Get all opening hours for a location
 */
function getHours($id) {
    $response = callLaravelAPI("/wordpress/location/{$id}/all-hours");
    return $response ? $response['schedule'] : [];
}

/**
 * Get opening time for current day
 */
function getOpen($id) {
    if($id == 9) {
        $id = 5;
    }
    
    $response = callLaravelAPI("/wordpress/location/{$id}/opening-hours");
    return $response ? $response['open_time'] : "12:00";
}

/**
 * Get status for current day
 */
function getStatus($id) {
    if($id == 9) {
        $id = 5;
    }
    
    $response = callLaravelAPI("/wordpress/location/{$id}/opening-hours");
    return $response ? $response['status'] : 0;
}

/**
 * Get next opening time (for tomorrow)
 */
function getNextOpen() {
    $day = date('Y-m-d', strtotime(' +1 day'));
    return $day;
}

/**
 * Get closing time for current day
 */
function getClose($id) {
    if($id == 9) {
        $id = 5;
    }
    
    $response = callLaravelAPI("/wordpress/location/{$id}/opening-hours");
    return $response ? $response['close_time'] : "22:00";
}

/**
 * Get notes for current day
 */
function getNote($id) {
    $response = callLaravelAPI("/wordpress/location/{$id}/opening-hours");
    return $response ? $response['notes'] : false;
}

/**
 * Check if location is open
 */
function isOpen($id) {
    $response = callLaravelAPI("/wordpress/location/{$id}/is-open");
    return $response ? $response['is_open'] : false;
}

/**
 * DEPRECATED: Database function - no longer used
 * All database operations should go through Laravel API
 */
function database($sql) {
    trigger_error('Direct database access is deprecated. Use Laravel API instead.', E_USER_WARNING);
    return false;
}