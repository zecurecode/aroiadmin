<?php
include 'dbcon.php';
// Initialize the session
if (!isset($_SESSION)) {
session_start();
}
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
/**if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['postdata'] = $_POST;
    unset($_POST);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}**/
//print_r($_POST);
hours();
?>

<!DOCTYPE html>
<html>
 
<head>
<style>
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

#customers td, #customers th {
  border: 1px solid #ddd;
  padding: 8px;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #8a3695;
  color: white;
}
</style>
 
 
 </head>
 </html>


<?php

	$userid = $_SESSION["id"];
	$siteid = 0;
    echo "UserID:".$userid."<br>";
	switch($userid){
		case 17:
			$siteid = "Steinkjer";
			break;
		case 10:
			$siteid = "Namsos";
			break;
		case 11:
			$siteid = "Lade";
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
		case 23:
			$siteid = 15;
		default:
			$license = 0;
			break;
		
	}	
	

if(isset($_POST['submitlade'] )) {
endre_tiderlade();
}

if(isset($_POST['submitsteinkjer'] )) {
endre_tidersteinkjer();
}	

if(isset($_POST['submitmoan'] )) {
		endre_tidermoan();
}
if(isset($_POST['submitnamsos'] )) {
		endre_tidernamsos();
}
if(isset($_POST['submitgramyra'] )) {
		endre_tidergramyra();
}
if(isset($_POST['submitfrosta'] )) {
		endre_tiderfrosta();
}
if(isset($_POST['submithell'] )) {
		endre_tiderhell();
}

