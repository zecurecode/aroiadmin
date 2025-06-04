<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
  <link rel="stylesheet" href="style.css">

  <meta name="viewport" content="width=device-width, initial-scale=1">
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/bootstrap-material-design.min.css">
<link rel="stylesheet" type="text/css" href="css/ripples.min.css">
</head>

<body>

<div class="p-5 bg-success">
				<?php

include 'dbcon.php';

/* Attempt MySQL server connection. Assuming you are running MySQL
server with default setting (user 'root' with no password) */

 
// Attempt select query execution
$sql = "SELECT * FROM orders";
if($result = mysqli_query($conn, $sql)){
    if(mysqli_num_rows($result) > 0){
		?>
		<div class="p-5 bg-success">
		<form method="post" action="sendepost.php">
  <table class= "table table-bordered table-sm">
	    <thead>
				<tr class="table-danger">
				
					<th scope='col'>Fornavn</th>
					<th scope='col'>Etternavn</th>
					<th scope='col'>Mobil</th>
					<th scope='col'>Epost</th>
					<th scope='col'>Ordre Id</th>
				
				
				
				</tr>
			  </thead>
  <tbody>
				<?php
        while($row = mysqli_fetch_array($result)){
            echo "<tr>";
                echo "<td>" . $row['fornavn'] . "</td>";
                echo "<td>" . $row['etternavn'] . "</td>";
                echo "<td>" . $row['telefon'] . "</td>";
				echo "<td>" . $row['epost'] . "</td>";
				 echo "<td>" . $row['ordreid'] . "</td>";
				?>
				  <input type="hidden" name="email" value="<?php echo $row['epost']; ?>" />
				<td><button type="submit" class="btn btn-success"name="klar">Klar for henting</button></td>
      <?php
            echo "</tr>";
        }
        echo "</table>";
		?>
		</form>
		<?php
        // Free result set
        mysqli_free_result($result);
    } else{
        echo "No records matching your query were found.";
    }
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
}
 
// Close connection
mysqli_close($conn);
?>
 </div>
  </div>
			 <div class="m-4 pb-5 bg-danger">
      
			  
				 <input type="button" class="btn btn-danger btn-lg" value="Hjem" onclick=" relocate_home()">

<script>
function relocate_home()
{
     location.href = "/index.html";
} 
</script>
				 </div>
				 </body>
				 </html>