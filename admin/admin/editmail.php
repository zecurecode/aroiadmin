<!DOCTYPE html>

<?php

include 'dbcon.php';
include 'sendepost.php';

session_start();
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$userid = $_SESSION["id"];
	$siteid = 0;
	switch($userid){
		case 17:
			$siteid = 13;
			break;	
		case 10:
			$siteid = 7;
			break;
		case 11:
			$siteid = 4;
			break;
		case 12:
			$siteid = 6;
			break;
		case 13:
			$siteid = 5;
			break;
		case 14:
			$siteid = 10;
			break;
		case 16:
			$siteid = 11;
			break;

		default:
			$license = 0;
			break;
		
	}	

if(isset($_POST['endre']))
{
	$text = $_POST['inndata'];
	//complete($ordreid);
	//echo $text;
	//$text = mb_convert_encoding($text, ); iconv(mb_detect_encoding($text, mb_detect_order(), true), "no_NO.UTF-8", $text);
	
	setMail($text, $siteid);
}
if(isset($_POST['tilbake']))
{
	header( "refresh:0;url=https://aroiasia.no/admin" );
}
?>
<html>
        <head>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
        </head>
        <body>
<div class="form-floating">              
			  <form method="post" action="">
			  <h4 align="center"><?php echo $_SESSION["username"]."( ".$_SESSION["id"]." )"; ?></h4>
			  <h5 align="center">Her endrer du innholdet i SMS som sendes til kunde. OrdreID / Ordrereferanse legges til automatisk</h5>
				<textarea class="form-control" name="inndata" style="height: 100px"><?php echo getMail($siteid); ?></textarea>
				

				<button type="submit" value="22" class="btn btn-success" name="endre">Klikk for å endre</button>
				<button type="submit" class="btn btn-success" name="tilbake">Gå tilbake</button>
				</form>
<div>
        </body>
</html>