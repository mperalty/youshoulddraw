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
</head>
<body>
<h3 class="main"><?php echo htmlspecialchars($prompt); ?></h3>
<div class="share"><a href="index.php">Generate your own idea</a></div>
</body>
</html>

