<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>PHP Configuration Check</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
</head>
<body>
<div class='container mt-4'>
    <h2>PHP Configuration Diagnostic</h2>";

// Check PHP version
echo "<div class='alert alert-info'>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION;
echo "</div>";

// Check for MySQL extensions
$extensions_to_check = ['mysqli', 'pdo_mysql', 'mysql'];
echo "<h3>MySQL Extensions Status</h3>";
echo "<table class='table table-bordered'>";
echo "<tr><th>Extension</th><th>Status</th><th>Description</th></tr>";

foreach ($extensions_to_check as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "<span class='text-success'>✓ Loaded</span>" : "<span class='text-danger'>✗ Not Loaded</span>";
    $description = '';
    
    switch ($ext) {
        case 'mysqli':
            $description = 'MySQL Improved (Required for your app)';
            break;
        case 'pdo_mysql':
            $description = 'PDO MySQL Driver';
            break;
        case 'mysql':
            $description = 'Original MySQL (Deprecated)';
            break;
    }
    
    echo "<tr><td><code>$ext</code></td><td>$status</td><td>$description</td></tr>";
}
echo "</table>";

// Check if mysqli functions exist
echo "<h3>Function Availability</h3>";
$functions_to_check = ['mysqli_connect', 'mysqli_query', 'mysqli_error'];
echo "<ul>";
foreach ($functions_to_check as $func) {
    $exists = function_exists($func);
    $status = $exists ? "<span class='text-success'>✓</span>" : "<span class='text-danger'>✗</span>";
    echo "<li>$status <code>$func()</code></li>";
}
echo "</ul>";

// Show loaded extensions
echo "<h3>All Loaded Extensions</h3>";
$loaded_extensions = get_loaded_extensions();
sort($loaded_extensions);
echo "<div class='row'>";
$chunks = array_chunk($loaded_extensions, ceil(count($loaded_extensions) / 3));
foreach ($chunks as $chunk) {
    echo "<div class='col-md-4'><ul>";
    foreach ($chunk as $ext) {
        echo "<li><code>$ext</code></li>";
    }
    echo "</ul></div>";
}
echo "</div>";

// PHP.ini location
echo "<h3>PHP Configuration</h3>";
echo "<table class='table table-bordered'>";
echo "<tr><td><strong>php.ini location:</strong></td><td>" . php_ini_loaded_file() . "</td></tr>";
echo "<tr><td><strong>Additional .ini files:</strong></td><td>" . (php_ini_scanned_files() ?: 'None') . "</td></tr>";
echo "</table>";

// XAMPP specific instructions
if (!extension_loaded('mysqli')) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>🔧 XAMPP Fix Required</h4>";
    echo "<p>The <code>mysqli</code> extension is not loaded. Here's how to fix it:</p>";
    echo "<ol>";
    echo "<li><strong>Open php.ini file:</strong> " . (php_ini_loaded_file() ?: 'Not found') . "</li>";
    echo "<li><strong>Find this line:</strong> <code>;extension=mysqli</code></li>";
    echo "<li><strong>Remove the semicolon:</strong> <code>extension=mysqli</code></li>";
    echo "<li><strong>Save the file and restart XAMPP</strong></li>";
    echo "</ol>";
    echo "<p><strong>Alternative:</strong> In XAMPP Control Panel, stop and restart Apache.</p>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h5>Quick XAMPP Fix Steps:</h5>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Config' next to Apache → PHP (php.ini)</li>";
    echo "<li>Find <code>;extension=mysqli</code> (around line 900+)</li>";
    echo "<li>Remove the <code>;</code> to make it <code>extension=mysqli</code></li>";
    echo "<li>Save and restart Apache in XAMPP</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='alert alert-success'>";
    echo "<h4>✓ MySQL Extension is Working!</h4>";
    echo "<p>Your PHP installation has mysqli support.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<div class='text-center'>";
echo "<a href='test_connection.php' class='btn btn-primary'>Test Database Connection</a> ";
echo "<a href='switch_env.php' class='btn btn-info'>Environment Switcher</a> ";
echo "<a href='quick_local_setup.php' class='btn btn-success'>Local Setup</a> ";
echo "<a href='index.php' class='btn btn-secondary'>Back to Login</a>";
echo "</div>";

echo "</div></body></html>";
?> 