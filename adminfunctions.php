<?php
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
	
	//mysqli_set_charset($conn, "utf8");
	$result = mysqli_query($conn, $sql);
	
	if ($result) {
		//echo "DB ok!";
	} else {
		echo "Error in DB op: " . mysqli_error($conn);
	}

	return $result;
	mysqli_close($conn);
	
}

function gettid_function($id){
	
	$userid = 0;
	switch($id){
		case 7:
			$userid = 10;
			break;
		case 4:
			$userid = 11;
			break;
		case 6;
			$userid = 12;
			break;
		case 5:
			$userid = 13;
			break;
		case 9:
			$userid = 13;
			break;
		case 10:
			$userid = 14;
			break;
		case 11:
			$userid = 16;
			break;
		default:
			$userid = 0;
			break;
	}
	
	$sql = "SELECT tid FROM leveringstid WHERE id = $userid";
	$result = database($sql);
	$tid = mysqli_fetch_row($result);
	
	//print_r($tid);
	return $tid[0];
	//return $id;
	
}

function gettid_function2($atts){
	//echo "kjører gettid";
	$userid = $atts['site'];
	//echo $userid;
	$day = date("N");
	//echo $day;
	if($day == 0){
		$day = 7;
	}
	$vogn = "";
	switch($userid){
		case 7:
			$sql = "SELECT opennamsos, closenamsos, statusnamsos FROM apningstid where id=$day";
			$vogn = "namsos";
			break;
		case 4:
			$sql = "SELECT openlade, closelade, statuslade FROM apningstid where id=$day";
			$vogn = "lade";
			break;
		case 6;
			$sql = "SELECT openmoan, closemoan, statusmoan FROM apningstid where id=$day";
			$vogn = "moan";
			break;
		case 5:
			$sql = "SELECT opengramyra, closegramyra, statusgramyra FROM apningstid where id=$day";
			$vogn = "gramyra";
			break;
		case 10:
			$sql = "SELECT openfrosta, closefrosta, statusfrosta FROM apningstid where id=$day";
			$vogn = "frosta";
			break;
		case 11:
			$sql = "SELECT openhell, closehell, statushell FROM apningstid where id=$day";
			$vogn = "hell";
			break;
		default:
			$userid = 0;
			break;
	}
	$status = isItOpenNow($vogn);
	$closed = isItClosedNow($vogn);
	
	//echo $sql;
	//$sql = "SELECT opennamsos FROM apningstid";
	$result = database($sql);
	$tid = mysqli_fetch_row($result);
	echo $tid[0]." til ".$tid[1]."<br>";
	if($tid[2] == 0 || !$status || $closed){
		echo "<span style='color:red;'>Åpner klokken ".$tid[0]." i dag. Du kan fortsatt bestille for henting innen åpningstiden.</span>";
	}else{
		echo "<span style='color:green;'>Åpen for henting i dag</span>";
	}
	
	if($status && $tid[2] == 0){
		$statustxt = "status".$vogn;
		$sql = "UPDATE apningstid SET $statustxt=1 WHERE id=$day";
		$result = database($sql);
	}
	
	//print_r($tid);
	//return $tid[0];
}

function isItOpenNow($vogn){
	$status = false;
	date_default_timezone_set("Europe/Oslo");
	$day = date('N');
	//echo $day;
	if($day == 0){
		$day = 7;
	}
	
	
	
	$vtext = "open".$vogn;
	$sql = "SELECT $vtext FROM apningstid where id=$day";
	$result = database($sql);
	$tid = mysqli_fetch_row($result);
	$now = date("H:i");
	//$now = "11:00";
	//echo $getClose($vogn);
	$t1 = strtotime($now);
	$t2 = strtotime($tid[0]);
	$t3 = $t2 + 3600;
	//echo $t3;
	if($t1 >= $t2 ){
		$status = true;
	}else{
		$status = false;
	}
	/**
	if($status){
		
		echo "<br>T:$tid[0] - T1:$tid[1]<br>";
	}else{
		echo "Closed";
		echo "<br>$t1 - $t2<br>";
	}
	**/
	
	 return $status;
	
}

