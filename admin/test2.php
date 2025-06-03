<?php

	$linksms = "https://api1.teletopiasms.no/gateway/v3/plain?username=p3166eu720i&password=Nvn4xh8HADL5YvInFI4GLlhM&recipient=4790039911,4796017450&text=GetOrders_feilet_for_HungryEyes...";
	$linkstr = "https://min.pckasse.no/QueueGetOrders.aspx?licenceno=10957";
$httpcode = 400;
	
	    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $linkstr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   $output= curl_exec($ch);
	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
		//echo $httpcode;
		//echo $output;
		return $httpcode;

$response = $httpcode;
$failed = true;
if ($response == 201){
	$failed = false;
}
if ($response == 200){
	$failed = false;
}
if($failed){
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $linksms);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   $output= curl_exec($ch);
	   $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	   //echo "Second $httpcode";
        curl_close($ch);
}

echo $httpcode;
//echo $output;

?>