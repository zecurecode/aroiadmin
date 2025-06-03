<?php
// Debug configuration for development
// Include this file at the top of your PHP files for debugging

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Enable logging of errors
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

// Set memory limit for development
ini_set('memory_limit', '256M');

// Set maximum execution time
ini_set('max_execution_time', 300);

// Function to dump variables nicely
function debug_dump($var, $label = '') {
    echo '<div style="background: #f4f4f4; border: 1px solid #ddd; padding: 10px; margin: 10px 0; font-family: monospace;">';
    if ($label) {
        echo '<strong>' . htmlspecialchars($label) . ':</strong><br>';
    }
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    echo '</div>';
}

// Function to log debug messages
function debug_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents(__DIR__ . '/debug.log', $log_message, FILE_APPEND | LOCK_EX);
}

// Start output buffering to catch any output before headers
ob_start();

echo "<!-- Debug mode enabled -->\n";
?> 