function isItClosedNow($vogn){
	$status = false;
	date_default_timezone_set("Europe/Oslo");
	$day = date('N');
	//echo $day;
	if($day == 0){
		$day = 7;
	}
	
	
	
	$vtext = "close".$vogn;
	$sql = "SELECT $vtext FROM apningstid where id=$day";
	$result = database($sql);
	$tid = mysqli_fetch_row($result);
	$now = date("H:i");
	//$now = "11:00";
	//echo $getClose($vogn);
	$t1 = strtotime($now);
	$t2 = strtotime($tid[0]);
	$t3 = $t2 + 3600;
	//echo $t3;
	if($t1 >= $t2 ){
		$status = true;
		//echo "true";
	}else{
		$status = false;
		//echo "false";
	}
	/**
	if($status){
		
		echo "<br>T:$tid[0] - T1:$tid[1]<br>";
	}else{
		echo "Closed";
		echo "<br>$t1 - $t2<br>";
	}
	**/
	 return $status;
	
}

/**
	* NAME		SITE		USERID
	* Namsos	7			10
	* Lade		4			11
	* Moan		6			12
	* Gramyra	5			13
	* Hell	    11          
**/

function getSiteName($site){
	$sitename = "none";
	switch($site){
		case 11:
			$sitename = "hell";
			break;	
		case 7:
			$sitename = "namsos";
			break;
		case 4:
			$sitename = "lade";
			break;
		case 6;
			$sitename = "moan";
			break;
		case 5:
			$sitename = "gramyra";
			break;
		case 10:
			$sitename = "frosta";
			break;
		default:
			$sitename = "ingen";
			break;
	}
	return $sitename;
}

function getDay(){
	$converted = "";
	$day = date("D");
	switch($day){
		case "Mon":
			$converted = "Mandag";
			break;
		case "Tue":
			$converted = "Tirsdag";
			break;
		case "Wed":
			$converted = "Onsdag";
			break;
		case "Thu":
			$converted = "Torsdag";
			break;
		case "Fri":
			$converted = "Fredag";
			break;
		case "Sat":
			$converted = "Lørdag";
			break;
		case "Sun":
			$converted = "Søndag";
			break;
		default:
			$converted = "Ingen";
			break;
			
	}
	return $converted;
}

function nextDay(){
	$converted = "";
	$day = date("I");
	//$day = 6;
	if($day ==6){
		$day = 0;
	}else{
		$day += 1;
	}
	//$day += 1;
	switch($day){
		case 1:
			$converted = "Mandag";
			break;
		case 2:
			$converted = "Tirsdag";
			break;
		case 3:
			$converted = "Onsdag";
			break;
		case 4:
			$converted = "Torsdag";
			break;
		case 5:
			$converted = "Fredag";
			break;
		case 6:
			$converted = "Lørdag";
			break;
		case 0:
			$converted = "Søndag";
			break;
		default:
			$converted = "Ingen";
			break;
			
	}
	return $converted;
}

//Return all hours for all days
function getHours($id){
	$sitename = getSiteName($id);
	$sql = "SELECT day, open$sitename, close$sitename, notes$sitename FROM apningstid";
	
}
//Return open hours for current day
function getOpen($id){
	if($id == 9){
	$id = 5;
	}
	$day = getDay();
	//echo $day;
	$sitename = getSiteName($id);
	$sql = "SELECT open$sitename FROM apningstid WHERE day = '$day'";
	//echo $sql;
	$result = database($sql);
	$tid = mysqli_fetch_row($result);
	//echo $tid[0];
	//print_r($tid);
	return $tid[0];
	//return "12:00";
}

function getStatus($id){
	if($id == 9){
	$id = 5;
	}
	$day = getDay();
	//echo $day;
	$sitename = getSiteName($id);
	$sql = "SELECT status$sitename FROM apningstid WHERE day = '$day'";
	//echo $sql;
	$result = database($sql);
	$tid = mysqli_fetch_row($result);
	//echo $tid[0];
	//print_r($tid);
	return $tid[0];
	//return "12:00";
}

function getNextOpen(){
	//$day = nextDay();
	$day=date('Y-m-d', strtotime(' +1 day'));
	//echo $day;
	//echo date('I');
}

//Return close hour for current day
function getClose($id){
	if($id == 9){
	$id = 5;
	}
	$day = getDay();
	$sitename = getSiteName($id);
	$sql = "SELECT close$sitename FROM apningstid WHERE day = '$day'";
	//echo $sql;
	$result = database($sql);
	$tid = mysqli_fetch_row($result);
	//echo $tid[0];
	//print_r($tid);
	return $tid[0];
	//return "12:00";
}
//Return hour note for current date, or false if empty
function getNote($id){
	//echo "Kebabb";
}
//Return true or false
function isOpen($id){
	return true;
}

?>