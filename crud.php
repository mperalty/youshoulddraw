<?php
session_start();

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

$message = '';

function verifyPassword(PDO $pdo, &$message)
{
    if (isset($_SESSION['loginaccepted'])) {
        return true;
    }
    $password = filter_input(INPUT_POST, 'elementpassword', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if ($password === null) {
        $password = '';
    }
    $hash = $pdo->query('SELECT password FROM adminuser')->fetchColumn();
    if ($hash && password_verify($password, $hash)) {
        $_SESSION['loginaccepted'] = true;
        return true;
    }
    $message = 'Wrong password.';
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = filter_input(INPUT_POST, 'elementname', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $type  = filter_input(INPUT_POST, 'elementtype', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $theme = filter_input(INPUT_POST, 'elementtheme', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (isset($_POST['delete_id'])) {
        if (verifyPassword($pdo, $message)) {
            $stmt = $pdo->prepare('DELETE FROM drawoptions WHERE id = ?');
            $stmt->execute([(int)$_POST['delete_id']]);
            $message = 'Record deleted.';
        }
    } elseif (isset($_POST['edit_id'])) {
        if (verifyPassword($pdo, $message)) {
            $stmt = $pdo->prepare('UPDATE drawoptions SET name=:name, type=:type, theme=:theme WHERE id=:id');
            $stmt->execute([
                ':name'  => $name,
                ':type'  => $type,
                ':theme' => $theme,
                ':id'    => (int)$_POST['edit_id']
            ]);
            $message = 'Record updated.';
        }
    } else {
        if (verifyPassword($pdo, $message)) {
            $stmt = $pdo->prepare('INSERT INTO drawoptions (name, type, theme) VALUES (:name, :type, :theme)');
            $stmt->execute([
                ':name'  => $name,
                ':type'  => $type,
                ':theme' => $theme
            ]);
            $message = 'New record created.';
        }
    }
}

$editRow = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM drawoptions WHERE id = ?');
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editRow) {
        $editId = null;
    }
}

$allowedSort = ['name', 'type', 'theme'];
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$orderBy = in_array($sort, $allowedSort, true) ? $sort : 'id';
$stmt = $pdo->query('SELECT id, name, type, theme FROM drawoptions ORDER BY ' . $orderBy);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<style>
.crud-table{width:80%;margin:20px auto;border-collapse:collapse;background:#fff;}
.crud-table th,.crud-table td{border:1px solid #ccc;padding:8px;text-align:left;}
.crud-table th{background:#eee;}
.crud-form{width:80%;margin:20px auto;background:#fff;border:1px solid #ccc;padding:10px;}
.crud-actions form{display:inline;}
</style>
<script type="text/javascript">
function setLargeText(isLarge){
    document.body.classList.toggle('large-text', isLarge);
    document.getElementById('fontToggle').textContent = isLarge ? 'A-' : 'A+';
    localStorage.setItem('largeText', isLarge ? 'yes' : 'no');
}
function applySavedFont(){
    const saved = localStorage.getItem('largeText');
    setLargeText(saved === 'yes');
}
document.addEventListener('DOMContentLoaded',function(){
    applySavedFont();
    document.getElementById('fontToggle').addEventListener('click',function(){
        setLargeText(!document.body.classList.contains('large-text'));
    });
});
</script>
</head>
<body>
<a href="#maincontent" class="skip-link">Skip to content</a>
<button id="fontToggle" aria-label="Toggle large text">A+</button>

<?php if ($message): ?>
<p class="crud-form" style="color:red;"><?= htmlspecialchars($message); ?></p>
<?php endif; ?>

<form id="maincontent" class="crud-form" method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . ($sort ? '?sort=' . urlencode($sort) : ''); ?>">
    <?php if ($editRow): ?>
    <input type="hidden" name="edit_id" value="<?= $editRow['id']; ?>">
    <?php endif; ?>
    <label>Element Name: <input name="elementname" type="text" value="<?= htmlspecialchars($editRow['name'] ?? ''); ?>" required></label><br>
    <label>Element Type:
        <select name="elementtype">
            <?php
            $types = ['Base Class','Major Feature','Accessories','Emotion','Pet'];
            foreach ($types as $t) {
                $sel = ($editRow && $editRow['type'] === $t) ? 'selected' : '';
                echo "<option value=\"".htmlspecialchars($t)."\" $sel>".htmlspecialchars($t)."</option>";
            }
            ?>
        </select>
    </label><br>
    <label>Theme: <input name="elementtheme" type="text" value="<?= htmlspecialchars($editRow['theme'] ?? ''); ?>"></label><br>
    <?php if (!isset($_SESSION['loginaccepted'])): ?>
    <label>Password: <input name="elementpassword" type="password"></label><br>
    <?php endif; ?>
    <button type="submit"><?= $editRow ? 'Update' : 'Add'; ?></button>
    <?php if ($editRow): ?>
    <a href="crud.php<?= $sort ? '?sort=' . urlencode($sort) : ''; ?>">Cancel</a>
    <?php endif; ?>
</form>

<table class="crud-table">
<thead>
<tr>
<th><a href="?sort=name">Name</a></th>
<th><a href="?sort=type">Type</a></th>
<th><a href="?sort=theme">Theme</a></th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
<td><?= htmlspecialchars($row['name']); ?></td>
<td><?= htmlspecialchars($row['type']); ?></td>
<td><?= htmlspecialchars($row['theme']); ?></td>
<td class="crud-actions">
    <a href="?edit=<?= $row['id']; ?><?= $sort ? '&sort=' . urlencode($sort) : ''; ?>">Edit</a>
    <form method="post" action="<?= $sort ? '?sort=' . urlencode($sort) : ''; ?>" onsubmit="return confirm('Delete this item?');">
        <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
        <?php if (!isset($_SESSION['loginaccepted'])): ?>
        <input type="password" name="elementpassword" placeholder="Password" required>
        <?php endif; ?>
        <button type="submit">Delete</button>
    </form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
