<?php
require_once "debug.php";
require_once "DatabaseConnection.php";
$env = require_once __DIR__ . '/env.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Setup Helper</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
</head>
<body>
<div class='container mt-4'>
    <h2>Database Setup Helper</h2>";

// Get environment configuration
$app_env = $env['APP_ENV'];
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

echo "<div class='alert alert-info'>";
echo "<strong>Environment:</strong> " . strtoupper($app_env) . "<br>";
echo "<strong>Database:</strong> {$config['database']} on {$config['host']}:{$config['port']}";
echo "</div>";

// Define constants for the session (since we don't include config.php)
define('MASTER_PASSWORD', $env['MASTER_PASSWORD']);
define('TEST_ADMIN_USERNAME', $env['TEST_ADMIN_USERNAME']);
define('TEST_ADMIN_PASSWORD', $env['TEST_ADMIN_PASSWORD']);
define('USER_STEINKJER_ID', $env['USER_STEINKJER_ID']);
define('USER_NAMSOS_ID', $env['USER_NAMSOS_ID']);
define('USER_LADE_ID', $env['USER_LADE_ID']);
define('USER_MOAN_ID', $env['USER_MOAN_ID']);
define('USER_GRAMYRA_ID', $env['USER_GRAMYRA_ID']);
define('USER_FROSTA_ID', $env['USER_FROSTA_ID']);

try {
    // Connect to database
    $db = new DatabaseConnection($config);
    echo "<div class='alert alert-success'>✓ Database connection successful using " . strtoupper($db->getConnectionType()) . "</div>";
    
    // Create users table if it doesn't exist
    $create_users_table = "
    CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    try {
        $db->query($create_users_table);
        echo "<p style='color: green;'>✓ Users table created/verified successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating users table: " . $e->getMessage() . "</p>";
    }
    
    // Create test admin user
    $test_password = password_hash(TEST_ADMIN_PASSWORD, PASSWORD_DEFAULT);
    
    try {
        if ($db->getConnectionType() === 'pdo_mysql') {
            $stmt = $db->query("INSERT IGNORE INTO users (username, password) VALUES (?, ?)", [TEST_ADMIN_USERNAME, $test_password]);
        } else {
            $escaped_username = $db->escape(TEST_ADMIN_USERNAME);
            $escaped_password = $db->escape($test_password);
            $db->query("INSERT IGNORE INTO users (username, password) VALUES ($escaped_username, $escaped_password)");
        }
        echo "<p style='color: green;'>✓ Test admin user created/verified (username: " . TEST_ADMIN_USERNAME . ", password: " . TEST_ADMIN_PASSWORD . ")</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating admin user: " . $e->getMessage() . "</p>";
    }
    
    // Check database connection and show info
    try {
        $info = $db->testConnection();
        echo "<h3>Database Connection Information:</h3>";
        echo "<table class='table table-bordered'>";
        echo "<tr><td><strong>MySQL Version:</strong></td><td>" . ($info['version'] ?? 'Unknown') . "</td></tr>";
        echo "<tr><td><strong>Current Database:</strong></td><td>" . ($info['current_db'] ?? 'None') . "</td></tr>";
        echo "<tr><td><strong>Connected as:</strong></td><td>" . ($info['current_user'] ?? 'Unknown') . "</td></tr>";
        echo "<tr><td><strong>Connection Type:</strong></td><td>" . strtoupper($db->getConnectionType()) . "</td></tr>";
        echo "</table>";
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>Could not retrieve database info: " . $e->getMessage() . "</div>";
    }
    
    // Show existing users
    try {
        $result = $db->query("SELECT username, created_at FROM users ORDER BY id");
        if ($db->getConnectionType() === 'pdo_mysql') {
            $users = $result->fetchAll();
        } else {
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        echo "<h3>Existing Users:</h3>";
        if (empty($users)) {
            echo "<div class='alert alert-warning'>No users found in database</div>";
        } else {
            echo "<table class='table table-striped'>";
            echo "<tr><th>Username</th><th>Created At</th></tr>";
            foreach ($users as $user) {
                echo "<tr><td>" . htmlspecialchars($user['username']) . "</td><td>" . ($user['created_at'] ?? 'Unknown') . "</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>Could not retrieve users: " . $e->getMessage() . "</div>";
    }
    
    // Show available tables
    try {
        $tables = $db->getTables();
        echo "<h3>Database Tables:</h3>";
        if (empty($tables)) {
            echo "<div class='alert alert-warning'>No tables found</div>";
        } else {
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
    echo "<div class='alert alert-danger'>";
    echo "<h4>Database Connection Failed</h4>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    
    // Check available extensions
    $mysql_extensions = ['pdo_mysql', 'mysqli'];
    $available = [];
    foreach ($mysql_extensions as $ext) {
        if (extension_loaded($ext)) {
            $available[] = $ext;
        }
    }
    
    if (empty($available)) {
        echo "<div style='background-color: #fff3e0; border: 1px solid #ff9800; padding: 10px; margin: 10px 0;'>";
        echo "<h5>🔧 No MySQL Extensions Available</h5>";
        echo "<p>You need to enable at least one MySQL extension:</p>";
        echo "<ol>";
        echo "<li><strong>XAMPP:</strong> Enable mysqli in php.ini</li>";
        echo "<li>Restart Apache/web server</li>";
        echo "<li>Check PHP configuration</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<p><strong>Available extensions:</strong> " . implode(', ', $available) . "</p>";
    }
    
    echo "<p><a href='test_connection.php' class='btn btn-warning'>Test Connection</a></p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Login Information</h3>";
echo "<div class='alert alert-info'>";
echo "<h5>Available Login Methods:</h5>";
echo "<ul>";
echo "<li><strong>Test Admin:</strong> " . TEST_ADMIN_USERNAME . " / " . TEST_ADMIN_PASSWORD . "</li>";
echo "<li><strong>Master Password:</strong> Any location username with password: " . MASTER_PASSWORD . "</li>";
echo "</ul>";
echo "<h5>Location Usernames (use with master password):</h5>";
echo "<ul>";
echo "<li>steinkjer (ID: " . USER_STEINKJER_ID . ")</li>";
echo "<li>namsos (ID: " . USER_NAMSOS_ID . ")</li>";
echo "<li>lade (ID: " . USER_LADE_ID . ")</li>";
echo "<li>moan (ID: " . USER_MOAN_ID . ")</li>";
echo "<li>gramyra (ID: " . USER_GRAMYRA_ID . ")</li>";
echo "<li>frosta (ID: " . USER_FROSTA_ID . ")</li>";
echo "</ul>";
echo "</div>";

echo "<div class='text-center'>";
echo "<a href='index.php' class='btn btn-primary'>Go to Login Page</a> ";
echo "<a href='test_connection.php' class='btn btn-info'>Test Connection</a> ";
echo "<a href='switch_env.php' class='btn btn-secondary'>Environment Switcher</a>";
echo "</div>";

echo "</div></body></html>";
?> 