<?php
//CRUD TOOL for Manipulating the DB

//PASSWORD CHECK

//DB CONNECTION
 require "/includes/dbcon.php";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

//DISPLAY VALUES

$sql = "SELECT name,type FROM drawoptions;";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
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
	
	// Select password hash for admin from database
	$sqlpasscheck = "SELECT password FROM adminuser;";
	$passfromdb = $conn->query($sqlpasscheck);
	
	if (password_verify($password, $passfromdb)){
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
<form method="post" action="<?php $_PHP_SELF ?>">
<input name="elementname" type="text" id="ename">
<input name="elementtype" type="text" id="etyle">
<input name="elementpassword" type="password" id="pw">
<input type="submit">
</form>
