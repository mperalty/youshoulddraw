<?php
$config = __DIR__ . '/includes/dbcon.php';
if (file_exists($config)) {
    require $config;
}

if (function_exists('getPDO')) {
    $pdo = getPDO();
} else {
    $dbType = getenv('YSD_DB_TYPE');
    $type = $dbType ?: ((getenv('YSD_DB_USER') && getenv('YSD_DB_NAME')) ? 'mysql' : 'sqlite');
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>You Should Draw - Random Character Art Ideas Generator</title>
  <meta name="description" content="You Should Draw - Random Character Art Ideas Generator">
  <meta name="author" content="Malcolm Peralty">
  <link rel="stylesheet" href="/style.css">
  <link rel='stylesheet' id='open-sans-css' href='//fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&#038;subset=latin%2Clatin-ext&#038;ver=4.5.2' type='text/css' media='all' />
<script type="text/javascript">
let historyItems = [];

function addToHistory(text) {
  historyItems.unshift(text);
  historyItems = historyItems.slice(0, 5);

  const summary = document.getElementById('history_summary');
  const list = document.getElementById('idea_history');
  const details = document.getElementById('history_details');
  list.innerHTML = '';

  if (historyItems.length > 0) {
    summary.innerHTML = historyItems[0] + ' <span class="history-arrow">▾</span>';
    historyItems.slice(1).forEach(item => {
      const li = document.createElement('li');
      li.innerHTML = item;
      list.appendChild(li);
    });
    details.hidden = false;
  } else {
    details.hidden = true;
  }
}

function setLoadingState(isLoading) {
  const main = document.querySelector('.main');
  const submit = document.getElementById('generateButton');
  main.classList.toggle('loading', isLoading);
  main.setAttribute('aria-busy', isLoading ? 'true' : 'false');
  submit.disabled = isLoading;
  submit.value = isLoading ? 'Generating…' : 'Generate Prompt';
}

function fetchIdea(formData) {
  setLoadingState(true);
  fetch('grabinfo.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(data => {
    const main = document.querySelector('.main');
    if (main.innerHTML.trim() !== '' && !main.classList.contains('placeholder')) {
      addToHistory(main.innerHTML);
    }
    main.classList.remove('placeholder');
    main.innerHTML = data;
  })
  .catch(() => {
    document.querySelector('.main').innerHTML = 'Could not generate an idea right now. Please try again.';
  })
  .finally(() => {
    setLoadingState(false);
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

  document.getElementById('toggleOptions').addEventListener('click', function() {
    document.getElementById('draw_options').classList.toggle('is-open');
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
<div class="utility-buttons" role="toolbar" aria-label="Display settings">
  <button id="themeToggle" aria-label="Toggle dark mode" class="utility-button">🌙</button>
  <button id="fontToggle" aria-label="Toggle large text" class="utility-button">A+</button>
</div>

<div class="app-shell">
  <header class="hero">
    <p class="kicker">Creative Prompt Generator</p>
    <h1>You Should Draw</h1>
    <p class="hero-copy">Generate unique character ideas in one click, then fine-tune the vibe with custom options.</p>
  </header>

  <main id="maincontent" class="content-grid">
    <section class="prompt-panel card">
      <h2>Your Prompt</h2>
      <h3 class="main placeholder" aria-live="polite" aria-busy="true">Creating your first idea…</h3>
      <details id="history_details" class="history" hidden>
        <summary id="history_summary"></summary>
        <ul id="idea_history"></ul>
      </details>
    </section>

    <section class="controls-panel card">
      <div class="controls-header">
        <h2>Customize</h2>
        <button type="button" id="toggleOptions" class="ghost-button">Show / Hide Options</button>
      </div>

      <form method="post" id="optionsform" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div id="draw_options">
          <div class="left_side">
            <label><input type="checkbox" name="gender" id="gender" value="Gender">Random gender</label>
            <label><input type="checkbox" name="emotion" id="emotion" value="Emotion">Random emotion</label>
            <label><input type="checkbox" name="pet" id="pet" value="Pet">Random pet</label>
            <label for="themeSelect">Theme</label>
            <select name="theme" id="themeSelect" class="options">
              <option value="">Any Theme</option>
              <?php foreach ($themes as $t): ?>
              <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="right_side">
            <label><input type="radio" name="accessories" class="accessories" value="1" checked>One accessory</label>
            <label><input type="radio" name="accessories" class="accessories" value="2">Two accessories</label>
            <label><input type="radio" name="accessories" class="accessories" value="3">Three accessories</label>
          </div>
        </div>
        <input type="submit" id="generateButton" class="submit button" value="Generate Prompt">
      </form>
    </section>
  </main>
</div>
</body>
</html>
