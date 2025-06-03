<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<!-- CSS only -->
<!--
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
-->
<!-- Button trigger modal -->

<h1>Aroi Asia Ordre status</h1>

<?php
include 'api.php';
//showButton(20251);
//showButton(20250);

//$page = $_SERVER['PHP_SELF'];
//$sec = "120";
//header("Refresh: $sec; url=$page");

showOrders(7);
showOrders(4);
showOrders(6);
showOrders(5);

function showOrders($siteid){
	$temp = '';
	//$orderid = 20251;
	
	$username = ''; // Add Consumer Key here
	$password = ''; // Add  Consumer Secret here

	/**
	* NAME		SITE		USERID
	* Namsos	7			10
	* Lade		4			11
	* Moan		6			12
	* Gramyra	5			13
	**/

		switch($siteid){
		case 7:
			$temp = 'https://namsos.aroiasia.no';
			$username = 'ck_2a4b75485f94a9e44674cbdfe3e31f170f89013c'; // Add Consumer Key here
			$password = 'cs_bbb666f44f58067e29b496caba424de4b478ff19'; // Add  Consumer Secret here
			break;
		case 4:
			$temp = 'https://lade.aroiasia.no';
			$username = 'ck_bafedee6aeb279a36d03d49e5e1c1cead0f83a70'; // Add Consumer Key here
			$password = 'cs_2bb6e76e95027487336568b0951fbefc369132ff'; // Add  Consumer Secret here
			break;
		case 6:
			$temp = 'https://moan.aroiasia.no';
			$username = 'ck_81b9ce602a9f1d43fe4f43bf3a0ec9a8d2124243'; // Add Consumer Key here
			$password = 'cs_489cef04590eea21373aa829246c9e35b9b20745'; // Add  Consumer Secret here
			break;
		case 5:
			$temp = 'https://gramyra.aroiasia.no';
			$username = 'ck_0c755f0c8a5ac6e00d407980e011c23bf653f611'; // Add Consumer Key here
			$password = 'cs_9d75556f8c1936dae310351d5dfd46396cba2ba1'; // Add  Consumer Secret here
			break;
		default:
			$siteid = 0;
			break;
		
	}
	$url = $temp.'/wp-json/wc/v3/orders';
	//$url = $temp.'/wp-json/wc/v3/orders/'.$orderid;
	
	getOrderSync($url, $username, $password, $siteid);
	getOrder($url, $username, $password, $siteid);
	
}

                                                                                  
function getOrder($url, $username, $password, $site){
$data = array("status" => "completed");                                                                    
$data_string = json_encode($data);

//echo $url.'/?consumer_key='.$username.'&consumer_secret='.$password;
$ch = curl_init($url.'/?consumer_key='.$username.'&consumer_secret='.$password);
//echo $ch;
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);                                  
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                     
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',                                                                                
    'Content-Length: ' . strlen($data_string))                                                                       
);                                                                                                                   

$result = json_decode(curl_exec($ch), true);
//$result = $result[0];
//var_dump($result);
//print_r($result->id);

date_default_timezone_set("Europe/Oslo");
	
	foreach($result as $v){
		$ordreid = $v['id'];

		if(!checkOrderExist($ordreid)){
	 
			$fornavn = $v['billing']['first_name'];
			$etternavn = $v['billing']['last_name'];
			$telefon = $v['billing']['phone'];
			$epost = $v['billing']['email'];
			$ordrestatus = 0;
			$curl = queGetOrders($site);
			$curltime = date('d-m-Y-h:m');
			$datetime = $v['date_created'];
			$wcstatus = $v['status'];
			$payref = "ingen";
			$paid = 1;
			$seordre = 0;
			$paymentmethod = "ingen";
			$hentes = "0";
			
			foreach($v['meta_data'] as $card){
				if($card['key'] == "hentes_kl"){
					$hentes = "Hentes: ".$card['value']."<br>";
				}		
				if($card['key'] == "dibs_payment_method"){
					$paymentmethod = "Betalt med: ".$card['value']."<br>";
				}
				if($card['key'] == "_dibs_payment_id"){
					$payref = $card['value'];
				}
			
			}
			
			$sql = "INSERT INTO orders(fornavn, etternavn, telefon, ordreid, ordrestatus, curl, curltime, datetime, epost, site, paid, wcstatus, payref, seordre, paymentmethod, hentes) VALUES( '$fornavn', '$etternavn', '$telefon', $ordreid, $ordrestatus, '$curl', '$curltime', '$datetime', '$epost', $site, $paid, '$wcstatus', '$payref', $seordre, '$paymentmethod', '$hentes')";
			$sqlsvar = database($sql);
			sendSms($ordreid, $telefon);
			echo "- Inserted row";
			//echo mysqli_num_rows ( $sqlsvar );
			echo "<br>";
		}
		else{
			echo "Nothing to insert";
		}	

	}	
 //var_dump($test);
 
}

function getOrderSync($url, $username, $password, $site){
$data = array("status" => "processing");                                                                    
$data_string = json_encode($data);

//echo $url.'/?consumer_key='.$username.'&consumer_secret='.$password;
$ch = curl_init($url.'/?consumer_key='.$username.'&consumer_secret='.$password);
//echo $ch;
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);                                  
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                     
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',                                                                                
    'Content-Length: ' . strlen($data_string))                                                                       
);                                                                                                                   

$result = json_decode(curl_exec($ch), true);
//$result = $result[0];
//var_dump($result);
//print_r($result->id);

date_default_timezone_set("Europe/Oslo");
	
	foreach($result as $v){
		$ordreid = $v['id'];

		queGetOrders($site);
		echo "<p style='color:red'>OrdreID ".$ordreid." from site ".$site." is still processing!</p>";
		
		}
			

}	

 


function checkOrderExist($ordreid){
	$status = false;
	$sql = "SELECT ordreid FROM orders WHERE ordreid = $ordreid";
	$result = database($sql);
		if(mysqli_num_rows ( $result ) > 0){
			$status = true;
			echo "<br />Ordre ".$ordreid." Exist!";
		}else{
			$status = false;
			echo "<br />Ordre ".$ordreid." DO NOT Exist!";
		}
		
	return $status;	
}
	
?>