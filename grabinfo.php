<?php 
	
//DB CONNECTION
if($_POST){
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $emotion = filter_input(INPUT_POST, 'emotion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $pet = filter_input(INPUT_POST, 'pet', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $accessories = filter_input(INPUT_POST, 'accessories', FILTER_VALIDATE_INT);
        $theme = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($accessories === false || $accessories === null) {
                echo "Invalid accessories value. Defaulting to 1.<br />";
                $accessories = 1;
        }
}
	
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
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $rand = ($driver === 'sqlite') ? 'RANDOM()' : 'RAND()';
	
        $query = "SELECT name FROM drawoptions WHERE type = :type";
        $params = [':type' => 'Base Class'];
        if (!empty($theme)) {
                $query .= " AND theme = :theme";
                $params[':theme'] = $theme;
        }
        $query .= " ORDER BY $rand LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $baseclass_output = $stmt->fetchColumn();

        $query = "SELECT name FROM drawoptions WHERE type = :type";
        $params = [':type' => 'Major Feature'];
        if (!empty($theme)) {
                $query .= " AND theme = :theme";
                $params[':theme'] = $theme;
        }
        $query .= " ORDER BY $rand LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $majorfeature_output = $stmt->fetchColumn();
	
	if (!$accessories){
		$accessories = 1;
	}
	
        $query = "SELECT name FROM drawoptions WHERE type = 'Accessories'";
        $params = [];
        if (!empty($theme)) {
                $query .= " AND theme = :theme";
                $params[':theme'] = $theme;
        }
        $query .= " ORDER BY $rand LIMIT :lim";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':lim', (int)$accessories, PDO::PARAM_INT);
        foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $accessory_output = $stmt->fetchAll(PDO::FETCH_COLUMN);
	
        if (isset($emotion)){
                $query = "SELECT name FROM drawoptions WHERE type = :type";
                $params = [':type' => 'Emotion'];
                if (!empty($theme)) {
                        $query .= " AND theme = :theme";
                        $params[':theme'] = $theme;
                }
                $query .= " ORDER BY $rand LIMIT 1";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $emotion_output = $stmt->fetchColumn();
        }

        if (isset($pet)){
                $query = "SELECT name FROM drawoptions WHERE type = :type";
                $params = [':type' => 'Pet'];
                if (!empty($theme)) {
                        $query .= " AND theme = :theme";
                        $params[':theme'] = $theme;
                }
                $query .= " ORDER BY $rand LIMIT 1";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $pet_output = $stmt->fetchColumn();
        }

$vowels = array('A', 'E', 'I', 'O', 'U');  	
	
function weighted_random_simple($values, $weights){ 
    $count = count($values); 
    $i = 0; 
    $n = 0; 
    $num = mt_rand(0, array_sum($weights)); 
    while($i < $count){
        $n += $weights[$i]; 
        if($n >= $num){
            break; 
        }
        $i++; 
    } 
    return $values[$i]; 
}
?> 
You should draw 
<?php 

if (isset($emotion)){
		if (in_array($emotion_output[0], $vowels)){ 
			echo 'an '; 
		} else { 
			echo 'a '; 
		} 
        echo htmlspecialchars($emotion_output) ." ";
}

if (!isset($emotion)){
	if (in_array($majorfeature_output[0], $vowels)){ 
		echo 'an '; 
	} else { 
		echo 'a '; 
	}
}
echo htmlspecialchars($majorfeature_output) ." ";

if (isset($gender)){
	$values = array("Male", "Female", "Androgynous");
	$weights = array(49, 47, 2);
	$gender_output = weighted_random_simple($values, $weights);

echo htmlspecialchars($gender_output) ." ";
}

echo htmlspecialchars($baseclass_output);

?> with <?php 
if (substr($accessory_output[0], -1) != "s"){ 
	$accessory1 = substr($accessory_output[0], 0, 1);
	if (in_array($accessory1, $vowels)){ 
		echo 'an '; 
	} else { 
		echo 'a '; 
	}
} 
echo htmlspecialchars($accessory_output[0]);

if ($accessories > 1){ 
 
	if ($accessories == 2){
		echo " and ";
	} else {
		echo ", ";
	}
	
	if (substr($accessory_output[1], -1) != "s") {
		$accessory2 = substr($accessory_output[1], 0, 1);
		if (in_array($accessory2, $vowels)){ 
			echo 'an '; 
		} else { 
			echo 'a '; 
		}} 
        echo htmlspecialchars($accessory_output[1]);
}; 

if ($accessories > 2){
	echo ", and ";
		if (substr($accessory_output[2], -1) != "s") {
		$accessory3 = substr($accessory_output[2], 0, 1);
		if (in_array($accessory3, $vowels)){ 
			echo 'an '; 
		} else { 
			echo 'a '; 
		}} 
        echo htmlspecialchars($accessory_output[2]);
}

if (isset($pet)){
	echo " that owns ";
		if (substr($pet_output, -1) != "s") {
			if (in_array($pet_output[0], $vowels)){ 
				echo 'an '; 
			} else { 
				echo 'a '; 
			}
		}
        echo htmlspecialchars($pet_output);
}
?>
