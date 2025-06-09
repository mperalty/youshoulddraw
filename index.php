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
$themes = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT theme FROM drawoptions WHERE theme IS NOT NULL AND theme != '' ORDER BY theme");
    $themes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $themes = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>You Should Draw - Random Character Art Ideas Generator</title>
  <meta name="description" content="You Should Draw - Random Character Art Ideas Generator">
  <meta name="author" content="Malcolm Peralty">

  <link rel="stylesheet" href="/style.css">
  <link rel='stylesheet' id='open-sans-css'  href='//fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&#038;subset=latin%2Clatin-ext&#038;ver=4.5.2' type='text/css' media='all' />

  <!--[if lt IE 9]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

<script type="text/javascript">
let historyItems = [];
function toggleDiv(divId) {
  const el = document.getElementById(divId);
  if (window.getComputedStyle(el).display === 'none') {
    el.style.display = 'block';
  } else {
    el.style.display = 'none';
  }
}

function addToHistory(text) {
  historyItems.unshift(text);
  historyItems = historyItems.slice(0, 5);

  const summary = document.getElementById('history_summary');
  const list = document.getElementById('idea_history');
  list.innerHTML = '';

  if (historyItems.length > 0) {
    summary.innerHTML = historyItems[0] + ' <span class="history-arrow">&#x25BC;</span>';
    historyItems.slice(1).forEach(item => {
      const li = document.createElement('li');
      li.innerHTML = item;
      list.appendChild(li);
    });
    document.getElementById('history_details').style.display = 'block';
  } else {
    document.getElementById('history_details').style.display = 'none';
  }
}

function fetchIdea(formData) {
  fetch('grabinfo.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(data => {
    const main = document.querySelector('.main');
    if (main.innerHTML.trim() !== '') {
      addToHistory(main.innerHTML);
    }
    main.innerHTML = data;
  });
}

function setTheme(isDark) {
  document.body.classList.toggle('dark', isDark);
  document.getElementById('themeToggle').textContent = isDark ? '☀️' : '🌙';
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

function setLargeText(isLarge) {
  document.body.classList.toggle('large-text', isLarge);
  document.getElementById('fontToggle').textContent = isLarge ? 'A-' : 'A+';
  localStorage.setItem('largeText', isLarge ? 'yes' : 'no');
}

function applySavedFontSize() {
  const saved = localStorage.getItem('largeText');
  setLargeText(saved === 'yes');
}

function applySavedTheme() {
  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  setTheme(saved === 'dark' || (!saved && prefersDark));
}

document.addEventListener('DOMContentLoaded', function() {
  const fd = new FormData();
  fd.append('firstload', '1');
  fetchIdea(fd);

  document.getElementById('optionsform').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetchIdea(formData);
  });

  applySavedTheme();
  applySavedFontSize();
  document.getElementById('themeToggle').addEventListener('click', function() {
    setTheme(!document.body.classList.contains('dark'));
  });
  document.getElementById('fontToggle').addEventListener('click', function() {
    setLargeText(!document.body.classList.contains('large-text'));
  });
});
</script>

</head>
<body>
<a href="#maincontent" class="skip-link">Skip to content</a>
<button id="themeToggle" aria-label="Toggle dark mode">🌙</button>
<button id="fontToggle" aria-label="Toggle large text">A+</button>
<h3 id="maincontent" class="main" aria-live="polite"></h3>
<details id="history_details" style="display:none;">
  <summary id="history_summary"></summary>
  <ul id="idea_history"></ul>
</details>

<div id="draw_options">
<form method="post" id="optionsform" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
<div class="left_side"><input type="checkbox" name="gender" id="gender" value="Gender">Random Gender?<br />
<input type="checkbox" name="emotion" id="emotion" value="Emotion">Random Emotion?<br />
<input type="checkbox" name="pet" id="pet" value="Pet">Random Pet?</br>
<label for="themeSelect">Theme:</label>
<select name="theme" id="themeSelect">
<option value="">Any Theme</option>
<?php foreach ($themes as $t): ?>
<option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
<?php endforeach; ?>
</select><br />
</div>
<div class="right_side">
<input type="radio" name="accessories" class="accessories" value="1" checked>One Accessory<br />
<input type="radio" name="accessories" class="accessories" value="2">Two Accessories<br />
<input type="radio" name="accessories" class="accessories" value="3">Three Accessories<br />
</div>
</div>
<div class="subdraw_notice">
	<a href="javascript:toggleDiv('draw_options');" class="options button">Options</a>
	<input type="submit" class="submit button" value="Next Idea" />
	</form>
</div>
<div class="share">Please tag your images with #ysdidea so that I can find them!</div>
<div class="details">Developed by <a href="https://www.peralty.com">Malcolm Peralty</a></div>
</body>
</html>
