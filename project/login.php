<?php
declare(strict_types=1);
session_start();

// redirect to admin panel if already logged in
if (!empty($_SESSION['is_admin'])) {
    header('Location: AdminPanel.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // hardcoded admin credentials -- change these before deploying
    $valid_user = 'admin';
    $valid_pass = 'hospital2024';

    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');

    if ($user === $valid_user && $pass === $valid_pass) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        header('Location: AdminPanel.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Hospital Records</title>
  <style>
    :root {
      --bg: #f4f6f8;
      --card: #fff;
      --border: #d0d7de;
      --text: #1f2328;
      --muted: #59636e;
      --accent: #0969da;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
      padding: 1.5rem;
    }
    main {
      max-width: 24rem;
      margin: 4rem auto 0;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1.5rem 1.75rem 2rem;
      box-shadow: 0 1px 3px rgba(31, 35, 40, 0.08);
    }
    h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 1.25rem; }
    .row { margin-bottom: 1rem; }
    label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; }
    input {
      width: 100%;
      padding: 0.5rem 0.65rem;
      font-size: 1rem;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: #fff;
    }
    input:focus { outline: 2px solid rgba(9,105,218,0.35); outline-offset: 1px; }
    button {
      width: 100%;
      font-size: 1rem;
      padding: 0.55rem 1.1rem;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--accent);
      color: #fff;
      font-weight: 500;
      cursor: pointer;
      margin-top: 0.25rem;
    }
    button:hover { filter: brightness(1.05); }
    .banner {
      padding: 0.75rem 1rem;
      border-radius: 6px;
      margin-bottom: 1rem;
      font-size: 0.9rem;
      background: #ffebe9;
      border: 1px solid #ff8182;
      color: #82071e;
    }
    .back { font-size: 0.875rem; color: var(--muted); margin-top: 1rem; text-align: center; }
    .back a { color: var(--accent); }
  </style>
</head>
<body>
  <main>
    <h1>Admin login</h1>
    <?php if ($error !== ''): ?>
      <div class="banner"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <div class="row">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username">
      </div>
      <div class="row">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit">Log in</button>
    </form>
    <p class="back"><a href="index.php">← Back to home</a></p>
  </main>
</body>
</html>
