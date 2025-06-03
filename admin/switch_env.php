<?php
// Environment Switcher
// Simple script to switch between debug and production environments

$env_file = __DIR__ . '/env.php';
$current_env = require $env_file;

// Handle form submission
if ($_POST['action'] ?? false) {
    $new_env = $_POST['environment'];
    $current_env['APP_ENV'] = $new_env;
    
    // Update database settings if needed
    if (isset($_POST['db_host'])) {
        if ($new_env === 'debug') {
            $current_env['DB_HOST_DEBUG'] = $_POST['db_host'];
            $current_env['DB_USERNAME_DEBUG'] = $_POST['db_username'] ?? $current_env['DB_USERNAME_DEBUG'];
            $current_env['DB_PASSWORD_DEBUG'] = $_POST['db_password'] ?? $current_env['DB_PASSWORD_DEBUG'];
        } else {
            $current_env['DB_HOST_PROD'] = $_POST['db_host'];
            $current_env['DB_USERNAME_PROD'] = $_POST['db_username'] ?? $current_env['DB_USERNAME_PROD'];
            $current_env['DB_PASSWORD_PROD'] = $_POST['db_password'] ?? $current_env['DB_PASSWORD_PROD'];
        }
    }
    
    // Write updated config
    $config_content = "<?php\n// Environment Configuration File\n// This file contains all environment-specific settings\n\nreturn " . var_export($current_env, true) . ";\n?>";
    file_put_contents($env_file, $config_content);
    
    echo "<div class='alert alert-success'>Environment switched to: <strong>" . strtoupper($new_env) . "</strong></div>";
}

$current_mode = $current_env['APP_ENV'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Environment Switcher</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Environment Configuration</h2>
    
    <div class="alert alert-info">
        <strong>Current Environment:</strong> <?= strtoupper($current_mode) ?>
    </div>
    
    <form method="post">
        <div class="form-group">
            <label>Switch Environment:</label>
            <select name="environment" class="form-control">
                <option value="debug" <?= $current_mode === 'debug' ? 'selected' : '' ?>>Debug/Development</option>
                <option value="production" <?= $current_mode === 'production' ? 'selected' : '' ?>>Production</option>
            </select>
        </div>
        
        <h4>Database Settings</h4>
        <div class="row">
            <div class="col-md-6">
                <h5>Debug/Development</h5>
                <div class="form-group">
                    <label>Host:</label>
                    <input type="text" class="form-control" value="<?= $current_env['DB_HOST_DEBUG'] ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" class="form-control" value="<?= $current_env['DB_USERNAME_DEBUG'] ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Database:</label>
                    <input type="text" class="form-control" value="<?= $current_env['DB_NAME_DEBUG'] ?>" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <h5>Production</h5>
                <div class="form-group">
                    <label>Host:</label>
                    <input type="text" class="form-control" value="<?= $current_env['DB_HOST_PROD'] ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" class="form-control" value="<?= $current_env['DB_USERNAME_PROD'] ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Database:</label>
                    <input type="text" class="form-control" value="<?= $current_env['DB_NAME_PROD'] ?>" readonly>
                </div>
            </div>
        </div>
        
        <button type="submit" name="action" value="switch" class="btn btn-primary">Switch Environment</button>
        <a href="index.php" class="btn btn-secondary">Go to Login</a>
        <a href="setup_database.php" class="btn btn-info">Setup Database</a>
    </form>
    
    <hr>
    <h4>Application Users</h4>
    <ul>
        <li><strong>Master Password:</strong> <?= $current_env['MASTER_PASSWORD'] ?> (works with location usernames)</li>
        <li><strong>Test Admin:</strong> <?= $current_env['TEST_ADMIN_USERNAME'] ?> / <?= $current_env['TEST_ADMIN_PASSWORD'] ?></li>
    </ul>
    
    <h4>Location Users (use with master password):</h4>
    <ul>
        <li>steinkjer (ID: <?= $current_env['USER_STEINKJER_ID'] ?>)</li>
        <li>namsos (ID: <?= $current_env['USER_NAMSOS_ID'] ?>)</li>
        <li>lade (ID: <?= $current_env['USER_LADE_ID'] ?>)</li>
        <li>moan (ID: <?= $current_env['USER_MOAN_ID'] ?>)</li>
        <li>gramyra (ID: <?= $current_env['USER_GRAMYRA_ID'] ?>)</li>
        <li>frosta (ID: <?= $current_env['USER_FROSTA_ID'] ?>)</li>
    </ul>
</div>
</body>
</html> 