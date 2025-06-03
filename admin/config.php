<?php
// Load environment configuration
$env = require_once __DIR__ . '/env.php';
require_once __DIR__ . '/DatabaseConnection.php';

// Determine environment
$app_env = $env['APP_ENV'];

// Set database configuration based on environment
if ($app_env === 'debug') {
    $db_config = [
        'host' => $env['DB_HOST_DEBUG'],
        'port' => $env['DB_PORT_DEBUG'],
        'username' => $env['DB_USERNAME_DEBUG'],
        'password' => $env['DB_PASSWORD_DEBUG'],
        'database' => $env['DB_NAME_DEBUG']
    ];
} else {
    $db_config = [
        'host' => $env['DB_HOST_PROD'],
        'port' => $env['DB_PORT_PROD'],
        'username' => $env['DB_USERNAME_PROD'],
        'password' => $env['DB_PASSWORD_PROD'],
        'database' => $env['DB_NAME_PROD']
    ];
}

// Define constants for backward compatibility
define('DB_HOST', $db_config['host']);
define('DB_PORT', $db_config['port']);
define('DB_USERNAME', $db_config['username']);
define('DB_PASSWORD', $db_config['password']);
define('DB_NAME', $db_config['database']);

// Define other environment variables
define('MASTER_PASSWORD', $env['MASTER_PASSWORD']);
define('TEST_ADMIN_USERNAME', $env['TEST_ADMIN_USERNAME']);
define('TEST_ADMIN_PASSWORD', $env['TEST_ADMIN_PASSWORD']);

// Location-based User IDs
define('USER_STEINKJER_ID', $env['USER_STEINKJER_ID']);
define('USER_NAMSOS_ID', $env['USER_NAMSOS_ID']);
define('USER_LADE_ID', $env['USER_LADE_ID']);
define('USER_MOAN_ID', $env['USER_MOAN_ID']);
define('USER_GRAMYRA_ID', $env['USER_GRAMYRA_ID']);
define('USER_FROSTA_ID', $env['USER_FROSTA_ID']);

// Debug environment information
if ($app_env === 'debug') {
    echo "<!-- Environment: DEBUG mode -->\n";
    echo "<!-- Database: " . $db_config['host'] . ":" . $db_config['port'] . " -->\n";
}

// Global database connection variable for backward compatibility
$link = null;
$db = null;

try {
    // Create modern database connection
    $db = new DatabaseConnection($db_config);
    
    // For backward compatibility, create mysqli connection if available
    if ($db->getConnectionType() === 'mysqli') {
        $link = $db->getConnection();
    } else {
        // Create a wrapper for PDO to work with existing mysqli code
        $link = new class($db) {
            private $db;
            
            public function __construct($database_connection) {
                $this->db = $database_connection;
            }
            
            public function query($sql) {
                return $this->db->query($sql);
            }
            
            public function prepare($sql) {
                return $this->db->getConnection()->prepare($sql);
            }
            
            public function close() {
                return $this->db->close();
            }
            
            public function real_escape_string($value) {
                return $this->db->escape($value);
            }
            
            public function __get($property) {
                if ($property === 'insert_id') {
                    return $this->db->lastInsertId();
                }
                return null;
            }
        };
    }
    
    if ($app_env === 'debug') {
        $info = $db->getServerInfo();
        echo "<!-- Database connected successfully using " . $info['connection_type'] . " -->\n";
    }
    
} catch (Exception $e) {
    $error_msg = "Database connection failed: " . $e->getMessage();
    
    if ($app_env === 'debug') {
        echo "<div style='background-color: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px; border-radius: 4px;'>";
        echo "<h3 style='color: #d32f2f; margin-top: 0;'>Database Connection Error</h3>";
        echo "<p><strong>Environment:</strong> " . strtoupper($app_env) . "</p>";
        echo "<p><strong>Host:</strong> " . $db_config['host'] . ":" . $db_config['port'] . "</p>";
        echo "<p><strong>Database:</strong> " . $db_config['database'] . "</p>";
        echo "<p><strong>Username:</strong> " . $db_config['username'] . "</p>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        
        // Check what extensions are available
        $available_extensions = [];
        $mysql_extensions = ['pdo_mysql', 'mysqli', 'mysql'];
        foreach ($mysql_extensions as $ext) {
            if (extension_loaded($ext)) {
                $available_extensions[] = $ext;
            }
        }
        
        echo "<p><strong>Available MySQL Extensions:</strong> " . (empty($available_extensions) ? 'None!' : implode(', ', $available_extensions)) . "</p>";
        
        if (empty($available_extensions)) {
            echo "<div style='background-color: #fff3e0; border: 1px solid #ff9800; padding: 10px; margin: 10px 0;'>";
            echo "<h4 style='color: #f57c00; margin-top: 0;'>🔧 No MySQL Extensions Available</h4>";
            echo "<p>You need to enable at least one MySQL extension in PHP:</p>";
            echo "<ol>";
            echo "<li><strong>For XAMPP:</strong> Enable mysqli in php.ini</li>";
            echo "<li><strong>For Windows:</strong> Uncomment <code>extension=mysqli</code> or <code>extension=pdo_mysql</code></li>";
            echo "<li><strong>For Linux:</strong> Install php-mysqli or php-mysql package</li>";
            echo "</ol>";
            echo "</div>";
        }
        
        // Specific error handling
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "<div style='background-color: #fff3e0; border: 1px solid #ff9800; padding: 10px; margin: 10px 0;'>";
            echo "<h4 style='color: #f57c00; margin-top: 0;'>🔒 Access Denied Issue</h4>";
            echo "<p>Database server rejected your connection.</p>";
            echo "<p><strong>Solutions:</strong></p>";
            echo "<ol>";
            echo "<li>Check username and password</li>";
            echo "<li>Verify your IP is authorized</li>";
            echo "<li>Contact database administrator</li>";
            echo "</ol>";
            echo "</div>";
        }
        
        echo "<h4>Quick Actions:</h4>";
        echo "<p>";
        echo "<a href='phpinfo.php' style='display: inline-block; padding: 8px 16px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px;'>Check PHP Extensions</a> ";
        echo "<a href='test_connection.php' style='display: inline-block; padding: 8px 16px; background: #FF9800; color: white; text-decoration: none; border-radius: 4px;'>Test Connection</a> ";
        echo "<a href='switch_env.php' style='display: inline-block; padding: 8px 16px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Switch Environment</a>";
        echo "</p>";
        echo "</div>";
    }
    
    // Don't die in debug mode, allow the page to continue for better debugging
    if ($app_env !== 'debug') {
        die($error_msg);
    }
}

// Helper functions for backward compatibility
if (!function_exists('mysqli_connect_error')) {
    function mysqli_connect_error() {
        return "mysqli extension not available";
    }
}

if (!function_exists('mysqli_query')) {
    function mysqli_query($link, $query) {
        global $db;
        if ($db) {
            return $db->query($query);
        }
        return false;
    }
}

if (!function_exists('mysqli_close')) {
    function mysqli_close($link) {
        global $db;
        if ($db) {
            $db->close();
        }
        return true;
    }
}
?>