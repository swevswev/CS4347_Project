<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$departments = db()->query(
    'SELECT department_id, department_name AS dept_name, location AS dept_location
     FROM departments
     ORDER BY department_name'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel — Hospital Records</title>
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
      max-width: 36rem;
      margin: 0 auto;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1.5rem 1.75rem 2rem;
      box-shadow: 0 1px 3px rgba(31, 35, 40, 0.08);
    }
    h1 { font-size: 1.35rem; font-weight: 600; margin: 0 0 0.25rem; }
    .subtitle { color: var(--muted); font-size: 0.9rem; margin: 0 0 1.5rem; }
    .subtitle a { color: var(--accent); }
    fieldset {
      border: 1px solid var(--border);
      border-radius: 6px;
      margin: 0 0 1.25rem;
      padding: 1rem 1rem 0.25rem;
    }
    legend { font-weight: 600; font-size: 0.85rem; padding: 0 0.35rem; }
    .row { margin-bottom: 1rem; }
    label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; }
    .hint { font-weight: 400; color: var(--muted); font-size: 0.8rem; }
    input, select {
      width: 100%;
      max-width: 100%;
      padding: 0.5rem 0.65rem;
      font-size: 1rem;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: #fff;
    }
    input:focus, select:focus {
      outline: 2px solid rgba(9, 105, 218, 0.35);
      outline-offset: 1px;
    }
    .actions { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.5rem; }
    button {
      font-size: 1rem;
      padding: 0.55rem 1.1rem;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--accent);
      color: #fff;
      font-weight: 500;
      cursor: pointer;
    }
    button[type="reset"] { background: #fff; color: var(--text); }
    button:hover { filter: brightness(1.05); }
    input.no-spinner::-webkit-outer-spin-button,
    input.no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input.no-spinner { -moz-appearance: textfield; appearance: textfield; }
    main > form + form { margin-top: 1.75rem; }
    main > hr { margin: 2rem 0; border: 0; border-top: 1px solid var(--border); }
    .banner { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .banner-error { background: #ffebe9; border: 1px solid #ff8182; color: #82071e; }
    .banner-success { background: #dafbe1; border: 1px solid #4ac26b; color: #116329; }
  </style>
</head>
<body>
  <main>
    <p class="subtitle" style="margin-top:0"><a href="index.php">← Home</a></p>
    <h1>Admin panel</h1>
    <?php if (!empty($_GET['error'])): ?>
      <div class="banner banner-error"><?= h((string) $_GET['error']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['ok'])): ?>
      <div class="banner banner-success"><?= h((string) $_GET['ok']) ?></div>
    <?php endif; ?>

    <form method="post" action="handlers/admin_post.php">
      <input type="hidden" name="admin_form" value="add_department">
      <fieldset>
        <legend>Add department <span class="hint">(DEPARTMENTS)</span></legend>
        <div class="row">
          <label for="dept_name">Department name</label>
          <input type="text" id="dept_name" name="dept_name" required maxlength="100" placeholder="e.g. Cardiology, Emergency">
        </div>
        <div class="row">
          <label for="dept_location">Location <span class="hint">(optional)</span></label>
          <input type="text" id="dept_location" name="dept_location" maxlength="100" placeholder="Wing / floor">
        </div>
      </fieldset>
      <div class="actions">
        <button type="submit">Save department</button>
        <button type="reset">Clear</button>
      </div>
    </form>

    <form method="post" action="handlers/admin_post.php">
      <input type="hidden" name="admin_form" value="remove_department">
      <fieldset>
        <legend>Remove department <span class="hint">(DEPARTMENTS)</span></legend>
        <div class="row">
          <label for="remove_department_id">Department ID</label>
          <input type="number" class="no-spinner" id="remove_department_id" name="remove_department_id" required min="1" step="1" inputmode="numeric" placeholder="Department_ID to delete">
        </div>
      </fieldset>
      <div class="actions">
        <button type="submit">Remove department</button>
        <button type="reset">Clear</button>
      </div>
    </form>

    <hr>

    <form method="post" action="handlers/admin_post.php">
      <input type="hidden" name="admin_form" value="add_doctor">
      <fieldset>
        <legend>Add doctor <span class="hint">(DOCTORS)</span></legend>
        <div class="row">
          <label for="doctor_name">Doctor name</label>
          <input type="text" id="doctor_name" name="doctor_name" required maxlength="100" autocomplete="name">
        </div>
        <div class="row">
          <label for="specialization">Specialization <span class="hint">(optional)</span></label>
          <input type="text" id="specialization" name="specialization" maxlength="100" placeholder="e.g. Internal medicine">
        </div>
        <div class="row">
          <label for="doctor_department_id">Department</label>
          <select id="doctor_department_id" name="doctor_department_id" required>
            <option value="" selected disabled>— Select department —</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int) $d['department_id'] ?>">
                <?= h($d['dept_name']) ?> (ID <?= (int) $d['department_id'] ?>)
                <?php if (!empty($d['dept_location'])): ?> — <?= h((string) $d['dept_location']) ?><?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </fieldset>
      <div class="actions">
        <button type="submit">Save doctor</button>
        <button type="reset">Clear</button>
      </div>
    </form>

    <form method="post" action="handlers/admin_post.php">
      <input type="hidden" name="admin_form" value="remove_doctor">
      <fieldset>
        <legend>Remove doctor <span class="hint">(DOCTORS)</span></legend>
        <div class="row">
          <label for="remove_doctor_id">Doctor ID</label>
          <input type="number" class="no-spinner" id="remove_doctor_id" name="remove_doctor_id" required min="1" step="1" inputmode="numeric" placeholder="Doctor_ID to delete">
        </div>
      </fieldset>
      <div class="actions">
        <button type="submit">Remove doctor</button>
        <button type="reset">Clear</button>
      </div>
    </form>

    <hr>

    <form method="post" action="handlers/admin_post.php">
      <input type="hidden" name="admin_form" value="add_condition">
      <fieldset>
        <legend>Add condition <span class="hint">(CONDITIONS)</span></legend>
        <div class="row">
          <label for="condition_name">Condition name</label>
          <input type="text" id="condition_name" name="condition_name" required maxlength="100" placeholder="e.g. Diabetes">
        </div>
      </fieldset>
      <div class="actions">
        <button type="submit">Save condition</button>
        <button type="reset">Clear</button>
      </div>
    </form>

    <form method="post" action="handlers/admin_post.php">
      <input type="hidden" name="admin_form" value="remove_condition">
      <fieldset>
        <legend>Remove condition <span class="hint">(CONDITIONS)</span></legend>
        <div class="row">
          <label for="remove_condition_id">Condition ID</label>
          <input type="number" class="no-spinner" id="remove_condition_id" name="remove_condition_id" required min="1" step="1" inputmode="numeric" placeholder="Condition_ID to delete">
        </div>
      </fieldset>
      <div class="actions">
        <button type="submit">Remove condition</button>
        <button type="reset">Clear</button>
      </div>
    </form>
  </main>
</body>
</html>
