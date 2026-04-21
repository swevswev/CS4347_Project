<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$report = $_GET['report'] ?? null;
$data   = [];
$title  = '';
$error  = null;

try {
    if ($report === 'avg_cost') {
        $title = 'Average Cost by Condition';
        $data = db()->query(
            'SELECT c.Condition_Name AS condition_name, AVG(v.Cost) AS avg_cost
             FROM VISITS v
             JOIN VISITS_CONDITIONS vc ON vc.Visit_ID = v.Visit_ID
             JOIN CONDITIONS c ON vc.Condition_ID = c.Condition_ID
             GROUP BY c.Condition_Name
             ORDER BY avg_cost DESC'
        )->fetchAll();

    } elseif ($report === 'doctor_workload') {
        $title = 'Doctor Workload';
        // Name is the column in DOCTORS per create.sql (not Doctor_Name)
        $data = db()->query(
            'SELECT d.Name AS doctor_name, COUNT(v.Visit_ID) AS total_visits
             FROM DOCTORS d
             LEFT JOIN VISITS v ON d.Doctor_ID = v.Doctor_ID
             GROUP BY d.Doctor_ID, d.Name
             ORDER BY total_visits DESC'
        )->fetchAll();

    } elseif ($report === 'procedure_cost') {
        $title = 'Procedure Cost Analysis';
        // column is Procedure in create.sql (not Procedure_Name)
        $data = db()->query(
            'SELECT `Procedure` AS procedure_name, AVG(Cost) AS avg_cost
             FROM VISITS
             WHERE `Procedure` IS NOT NULL
             GROUP BY `Procedure`
             ORDER BY avg_cost DESC'
        )->fetchAll();

    } elseif ($report === 'common_conditions') {
        $title = 'Most Common Conditions';
        $data = db()->query(
            'SELECT c.Condition_Name AS condition_name, COUNT(*) AS occurrences
             FROM VISITS_CONDITIONS vc
             JOIN CONDITIONS c ON vc.Condition_ID = c.Condition_ID
             GROUP BY c.Condition_Name
             ORDER BY occurrences DESC'
        )->fetchAll();

    } elseif ($report === 'doctors_by_dept') {
        $title = 'Doctors by Department';
        // Name for both DOCTORS and DEPARTMENTS per create.sql
        $data = db()->query(
            'SELECT d.Name AS doctor_name, dep.Name AS department_name
             FROM DOCTORS d
             JOIN DEPARTMENTS dep ON d.Department_ID = dep.Department_ID
             ORDER BY dep.Name, d.Name'
        )->fetchAll();

    } elseif ($report === 'patient_history') {
        $title = 'Patient History';
        $pid = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($pid === false) {
            $error = 'Patient ID must be a positive integer.';
        } else {
            // Name for DOCTORS, Procedure for VISITS
            $stmt = db()->prepare(
                'SELECT v.Visit_ID AS visit_id, d.Name AS doctor_name,
                        GROUP_CONCAT(DISTINCT c.Condition_Name ORDER BY c.Condition_Name SEPARATOR ", ") AS condition_names,
                        v.`Procedure` AS procedure_name, v.Cost AS cost,
                        v.Length_of_Stay AS length_of_stay, v.Satisfaction AS satisfaction,
                        v.Outcome AS outcome, v.Re_Admission AS re_admission
                 FROM VISITS v
                 LEFT JOIN DOCTORS d ON d.Doctor_ID = v.Doctor_ID
                 LEFT JOIN VISITS_CONDITIONS vc ON vc.Visit_ID = v.Visit_ID
                 LEFT JOIN CONDITIONS c ON c.Condition_ID = vc.Condition_ID
                 WHERE v.Patient_ID = ?
                 GROUP BY v.Visit_ID, d.Name, v.`Procedure`, v.Cost,
                          v.Length_of_Stay, v.Satisfaction, v.Outcome, v.Re_Admission
                 ORDER BY v.Visit_ID DESC'
            );
            $stmt->execute([$pid]);
            $data = $stmt->fetchAll();
        }

    } elseif ($report !== null) {
        $error = 'Unknown report selected.';
    }
} catch (PDOException $e) {
    $error = 'Database error while generating report.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics — Hospital Records</title>
  <style>
    :root { --bg:#f4f6f8; --card:#fff; --border:#d0d7de; --text:#1f2328; --muted:#59636e; --accent:#0969da; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; background:var(--bg); color:var(--text); line-height:1.5; padding:1.5rem; }
    main { max-width:52rem; margin:0 auto; background:var(--card); border:1px solid var(--border); border-radius:8px; padding:1.5rem 1.75rem 2rem; box-shadow:0 1px 3px rgba(31,35,40,.08); }
    h1 { font-size:1.35rem; font-weight:600; margin:0 0 1rem; }
    .subtitle a { color:var(--accent); }
    .actions { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem; }
    .actions a { text-decoration:none; }
    button { padding:.5rem 1rem; border-radius:6px; border:1px solid var(--border); background:var(--accent); color:#fff; cursor:pointer; font-size:.9rem; }
    button:hover { filter:brightness(1.05); }
    table { width:100%; border-collapse:collapse; margin-top:1rem; font-size:.875rem; }
    th, td { border:1px solid var(--border); padding:.45rem .5rem; text-align:left; }
    th { background:#f6f8fa; }
    .banner { padding:.75rem 1rem; border-radius:6px; margin-top:1rem; font-size:.9rem; background:#ffebe9; border:1px solid #ff8182; color:#82071e; }
    input[type=number] { padding:.45rem .65rem; border:1px solid var(--border); border-radius:6px; font-size:.95rem; width:10rem; }
  </style>
</head>
<body>
  <main>
    <p class="subtitle"><a href="index.php">&#8592; Home</a></p>
    <h1>Analytics dashboard</h1>

    <div class="actions">
      <a href="?report=avg_cost"><button>Avg Cost by Condition</button></a>
      <a href="?report=doctor_workload"><button>Doctor Workload</button></a>
      <a href="?report=procedure_cost"><button>Procedure Costs</button></a>
      <a href="?report=common_conditions"><button>Common Conditions</button></a>
      <a href="?report=doctors_by_dept"><button>Doctors by Dept</button></a>
    </div>

    <form method="get" style="margin-top:1rem;display:flex;gap:.5rem;align-items:center">
      <input type="hidden" name="report" value="patient_history">
      <input type="number" name="patient_id" placeholder="Patient ID" min="1" step="1" required
        value="<?= isset($_GET['patient_id']) ? htmlspecialchars((string) $_GET['patient_id'], ENT_QUOTES, 'UTF-8') : '' ?>">
      <button type="submit">Patient History</button>
    </form>

    <?php if ($report !== null): ?>
      <h2 style="font-size:1.05rem;margin-top:1.5rem"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
      <?php if ($error !== null): ?>
        <div class="banner"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php elseif (empty($data)): ?>
        <p style="color:var(--muted)">No results found.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <?php foreach (array_keys($data[0]) as $col): ?>
                <th><?= htmlspecialchars($col, ENT_QUOTES, 'UTF-8') ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $row): ?>
              <tr>
                <?php foreach ($row as $val): ?>
                  <td><?= htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
