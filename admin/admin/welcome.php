<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

header("Refresh:120");
include 'dbcon.php';
include 'sendepost.php';
include 'getOrders.php';
if(isset($_POST['klar']))
{
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
	$ordreid = $_POST['klar'];
	//echo "TEST: Ordre - $ordreid, Site - $siteid";
	sendSms($ordreid, $siteid);
	//echo $mail;
}

if(isset($_POST['hentet']))
{
	$ordreid = $_POST['hentet'];
	complete($ordreid);
	//echo $ordreid;
}


if(isset($_POST['statuschange']))
{
	//$ordreid = $_POST['hentet'];
	//complete($ordreid);
	//echo $ordreid;
	//echo "<h1>Test</h1>";
	updateOverstyr($_POST['statuschange'], $_SESSION['id']);
}

if(isset($_POST['slett']))
{
	$ordreid = $_POST['slett'];
	complete($ordreid);
	//echo $ordreid;
}

if(isset($_GET['nytid']))
{
	//$ordreid = $_POST['hentet'];
	//complete($ordreid);
	//echo $ordreid;
	$tid = $_GET['changedtime'];
	//echo $tid;
	$userid = $_SESSION["id"];
	setTid($tid, $userid);
}

if(isset($_POST['seordre'])){
	//echo showButton($_POST['seordre']);
	//$oid = $_POST['seordre'];
	setActive($_POST['seordre']);
	//echo "TEST";
}

?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aroi Asia Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body{ font: 14px sans-serif; text-align: center; }
    </style>
</head>
<body>
<script>
	//alert(document.getElementById('customRange3').value);
	function changeTime(){
	document.getElementById('showtime').value = document.getElementById('customRange3').value
	}
</script>
    <h1 class="my-5">Hei, <b><?php echo htmlspecialchars($_SESSION["username"]);?> (<?php echo htmlspecialchars($_SESSION["id"]);?>)</b>. Velkommen tilbake!</h1>
    <p>
        <a href="reset-password.php" class="btn btn-warning">Bytt passord</a>
        <a href="logout.php" class="btn btn-danger ml-3">Logg ut</a>
		 <a href="editmail.php" class="btn btn-dark ml-3">Rediger mail</a>
		 <a href="apningstid.php" class="btn btn-success ml-3">Rediger åpningstid</a>
    </p>
	
	
	<div class="p-5">
	<hr />
	<h1 align="center">Leveringstid</h1>
	<h3 align="center">Leveringstid er satt til <span id="showtime"><?php echo getTid($_SESSION["id"]); ?></span> min</h3>
	<form class="form" action="">
	<div class="mb-3">
		<label for="customRange3" class="form-label">Leveringstid</label></br>
		<input type="range" class="form-range form-control" min="10" max="120" step="5" id="customRange3" name="changedtime" value="<?php echo getTid($_SESSION["id"]); ?>" onchange="changeTime()"><br/>
		<button type="submit" value="22" class="btn btn-dark" name="nytid">Klikk for å endre...</button>
	</div>
	<?php
	//Collection data...
	$apner = "12:00";
	$stenger = "24:00";
	$vognstatus = 1;
	$apentst = '<button type="submit" name="statuschange" value=1 class="btn btn-success">Åpent</button>';
	$stengtst = '<button type="submit" name="statuschange" value=0 class="btn btn-danger">Stengt</button>';
	$dagint = date('N');
	$vogn = $_SESSION["username"];
	
	$opentxt = "open".$vogn;
	$closetxt = "close".$vogn;
	$statust = "status".$vogn;
		
	$sql = "SELECT * FROM apningstid WHERE id=$dagint";
	$result = database($sql);
	$data = mysqli_fetch_array($result);
	$apner = $data[$opentxt];
	$stenger = $data[$closetxt];
	$vognstatus = $data[$statust];
	//$overstyrt = getOverstyr($_SESSION['id']);
	//echo $overstyrt;
	?>
	</form>
	<form class="openform" action="" method="POST">
	<h4>Dagens åpningstid er satt til: <?php echo $apner," - ", $stenger?></h4>
	<p align="center">Klikk på knappen for å endre status. Knappen overstyrer åpningstidene og viser status i tillegg til åpningstidene til kundene</p>
	<p align="center"><?php if($vognstatus == 1){ echo $apentst;} else { echo $stengtst;} ?></p>
	</form>
	
	<script>
	//alert(document.getElementById('customRange3').value);
	function changeTime(){
	document.getElementById('showtime').innerHTML = document.getElementById('customRange3').value;
	}
	</script>
	<hr />
