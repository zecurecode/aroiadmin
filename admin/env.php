<?php
// Environment Configuration File
// This file contains all environment-specific settings

return [
    // Application Environment
    'APP_ENV' => 'debug', // Options: 'debug', 'production'
    
    // Database Configuration - Debug/Development (XAMPP defaults)
    'DB_HOST_DEBUG' => '141.94.143.8',
    'DB_PORT_DEBUG' => '3306',
    'DB_USERNAME_DEBUG' => 'adminaroi',
    'DB_PASSWORD_DEBUG' => 'b^754Xws',
    'DB_NAME_DEBUG' => 'admin_aroi',
    
    // Database Configuration - Production
    'DB_HOST_PROD' => 'localhost',
    'DB_PORT_PROD' => '3306',
    'DB_USERNAME_PROD' => 'adminaroi',
    'DB_PASSWORD_PROD' => 'b^754Xws',
    'DB_NAME_PROD' => 'admin_aroi',
    
    // Application Users & Passwords
    'MASTER_PASSWORD' => 'AroMat1814',
    
    // Test Admin User
    'TEST_ADMIN_USERNAME' => 'admin',
    'TEST_ADMIN_PASSWORD' => 'admin123',
    
    // Location-based User IDs
    'USER_STEINKJER_ID' => 17,
    'USER_NAMSOS_ID' => 10,
    'USER_LADE_ID' => 11,
    'USER_MOAN_ID' => 12,
    'USER_GRAMYRA_ID' => 13,
    'USER_FROSTA_ID' => 14,
    
    // Debug Settings
    'DEBUG_ENABLED' => true,
    'DEBUG_LOG_ERRORS' => true,
    'DEBUG_DISPLAY_ERRORS' => true,
];
?> 