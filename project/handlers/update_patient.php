<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../EditPatient.php');
}

function failUpdate(string $msg, int $pid): never
{
    redirect('../EditPatient.php?patient_id=' . $pid . '&error=' . urlencode($msg));
}

$pid = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($pid === false) {
    redirect('../EditPatient.php?error=' . urlencode('Invalid patient ID.'));
}

// verify patient exists
$chk = db()->prepare('SELECT 1 FROM PATIENTS WHERE Patient_ID = ?');
$chk->execute([$pid]);
if (!$chk->fetchColumn()) {
    failUpdate('Patient not found.', $pid);
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
if (strlen($fullName) > 100) {
    failUpdate('Full name must be at most 100 characters.', $pid);
}
$fullName = $fullName === '' ? null : $fullName;

$age = filter_var($_POST['age'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 150]]);
if ($age === false) {
    failUpdate('Age must be a whole number from 0 to 150.', $pid);
}

$gender = trim((string) ($_POST['gender'] ?? ''));
$allowedG = ['Female', 'Male', 'Non-binary', 'Other', 'Prefer not to say'];
if (!in_array($gender, $allowedG, true)) {
    failUpdate('Please choose a valid gender option.', $pid);
}

$insType = trim((string) ($_POST['ins_type'] ?? ''));
if ($insType !== '' && strlen($insType) > 50) {
    failUpdate('Insurance type must be at most 50 characters.', $pid);
}
$insType = $insType === '' ? null : $insType;

$provider = trim((string) ($_POST['provider'] ?? ''));
if ($provider !== '' && strlen($provider) > 100) {
    failUpdate('Insurance provider must be at most 100 characters.', $pid);
}
$provider = $provider === '' ? null : $provider;

$deductible = null;
if (isset($_POST['deductible']) && $_POST['deductible'] !== '') {
    $deductible = filter_var($_POST['deductible'], FILTER_VALIDATE_FLOAT);
    if ($deductible === false || $deductible < 0) {
        failUpdate('Deductible must be a non-negative number.', $pid);
    }
}

$prim = null;
if (isset($_POST['prim_doctor']) && $_POST['prim_doctor'] !== '') {
    $prim = filter_var($_POST['prim_doctor'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($prim === false) {
        failUpdate('Primary doctor ID must be a positive integer.', $pid);
    }
    $stDoc = db()->prepare('SELECT 1 FROM DOCTORS WHERE Doctor_ID = ?');
    $stDoc->execute([$prim]);
    if (!$stDoc->fetchColumn()) {
        failUpdate('Primary doctor ID does not exist.', $pid);
    }
}

try {
    $sql = 'UPDATE PATIENTS SET Full_Name = ?, Age = ?, Gender = ?, Ins_Type = ?,
            Provider = ?, Deductible = ?, Prim_Doctor = ?
            WHERE Patient_ID = ?';
    $st = db()->prepare($sql);
    $st->execute([$fullName, $age, $gender, $insType, $provider, $deductible, $prim, $pid]);
    redirect('../EditPatient.php?patient_id=' . $pid . '&ok=' . urlencode('Patient updated successfully.'));
} catch (PDOException $e) {
    $code = (int) ($e->errorInfo[1] ?? 0);
    if ($code === 1452) {
        failUpdate('Primary doctor ID does not exist.', $pid);
    }
    failUpdate('Database error while updating patient.', $pid);
}
