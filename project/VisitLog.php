<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$conditions = db()->query('SELECT condition_id, condition_name FROM conditions ORDER BY condition_name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Visit Log — Hospital Records</title>
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
    .doctor-row-tools { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
    .doctor-row-tools > input { flex: 1; min-width: 0; width: auto; max-width: 100%; }
    button.doctor-remove { background: #fff; color: var(--text); }
    .banner { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .banner-error { background: #ffebe9; border: 1px solid #ff8182; color: #82071e; }
    .banner-success { background: #dafbe1; border: 1px solid #4ac26b; color: #116329; }
  </style>
</head>
<body>
  <main>
    <p class="subtitle" style="margin-top:0"><a href="index.php">← Home</a></p>
    <h1>Visit log</h1>
    <?php if (!empty($_GET['error'])): ?>
      <div class="banner banner-error"><?= h((string) $_GET['error']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['ok'])): ?>
      <div class="banner banner-success"><?= h((string) $_GET['ok']) ?></div>
    <?php endif; ?>

    <form method="post" action="handlers/log_visit.php">
      <fieldset>
        <legend>Encounter</legend>
        <div class="row">
          <label for="patient_id">Patient ID</label>
          <input type="number" class="no-spinner" id="patient_id" name="patient_id" required min="1" step="1" inputmode="numeric" placeholder="Patient_ID">
        </div>
        <div id="doctor-rows">
          <div class="row doctor-row">
            <label for="doctor_id_main">Doctor ID <span class="hint">(at least one)</span></label>
            <div class="doctor-row-tools">
              <input type="number" class="no-spinner" id="doctor_id_main" name="doctor_id[]" required min="1" step="1" inputmode="numeric" placeholder="Doctor_ID">
            </div>
          </div>
        </div>
        <div class="row">
          <button type="button" id="add-doctor-btn">Add another doctor</button>
        </div>
      </fieldset>

      <template id="doctor-row-template">
        <div class="row doctor-row">
          <label>Doctor ID <span class="hint">(optional)</span></label>
          <div class="doctor-row-tools">
            <input type="number" class="no-spinner" name="doctor_id[]" min="1" step="1" inputmode="numeric" placeholder="Doctor_ID">
            <button type="button" class="doctor-remove">Remove</button>
          </div>
        </div>
      </template>

      <fieldset>
        <legend>Reason &amp; procedure</legend>
        <div class="row">
          <label for="condition_id">Condition <span class="hint">(optional)</span></label>
          <select id="condition_id" name="condition_id">
            <option value="" selected>— No specific condition —</option>
            <?php foreach ($conditions as $c): ?>
              <option value="<?= (int) $c['condition_id'] ?>"><?= h($c['condition_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <label for="procedure">Procedure <span class="hint">(optional)</span></label>
          <input type="text" id="procedure" name="procedure" maxlength="100" placeholder="Procedure performed during visit">
        </div>
      </fieldset>

      <fieldset>
        <legend>Stay &amp; cost</legend>
        <div class="row">
          <label for="cost">Cost ($)</label>
          <input type="number" id="cost" name="cost" required min="0" step="0.01" inputmode="decimal" value="0.00">
        </div>
        <div class="row">
          <label for="length_of_stay">Length of stay (days) <span class="hint">(optional)</span></label>
          <input type="number" id="length_of_stay" name="length_of_stay" min="0" step="1" inputmode="numeric" placeholder="Days">
        </div>
      </fieldset>

      <fieldset>
        <legend>Outcomes <span class="hint">(optional)</span></legend>
        <div class="row">
          <label for="satisfaction">Satisfaction score</label>
          <input type="number" id="satisfaction" name="satisfaction" min="1" max="10" step="1" inputmode="numeric" placeholder="1–10">
        </div>
        <div class="row">
          <label for="outcome">Outcome</label>
          <input type="text" id="outcome" name="outcome" maxlength="50" placeholder="e.g. discharged, improved, critical">
        </div>
        <div class="row">
          <label for="read_admission">Readmission</label>
          <select id="read_admission" name="read_admission">
            <option value="" selected>Not specified</option>
            <option value="1">Yes</option>
            <option value="0">No</option>
          </select>
        </div>
      </fieldset>

      <div class="actions">
        <button type="submit">Log visit</button>
        <button type="reset">Clear form</button>
      </div>
    </form>
  </main>
  <script>
    (function () {
      var container = document.getElementById('doctor-rows');
      var template = document.getElementById('doctor-row-template');
      var form = container.closest('form');
      var addBtn = document.getElementById('add-doctor-btn');
      var doctorRowCounter = 0;

      addBtn.addEventListener('click', function () {
        doctorRowCounter += 1;
        var frag = template.content.cloneNode(true);
        var input = frag.querySelector('input');
        var id = 'doctor_id_extra_' + doctorRowCounter;
        input.id = id;
        frag.querySelector('label').setAttribute('for', id);
        container.appendChild(frag);
      });

      container.addEventListener('click', function (e) {
        var btn = e.target.closest('.doctor-remove');
        if (!btn) return;
        var row = btn.closest('.doctor-row');
        if (row && row !== container.firstElementChild) row.remove();
      });

      form.addEventListener('reset', function () {
        while (container.lastElementChild !== container.firstElementChild) {
          container.removeChild(container.lastElementChild);
        }
      });
    })();
  </script>
</body>
</html>
