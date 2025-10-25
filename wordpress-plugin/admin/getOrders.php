<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<!-- CSS only -->
<!--
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
-->
<!-- Button trigger modal -->

<?php

//showButton(20251);
//showButton(20250);
function showButton($orderid, $siteid){
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
		case 13:
			$temp = 'https://steinkjer.aroiasia.no';
			$username = 'ck_6bf2d1f2faceb2ad6160a52b82cbfd427c529f3d'; // Add Consumer Key here
			$password = 'cs_63dfe1322dd1c02e5f6ded03091ae79c177c02ba'; // Add  Consumer Secret here
			break;
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
		case 10:
			$temp = 'https://stjordal.aroiasia.no';
			$username = 'ck_d0badbe232a9a4ecb216111bdef901516eea4dfa'; // Add Consumer Key here
			$password = 'cs_0f5862a1783a7690ed30e92f6ca837fcabfea1c4'; // Add  Consumer Secret here
			break;
		case 11:
			$temp = 'https://hell.aroiasia.no';
			$username = 'ck_45df43fcf8ff4c3868c82bce06f2d847c6b39010'; // Add Consumer Key here
			$password = 'cs_ae29770a0bc73e905558c883a76e2050181b9c7b'; // Add  Consumer Secret here
			break;
		case 15:
			$temp = 'https://malvik.aroiasia.no';
			$username = 'ck_80a0c00216bffaf9816218d2a1666d99133fd84c'; // Add Consumer Key here
			$password = 'cs_19d03b1b34dcb30d833a6c78c253f869dd3d6386'; // Add  Consumer Secret here
			break;
		default:
			$siteid = 0;
			break;
		
	}
	$url = $temp.'/wp-json/wc/v3/orders/'.$orderid;

	if(isset($_POST['orderdeails'])){
		$orderid = $_POST['orderdeails'];
		echo "<h1>Have been set with value: </h1>";
	}
?>

<!-- Modal -->
<div class="modal fade" id="ordre<?php echo $orderid; ?>" tabindex="-1" aria-labelledby="ordre<?php echo $orderid; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ordre<?php echo $orderid; ?>">Ordredetaljer for ordre <?php echo $orderid; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php 
			echo getOrder($url, $username, $password); 
		
		?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Lukk</button>
      </div>
    </div>
  </div>
</div>

<?php

}
                                                                                  
function getOrder($url, $username, $password){
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
 

//echo $result;

//echo "----";
echo "<h5>Bestiller:</h5>";
echo $result['billing']['first_name']." ".$result['billing']['last_name']."<br>";
echo $result['billing']['address_1'].", ".$result['billing']['postcode'].", ".$result['billing']['city']."<br>";
echo "Telefon: ".$result['billing']['phone']." Epost: ".$result['billing']['email']."<br>";
//echo $result['payment_method'];
//echo $result['payment_method_title'];
//echo $result['transaction_id'];

foreach($result['meta_data'] as $card){
	if($card['key'] == "hentes_kl"){
	echo "Hentes: ".$card['key']." ".$card['value']."<br>";
	}
	if($card['key'] == "dibs_customer_card"){
	echo "Betalt med kort: ".$card['value']."<br>";
	}
	//echo "Betalt via: ".$card['key']." ".$card['value']."<br>";
	if($card['value'] == "Vipps"){
	echo "Betalt med Vipps"."<br>";
	}
	//print_r($card);
	
}
echo "<hr><h5>Ordre:</h5>";
$teller = 1;
foreach($result['line_items'] as $order){
	echo "<b>Ordelinje: ".$teller."</b><br>";
	echo $order['name']."<br> Antall: ".$order['quantity']."<br>";
		foreach($order['meta_data'] as $tilvalg){
			if($tilvalg['key'] != "_reduced_stock"){
			echo "Tilvalg: ".$tilvalg['key']."( ".$tilvalg['value']." )";
			echo "<br>";
			}
		}
	echo "<hr>";
	$teller++;
}
echo "<h5>Ordrekommentar:</h5>";
echo "Kunde skrev :".$result['customer_note']."<br>";
echo "<hr>";
	
echo "<h5>Betaling:</h5>";
echo "Kunden har betalt kroner: ".$result['total']."<br>";
echo "Bestillingen ble gjort fra IP :".$result['customer_ip_address']. " og utstyr ".$result['customer_user_agent']." Dato: ".$result['date_created'];
}//End function
	
?>