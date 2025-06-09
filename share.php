<?php
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
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id && isset($_SERVER['REQUEST_URI'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#/share/([0-9]+)#', $path, $m)) {
        $id = (int)$m[1];
    }
}

// Fetch all necessary fields from generated_prompts
$stmt = $pdo->prepare('SELECT prompt, base_class_id, major_feature_id, accessory1_id, accessory2_id, accessory3_id, emotion_id, pet_id FROM generated_prompts WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo 'Prompt not found';
    exit;
}

$original_prompt_text = $row['prompt']; // Keep the original for reference or gender extraction

// Helper function to get component name
function getComponentName($pdo, $component_id) {
    if (!$component_id) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT name FROM drawoptions WHERE id = ?");
    $stmt->execute([$component_id]);
    return $stmt->fetchColumn();
}

$vowels = ['A', 'E', 'I', 'O', 'U'];

// Fetch names
$emotion_name = getComponentName($pdo, $row['emotion_id']);
$major_feature_name = getComponentName($pdo, $row['major_feature_id']);
$base_class_name = getComponentName($pdo, $row['base_class_id']);
$accessory1_name = getComponentName($pdo, $row['accessory1_id']);
$accessory2_name = getComponentName($pdo, $row['accessory2_id']);
$accessory3_name = getComponentName($pdo, $row['accessory3_id']);
$pet_name = getComponentName($pdo, $row['pet_id']);

// Reconstruct the styled prompt
$styled_prompt_parts = [];
$styled_prompt_parts[] = 'You should draw ';

$emotion_added = false;
if ($emotion_name) {
    $emotion_str = (in_array(strtoupper($emotion_name[0]), $vowels) ? 'an ' : 'a ') . '<span class="prompt-emotion">' . htmlspecialchars($emotion_name) . '</span>';
    $styled_prompt_parts[] = $emotion_str;
    $emotion_added = true;
}

if ($major_feature_name) {
    $prefix = '';
    if (!$emotion_added) {
        $prefix = (in_array(strtoupper($major_feature_name[0]), $vowels) ? 'an ' : 'a ');
    }
    $major_feature_str = $prefix . '<span class="prompt-major-feature">' . htmlspecialchars($major_feature_name) . '</span>';
    $styled_prompt_parts[] = $major_feature_str;
}

// Gender extraction and styling
if ($base_class_name) { // Need base_class_name as a reference point
    $genders = ["Male", "Female", "Androgynous"];
    foreach ($genders as $gender_type) {
        // Search for " Gender BaseClassName" in the original prompt
        // The space after gender_type is important to match "Male ", "Female ", etc.
        $pattern = '/\b' . preg_quote($gender_type, '/') . '\s+' . preg_quote($base_class_name, '/') . '/';
        if (preg_match($pattern, $original_prompt_text)) {
            $gender_str = '<span class="prompt-gender">' . htmlspecialchars($gender_type) . '</span>';
            $styled_prompt_parts[] = $gender_str;
            break;
        }
    }
}

if ($base_class_name) {
    $base_class_str = '<span class="prompt-base-class">' . htmlspecialchars($base_class_name) . '</span>';
    $styled_prompt_parts[] = $base_class_str;
}

$accessories = array_filter([$accessory1_name, $accessory2_name, $accessory3_name]);
if (!empty($accessories)) {
    $styled_prompt_parts[] = 'with';
    $accessory_html_parts = [];
    foreach ($accessories as $acc_name) {
        $current_accessory_html = '';
        if (substr(htmlspecialchars($acc_name), -1) != 's') { // Check original name for 's'
             $current_accessory_html .= (in_array(strtoupper($acc_name[0]), $vowels) ? 'an ' : 'a ');
        }
        $current_accessory_html .= '<span class="prompt-accessory">' . htmlspecialchars($acc_name) . '</span>';
        $accessory_html_parts[] = $current_accessory_html;
    }
    // Join accessories with commas and 'and'
    if (count($accessory_html_parts) == 1) {
        $styled_prompt_parts[] = $accessory_html_parts[0];
    } elseif (count($accessory_html_parts) == 2) {
        $styled_prompt_parts[] = $accessory_html_parts[0] . ' and ' . $accessory_html_parts[1];
    } else {
        $last_accessory = array_pop($accessory_html_parts);
        $styled_prompt_parts[] = implode(', ', $accessory_html_parts) . ', and ' . $last_accessory;
    }
}

if ($pet_name) {
    $pet_str = 'that owns ';
    if (substr(htmlspecialchars($pet_name), -1) != 's') { // Check original name for 's'
        $pet_str .= (in_array(strtoupper($pet_name[0]), $vowels) ? 'an ' : 'a ');
    }
    $pet_str .= '<span class="prompt-pet">' . htmlspecialchars($pet_name) . '</span>';
    $styled_prompt_parts[] = $pet_str;
}

// Join all parts with a space, then clean up any potential double spaces.
$final_styled_prompt = implode(' ', $styled_prompt_parts);
$final_styled_prompt = preg_replace('/\s+/', ' ', $final_styled_prompt);
$final_styled_prompt = trim($final_styled_prompt); // Trim leading/trailing spaces

// If reconstruction fails or results in an empty prompt (highly unlikely if DB is consistent), fallback to original.
if (empty($final_styled_prompt) || strlen($final_styled_prompt) < strlen("You should draw ")) {
    // Basic fallback if reconstruction seems to have failed significantly
    $final_styled_prompt = htmlspecialchars($original_prompt_text);
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>You Should Draw - Shared Prompt</title>
  <meta name="description" content="You Should Draw - Random Character Art Ideas Generator">
  <meta name="author" content="Malcolm Peralty">
  <link rel="stylesheet" href="/style.css">
  <script type="text/javascript">
    function setTheme(isDark){
      document.body.classList.toggle('dark', isDark);
      document.getElementById('themeToggle').textContent = isDark ? '☀️' : '🌙';
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
    }
    function applySavedTheme(){
      const saved = localStorage.getItem('theme');
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      setTheme(saved === 'dark' || (!saved && prefersDark));
    }
    function setLargeText(isLarge){
      document.body.classList.toggle('large-text', isLarge);
      document.getElementById('fontToggle').textContent = isLarge ? 'A-' : 'A+';
      localStorage.setItem('largeText', isLarge ? 'yes' : 'no');
    }
    function applySavedFont(){
      const saved = localStorage.getItem('largeText');
      setLargeText(saved === 'yes');
    }
    document.addEventListener('DOMContentLoaded', function(){
      applySavedTheme();
      applySavedFont();
      document.getElementById('themeToggle').addEventListener('click', function(){
        setTheme(!document.body.classList.contains('dark'));
      });
      document.getElementById('fontToggle').addEventListener('click', function(){
        setLargeText(!document.body.classList.contains('large-text'));
      });
    });
  </script>
</head>
<body>
<a href="#maincontent" class="skip-link">Skip to content</a>
<button id="themeToggle" aria-label="Toggle dark mode">🌙</button>
<button id="fontToggle" aria-label="Toggle large text">A+</button>
<h3 id="maincontent" class="main" aria-live="polite"><?php echo $final_styled_prompt; ?></h3>
<div class="subdraw_notice">
    <a href="/index.php" class="submit button">Generate your own idea</a>
</div>
<div class="share">Please tag your images with #ysdidea so that I can find them!</div>
<div class="details">Developed by <a href="https://www.peralty.com">Malcolm Peralty</a></div>
</body>
</html>

