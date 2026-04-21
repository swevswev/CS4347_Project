<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$patient = null;
$searchError = null;

// load patient data if ID provided
if (isset($_GET['patient_id']) && (string) $_GET['patient_id'] !== '') {
    $pid = filter_var($_GET['patient_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($pid === false) {
        $searchError = 'Patient ID must be a positive integer.';
    } else {
        $st = db()->prepare(
            'SELECT Patient_ID, Full_Name, Age, Gender, Ins_Type, Provider, Deductible, Prim_Doctor
             FROM PATIENTS WHERE Patient_ID = ?'
        );
        $st->execute([$pid]);
        $patient = $st->fetch();
        if (!$patient) {
            $searchError = 'No patient found with that ID.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Patient — Hospital Records</title>
  <style>
    :root { --bg: #f4f6f8; --card: #fff; --border: #d0d7de; --text: #1f2328; --muted: #59636e; --accent: #0969da; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; padding: 1.5rem; }
    main { max-width: 36rem; margin: 0 auto; background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem 1.75rem 2rem; box-shadow: 0 1px 3px rgba(31,35,40,0.08); }
    h1 { font-size: 1.35rem; font-weight: 600; margin: 0 0 0.25rem; }
    .subtitle { color: var(--muted); font-size: 0.9rem; margin: 0 0 1.5rem; }
    .subtitle a { color: var(--accent); }
    fieldset { border: 1px solid var(--border); border-radius: 6px; margin: 0 0 1.25rem; padding: 1rem 1rem 0.25rem; }
    legend { font-weight: 600; font-size: 0.85rem; padding: 0 0.35rem; }
    .row { margin-bottom: 1rem; }
    label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; }
    .hint { font-weight: 400; color: var(--muted); font-size: 0.8rem; }
    input, select { width: 100%; max-width: 100%; padding: 0.5rem 0.65rem; font-size: 1rem; border: 1px solid var(--border); border-radius: 6px; background: #fff; }
    input:focus, select:focus { outline: 2px solid rgba(9,105,218,0.35); outline-offset: 1px; }
    .actions { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.5rem; }
    button { font-size: 1rem; padding: 0.55rem 1.1rem; border-radius: 6px; border: 1px solid var(--border); background: var(--accent); color: #fff; font-weight: 500; cursor: pointer; }
    button[type="reset"] { background: #fff; color: var(--text); }
    button:hover { filter: brightness(1.05); }
    input.no-spinner::-webkit-outer-spin-button, input.no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input.no-spinner { -moz-appearance: textfield; appearance: textfield; }
    .banner { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
    .banner-error { background: #ffebe9; border: 1px solid #ff8182; color: #82071e; }
    .banner-success { background: #dafbe1; border: 1px solid #4ac26b; color: #116329; }
  </style>
</head>
<body>
  <main>
    <p class="subtitle" style="margin-top:0"><a href="index.php">← Home</a></p>
    <h1>Edit patient</h1>

    <?php if (!empty($_GET['error'])): ?>
      <div class="banner banner-error"><?= h((string) $_GET['error']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['ok'])): ?>
      <div class="banner banner-success"><?= h((string) $_GET['ok']) ?></div>
    <?php endif; ?>

    <!-- step 1: look up patient by ID -->
    <form method="get" action="EditPatient.php">
      <fieldset>
        <legend>Look up patient</legend>
        <div class="row">
          <label for="patient_id">Patient ID</label>
          <input type="number" class="no-spinner" id="patient_id" name="patient_id" required min="1" step="1"
            inputmode="numeric" placeholder="Enter Patient_ID"
            value="<?= isset($_GET['patient_id']) ? h((string) $_GET['patient_id']) : '' ?>">
        </div>
      </fieldset>
      <div class="actions">
        <button type="submit">Load patient</button>
      </div>
    </form>

    <?php if ($searchError !== null): ?>
      <div class="banner banner-error" style="margin-top:1rem"><?= h($searchError) ?></div>
    <?php endif; ?>

    <?php if ($patient !== null): ?>
      <!-- step 2: edit form pre-filled with current values -->
      <form method="post" action="handlers/update_patient.php" style="margin-top:1.5rem">
        <input type="hidden" name="patient_id" value="<?= (int) $patient['Patient_ID'] ?>">
        <fieldset>
          <legend>Patient identity <span class="hint">(ID: <?= (int) $patient['Patient_ID'] ?>)</span></legend>
          <div class="row">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" maxlength="100"
              value="<?= h((string) ($patient['Full_Name'] ?? '')) ?>">
          </div>
          <div class="row">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" required min="0" max="150" step="1"
              value="<?= (int) $patient['Age'] ?>">
          </div>
          <div class="row">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
              <?php foreach (['Female','Male','Non-binary','Other','Prefer not to say'] as $g): ?>
                <option value="<?= $g ?>" <?= $patient['Gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </fieldset>

        <fieldset>
          <legend>Insurance <span class="hint">(optional)</span></legend>
          <div class="row">
            <label for="ins_type">Insurance type</label>
            <input type="text" id="ins_type" name="ins_type" maxlength="50"
              value="<?= h((string) ($patient['Ins_Type'] ?? '')) ?>">
          </div>
          <div class="row">
            <label for="provider">Insurance provider</label>
            <input type="text" id="provider" name="provider" maxlength="100"
              value="<?= h((string) ($patient['Provider'] ?? '')) ?>">
          </div>
          <div class="row">
            <label for="deductible">Deductible ($)</label>
            <input type="number" id="deductible" name="deductible" min="0" step="0.01"
              value="<?= $patient['Deductible'] !== null ? h((string) $patient['Deductible']) : '' ?>">
          </div>
        </fieldset>

        <fieldset>
          <legend>Care team <span class="hint">(optional)</span></legend>
          <div class="row">
            <label for="prim_doctor">Primary doctor ID</label>
            <input type="number" class="no-spinner" id="prim_doctor" name="prim_doctor" min="1" step="1"
              value="<?= $patient['Prim_Doctor'] !== null ? (int) $patient['Prim_Doctor'] : '' ?>"
              placeholder="Doctor_ID">
          </div>
        </fieldset>

        <div class="actions">
          <button type="submit">Save changes</button>
          <a href="EditPatient.php?patient_id=<?= (int) $patient['Patient_ID'] ?>">
            <button type="button">Reset</button>
          </a>
        </div>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
