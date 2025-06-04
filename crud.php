<?php
//CRUD TOOL for Manipulating the DB

// Start the session
session_start();

//PASSWORD CHECK

//DB CONNECTION
$config = __DIR__ . '/includes/dbcon.php';
if (file_exists($config)) {
    require $config;
}

if (function_exists('getPDO')) {
    $pdo = getPDO();
} else {
    $type = getenv('YSD_DB_TYPE') ?: 'mysql';
    if ($type === 'sqlite') {
        $path = getenv('YSD_DB_PATH') ?: __DIR__ . '/ysd.sqlite';
        $pdo = new PDO('sqlite:' . $path);
    } else {
        $host = getenv('YSD_DB_HOST') ?: 'localhost';
        $user = getenv('YSD_DB_USER');
        $pass = getenv('YSD_DB_PASS');
        $dbname = getenv('YSD_DB_NAME');
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
    }
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//DISPLAY VALUES

$currdbsql = "SELECT name,type FROM drawoptions";
$stmt = $pdo->query($currdbsql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($rows) {
     foreach ($rows as $row) {
         echo htmlspecialchars($row['name']) . " - " . htmlspecialchars($row['type']) . "<br />";
     }
} else {
     echo "Zero results";
}

//FORM TO ADD VALUES

if (!empty($_POST)) {
        $name = filter_input(INPUT_POST, "elementname", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($name === null) {
                $name = '';
                echo "Invalid element name provided.<br />";
        }

        $type = filter_input(INPUT_POST, "elementtype", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($type === null) {
                $type = '';
                echo "Invalid element type provided.<br />";
        }

        $password = filter_input(INPUT_POST, "elementpassword", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($password === null) {
                $password = '';
                echo "Password missing.<br />";
        }
	$passfromdb = '';
	
        // Select password hash for admin from database
        $sqlpasscheck = "SELECT password FROM adminuser";
        $passresults = $pdo->query($sqlpasscheck);
        $row = $passresults->fetch(PDO::FETCH_ASSOC);
        if ($row) {
                $passfromdb = $row['password'];
        }
	
	if ( password_verify($password, $passfromdb) || isset($_SESSION["loginaccepted"]) ){

		$_SESSION["loginaccepted"] = "true";
		
                $stmt = $pdo->prepare("INSERT INTO drawoptions (name, type) VALUES (:name, :type)");
                if ($stmt->execute([':name' => $name, ':type' => $type])) {
                        echo "New record created successfully";
                } else {
                        echo "Error: " . implode(' ', $stmt->errorInfo());
                }
	} else {
		echo "Wrong password.";
	}
}

?>
<!DOCTYPE html>
<html>
<body>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
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
