<?php
require_once "debug.php";

echo "<!DOCTYPE html>
<html>
<head>
    <title>System Status</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
</head>
<body>
<div class='container mt-4'>
    <h1>🚀 PHP Application Status</h1>";

// Check PHP version
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h4>📋 System Information</h4></div>";
echo "<div class='card-body'>";
echo "<table class='table table-bordered'>";
echo "<tr><td><strong>PHP Version:</strong></td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td><strong>Server:</strong></td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
echo "<tr><td><strong>Document Root:</strong></td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
echo "<tr><td><strong>Current Time:</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>";
echo "</table>";
echo "</div></div>";

// Check PHP Extensions
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h4>🔧 PHP Extensions</h4></div>";
echo "<div class='card-body'>";
$required_extensions = ['pdo_mysql', 'mysqli', 'json', 'session'];
$extension_status = [];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    $extension_status[$ext] = $loaded;
}

echo "<table class='table table-sm'>";
echo "<tr><th>Extension</th><th>Status</th></tr>";
foreach ($extension_status as $ext => $loaded) {
    $status = $loaded ? "<span class='badge badge-success'>✓ Loaded</span>" : "<span class='badge badge-danger'>✗ Missing</span>";
    echo "<tr><td><code>$ext</code></td><td>$status</td></tr>";
}
echo "</table>";

$mysql_available = extension_loaded('pdo_mysql') || extension_loaded('mysqli');
if ($mysql_available) {
    echo "<div class='alert alert-success'>✓ MySQL support is available</div>";
} else {
    echo "<div class='alert alert-danger'>❌ No MySQL extensions available - database connections will fail</div>";
}
echo "</div></div>";

// Check Environment Configuration
$env_loaded = false;
try {
    $env = require_once __DIR__ . '/env.php';
    $env_loaded = true;
    
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h4>⚙️ Environment Configuration</h4></div>";
    echo "<div class='card-body'>";
    echo "<p><strong>Current Environment:</strong> <span class='badge badge-info'>" . strtoupper($env['APP_ENV']) . "</span></p>";
    
    if ($env['APP_ENV'] === 'debug') {
        $db_config = [
            'host' => $env['DB_HOST_DEBUG'],
            'port' => $env['DB_PORT_DEBUG'],
            'database' => $env['DB_NAME_DEBUG']
        ];
    } else {
        $db_config = [
            'host' => $env['DB_HOST_PROD'],
            'port' => $env['DB_PORT_PROD'],
            'database' => $env['DB_NAME_PROD']
        ];
    }
    
    echo "<table class='table table-sm'>";
    echo "<tr><td><strong>Database Host:</strong></td><td>{$db_config['host']}:{$db_config['port']}</td></tr>";
    echo "<tr><td><strong>Database Name:</strong></td><td>{$db_config['database']}</td></tr>";
    echo "<tr><td><strong>Debug Mode:</strong></td><td>" . ($env['DEBUG_ENABLED'] ? '✓ Enabled' : '✗ Disabled') . "</td></tr>";
    echo "</table>";
    echo "</div></div>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Environment configuration error: " . $e->getMessage() . "</div>";
}

// Test Database Connection
if ($env_loaded && $mysql_available) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h4>🗄️ Database Connection</h4></div>";
    echo "<div class='card-body'>";
    
    try {
        require_once "DatabaseConnection.php";
        
        if ($env['APP_ENV'] === 'debug') {
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
        
        $db = new DatabaseConnection($config);
        echo "<div class='alert alert-success'>✓ Database connection successful using " . strtoupper($db->getConnectionType()) . "</div>";
        
        $info = $db->testConnection();
        echo "<p><strong>MySQL Version:</strong> " . ($info['version'] ?? 'Unknown') . "</p>";
        echo "<p><strong>Current Database:</strong> " . ($info['current_db'] ?? 'None') . "</p>";
        
        $tables = $db->getTables();
        echo "<p><strong>Tables:</strong> " . (empty($tables) ? 'None found' : count($tables) . ' table(s)') . "</p>";
        
        $db->close();
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    }
    echo "</div></div>";
}

// Available Tools
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h4>🛠️ Available Tools</h4></div>";
echo "<div class='card-body'>";
echo "<div class='row'>";

$tools = [
    ['name' => 'Login Page', 'url' => 'index.php', 'description' => 'Main application login', 'class' => 'btn-primary'],
    ['name' => 'PHP Info', 'url' => 'phpinfo.php', 'description' => 'Check PHP configuration', 'class' => 'btn-info'],
    ['name' => 'Test Connection', 'url' => 'test_connection.php', 'description' => 'Database connection tester', 'class' => 'btn-warning'],
    ['name' => 'Setup Database', 'url' => 'setup_database.php', 'description' => 'Initialize database tables', 'class' => 'btn-success'],
    ['name' => 'Environment Switcher', 'url' => 'switch_env.php', 'description' => 'Change environment settings', 'class' => 'btn-secondary'],
    ['name' => 'Quick Setup', 'url' => 'quick_local_setup.php', 'description' => 'Local development setup', 'class' => 'btn-outline-primary']
];

foreach ($tools as $tool) {
    echo "<div class='col-md-6 col-lg-4 mb-3'>";
    echo "<div class='card h-100'>";
    echo "<div class='card-body text-center'>";
    echo "<h6 class='card-title'>{$tool['name']}</h6>";
    echo "<p class='card-text small'>{$tool['description']}</p>";
    echo "<a href='{$tool['url']}' class='btn {$tool['class']} btn-sm'>{$tool['name']}</a>";
    echo "</div></div></div>";
}

echo "</div></div></div>";

// System Summary
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h4>📊 System Summary</h4></div>";
echo "<div class='card-body'>";

$status_items = [];
$overall_status = 'success';

// Check PHP
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    $status_items[] = ['PHP Version', 'success', '✓ PHP ' . PHP_VERSION . ' (Compatible)'];
} else {
    $status_items[] = ['PHP Version', 'warning', '⚠ PHP ' . PHP_VERSION . ' (Consider upgrading)'];
    $overall_status = 'warning';
}

// Check MySQL
if ($mysql_available) {
    $status_items[] = ['MySQL Extensions', 'success', '✓ Available'];
} else {
    $status_items[] = ['MySQL Extensions', 'danger', '❌ Not available'];
    $overall_status = 'danger';
}

// Check Environment
if ($env_loaded) {
    $status_items[] = ['Environment Config', 'success', '✓ Loaded'];
} else {
    $status_items[] = ['Environment Config', 'danger', '❌ Failed to load'];
    $overall_status = 'danger';
}

foreach ($status_items as $item) {
    echo "<div class='alert alert-{$item[1]} py-2 mb-2'>";
    echo "<strong>{$item[0]}:</strong> {$item[2]}";
    echo "</div>";
}

echo "<hr>";
if ($overall_status === 'success') {
    echo "<div class='alert alert-success'><strong>🎉 System Status: READY</strong><br>Your PHP application is properly configured and ready to use!</div>";
} elseif ($overall_status === 'warning') {
    echo "<div class='alert alert-warning'><strong>⚠ System Status: NEEDS ATTENTION</strong><br>Some components need attention but the system should work.</div>";
} else {
    echo "<div class='alert alert-danger'><strong>❌ System Status: CONFIGURATION REQUIRED</strong><br>Critical components are missing or misconfigured.</div>";
}

echo "</div></div>";

echo "</div></body></html>";
?> 