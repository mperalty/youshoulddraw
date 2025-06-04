<?php
header('Content-Type: application/json');

$gender = filter_input(INPUT_GET, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$emotion = filter_input(INPUT_GET, 'emotion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$pet = filter_input(INPUT_GET, 'pet', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$accessories = filter_input(INPUT_GET, 'accessories', FILTER_VALIDATE_INT);
$theme = filter_input(INPUT_GET, 'theme', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ($accessories === false || $accessories === null) {
    $accessories = 1;
}

$config = __DIR__ . '/../includes/dbcon.php';
if (file_exists($config)) {
    require $config;
}

if (function_exists('getPDO')) {
    $pdo = getPDO();
} else {
    $type = getenv('YSD_DB_TYPE') ?: 'mysql';
    if ($type === 'sqlite') {
        $path = getenv('YSD_DB_PATH') ?: __DIR__ . '/../ysd.sqlite';
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

function randomName($pdo, $type, $theme, $rand) {
    $query = "SELECT name FROM drawoptions WHERE type = :type";
    $params = [':type' => $type];
    if (!empty($theme)) {
        $query .= " AND theme = :theme";
        $params[':theme'] = $theme;
    }
    $query .= " ORDER BY $rand LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

$baseclass_output = randomName($pdo, 'Base Class', $theme, $rand);
$majorfeature_output = randomName($pdo, 'Major Feature', $theme, $rand);

if (!$accessories) {
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

if (isset($emotion)) {
    $emotion_output = randomName($pdo, 'Emotion', $theme, $rand);
}

if (isset($pet)) {
    $pet_output = randomName($pdo, 'Pet', $theme, $rand);
}

$vowels = ['A','E','I','O','U'];
function articleFor($word, $vowels){
    return (in_array(strtoupper($word[0]), $vowels)) ? 'an ' : 'a ';
}

$prompt = 'You should draw ';
if (isset($emotion)) {
    $prompt .= articleFor($emotion_output, $vowels) . $emotion_output . ' ';
}
if (!isset($emotion)) {
    $prompt .= articleFor($majorfeature_output, $vowels);
}
$prompt .= $majorfeature_output . ' ';
if (isset($gender)) {
    $values = ['Male', 'Female', 'Androgynous'];
    $weights = [49, 47, 2];
    $num = mt_rand(0, array_sum($weights));
    $n = 0;
    foreach ($weights as $i => $w) {
        $n += $w;
        if ($n >= $num) {
            $gender_output = $values[$i];
            break;
        }
    }
    $prompt .= $gender_output . ' ';
}
$prompt .= $baseclass_output . ' with ';
for ($i=0; $i<count($accessory_output); $i++) {
    if ($i>0) {
        if ($i == count($accessory_output)-1) {
            $prompt .= (count($accessory_output)>2 ? ', and ' : ' and ');
        } else {
            $prompt .= ', ';
        }
    }
    if (substr($accessory_output[$i], -1) != 's') {
        $prompt .= articleFor($accessory_output[$i], $vowels);
    }
    $prompt .= $accessory_output[$i];
}
if (isset($pet)) {
    $prompt .= ' that owns ';
    if (substr($pet_output, -1) != 's') {
        $prompt .= articleFor($pet_output, $vowels);
    }
    $prompt .= $pet_output;
}

echo json_encode(['prompt' => $prompt]);