function hours()
	{
		$userid = $_SESSION["id"];
	$siteid = 0;
	switch($userid){
		case 17:
			$siteid = "Steinkjer";
			break;
		case 10:
			$siteid = "Namsos";
			break;
		case 11:
			$siteid = "Lade";
			break;
		case 12:
			$siteid = "Moan";
			break;
		case 13:
			$siteid = "Gramyra";
			break;
		case 14:
			$siteid = "Frosta";
			break;
		case 16:
			$siteid = "Hell";
			break;	
		case 15:
			$siteid = "Malvik";
			break;	
		default:
			$license = 0;
			break;
		
	}	
	
		echo"<h1>Åpningstider $siteid($userid)</h1>";
		echo"<h2>Etter å ha endret åpningstider må du laste siden på nytt for at de skal vises her!</h2>";
		
		echo "<form method='post' action=''>
				<table id='customers'>
			        <th>Dag</th>
					<th>Åpner</th>
					<th>Stenger</th>
					<th>Notat</th>";
	
   $sql = "SELECT * FROM apningstid";
	$data = database($sql);
		$i = 1;
		if ($siteid=="Lade"){
		while($row = mysqli_fetch_array($data))
		{   
			
			echo "<tr>";
			echo "<td><input name='day$i' type='text' value=", $row['day'], " /></td>";
			echo "<td><input name='openlade$i' type='text' value=", $row['openlade'], " ></td>";
			echo "<td><input name='closelade$i' type='text' value=", $row['closelade'], " ></td>";
			echo "<td><input name='noteslade$i' type='text' value=", $row['noteslade'], " ></td>";
			echo "</tr>";
			$i++;
		
		}
		
echo "<tr><td><input type='submit' style='background-color:#8a3695; color: white; font-size:20px; text-align:center; border:none' name='submitlade' id='submit' value='Lagre endringer' /></td></tr></table></form>";
	}
	elseif ($siteid=="Moan"){
		while($row = mysqli_fetch_array($data))
		{   
			
			echo "<tr>";
			echo "<td><input name='day$i' type='text' value=", $row['day'], " /></td>";
			echo "<td><input name='openmoan$i' type='text' value=", $row['openmoan'], " ></td>";
			echo "<td><input name='closemoan$i' type='text' value=", $row['closemoan'], " ></td>";
			echo "<td><input name='notesmoan$i' type='text' value=", $row['notesmoan'], " ></td>";
			echo "</tr>";
			$i++;
		
		}
		
		echo "<tr><td><input type='submit' style='background-color:#8a3695; color: white; font-size:20px; text-align:center; border:none' name='submitmoan' id='submit' value='Lagre endringer' /></td></tr></table></form>";
	}
		elseif ($siteid=="Namsos"){
		while($row = mysqli_fetch_array($data))
		{   
			
			echo "<tr>";
			echo "<td><input name='day$i' type='text' value=", $row['day'], " /></td>";
			echo "<td><input name='opennamsos$i' type='text' value=", $row['opennamsos'], " ></td>";
			echo "<td><input name='closenamsos$i' type='text' value=", $row['closenamsos'], " ></td>";
			echo "<td><input name='notesnamsos$i' type='text' value=", $row['notesnamsos'], " ></td>";
			echo "</tr>";
			$i++;
		
		}
		
		echo "<tr><td><input type='submit' style='background-color:#8a3695; color: white; font-size:20px; text-align:center; border:none' name='submitnamsos' id='submit' value='Lagre endringer' /></td></tr></table></form>";
		}
		elseif ($siteid=="Gramyra"){
		while($row = mysqli_fetch_array($data))
		{   
			
			echo "<tr>";
			echo "<td><input name='day$i' type='text' value=", $row['day'], " /></td>";
			echo "<td><input name='opengramyra$i' type='text' value=", $row['opengramyra'], " ></td>";
			echo "<td><input name='closegramyra$i' type='text' value=", $row['closegramyra'], " ></td>";
			echo "<td><input name='notesgramyra$i' type='text' value=", $row['notesgramyra'], " ></td>";
			echo "</tr>";
			$i++;
		
		}
		
		echo "<tr><td><input type='submit' style='background-color:#8a3695; color: white; font-size:20px; text-align:center; border:none' name='submitgramyra' id='submit' value='Lagre endringer' /></td></tr></table></form>";
		}
	elseif ($siteid=="Hell"){
		while($row = mysqli_fetch_array($data))
		{   
			
			echo "<tr>";
			echo "<td><input name='day$i' type='text' value=", $row['day'], " /></td>";
			echo "<td><input name='openhell$i' type='text' value=", $row['openhell'], " ></td>";
			echo "<td><input name='closehell$i' type='text' value=", $row['closehell'], " ></td>";
			echo "<td><input name='noteshell$i' type='text' value=", $row['noteshell'], " ></td>";
			echo "</tr>";
			$i++;
		
		}
		
		echo "<tr><td><input type='submit' style='background-color:#8a3695; color: white; font-size:20px; text-align:center; border:none' name='submithell' id='submit' value='Lagre endringer' /></td></tr></table></form>";
		}
		elseif ($siteid=="Steinkjer"){
		while($row = mysqli_fetch_array($data))
		{   		
			echo "<tr>";
			echo "<td><input name='day$i' type='text' value=", $row['day'], " /></td>";
			echo "<td><input name='opensteinkjer$i' type='text' value=", $row['opensteinkjer'], " ></td>";
			echo "<td><input name='closesteinkjer$i' type='text' value=", $row['closesteinkjer'], " ></td>";
			echo "<td><input name='notessteinkjer$i' type='text' value=", $row['notessteinkjer'], " ></td>";
			echo "</tr>";
			$i++;		
		}		
		echo "<tr><td><input type='submit' style='background-color:#8a3695; color: white; font-size:20px; text-align:center; border:none' name='submitsteinkjer' id='submit' value='Lagre endringer' /></td></tr></table></form>";
		}
		elseif ($siteid=="Frosta"){
		while($row = mysqli_fetch_array($data))
		{   
			
			echo "<tr>";
			echo "<td><input name='day$i' type='text' value=", $row['day'], " /></td>";
			echo "<td><input name='openfrosta$i' type='text' value=", $row['openfrosta'], " ></td>";
			echo "<td><input name='closefrosta$i' type='text' value=", $row['closefrosta'], " ></td>";
			echo "<td><input name='notesfrosta$i' type='text' value=", $row['notesfrosta'], " ></td>";
			echo "</tr>";
			$i++;
		
		}
		
		echo "<tr><td><input type='submit' style='background-color:#8a3695; color: white; font-size:20px; text-align:center; border:none' name='submitfrosta' id='submit' value='Lagre endringer' /></td></tr></table></form>";
		}
	}
		
	
	function endre_tiderlade()
	{
		//Mandag
         $i = 1;
		$nameErr ="";
		while($i<=7)
		{
			$popen= (string)"openlade$i";
			$pclose= (string)"closelade$i";
			$pnotes= (string)"noteslade$i";
		
			$open = $_POST[$popen];
			$close = $_POST[$pclose];
			$notes = $_POST[$pnotes];

  if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$open)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
    return false;
  }
   if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$close)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
  return false;
  }

	       $sql = "UPDATE apningstid SET openlade='$open', closelade='$close', noteslade='$notes' WHERE id=$i";
			
			database($sql);
			$i++;
		}
	}

	function endre_tidersteinkjer()
	{
		$i = 1;
		$nameErr ="";
		while($i<=7)
		{
			$popen= (string)"opensteinkjer$i";
			$pclose= (string)"closesteinkjer$i";
			$pnotes= (string)"notessteinkjer$i";
			$open = $_POST[$popen];
			$close = $_POST[$pclose];
			$notes = $_POST[$pnotes];
  if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$open)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
    return false;
  }
   if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$close)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
  return false;
  }
	       $sql = "UPDATE apningstid SET opensteinkjer='$open', closesteinkjer='$close', notessteinkjer='$notes' WHERE id=$i";		
			database($sql);
			$i++;
		}
	}
	
	function endre_tidermoan()
	{
		//Mandag
         $i = 1;
		$nameErr ="";
		while($i<=7)
		{
			$popen= (string)"openmoan$i";
			$pclose= (string)"closemoan$i";
			$pnotes= (string)"notesmoan$i";
		
			$open = $_POST[$popen];
		    $close = $_POST[$pclose];
			$notes = $_POST[$pnotes];
	/* if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$open)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
    return false;
  }
   if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$close)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
  return false;
  }*/
		
			$sql = "UPDATE apningstid SET openmoan='$open', closemoan='$close', notesmoan='$notes' WHERE id=$i";
			
			database($sql);
			$i++;
		}
	}
	function endre_tidernamsos()
	{
		//Mandag
         $i = 1;
		$nameErr ="";
		while($i<=7)
		{
			$popen= (string)"opennamsos$i";
			$pclose= (string)"closenamsos$i";
			$pnotes= (string)"notesnamsos$i";
		
			$open = $_POST[$popen];
			$close = $_POST[$pclose];
			$notes = $_POST[$pnotes];
		 if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$open)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
    return false;
  }
   if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$close)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
  return false;
  }
		
			$sql = "UPDATE apningstid SET opennamsos='$open', closenamsos='$close', notesnamsos='$notes' WHERE id=$i";
			
			database($sql);
			$i++;
		}
	}
	function endre_tidergramyra()
	{
		//Mandag
         $i = 1;
		$nameErr ="";
		while($i<=7)
		{
			$popen= (string)"opengramyra$i";
			$pclose= (string)"closegramyra$i";
			$pnotes= (string)"notesgramyra$i";
		
			$open = $_POST[$popen];
			$close = $_POST[$pclose];
			$notes = $_POST[$pnotes];
		 /*if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$open)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
    return false;
	
  }
   if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$close)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
  return false;
  }*/
		
			$sql = "UPDATE apningstid SET opengramyra='$open', closegramyra='$close', notesgramyra='$notes' WHERE id=$i";
			
			database($sql);
		
			$i++;
		}
	}

	function endre_tiderfrosta()
	{
		//Mandag
         $i = 1;
		$nameErr ="";
		while($i<=7)
		{
			$popen= (string)"openfrosta$i";
			$pclose= (string)"closefrosta$i";
			$pnotes= (string)"notesfrosta$i";
		
			$open = $_POST[$popen];
			$close = $_POST[$pclose];
			$notes = $_POST[$pnotes];
		 /*if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$open)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
    return false;
	
  }
   if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$close)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
  return false;
  }*/
		
			$sql = "UPDATE apningstid SET openfrosta='$open', closefrosta='$close', notesfrosta='$notes' WHERE id=$i";
			
			database($sql);
			$i++;
		}
	}

function endre_tiderhell()
	{
		//Mandag
         $i = 1;
		$nameErr ="";
		while($i<=7)
		{
			$popen= (string)"openhell$i";
			$pclose= (string)"closehell$i";
			$pnotes= (string)"noteshell$i";
		
			$open = $_POST[$popen];
			$close = $_POST[$pclose];
			$notes = $_POST[$pnotes];
		 /*if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$open)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
    return false;
	
  }
   if( !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/",$close)) {
  echo "<p>Feilmelding: Skriv inn klokkeslett i  00:00 format !</p>";
  return false;
  }*/
		
			$sql = "UPDATE apningstid SET openhell='$open', closehell='$close', noteshell='$notes' WHERE id=$i";
			echo $sql;
			database($sql);
			$i++;
		}
	}
 
