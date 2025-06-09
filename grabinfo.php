<?php 
	
//DB CONNECTION
if($_POST){
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $emotion = filter_input(INPUT_POST, 'emotion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $pet = filter_input(INPUT_POST, 'pet', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $accessories = filter_input(INPUT_POST, 'accessories', FILTER_VALIDATE_INT);
        $theme = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($accessories === false || $accessories === null) {
                // Default to one accessory when no valid value is provided.
                // Silently correct the value instead of displaying an error to the user.
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

        // When the database is empty (or missing required types), we can't
        // build a prompt. Instead of throwing notices, display a friendly
        // message.
        if (!$baseclass || !$majorfeature) {
                echo 'No drawing options have been added yet.';
                return;
        }
	
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

        if (count($accessory_rows) === 0) {
                echo 'No drawing options have been added yet.';
                return;
        }
	
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
        $emotion_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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
        $pet_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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
// --- Start Plain Text Prompt Construction ---
$plain_text_prompt = 'You should draw ';
if (isset($emotion) && $emotion_row) {
    $plain_text_prompt .= (in_array(strtoupper($emotion_row['name'][0]), $vowels) ? 'an ' : 'a ') . $emotion_row['name'] . ' ';
}
if (!isset($emotion) || !$emotion_row) { // If no emotion, add 'a' or 'an' before major feature (for plain text)
    $plain_text_prompt .= (in_array(strtoupper($majorfeature['name'][0]), $vowels) ? 'an ' : 'a ');
}
$plain_text_prompt .= $majorfeature['name'] . ' ';
if (isset($gender)) {
    // Note: $gender_output is determined below for HTML prompt, we need it here for plain text too.
    // To avoid duplicating logic, we'll use the $gender_output that will be set for the HTML version.
    // This assumes $gender_output is set before this block if $gender is set.
    // For safety, let's ensure $gender_output is defined if $gender is set.
    $temp_gender_output = '';
    if (isset($gender)) {
        $values_gender = array('Male', 'Female', 'Androgynous'); // Renamed to avoid conflict
        $weights_gender = array(49, 47, 2); // Renamed to avoid conflict
        $temp_gender_output = weighted_random_simple($values_gender, $weights_gender);
        $plain_text_prompt .= $temp_gender_output . ' ';
    }
}
$plain_text_prompt .= $baseclass['name'] . ' with ';
for ($i=0; $i<count($accessory_rows); $i++) {
    if ($i > 0) {
        if ($i == count($accessory_rows)-1) {
            $plain_text_prompt .= (count($accessory_rows)>2 ? ', and ' : ' and ');
        } else {
            $plain_text_prompt .= ', ';
        }
    }
    if (substr($accessory_rows[$i]['name'], -1) != 's') {
        $plain_text_prompt .= (in_array(strtoupper($accessory_rows[$i]['name'][0]), $vowels) ? 'an ' : 'a ');
    }
    $plain_text_prompt .= $accessory_rows[$i]['name'];
}
if (isset($pet) && $pet_row) {
    $plain_text_prompt .= ' that owns ';
    if (substr($pet_row['name'], -1) != 's') {
        $plain_text_prompt .= (in_array(strtoupper($pet_row['name'][0]), $vowels) ? 'an ' : 'a ');
    }
    $plain_text_prompt .= $pet_row['name'];
}
// --- End Plain Text Prompt Construction ---

$prompt = 'You should draw '; // This is for the HTML version

$emotion_html = '';
if (isset($emotion) && $emotion_row) {
    $emotion_name = htmlspecialchars($emotion_row['name']);
    $emotion_html = (in_array(strtoupper($emotion_name[0]), $vowels) ? 'an ' : 'a ') . '<span class="prompt-emotion">' . $emotion_name . '</span> ';
}

$majorfeature_name = htmlspecialchars($majorfeature['name']);
$majorfeature_html = '<span class="prompt-major-feature">' . $majorfeature_name . '</span> ';

$gender_html = '';
$gender_output_for_html = ''; // To be used if $gender is set
if (isset($gender)) {
    $values = array('Male', 'Female', 'Androgynous');
    $weights = array(49, 47, 2);
    $gender_output_for_html = weighted_random_simple($values, $weights); // Use the already defined $temp_gender_output for consistency if needed
    // Actually, $temp_gender_output was specifically for plain text. Let's use $gender_output_for_html for HTML
    $gender_html = '<span class="prompt-gender">' . htmlspecialchars($gender_output_for_html) . '</span> ';
}

$baseclass_name = htmlspecialchars($baseclass['name']);
$baseclass_html = '<span class="prompt-base-class">' . $baseclass_name . '</span>';

$accessories_html_array = [];
for ($i=0; $i<count($accessory_rows); $i++) {
    $accessory_name = htmlspecialchars($accessory_rows[$i]['name']);
    $current_accessory_html = '';
    if (substr($accessory_name, -1) != 's') {
        $current_accessory_html .= (in_array(strtoupper($accessory_name[0]), $vowels) ? 'an ' : 'a ');
    }
    $current_accessory_html .= '<span class="prompt-accessory">' . $accessory_name . '</span>';
    $accessories_html_array[] = $current_accessory_html;
}

$accessories_output_html = '';
if (!empty($accessories_html_array)) {
    $accessories_output_html .= ' with ';
    for ($i=0; $i<count($accessories_html_array); $i++) {
        if ($i > 0) {
            if ($i == count($accessories_html_array)-1) {
                $accessories_output_html .= (count($accessories_html_array)>2 ? ', and ' : ' and ');
            } else {
                $accessories_output_html .= ', ';
            }
        }
        $accessories_output_html .= $accessories_html_array[$i];
    }
}

$pet_html = '';
if (isset($pet) && $pet_row) {
    $pet_name = htmlspecialchars($pet_row['name']);
    $pet_html = ' that owns ';
    if (substr($pet_name, -1) != 's') {
        $pet_html .= (in_array(strtoupper($pet_name[0]), $vowels) ? 'an ' : 'a ');
    }
    $pet_html .= '<span class="prompt-pet">' . $pet_name . '</span>';
}

// Construct the HTML prompt using the HTML components
$prompt .= $emotion_html;
if (empty($emotion_html)) { // If no emotion, add 'a' or 'an' before major feature (for HTML prompt)
    $prompt .= (in_array(strtoupper($majorfeature['name'][0]), $vowels) ? 'an ' : 'a ');
}
$prompt .= $majorfeature_html;
// $prompt .= $gender_html; // This was $gender_output before, now it is $gender_html
if (isset($gender)) { // Only add gender if it's set
    $prompt .= $gender_html; // Add the HTML gender string
}
$prompt .= $baseclass_html;
$prompt .= $accessories_output_html;
$prompt .= $pet_html;

// store IDs

$acc1 = $accessory_rows[0]['id'] ?? null;
$acc2 = $accessory_rows[1]['id'] ?? null;
$acc3 = $accessory_rows[2]['id'] ?? null;
$emotion_id = $emotion_row['id'] ?? null;
$pet_id = $pet_row['id'] ?? null;

// Reuse an existing prompt entry if the text matches
$stmt = $pdo->prepare('SELECT id FROM generated_prompts WHERE prompt = ? LIMIT 1');
$stmt->execute([$prompt]);
$existingId = $stmt->fetchColumn();
if ($existingId) {
    $share_id = $existingId;
} else {
    $stmt = $pdo->prepare("INSERT INTO generated_prompts (base_class_id, major_feature_id, accessory1_id, accessory2_id, accessory3_id, emotion_id, pet_id, prompt) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$baseclass['id'], $majorfeature['id'], $acc1, $acc2, $acc3, $emotion_id, $pet_id, $prompt]);
    $share_id = $pdo->lastInsertId();
}

echo $prompt . '<br /><a href="share/' . $share_id . '">Share this prompt</a>';

// Social Media Sharing
$encoded_prompt = urlencode($plain_text_prompt);
// Construct the base URL dynamically
$scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_share_url = $scheme . '://' . $host . '/share/' . $share_id;

$twitter_url = "https://twitter.com/intent/tweet?text=" . $encoded_prompt . "&url=" . urlencode($base_share_url) . "&hashtags=ysdidea";
$facebook_url = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($base_share_url) . "&quote=" . $encoded_prompt;
$pinterest_url = "https://pinterest.com/pin/create/button/?url=" . urlencode($base_share_url) . "&description=" . $encoded_prompt;

echo '<div class="social-share">';
echo '<a href="' . htmlspecialchars($twitter_url) . '" target="_blank" class="twitter">Tweet</a>';
echo '<a href="' . htmlspecialchars($facebook_url) . '" target="_blank" class="facebook">Share</a>';
echo '<a href="' . htmlspecialchars($pinterest_url) . '" target="_blank" class="pinterest">Pin</a>';
echo '</div>';

