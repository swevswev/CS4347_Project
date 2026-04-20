<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$conditions = db()->query('SELECT condition_id, condition_name FROM conditions ORDER BY condition_name')->fetchAll();

$searchError = null;
$searchPatient = null;
/** @var list<array<string,mixed>>|null */
$searchVisits = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['patient_id']) && (string) $_GET['patient_id'] !== '') {
    $pid = filter_var($_GET['patient_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($pid === false) {
        $searchError = 'Patient ID must be a positive integer.';
    } else {
        $p = db()->prepare('SELECT * FROM patients WHERE patient_id = ?');
        $p->execute([$pid]);
        $searchPatient = $p->fetch();
        if (!$searchPatient) {
            $searchError = 'No patient found with that ID.';
        } else {
            $condFilter = null;
            if (isset($_GET['condition_id']) && (string) $_GET['condition_id'] !== '') {
                $condFilter = filter_var($_GET['condition_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($condFilter === false) {
                    $searchError = 'Invalid condition filter.';
                }
            }
            if ($searchError === null) {
                $sql = 'SELECT v.visit_id, v.patient_id, v.condition_id, v.procedure_text, v.cost, v.length_of_stay,
                        v.satisfaction, v.outcome, v.read_admission, v.visit_date, c.condition_name,
                        (SELECT GROUP_CONCAT(CONCAT(d.doctor_id, \': \', d.doctor_name) ORDER BY d.doctor_id SEPARATOR \' | \')
                         FROM visit_doctors vd2
                         INNER JOIN doctors d ON d.doctor_id = vd2.doctor_id
                         WHERE vd2.visit_id = v.visit_id) AS doctors
                        FROM visits v
                        LEFT JOIN conditions c ON c.condition_id = v.condition_id
                        WHERE v.patient_id = ? ';
                $params = [$pid];
                if ($condFilter !== null) {
                    $sql .= ' AND v.condition_id = ? ';
                    $params[] = $condFilter;
                }
                $sql .= ' ORDER BY v.visit_id DESC';
                $sv = db()->prepare($sql);
                $sv->execute($params);
                $searchVisits = $sv->fetchAll();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Search — Hospital Records</title>
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
      max-width: 52rem;
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
    .banner { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .banner-error { background: #ffebe9; border: 1px solid #ff8182; color: #82071e; }
    table.results { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-top: 1rem; }
    table.results th, table.results td { border: 1px solid var(--border); padding: 0.45rem 0.5rem; text-align: left; vertical-align: top; }
    table.results th { background: #f6f8fa; }
    .patient-card { background: #f6f8fa; border: 1px solid var(--border); border-radius: 6px; padding: 1rem; margin-top: 1rem; font-size: 0.9rem; }
    .patient-card dt { color: var(--muted); float: left; clear: left; width: 9rem; }
    .patient-card dd { margin: 0 0 0.35rem 9.5rem; }
  </style>
</head>
<body>
  <main>
    <p class="subtitle" style="margin-top:0"><a href="index.php">← Home</a></p>
    <h1>Patient search</h1>

    <form method="get" action="PatientSearch.php">
      <fieldset>
        <legend>Lookup</legend>
        <div class="row">
          <label for="patient_id">Patient ID</label>
          <input type="number" class="no-spinner" id="patient_id" name="patient_id" required min="1" step="1" inputmode="numeric" placeholder="Patient_ID"
            value="<?= isset($_GET['patient_id']) ? h((string) $_GET['patient_id']) : '' ?>">
        </div>
      </fieldset>

      <fieldset>
        <legend>Optional filters</legend>
        <div class="row">
          <label for="condition_id">Condition <span class="hint">(Optional)</span></label>
          <select id="condition_id" name="condition_id">
            <option value="" <?= empty($_GET['condition_id']) ? ' selected' : '' ?>>Any condition</option>
            <?php foreach ($conditions as $c): ?>
              <option value="<?= (int) $c['condition_id'] ?>"
                <?= (isset($_GET['condition_id']) && (string) $_GET['condition_id'] === (string) $c['condition_id']) ? ' selected' : '' ?>>
                <?= h($c['condition_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </fieldset>

      <div class="actions">
        <button type="submit">Search</button>
        <button type="reset">Clear</button>
      </div>
    </form>

    <?php if ($searchError !== null): ?>
      <div class="banner banner-error"><?= h($searchError) ?></div>
    <?php elseif ($searchPatient !== null): ?>
      <h2 style="font-size:1.05rem;margin-top:1.75rem">Patient</h2>
      <dl class="patient-card">
        <dt>Patient ID</dt><dd><?= (int) $searchPatient['patient_id'] ?></dd>
        <dt>Name</dt><dd><?= h((string) $searchPatient['full_name']) ?></dd>
        <dt>Age / gender</dt><dd><?= (int) $searchPatient['age'] ?> / <?= h((string) $searchPatient['gender']) ?></dd>
        <dt>Insurance</dt><dd><?= h(trim((string) ($searchPatient['ins_type'] ?? '') . ' ' . (string) ($searchPatient['provider'] ?? ''))) ?: '—' ?></dd>
        <dt>Deductible</dt><dd><?= $searchPatient['deductible'] !== null ? h((string) $searchPatient['deductible']) : '—' ?></dd>
        <dt>Primary doctor ID</dt><dd><?= $searchPatient['primary_doctor_id'] !== null ? (int) $searchPatient['primary_doctor_id'] : '—' ?></dd>
      </dl>

      <h2 style="font-size:1.05rem;margin-top:1.5rem">Visits<?= $searchVisits !== null && isset($_GET['condition_id']) && $_GET['condition_id'] !== '' ? ' (filtered)' : '' ?></h2>
      <?php if ($searchVisits === []): ?>
        <p class="subtitle" style="margin:0.5rem 0 0">No visits match this filter.</p>
      <?php else: ?>
        <table class="results">
          <thead>
            <tr>
              <th>Visit ID</th>
              <th>Date</th>
              <th>Condition</th>
              <th>Procedure</th>
              <th>Doctors</th>
              <th>Cost</th>
              <th>LOS</th>
              <th>Sat.</th>
              <th>Outcome</th>
              <th>Readmit</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($searchVisits as $v): ?>
              <tr>
                <td><?= (int) $v['visit_id'] ?></td>
                <td><?= h((string) $v['visit_date']) ?></td>
                <td><?= h((string) ($v['condition_name'] ?? '')) ?: '—' ?></td>
                <td><?= h((string) ($v['procedure_text'] ?? '')) ?: '—' ?></td>
                <td><?= h((string) ($v['doctors'] ?? '')) ?: '—' ?></td>
                <td><?= h((string) $v['cost']) ?></td>
                <td><?= $v['length_of_stay'] !== null ? (int) $v['length_of_stay'] : '—' ?></td>
                <td><?= $v['satisfaction'] !== null ? (int) $v['satisfaction'] : '—' ?></td>
                <td><?= h((string) ($v['outcome'] ?? '')) ?: '—' ?></td>
                <td><?php
                  $r = $v['read_admission'];
                  if ($r === null) {
                      echo '—';
                  } elseif ((int) $r === 1) {
                      echo 'Yes';
                  } else {
                      echo 'No';
                  }
                ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
