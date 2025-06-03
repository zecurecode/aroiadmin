<?php
 echo "<b>HEI HEI </b><br>";
// $servername = 'localhost:3306';
//        $username = 'adminhungry';
//        $password ='k^i88o8Z';
//        $dbname = 'admin_hungry';
//$conn=mysqli_connect($servername,$username,$password,"$dbname");
//if(!$conn){
//   die('Could not Connect My Sql:' .mysql_error());
//}

function database($sql){

	$servername = "localhost:3306";
	$username = "adminaroi";
	$password = "b^754Xws";
	$dbname = "admin_aroi";

//echo $sql;	
 
	// Create connection
	$conn = mysqli_connect($servername, $username, $password, $dbname);
	// Check connection
	if (!$conn) {
		die("Connection failed: " . mysqli_connect_error());
	}
	
	//mysqli_set_charset($conn, "UTF-8");
	$result = mysqli_query($conn, $sql);
	
	if ($result) {
		//echo "DB ok!";
	} else {
		echo "Error in DB op: " . mysqli_error($conn);
	}

	return $result;
	mysqli_close($conn);
	
}
?>