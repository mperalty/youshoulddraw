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
	
        $query = "SELECT id, name FROM drawoptions WHERE type = :type";
        $params = [':type' => 'Base Class'];
        if (!empty($theme)) {
                $query .= " AND theme = :theme";
                $params[':theme'] = $theme;
        }
        $query .= " ORDER BY $rand LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $baseclass = $stmt->fetch(PDO::FETCH_ASSOC);

        $query = "SELECT id, name FROM drawoptions WHERE type = :type";
        $params = [':type' => 'Major Feature'];
        if (!empty($theme)) {
                $query .= " AND theme = :theme";
                $params[':theme'] = $theme;
        }
        $query .= " ORDER BY $rand LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $majorfeature = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if (!$accessories){
		$accessories = 1;
	}
	
        $query = "SELECT id, name FROM drawoptions WHERE type = 'Accessories'";
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
        $accessory_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
        if (isset($emotion)){
                $query = "SELECT id, name FROM drawoptions WHERE type = :type";
                $params = [':type' => 'Emotion'];
                if (!empty($theme)) {
                        $query .= " AND theme = :theme";
                        $params[':theme'] = $theme;
                }
                $query .= " ORDER BY $rand LIMIT 1";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $emotion_row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (isset($pet)){
                $query = "SELECT id, name FROM drawoptions WHERE type = :type";
                $params = [':type' => 'Pet'];
                if (!empty($theme)) {
                        $query .= " AND theme = :theme";
                        $params[':theme'] = $theme;
                }
                $query .= " ORDER BY $rand LIMIT 1";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $pet_row = $stmt->fetch(PDO::FETCH_ASSOC);
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
<?php
$prompt = 'You should draw ';
if (isset($emotion_row)) {
    $prompt .= (in_array(strtoupper($emotion_row['name'][0]), $vowels) ? 'an ' : 'a ') . $emotion_row['name'] . ' ';
}
if (!isset($emotion_row)) {
    $prompt .= (in_array(strtoupper($majorfeature['name'][0]), $vowels) ? 'an ' : 'a ');
}
$prompt .= $majorfeature['name'] . ' ';
if (isset($gender)) {
    $values = array('Male', 'Female', 'Androgynous');
    $weights = array(49, 47, 2);
    $gender_output = weighted_random_simple($values, $weights);
    $prompt .= $gender_output . ' ';
}
$prompt .= $baseclass['name'] . ' with ';
for ($i=0; $i<count($accessory_rows); $i++) {
    if ($i > 0) {
        if ($i == count($accessory_rows)-1) {
            $prompt .= (count($accessory_rows)>2 ? ', and ' : ' and ');
        } else {
            $prompt .= ', ';
        }
    }
    if (substr($accessory_rows[$i]['name'], -1) != 's') {
        $prompt .= (in_array(strtoupper($accessory_rows[$i]['name'][0]), $vowels) ? 'an ' : 'a ');
    }
    $prompt .= $accessory_rows[$i]['name'];
}
if (isset($pet_row)) {
    $prompt .= ' that owns ';
    if (substr($pet_row['name'], -1) != 's') {
        $prompt .= (in_array(strtoupper($pet_row['name'][0]), $vowels) ? 'an ' : 'a ');
    }
    $prompt .= $pet_row['name'];
}

// store IDs
$acc1 = $accessory_rows[0]['id'] ?? null;
$acc2 = $accessory_rows[1]['id'] ?? null;
$acc3 = $accessory_rows[2]['id'] ?? null;
$emotion_id = $emotion_row['id'] ?? null;
$pet_id = $pet_row['id'] ?? null;
$stmt = $pdo->prepare("INSERT INTO generated_prompts (base_class_id, major_feature_id, accessory1_id, accessory2_id, accessory3_id, emotion_id, pet_id, prompt) VALUES (?,?,?,?,?,?,?,?)");
$stmt->execute([$baseclass['id'], $majorfeature['id'], $acc1, $acc2, $acc3, $emotion_id, $pet_id, $prompt]);
$share_id = $pdo->lastInsertId();

echo htmlspecialchars($prompt) . '<br /><a href="share/' . $share_id . '">Share this prompt</a>';

