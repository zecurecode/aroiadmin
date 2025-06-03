<?php
require_once "debug.php";
require_once "DatabaseConnection.php";
$env = require_once __DIR__ . '/env.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Tester</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
</head>
<body>
<div class='container mt-4'>
    <h2>Database Connection Tester</h2>";

// Get current environment
$app_env = $env['APP_ENV'];
echo "<div class='alert alert-info'>Current Environment: <strong>" . strtoupper($app_env) . "</strong></div>";

// Current settings based on environment
if ($app_env === 'debug') {
    $config = [
        'host' => $env['DB_HOST_DEBUG'],
        'port' => $env['DB_PORT_DEBUG'],
        'username' => $env['DB_USERNAME_DEBUG'],
        'password' => $env['DB_PASSWORD_DEBUG'],
        'database' => $env['DB_NAME_DEBUG']
    ];
} else {
    $config = [
        'host' => $env['DB_HOST_PROD'],
        'port' => $env['DB_PORT_PROD'],
        'username' => $env['DB_USERNAME_PROD'],
        'password' => $env['DB_PASSWORD_PROD'],
        'database' => $env['DB_NAME_PROD']
    ];
}

echo "<h3>Connection Details</h3>";
echo "<table class='table table-bordered'>";
echo "<tr><td><strong>Host:</strong></td><td>{$config['host']}</td></tr>";
echo "<tr><td><strong>Port:</strong></td><td>{$config['port']}</td></tr>";
echo "<tr><td><strong>Username:</strong></td><td>{$config['username']}</td></tr>";
echo "<tr><td><strong>Database:</strong></td><td>{$config['database']}</td></tr>";
echo "<tr><td><strong>Your IP:</strong></td><td>" . ($_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "</td></tr>";
echo "</table>";

// Check available PHP extensions
echo "<h3>PHP Extensions Check</h3>";
$mysql_extensions = ['pdo_mysql', 'mysqli', 'mysql'];
$available = [];
echo "<table class='table table-bordered'>";
echo "<tr><th>Extension</th><th>Status</th><th>Description</th></tr>";
foreach ($mysql_extensions as $ext) {
    $loaded = extension_loaded($ext);
    if ($loaded) $available[] = $ext;
    $status = $loaded ? "<span class='text-success'>✓ Available</span>" : "<span class='text-danger'>✗ Not Available</span>";
    $description = '';
    switch ($ext) {
        case 'pdo_mysql': $description = 'PDO MySQL (Preferred)'; break;
        case 'mysqli': $description = 'MySQLi Extension'; break;
        case 'mysql': $description = 'Legacy MySQL (Deprecated)'; break;
    }
    echo "<tr><td><code>$ext</code></td><td>$status</td><td>$description</td></tr>";
}
echo "</table>";

if (empty($available)) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ No MySQL Extensions Available</h4>";
    echo "<p>You need to enable at least one MySQL extension in PHP.</p>";
    echo "<h5>XAMPP Fix:</h5>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Config' next to Apache → PHP (php.ini)</li>";
    echo "<li>Find <code>;extension=mysqli</code> and remove the semicolon</li>";
    echo "<li>Save and restart Apache</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='alert alert-success'>✓ Available extensions: " . implode(', ', $available) . "</div>";
}

// Test basic connectivity
echo "<h3>Connection Tests</h3>";

// Test 1: Host reachability
echo "<h4>1. Host Reachability Test</h4>";
$connection = @fsockopen($config['host'], $config['port'], $errno, $errstr, 5);
if ($connection) {
    echo "<div class='alert alert-success'>✓ Host {$config['host']}:{$config['port']} is reachable</div>";
    fclose($connection);
} else {
    echo "<div class='alert alert-danger'>✗ Cannot reach {$config['host']}:{$config['port']}<br>Error: $errstr ($errno)</div>";
}

// Test 2: Database connection
echo "<h4>2. Database Connection Test</h4>";

