<?php
//CRUD TOOL for Manipulating the DB

// Start the session
session_start();

//PASSWORD CHECK

//DB CONNECTION
require "includes/dbcon.php";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

//DISPLAY VALUES

$currdbsql = "SELECT name,type FROM drawoptions;";
$currdbresult = $conn->query($currdbsql);

if ($currdbresult->num_rows > 0) {
     // output data of each row
     while($row = $currdbresult->fetch_assoc()) {
         echo $row["name"]. " - " . $row["type"] . "<br />";
     }
} else {
     echo "Zero results";
}

//FORM TO ADD VALUES

if (!empty($_POST)) {
	$name = $_POST["elementname"];
	$type = $_POST["elementtype"];
	$password = $_POST["elementpassword"];
	$passfromdb = '';
	
	// Select password hash for admin from database
	$sqlpasscheck = "SELECT password FROM adminuser;";
	$passresults = $conn->query($sqlpasscheck);
	
	if ($passresults->num_rows > 0) {
		while($row = $passresults->fetch_assoc()) {
			$passfromdb = $row["password"];
		}
	}
	
	if ( password_verify($password, $passfromdb) || isset($_SESSION["loginaccepted"]) ){

		$_SESSION["loginaccepted"] = "true";
		
		$sqladdnew = "INSERT INTO drawoptions (id, name, type) VALUES ('', '$name', '$type');";

		if ($conn->query($sqladdnew) === TRUE) {
    		echo "New record created successfully";
		} else {
    		echo "Error: " . $sqladdnew . "<br>" . $conn->error;
		}
	} else {
		echo "Wrong password.";
	}
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<body>

<form method="post" action="<?php $_PHP_SELF ?>">
Element Name: <input name="elementname" type="text" id="ename"><br />
Element Type: <select name="elementtype" type="text" id="etype">
				<option value="Base Class">Base Class</option>
				<option value="Major Feature">Major Feature</option>
				<option value="Accessories">Accessory</option>
				<option value="Emotion">Emotion</option>
				<option value="Pet">Pet</option>
			</select>
<br />
<?php if ( !isset($_SESSION["loginaccepted"]) ) {?>
Password: <input name="elementpassword" type="password" id="pw"><br />
<?php } ?>
<input type="submit">
</form>

</body>
</html>
