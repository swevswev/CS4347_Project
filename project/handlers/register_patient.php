<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../PatientRegistration.php');
}

function fail(string $msg): never
{
    redirect('../PatientRegistration.php?error=' . urlencode($msg));
}

$full = trim((string) ($_POST['full_name'] ?? ''));
if ($full === '' || strlen($full) > 100) {
    fail('Full name is required (max 100 characters).');
}

$age = filter_var($_POST['age'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 150]]);
if ($age === false) {
    fail('Age must be a whole number from 0 to 150.');
}

$gender = trim((string) ($_POST['gender'] ?? ''));
$allowedG = ['Female', 'Male', 'Non-binary', 'Other', 'Prefer not to say'];
if (!in_array($gender, $allowedG, true)) {
    fail('Please choose a valid gender option.');
}

$insType = trim((string) ($_POST['ins_type'] ?? ''));
if ($insType !== '' && strlen($insType) > 50) {
    fail('Insurance type must be at most 50 characters.');
}
$insType = $insType === '' ? null : $insType;

$provider = trim((string) ($_POST['provider'] ?? ''));
if ($provider !== '' && strlen($provider) > 100) {
    fail('Insurance provider must be at most 100 characters.');
}
$provider = $provider === '' ? null : $provider;

$deductible = null;
if (isset($_POST['deductible']) && $_POST['deductible'] !== '') {
    $deductible = filter_var($_POST['deductible'], FILTER_VALIDATE_FLOAT);
    if ($deductible === false || $deductible < 0) {
        fail('Deductible must be a non-negative number.');
    }
}

$prim = null;
if (isset($_POST['prim_doctor']) && $_POST['prim_doctor'] !== '') {
    $prim = filter_var($_POST['prim_doctor'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($prim === false) {
        fail('Primary doctor ID must be a positive integer.');
    }
    $st = db()->prepare('SELECT 1 FROM DOCTORS WHERE Doctor_ID = ?');
    $st->execute([$prim]);
    if (!$st->fetchColumn()) {
        fail('Primary doctor ID does not exist. Add the doctor in Admin first.');
    }
}

try {
    $nextId = (int) db()->query('SELECT COALESCE(MAX(Patient_ID), 0) + 1 FROM PATIENTS')->fetchColumn();
    $sql = 'INSERT INTO PATIENTS (Patient_ID, Full_Name, Age, Gender, Ins_Type, Provider, Deductible, Prim_Doctor)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $st = db()->prepare($sql);
    $st->execute([$nextId, $full, $age, $gender, $insType, $provider, $deductible, $prim]);
    $id = $nextId;
    redirect('../PatientRegistration.php?ok=' . urlencode("Patient registered successfully. New patient ID: {$id}."));
} catch (PDOException $e) {
    $code = (int) ($e->errorInfo[1] ?? 0);
    if ($code === 1062) {
        fail('Duplicate or invalid data.');
    }
    if ($code === 1451 || $code === 1452) {
        fail('Could not save patient: referenced primary doctor does not exist.');
    }
    fail('Database error while saving patient. Check that sql/create.sql has been applied.');
}
