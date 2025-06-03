<?php
require_once "debug.php";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Quick Local Setup</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
</head>
<body>
<div class='container mt-4'>
    <h2>Quick Local Development Setup</h2>";

echo "<div class='alert alert-info'>";
echo "<strong>Purpose:</strong> This script helps you set up a local SQLite database for quick testing without needing MySQL access.";
echo "</div>";

// Check if SQLite is available
if (class_exists('SQLite3')) {
    echo "<div class='alert alert-success'>✓ SQLite3 is available on your system</div>";
    
    if ($_POST['action'] === 'create_sqlite' ?? false) {
        try {
            // Create SQLite database
            $db = new SQLite3(__DIR__ . '/local_database.sqlite');
            
            // Create users table
            $create_table = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            
            $db->exec($create_table);
            
            // Create test admin user
            $test_password = password_hash("admin123", PASSWORD_DEFAULT);
            $insert_admin = "INSERT OR IGNORE INTO users (username, password) VALUES ('admin', '$test_password')";
            $db->exec($insert_admin);
            
            $db->close();
            
            echo "<div class='alert alert-success'>";
            echo "<h4>✓ Local SQLite Database Created!</h4>";
            echo "<p>Database file: <code>admin/local_database.sqlite</code></p>";
            echo "<p>Test user created: <strong>admin</strong> / <strong>admin123</strong></p>";
            echo "</div>";
            
            // Create a simple SQLite config
            $sqlite_config = "<?php
// Local SQLite Configuration
\$sqlite_db_path = __DIR__ . '/local_database.sqlite';

function get_sqlite_connection() {
    global \$sqlite_db_path;
    try {
        \$db = new SQLite3(\$sqlite_db_path);
        return \$db;
    } catch (Exception \$e) {
        die('SQLite Connection Error: ' . \$e->getMessage());
    }
}

// Test connection
\$test_db = get_sqlite_connection();
if (\$test_db) {
    echo '<!-- SQLite connection successful -->';
    \$test_db->close();
}
?>";
            
            file_put_contents(__DIR__ . '/sqlite_config.php', $sqlite_config);
            echo "<div class='alert alert-info'>SQLite configuration file created: <code>sqlite_config.php</code></div>";
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Error creating SQLite database: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<form method='post' class='mb-4'>";
    echo "<button type='submit' name='action' value='create_sqlite' class='btn btn-success'>Create Local SQLite Database</button>";
    echo "</form>";
    
} else {
    echo "<div class='alert alert-warning'>SQLite3 is not available. You'll need to install it or use MySQL.</div>";
}

// Local MySQL setup instructions
echo "<div class='card mt-4'>";
echo "<div class='card-header'><h4>Alternative: Local MySQL Setup</h4></div>";
echo "<div class='card-body'>";
echo "<h5>Option 1: XAMPP (Recommended for Windows)</h5>";
echo "<ol>";
echo "<li>Download XAMPP from <a href='https://www.apachefriends.org/download.html' target='_blank'>https://www.apachefriends.org/download.html</a></li>";
echo "<li>Install and start Apache + MySQL</li>";
echo "<li>Access phpMyAdmin at <code>http://localhost/phpmyadmin</code></li>";
echo "<li>Create database: <code>admin_aroi_dev</code></li>";
echo "<li>Update your env.php to use localhost MySQL</li>";
echo "</ol>";

echo "<h5>Option 2: Docker MySQL</h5>";
echo "<pre>docker run -d --name mysql-dev -p 3306:3306 -e MYSQL_ROOT_PASSWORD=password -e MYSQL_DATABASE=admin_aroi_dev mysql:8.0</pre>";

echo "<h5>Option 3: Native MySQL Installation</h5>";
echo "<ul>";
echo "<li>Download MySQL from <a href='https://dev.mysql.com/downloads/mysql/' target='_blank'>MySQL Official Site</a></li>";
echo "<li>Install and configure</li>";
echo "<li>Create your development database</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

// SSH Tunnel option
echo "<div class='card mt-4'>";
echo "<div class='card-header'><h4>Option 3: SSH Tunnel to Remote Database</h4></div>";
echo "<div class='card-body'>";
echo "<p>If you have SSH access to a server that can connect to your MySQL database:</p>";
echo "<pre>ssh -L 3306:141.94.143.8:3306 username@your-server.com</pre>";
echo "<p>Then update your debug database host to <code>localhost</code> in env.php</p>";
echo "</div>";
echo "</div>";

echo "<hr>";
echo "<div class='text-center'>";
echo "<a href='test_connection.php' class='btn btn-primary'>Test Connection</a> ";
echo "<a href='switch_env.php' class='btn btn-info'>Environment Switcher</a> ";
echo "<a href='index.php' class='btn btn-secondary'>Back to Login</a>";
echo "</div>";

echo "</div></body></html>";
?> 