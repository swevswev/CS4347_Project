<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../VisitLog.php');
}

function failVisit(string $msg): never
{
    redirect('../VisitLog.php?error=' . urlencode($msg));
}

$patientId = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($patientId === false) {
    failVisit('Patient ID must be a positive integer.');
}

$st = db()->prepare('SELECT 1 FROM patients WHERE patient_id = ?');
$st->execute([$patientId]);
if (!$st->fetchColumn()) {
    failVisit('Patient ID does not exist.');
}

$doctorIds = int_list_from_post($_POST['doctor_id'] ?? null);
$doctorIds = array_values(array_unique($doctorIds));
if ($doctorIds === []) {
    failVisit('Enter at least one doctor ID.');
}

$stDoc = db()->prepare('SELECT 1 FROM doctors WHERE doctor_id = ?');
foreach ($doctorIds as $did) {
    $stDoc->execute([$did]);
    if (!$stDoc->fetchColumn()) {
        failVisit("Doctor ID {$did} does not exist. Add the doctor in Admin first.");
    }
}

$conditionId = null;
if (isset($_POST['condition_id']) && $_POST['condition_id'] !== '') {
    $conditionId = filter_var($_POST['condition_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($conditionId === false) {
        failVisit('Invalid condition selection.');
    }
    $stc = db()->prepare('SELECT 1 FROM conditions WHERE condition_id = ?');
    $stc->execute([$conditionId]);
    if (!$stc->fetchColumn()) {
        failVisit('Selected condition does not exist.');
    }
}

$procedure = trim((string) ($_POST['procedure'] ?? ''));
$procedure = $procedure === '' ? null : (strlen($procedure) > 100 ? substr($procedure, 0, 100) : $procedure);

$cost = filter_var($_POST['cost'] ?? null, FILTER_VALIDATE_FLOAT);
if ($cost === false || $cost < 0) {
    failVisit('Cost must be a non-negative number.');
}

$los = null;
if (isset($_POST['length_of_stay']) && $_POST['length_of_stay'] !== '') {
    $los = filter_var($_POST['length_of_stay'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($los === false) {
        failVisit('Length of stay must be a non-negative whole number.');
    }
}

$sat = null;
if (isset($_POST['satisfaction']) && $_POST['satisfaction'] !== '') {
    $sat = filter_var($_POST['satisfaction'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
    if ($sat === false) {
        failVisit('Satisfaction score must be between 1 and 10.');
    }
}

$outcome = trim((string) ($_POST['outcome'] ?? ''));
$outcome = $outcome === '' ? null : (strlen($outcome) > 50 ? substr($outcome, 0, 50) : $outcome);

$read = null;
$ra = $_POST['read_admission'] ?? '';
if ($ra === '1' || $ra === '0') {
    $read = (int) $ra;
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $ins = $pdo->prepare(
        'INSERT INTO visits (patient_id, condition_id, procedure_text, cost, length_of_stay, satisfaction, outcome, read_admission)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $patientId,
        $conditionId,
        $procedure,
        $cost,
        $los,
        $sat,
        $outcome,
        $read,
    ]);
    $visitId = (int) $pdo->lastInsertId();
    $vd = $pdo->prepare('INSERT INTO visit_doctors (visit_id, doctor_id) VALUES (?, ?)');
    foreach ($doctorIds as $did) {
        $vd->execute([$visitId, $did]);
    }
    $pdo->commit();
    redirect('../VisitLog.php?ok=' . urlencode("Visit logged. Visit ID: {$visitId}."));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    failVisit('Could not save visit. If doctors are linked to visits, check foreign key constraints.');
}
