				<!DOCTYPE html>
<html>
<head>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
				<?php

include 'dbcon.php';
header( "refresh:2;url=https://admin.hungryeyes.no" );
	if(isset($_POST['regTid'])) {
$rettid = $_POST['rettid'];
$rettnavn = $_POST['rettnavn'];
$leveringstid = $_POST['leveringstid'];
$sql="INSERT INTO `leveringstid`( `id_rett`, `rett navn`, `tid`) VALUES ($rettid,'$rettnavn',$leveringstid)";
	 if (mysqli_query($conn, $sql)) {
		
		 echo '<div class="p-5 bg-success"><h3>Leveringstid er oppdatert!</h3> </div>';
	 } else {
		echo "Error: " . $sql . "
" . mysqli_error($conn);
	 }
	 mysqli_close($conn);
}

?>