<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../AdminPanel.php');
}

$form = trim((string) ($_POST['admin_form'] ?? ''));
if ($form === '') {
    redirect('../AdminPanel.php?error=' . urlencode('Missing form identifier.'));
}

function failAdmin(string $msg): never
{
    redirect('../AdminPanel.php?error=' . urlencode($msg));
}

function okAdmin(string $msg): never
{
    redirect('../AdminPanel.php?ok=' . urlencode($msg));
}

$pdo = db();

try {
    switch ($form) {
        case 'add_department':
            $name = trim((string) ($_POST['dept_name'] ?? ''));
            if ($name === '' || strlen($name) > 100) {
                failAdmin('Department name is required (max 100 characters).');
            }
            $loc = trim((string) ($_POST['dept_location'] ?? ''));
            $loc = $loc === '' ? null : (strlen($loc) > 100 ? substr($loc, 0, 100) : $loc);
            $pdo->prepare('INSERT INTO departments (dept_name, dept_location) VALUES (?, ?)')->execute([$name, $loc]);
            okAdmin('Department saved.');
            break;

        case 'remove_department':
            $id = filter_var($_POST['remove_department_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                failAdmin('Valid department ID is required.');
            }
            $pdo->prepare('DELETE FROM departments WHERE department_id = ?')->execute([$id]);
            okAdmin('Department removed (if it existed and no doctors referenced it).');
            break;

        case 'add_doctor':
            $dname = trim((string) ($_POST['doctor_name'] ?? ''));
            if ($dname === '' || strlen($dname) > 100) {
                failAdmin('Doctor name is required (max 100 characters).');
            }
            $spec = trim((string) ($_POST['specialization'] ?? ''));
            $spec = $spec === '' ? null : (strlen($spec) > 100 ? substr($spec, 0, 100) : $spec);
            $deptId = filter_var($_POST['doctor_department_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($deptId === false) {
                failAdmin('Select a valid department.');
            }
            $chk = $pdo->prepare('SELECT 1 FROM departments WHERE department_id = ?');
            $chk->execute([$deptId]);
            if (!$chk->fetchColumn()) {
                failAdmin('Department does not exist.');
            }
            $pdo->prepare(
                'INSERT INTO doctors (doctor_name, specialization, department_id) VALUES (?, ?, ?)'
            )->execute([$dname, $spec, $deptId]);
            okAdmin('Doctor saved. New doctor ID: ' . (int) $pdo->lastInsertId() . '.');
            break;

        case 'remove_doctor':
            $id = filter_var($_POST['remove_doctor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                failAdmin('Valid doctor ID is required.');
            }
            $pdo->prepare('DELETE FROM doctors WHERE doctor_id = ?')->execute([$id]);
            okAdmin('Doctor removed (if not referenced by visits or primary-care assignments).');
            break;

        case 'add_condition':
            $cname = trim((string) ($_POST['condition_name'] ?? ''));
            if ($cname === '' || strlen($cname) > 100) {
                failAdmin('Condition name is required (max 100 characters).');
            }
            $pdo->prepare('INSERT INTO conditions (condition_name) VALUES (?)')->execute([$cname]);
            okAdmin('Condition saved. New condition ID: ' . (int) $pdo->lastInsertId() . '.');
            break;

        case 'remove_condition':
            $id = filter_var($_POST['remove_condition_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                failAdmin('Valid condition ID is required.');
            }
            $pdo->prepare('DELETE FROM conditions WHERE condition_id = ?')->execute([$id]);
            okAdmin('Condition removed (visits using it will have condition cleared).');
            break;

        default:
            failAdmin('Unknown admin action.');
    }
} catch (PDOException $e) {
    $code = (int) ($e->errorInfo[1] ?? 0);
    if ($code === 1451 || $code === 1452) {
        failAdmin('Database refused the change: another record still references this row (foreign key).');
    }
    if ($code === 1062) {
        failAdmin('Duplicate entry (e.g. condition name already exists).');
    }
    failAdmin('Database error. If you are developing locally, enable error logging to see details.');
}
