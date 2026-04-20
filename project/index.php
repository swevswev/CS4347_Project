<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home — Hospital Records</title>
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
      max-width: 40rem;
      margin: 0 auto;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1.5rem 1.75rem 2rem;
      box-shadow: 0 1px 3px rgba(31, 35, 40, 0.08);
    }
    h1 { font-size: 1.35rem; font-weight: 600; margin: 0 0 0.25rem; }
    .subtitle { color: var(--muted); font-size: 0.9rem; margin: 0 0 1.75rem; }
    .nav-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));
      gap: 0.75rem;
    }
    .nav-grid a {
      display: block;
      text-align: center;
      text-decoration: none;
      font-size: 1rem;
      padding: 0.75rem 1rem;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--accent);
      color: #fff;
      font-weight: 500;
    }
    .nav-grid a:hover { filter: brightness(1.05); }
    .nav-grid a:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
  </style>
</head>
<body>
  <main>
    <h1>Hospital records</h1>
    <p class="subtitle">Forms below talk to MySQL through PHP. Use a web server with PHP and MySQL (see README.txt).</p>
    <nav class="nav-grid" aria-label="Forms">
      <a href="PatientRegistration.php">Patient registration</a>
      <a href="PatientSearch.php">Patient search</a>
      <a href="VisitLog.php">Visit log</a>
      <a href="AdminPanel.php">Admin panel</a>
    </nav>
  </main>
</body>
</html>
