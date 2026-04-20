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
$insType = $insType === '' ? null : (strlen($insType) > 50 ? substr($insType, 0, 50) : $insType);

$provider = trim((string) ($_POST['provider'] ?? ''));
$provider = $provider === '' ? null : (strlen($provider) > 100 ? substr($provider, 0, 100) : $provider);

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
    $st = db()->prepare('SELECT 1 FROM doctors WHERE doctor_id = ?');
    $st->execute([$prim]);
    if (!$st->fetchColumn()) {
        fail('Primary doctor ID does not exist. Add the doctor in Admin first.');
    }
}

try {
    $sql = 'INSERT INTO patients (full_name, age, gender, ins_type, provider, deductible, primary_doctor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)';
    $st = db()->prepare($sql);
    $st->execute([$full, $age, $gender, $insType, $provider, $deductible, $prim]);
    $id = (int) db()->lastInsertId();
    redirect('../PatientRegistration.php?ok=' . urlencode("Patient registered successfully. New patient ID: {$id}."));
} catch (PDOException $e) {
    if ((int) $e->errorInfo[1] === 1062) {
        fail('Duplicate or invalid data.');
    }
    fail('Could not save patient. Check the database connection and that sql/create.sql has been applied.');
}
