<?php
define('DB_SERVER', '141.94.143.8:3306');
define('DB_USERNAME', 'adminaroi');
define('DB_PASSWORD', 'b^754Xws');
define('DB_NAME', 'admin_aroi');
 
/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>