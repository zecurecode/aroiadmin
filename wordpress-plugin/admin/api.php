<?php
// required headers
header("Access-Control-Allow-Origin: *");
//header("Content-Type: application/json; charset=UTF-8");
	
include 'dbcon.php';	

$statuscode = 200;

function getUserInfo($siteid, $function){
	
	/**
	$function method:
	1. return username
	2. return license
	3. return userid
	*/
	
	$sql = "SELECT * FROM users WHERE siteid = $siteid";
	//echo $sql;
	$result = database($sql);
	$data = mysqli_fetch_array($result);
	$return = "";
	
	switch($function){
		case 1:
		$return = $data['username'];
		break;
		case 2:
		$return = $data['license'];
		break;
		case 3:
		$return = $data['id'];
		break;
		default:
		$return = "";
		break;
	}
	return $return;
}

//sendSms('1234', '+4790039911');

function sendSms($order_num, $telefon){
		
			$sql = "SELECT sms FROM orders WHERE ordreid = $order_num";
			
			$result = database($sql);
			$data = mysqli_fetch_array($result);
			$status = $data['sms'];
			
			echo "SMS STATUS = ", $status;
			
		if($status == 0){
		
			$sql = "UPDATE orders SET sms = 1 WHERE ordreid=$order_num";
			$result = database($sql);
			
			$message = "Takk for din ordre. Vi vil gjøre din bestilling klar så fort vi kan. Vi sender deg en ny SMS når maten er klar til henting. Ditt referansenummer er $order_num";
		
			//Password: 2tm2bxuIo2AixNELhXhwCdP8
			//Username: b3166vr0f0l
			//Paid = 0 ikke betalt
		
			$sms = "https://api1.teletopiasms.no/gateway/v3/plain?username=t3330w94i10&password=fiwb7lKlHTmJzaTDoVVZmGrp&recipient=";
			$sms .= $telefon;
			$sms .= "&text=";
			$sms .= $message;
			$sms = str_replace ( " ", "%20", $sms);
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $sms);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output= curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
		}
	
}

function queGetOrders($siteid){
	echo "calling queGetOrders...";
	$license = getUserInfo($siteid, 2);
	//$linksms = "https://api1.teletopiasms.no/gateway/v3/plain?username=p3166eu720i&password=Nvn4xh8HADL5YvInFI4GLlhM&recipient=4790039911,4796017450&text=GetOrders_feilet_for_AroiAsia...";
	$linkstr = "https://min.pckasse.no/QueueGetOrders.aspx?licenceno=$license";
    $httpcode = 400;
	
	    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $linkstr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $output= curl_exec($ch);
	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
		echo $httpcode;
		//echo $output;
		//return $httpcode;

	$response = $httpcode;
	$failed = true;
	
	if ($response == 201){
	$failed = false;
	}
	if ($response == 200){
	$failed = false;
	}
	
	
	//echo $response;
	return $response;
}
//queGetOrders(6);
echo "<br><br><br>";

function checkCurl(){	
	
	echo "calling checkCurl";
	$statuscode = 200;
	
	$sql = "SELECT * FROM orders WHERE curl = 0";
	
	$result = database($sql);
	
	$row = mysqli_fetch_array($result);
	
	print_r($row);
	
	while($row = mysqli_fetch_array($result)){
		
		$response = queGetOrders($row['site']);
		echo $response;
		if($response == "200" || $response == "201"){
			updateDb($response, $row['id']);
			sendSms($row['ordreid'], $row['telefon']);
		}
		//echo "data";
	}
}	checkFailedOrders();

checkCurl();
//printWhatWeGot();	

function checkFailedOrders(){
	echo "inside checkFailedOrders";
	$sql = "SELECT * FROM orders WHERE paid = 0";
	
	$result = database($sql);
	
	$row = mysqli_fetch_array($result);
	
	date_default_timezone_set('Europe/Oslo');
	$systemtime = new DateTime('NOW');
	//echo "System time: $systemtime";
	//echo $systemtime->format('Y-m-d h:i:s');
	
	
	while($row = mysqli_fetch_array($result)){
			
		$orderTime = new DateTime($row['datetime']);
		//$b = new DateTime('2016-11-05 00:19:00');
		$diff =  ($systemtime->getTimestamp() - $orderTime->getTimestamp())/60;
		//echo $diff;
		
		if($diff > 5){
			//$linksms = "https://api1.teletopiasms.no/gateway/v3/plain?username=p3166eu720i&password=Nvn4xh8HADL5YvInFI4GLlhM&recipient=4790039911,4796017450&text=";
			$linksms = "Aroi ordreid ".$row['ordreid']." (vogn ".$row['site'].") har ikke blitt betalt på ".number_format($diff, 0)." minutter!";
			echo $linksms;
			//sendAlertSms($linksms);
			
		}
		
	}
	deleteOldRecords();
}

function sendAlertSms($data){
		
		echo $data;
		$linksms = "https://api1.teletopiasms.no/gateway/v3/plain?username=p3166eu720i&password=Nvn4xh8HADL5YvInFI4GLlhM&recipient=4790039911,4796017450&text=";
		$linksms .= urlencode($data);
	
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $linksms);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output= curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		echo $output;
		

}

function deleteOldRecords(){
	
	$sql = "DELETE FROM orders WHERE datetime < NOW() - INTERVAL 14 DAY;";
	database($sql);
	
}

function updateDb($curl, $id){
	date_default_timezone_set("Europe/Oslo");
	$curltime = date('d-m-Y H:i:s');
	
	$sql = "UPDATE orders SET curl = $curl, curltime = '$curltime' WHERE id = $id";
	//echo $sql;
	$result = database($sql);
}
	
function printWhatWeGot(){
		$sql = "SELECT * FROM orders WHERE ordrestatus = 0";
	
	$result = database($sql);
	
	$row = mysqli_fetch_array($result);
	
	//print_r($row);
	
	while($row = mysqli_fetch_array($result)){
		
		echo $row['ordrestatus'];
		echo "|";
		echo $row['ordreid'];
		echo "|";
		echo $row['curl'];
		echo "|";
		echo $row['curltime'];
		echo "|";
		echo $row['site'];
		echo "||";
		echo getUserInfo($row['site'], 1);
		echo "||";
		echo getUserInfo($row['site'], 3);
		echo "<br>";
		//echo "data";
	}
	
}
	
	
	//JSON END
	http_response_code($statuscode);
	//$data = $tid;
	//echo json_encode($data);
	
?>
	