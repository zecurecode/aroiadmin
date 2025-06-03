<?php

//include 'dbcon.php';

function sendMail($id){
    //echo $id;
	$mail = getEmail($id);
	$to      = $mail;
    $subject = 'Aroi Asia';
    $message = getMail();
	$headers = 'From: post@hungryeyes.no' . "\r\n" .
    'Reply-To: post@hungryeyes.no' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);

    echo '<h1 align="center">Vi har sendt epost til ', $to, ' om å at maten er klar!';
	updateOrders($id);	
}

function updateOpen($i, $user){
	$dag = date('N');
	$vognst = "status".$user;
	$btn = "btn".$user;
	if($i == 0){
	$sql = "UPDATE apningstid SET $vognst = 1 $btn = 1 WHERE id = $dag";
	}else if($i == 1){
		$sql = "UPDATE apningstid SET $vognst = 0 $btn=0 WHERE id = $dag";
	}
	//echo $sql;
	$result = database($sql);
	header( "refresh:2;url=/admin/welcome.php" );
	
}

function updateOverstyr($status, $user){
	$sql = "UPDATE overstyr SET status = $status where vognid=$user";
	//echo $sql;
	$result = database($sql);
	header( "refresh:2;url=/admin/welcome.php" );
}

function getOverstyr($user) {
    $sql = "SELECT * FROM overstyr WHERE vognid=$user";
    $result = database($sql);

    // Check if the query was successful and if it returned at least one row
    if ($result && $result->num_rows > 0) {
        // Fetch the first row from the result set
        $row = $result->fetch_assoc();

        // Check if the 'status' column exists in the row
        if (isset($row['status'])) {
            // Return the value of 'status' column
            return $row['status'];
        }
    }

    // Redirect if no result found or 'status' column is not set
    header("refresh:2;url=/admin/welcome.php");
    exit; // Ensure no further code is executed after a redirect
}

function sendSms($id, $siteid) {
    $tel = getPhone($id);
    $message = getMail($siteid) . " Din ordrereferanse er: $id";

    // It's better to store credentials outside of the codebase, like in environment variables or a config file.
    $username = 'b3166vr0f0l';
    $password = '2tm2bxuIo2AixNELhXhwCdP8';

    // Build the URL
    $params = [
        'username' => $username,
        'password' => $password,
        'recipient' => $tel,
        'text' => $message
    ];
    $sms = "https://api1.teletopiasms.no/gateway/v3/plain?" . http_build_query($params);

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sms);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        // Handle error as needed, maybe log it or show an error message.
    }

    curl_close($ch);

    // Write to log.txt
    //$logFile = 'log.txt';
    //file_put_contents($logFile, "Output: " . $output . " HTTP Code: " . $httpcode . "\n" . "Source: " . $sms . "\n", FILE_APPEND);

    updateOrders($id);
}

function testMail(){
	//$mail = "Tester ø, æ og å";
	$to      = "hakon@driftsikker.no";
    $subject = 'Hungry Eyes AS';
    $message = "Tester ø, æ og å";
	$headers = 'From: post@hungryeyes.no' . "\r\n" .
    'Reply-To: post@hungryeyes.no' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);

    echo '<h1 align="center">Vi har sendt epost til ', $to, ' om å at maten er klar!';
}

function getEmail($id){
	$email = "nada";
	$sql = "SELECT * FROM orders WHERE ordreid = $id";
	//echo $sql;
	$result = database($sql);
	$data = mysqli_fetch_assoc($result);
	$email = $data['epost'];
	//echo $email;
	return $email;

}
function getPhone($id){
	$tel = 0;
	$sql = "SELECT * FROM orders WHERE ordreid = $id";
	//echo $sql;
	$result = database($sql);
	$data = mysqli_fetch_assoc($result);
	$tel = $data['telefon'];
	//echo $email;
	return $tel;

}

function updateOrders($id){
	$sql = "UPDATE orders SET ordrestatus = 1 WHERE ordreid = $id";
	$return = database($sql);
	
	header( "refresh:2;url=/admin/welcome.php" );
}

function setActive($id){
	$sql = "UPDATE orders SET seordre = 1 WHERE id = $id";
	echo $sql;
	$return = database($sql);
	
	header( "refresh:2;url=/admin/welcome.php" );
}

function complete($id){
	$sql = "UPDATE orders SET ordrestatus = 2 WHERE ordreid = $id";
	$return = database($sql);
	
	header( "refresh:2;url=/admin/welcome.php" );
}

function getTid($id){
	$sql = "SELECT * FROM leveringstid WHERE id = $id";
	$result = database($sql);
	$data = mysqli_fetch_assoc($result);
	$tid = $data['tid'];
	
	return $tid;
}

function setTid($tid, $id){
	$sql = "UPDATE leveringstid SET tid = $tid WHERE id = $id";
	$result = database($sql);
	
	
	return true;
}

function getMail($id){
	$sql = "SELECT * FROM mail WHERE id = $id";
	$result = database($sql);
	$data = mysqli_fetch_assoc($result);
	$str = $data['text'];
	
	return $str;
}
	
function setMail($str, $id){
	$sql = "UPDATE mail SET text = '$str' WHERE id = $id";
	$result = database($sql);
	//echo $sql;
	
	return true;
}	

?>
