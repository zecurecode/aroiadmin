<?php

	viewDevices();
	

	
// NOTE: Only fetches the first 300 devices.
//       Will need to add looping with offset to get all devices.
//https://documentation.onesignal.com/reference/view-devices
function getDevices(){ 
  $app_id = "f6af5303-a504-4c09-aeb2-151e27537f6e";
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/players?app_id=" . $app_id); 
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 
                                             'Authorization: Basic MjJiNTI0NTktZTkzYi00YjM2LWE3ODAtNTRmMTVhNDk1MjQ2')); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  $response = curl_exec($ch); 
  curl_close($ch); 
  return $response; 
}

function sendMessage() {
    $content      = array(
        "nb" => 'Test fra Hungry Eyes'
    );
    $hashes_array = array();
    array_push($hashes_array, array(
        "id" => "like-button",
        "text" => "Se vårt utvalg",
        "icon" => "http://i.imgur.com/N8SN8ZS.png",
        "url" => "https://hungryeyes.no"
    ));
    array_push($hashes_array, array(
        "id" => "like-button-2",
        "text" => "Prøv en ny rett",
        "icon" => "http://i.imgur.com/N8SN8ZS.png",
        "url" => "https://hungryeyes.no"
    ));
    $fields = array(
        'app_id' => "f6af5303-a504-4c09-aeb2-151e27537f6e",
        'included_segments' => array(
            'Subscribed Users'
        ),
        'data' => array(
            "foo" => "bar"
        ),
        'contents' => $content,
        'web_buttons' => $hashes_array
    );
    
    $fields = json_encode($fields);
    print("\nJSON sent:\n");
    print($fields);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic MjJiNTI0NTktZTkzYi00YjM2LWE3ODAtNTRmMTVhNDk1MjQ2'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

function startMsg(){
	$response = sendMessage();
$return["allresponses"] = $response;
$return = json_encode($return);

$data = json_decode($response, true);
print_r($data);
$id = $data['id'];
print_r($id);

print("\n\nJSON received:\n");
print($return);
print("\n");
	
}

function viewDevices(){

$response = getDevices(); 
$return["allresponses"] = $response; 
$return = json_encode( $return); 
print("\n\nJSON received:\n"); 
print($return); 
print("\n");
}


?>