<?php
include 'dbcon.php';	
	

	$sql = "SELECT tid FROM  leveringstid WHERE id = 1";
	$result = database($sql);
	$tid = mysqli_fetch_row($result);
	
	//print_r($tid);
	echo $tid[0];
	
?>
	