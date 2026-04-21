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
$st = db()->prepare('SELECT 1 FROM PATIENTS WHERE Patient_ID = ?');
$st->execute([$patientId]);
if (!$st->fetchColumn()) {
    failVisit('Patient ID does not exist.');
}

$doctorId = filter_var($_POST['doctor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($doctorId === false) {
    failVisit('Doctor ID must be a positive integer.');
}
$stDoc = db()->prepare('SELECT 1 FROM DOCTORS WHERE Doctor_ID = ?');
$stDoc->execute([$doctorId]);
if (!$stDoc->fetchColumn()) {
    failVisit("Doctor ID {$doctorId} does not exist. Add the doctor in Admin first.");
}

$conditionId = null;
if (isset($_POST['condition_id']) && $_POST['condition_id'] !== '') {
    $conditionId = filter_var($_POST['condition_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($conditionId === false) {
        failVisit('Invalid condition selection.');
    }
    $stc = db()->prepare('SELECT 1 FROM CONDITIONS WHERE Condition_ID = ?');
    $stc->execute([$conditionId]);
    if (!$stc->fetchColumn()) {
        failVisit('Selected condition does not exist.');
    }
}

// Procedure_Name is the column name in the schema (not Procedure)
$procedure = trim((string) ($_POST['procedure'] ?? ''));
if ($procedure !== '' && strlen($procedure) > 100) {
    failVisit('Procedure must be at most 100 characters.');
}
$procedure = $procedure === '' ? null : $procedure;

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
if ($outcome !== '' && strlen($outcome) > 50) {
    failVisit('Outcome must be at most 50 characters.');
}
$outcome = $outcome === '' ? null : $outcome;

$read = null;
$ra = $_POST['re_admission'] ?? '';
if ($ra === '1' || $ra === '0') {
    $read = (int) $ra;
} elseif ($ra !== '') {
    failVisit('Readmission must be Yes, No, or not specified.');
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $visitId = (int) $pdo->query('SELECT COALESCE(MAX(Visit_ID), 0) + 1 FROM VISITS')->fetchColumn();
    $ins = $pdo->prepare(
        'INSERT INTO VISITS (Visit_ID, Patient_ID, Doctor_ID, Satisfaction, Procedure_Name, Cost, Length_of_Stay, Re_Admission, Outcome)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([$visitId, $patientId, $doctorId, $sat, $procedure, $cost, $los, $read, $outcome]);
    if ($conditionId !== null) {
        $vc = $pdo->prepare('INSERT INTO VISITS_CONDITIONS (Visit_ID, Condition_ID) VALUES (?, ?)');
        $vc->execute([$visitId, $conditionId]);
    }
    $pdo->commit();
    redirect('../VisitLog.php?ok=' . urlencode("Visit logged. Visit ID: {$visitId}."));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $code = (int) ($e->errorInfo[1] ?? 0);
    if ($code === 1451 || $code === 1452) {
        failVisit('Could not save visit: related patient, doctor, or condition record is missing.');
    }
    if ($code === 1062) {
        failVisit('Could not save visit: duplicate visit ID. Please retry.');
    }
    failVisit('Database error while saving visit. Please try again.');
}
