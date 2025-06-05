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

function randomRow($pdo, $type, $theme, $rand) {
    $query = "SELECT id, name FROM drawoptions WHERE type = :type";
    $params = [':type' => $type];
    if (!empty($theme)) {
        $query .= " AND theme = :theme";
        $params[':theme'] = $theme;
    }
    $query .= " ORDER BY $rand LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$baseclass = randomRow($pdo, 'Base Class', $theme, $rand);
$majorfeature = randomRow($pdo, 'Major Feature', $theme, $rand);

if (!$baseclass || !$majorfeature) {
    echo json_encode(['error' => 'No drawing options have been added yet.']);
    return;
}

if (!$accessories) {
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
$accessories_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($accessories_rows) === 0) {
    echo json_encode(['error' => 'No drawing options have been added yet.']);
    return;
}

if (isset($emotion)) {
    $emotion_row = randomRow($pdo, 'Emotion', $theme, $rand) ?: null;
}

if (isset($pet)) {
    $pet_row = randomRow($pdo, 'Pet', $theme, $rand) ?: null;
}

$vowels = ['A','E','I','O','U'];
function articleFor($word, $vowels){
    return (in_array(strtoupper($word[0]), $vowels)) ? 'an ' : 'a ';
}

$prompt = 'You should draw ';
if (isset($emotion) && $emotion_row) {
    $prompt .= articleFor($emotion_row['name'], $vowels) . $emotion_row['name'] . ' ';
}
if (!isset($emotion) || !$emotion_row) {
    $prompt .= articleFor($majorfeature['name'], $vowels);
}
$prompt .= $majorfeature['name'] . ' ';
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
$prompt .= $baseclass['name'] . ' with ';
for ($i=0; $i<count($accessories_rows); $i++) {
    if ($i>0) {
        if ($i == count($accessories_rows)-1) {
            $prompt .= (count($accessories_rows)>2 ? ', and ' : ' and ');
        } else {
            $prompt .= ', ';
        }
    }
    if (substr($accessories_rows[$i]['name'], -1) != 's') {
        $prompt .= articleFor($accessories_rows[$i]['name'], $vowels);
    }
    $prompt .= $accessories_rows[$i]['name'];
}
if (isset($pet) && $pet_row) {
    $prompt .= ' that owns ';
    if (substr($pet_row['name'], -1) != 's') {
        $prompt .= articleFor($pet_row['name'], $vowels);
    }
    $prompt .= $pet_row['name'];
}

// store IDs of selected options
$acc1 = $accessories_rows[0]['id'] ?? null;
$acc2 = $accessories_rows[1]['id'] ?? null;
$acc3 = $accessories_rows[2]['id'] ?? null;
$emotion_id = isset($emotion_row) ? $emotion_row['id'] : null;
$pet_id = isset($pet_row) ? $pet_row['id'] : null;
$stmt = $pdo->prepare("INSERT INTO generated_prompts (base_class_id, major_feature_id, accessory1_id, accessory2_id, accessory3_id, emotion_id, pet_id, prompt) VALUES (?,?,?,?,?,?,?,?)");
$stmt->execute([$baseclass['id'], $majorfeature['id'], $acc1, $acc2, $acc3, $emotion_id, $pet_id, $prompt]);
$id = $pdo->lastInsertId();

echo json_encode(['prompt' => $prompt, 'id' => $id]);