<?php

	/**
	* NAME		SITE		USERID
	* Namsos	7			10
	* Lade		4			11
	* Moan		6			12
	* Gramyra	5			13
	**/
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




// Attempt select query execution
$sql = "SELECT * FROM orders WHERE site = $siteid AND paid=1 ORDER BY datetime desc";
$result = database($sql);
if($result){
    if(mysqli_num_rows($result) > 0){
		//echo "yes";
	}
}
		?>
		<div class="p-5">
		<form method="post" action="">
  <h1 align="center">Ordre</h1>

<p align="center">Dato er datoen ordren ble lagt inn. Dato i PCK er datoen PCK fikk ordren</p>
<hr>

<br>
 
  <table class= "table table-dark table-striped">
	    <thead>
				<tr class="">
				
					<th scope='col'>Fornavn</th>
					<th scope='col'>Etternavn</th>
					<th scope='col'>Mobil</th>
					<th scope='col'>Epost</th>
					<th scope='col'>Ordre Id</th>
					<th scope='col'>Dato</th>
					<th scope='col'>Se ordre</th>
					<th scope='col'>Hentes</th>
					<th scope='col'>Status</th>
					<th scope='col'>Slett</th>
					
				
				
				
				</tr>
			  </thead>
  <tbody>
				<?php
        
		
		
		while($row = mysqli_fetch_array($result)){
            echo "<tr>";
                $status = $row['ordrestatus'];
				if ($status == 0 || $status == 1){
				echo "<td>" . $row['fornavn'] . "</td>";
                echo "<td>" . $row['etternavn'] . "</td>";
                echo "<td>" . $row['telefon'] . "</td>";
				echo "<td>" . $row['epost'] . "</td>";
				echo "<td>" . $row['ordreid'] . "</td>";
				echo "<td>" . $row['datetime'] . "</td>";
				//echo "<td>".showButton($row['ordreid'])."</td>";
				if($row['seordre'] == 1){
					showButton($row['ordreid'], $siteid);
					echo '<td><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ordre'.$row['ordreid'].'">
							Vìs ordredetaljer ('.$row['ordreid'].')
							</button></td>';
				}else{
					echo "<td><button class='btn btn-warning' type='submit' value='".$row['id']."' name='seordre'>Se ordre</button></td>";
				}
				//echo "<td>" . $row['curltime'] . "</td>"; Viser dato hentet i pck
				echo "<td>" . $row['hentes'] . "</td>";
				
				
	 
				?>
				<input type="hidden" name="mail" id="mail" value="<?php echo $row['epost']; ?>" />
				<input type="hidden" name="ordreid" value="<?php echo $row['ordreid']; ?>" />
				<?php
					if($status == 0){
						?><td><button type="submit" value="<?php echo $row['ordreid']; ?>" class="btn btn-success"name="klar">Klar for henting</button></td><?php
					}
					if($status == 1){
						?><td><button type="submit" value="<?php echo $row['ordreid']; ?>" class="btn btn-danger"name="hentet">Kunde har hentet!</button></td><?php
					}
				?><td><button type="submit" value="<?php echo $row['ordreid']; ?>" class="btn btn-danger"name="slett">Slett</button></td><?php
		}
		}
            echo "</tr>";
        
	

        echo "</table>";
		?>
		</form>
		<?php
			
		?>
		
		
 </div>
 <iframe width="560" height="315" src="https://www.youtube.com/embed/N6ehocrk-X4" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
  </div>
	
</body>
</html>