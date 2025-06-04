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
$stmt = $pdo->prepare('SELECT prompt FROM generated_prompts WHERE id = ?');
$stmt->execute([$id]);
$prompt = $stmt->fetchColumn();
if (!$prompt) {
    http_response_code(404);
    echo 'Prompt not found';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>You Should Draw - Shared Prompt</title>
  <link rel="stylesheet" href="style.css">
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
<h3 id="maincontent" class="main" aria-live="polite"><?php echo htmlspecialchars($prompt); ?></h3>
<div class="share"><a href="index.php">Generate your own idea</a></div>
</body>
</html>