try {
    $db = new DatabaseConnection($config);
    echo "<div class='alert alert-success'>✓ Database connection successful!</div>";
    
    // Show connection type
    $connection_type = $db->getConnectionType();
    echo "<div class='alert alert-info'>Using connection method: <strong>" . strtoupper($connection_type) . "</strong></div>";
    
    // Test database information
    try {
        $info = $db->testConnection();
        echo "<h4>3. Database Information</h4>";
        echo "<table class='table table-bordered'>";
        echo "<tr><td><strong>MySQL Version:</strong></td><td>" . ($info['version'] ?? 'Unknown') . "</td></tr>";
        echo "<tr><td><strong>Current Database:</strong></td><td>" . ($info['current_db'] ?? 'None selected') . "</td></tr>";
        echo "<tr><td><strong>Connected as:</strong></td><td>" . ($info['current_user'] ?? 'Unknown') . "</td></tr>";
        echo "</table>";
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>Could not get database info: " . $e->getMessage() . "</div>";
    }
    
    // Test tables
    try {
        $tables = $db->getTables();
        echo "<h4>4. Available Tables</h4>";
        if (empty($tables)) {
            echo "<div class='alert alert-warning'>No tables found in database '{$config['database']}'</div>";
            echo "<p><a href='setup_database.php' class='btn btn-primary'>Setup Database Tables</a></p>";
        } else {
            echo "<div class='alert alert-success'>Found " . count($tables) . " table(s):</div>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>Could not list tables: " . $e->getMessage() . "</div>";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>✗ Database connection failed<br><strong>Error:</strong> " . $e->getMessage() . "</div>";
    
    // Parse the error for specific issues
    $error_message = $e->getMessage();
    
    if (strpos($error_message, 'All database connection methods failed') !== false) {
        echo "<div class='alert alert-warning'>";
        echo "<h5>🔧 No Working Database Extensions</h5>";
        echo "<p>None of the available database connection methods worked.</p>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Check that at least one MySQL extension is enabled in PHP</li>";
        echo "<li>Restart your web server after enabling extensions</li>";
        echo "<li>Verify database server is running and accessible</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    if (strpos($error_message, 'Access denied') !== false) {
        echo "<div class='alert alert-warning'>";
        echo "<h5>🔒 Access Denied Analysis</h5>";
        echo "<p>The database server rejected your connection. This means:</p>";
        echo "<ul>";
        echo "<li>The server is running and reachable</li>";
        echo "<li>Your credentials might be incorrect, or</li>";
        echo "<li>Your IP address is not authorized</li>";
        echo "</ul>";
        echo "<p><strong>Solutions:</strong></p>";
        echo "<ol>";
        echo "<li>Verify username and password</li>";
        echo "<li>Check if database exists</li>";
        echo "<li>Contact database administrator to authorize your IP</li>";
        echo "<li>For remote connections, ensure firewall allows access</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    if (strpos($error_message, 'Unknown database') !== false) {
        echo "<div class='alert alert-info'>";
        echo "<h5>📊 Database Not Found</h5>";
        echo "<p>The database '{$config['database']}' doesn't exist.</p>";
        echo "<p><strong>Solutions:</strong></p>";
        echo "<ol>";
        echo "<li>Create the database in phpMyAdmin (if using XAMPP)</li>";
        echo "<li>Ask your database administrator to create it</li>";
        echo "<li>Check if you're using the correct database name</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    if (strpos($error_message, 'Connection refused') !== false) {
        echo "<div class='alert alert-danger'>";
        echo "<h5>🚫 Connection Refused</h5>";
        echo "<p>The connection was refused. This usually means:</p>";
        echo "<ul>";
        echo "<li>MySQL server is not running</li>";
        echo "<li>Wrong port number</li>";
        echo "<li>Firewall blocking the connection</li>";
        echo "</ul>";
        echo "</div>";
    }
}

echo "<h3>Recommended Actions</h3>";
echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title'>🔧 Fix PHP Extensions</h5>";
echo "<p class='card-text'>Enable mysqli or PDO MySQL in PHP configuration</p>";
echo "<a href='phpinfo.php' class='btn btn-primary'>Check PHP Config</a>";
echo "</div></div></div>";

echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title'>🔄 Switch Environment</h5>";
echo "<p class='card-text'>Try different database settings</p>";
echo "<a href='switch_env.php' class='btn btn-info'>Environment Switcher</a>";
echo "</div></div></div>";

echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title'>⚡ Quick Setup</h5>";
echo "<p class='card-text'>Set up local SQLite database</p>";
echo "<a href='quick_local_setup.php' class='btn btn-success'>Local Setup</a>";
echo "</div></div></div>";
echo "</div>";

echo "<hr>";
echo "<div class='text-center'>";
echo "<a href='index.php' class='btn btn-secondary'>Back to Login</a> ";
echo "<a href='setup_database.php' class='btn btn-info'>Database Setup</a>";
echo "</div>";

echo "</div></body></html>";
?> 