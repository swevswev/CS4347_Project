<?php
	declare(strict_types=1);
	require_once __DIR__ . '/includes/bootstrap.php';

	$report = $_GET['report'] ?? null;
	$data = [];
	$title = '';
$error = null;

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
		}
		elseif ($report === 'doctor_workload') {
				$title = 'Doctor Workload';
				$data = db()->query(
						'SELECT d.Doctor_Name AS doctor_name, COUNT(v.Visit_ID) AS total_visits
						FROM DOCTORS d
						LEFT JOIN VISITS v ON d.Doctor_ID = v.Doctor_ID
						GROUP BY d.Doctor_Name
						ORDER BY total_visits DESC'
				)->fetchAll();
		}
		elseif ($report === 'procedure_cost') {
				$title = 'Procedure Cost Analysis';
				$data = db()->query(
						'SELECT Procedure_Name AS procedure_name, AVG(Cost) AS avg_cost
						FROM VISITS
						GROUP BY Procedure_Name
						ORDER BY avg_cost DESC'
				)->fetchAll();
		}
		elseif ($report === 'common_conditions') {
				$title = 'Most Common Conditions';
				$data = db()->query(
						'SELECT c.Condition_Name AS condition_name, COUNT(*) AS occurrences
						FROM VISITS_CONDITIONS vc
						JOIN CONDITIONS c ON vc.Condition_ID = c.Condition_ID
						GROUP BY c.Condition_Name
						ORDER BY occurrences DESC'
				)->fetchAll();
		}
		elseif ($report === 'doctors_by_dept') {
				$title = 'Doctors by Department';
				$data = db()->query(
						'SELECT d.Doctor_Name AS doctor_name, dep.Department_Name AS department_name
						FROM DOCTORS d
						JOIN DEPARTMENTS dep ON d.Department_ID = dep.Department_ID
						ORDER BY dep.Department_Name, d.Doctor_Name'
				)->fetchAll();
		}
		elseif ($report === 'patient_history') {
				$title = 'Patient History';
				$pid = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
				if ($pid === false) {
					$error = 'Patient ID must be a positive integer.';
				} else {
					$stmt = db()->prepare(
							'SELECT v.Visit_ID AS visit_id, d.Doctor_Name AS doctor_name,
											GROUP_CONCAT(DISTINCT c.Condition_Name ORDER BY c.Condition_Name SEPARATOR ", ") AS condition_names,
											v.Procedure_Name AS procedure_name, v.Cost AS cost, v.Length_of_Stay AS length_of_stay,
											v.Satisfaction AS satisfaction, v.Outcome AS outcome, v.Re_Admission AS re_admission
							FROM VISITS v
							LEFT JOIN DOCTORS d ON d.Doctor_ID = v.Doctor_ID
							LEFT JOIN VISITS_CONDITIONS vc ON vc.Visit_ID = v.Visit_ID
							LEFT JOIN CONDITIONS c ON c.Condition_ID = vc.Condition_ID
							WHERE v.Patient_ID = ?
							GROUP BY v.Visit_ID, d.Doctor_Name, v.Procedure_Name, v.Cost, v.Length_of_Stay, v.Satisfaction, v.Outcome, v.Re_Admission
							ORDER BY v.Visit_ID DESC'
						);
					$stmt->execute([$pid]);
					$data = $stmt->fetchAll();
				}
		}
		elseif ($report !== null) {
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
		<title>Analytics Dashboard</title>

		<style>
			:root {
				--bg: #f4f6f8;
				--card: #fff;
				--border: #d0d7de;
				--text: #1f2328;
				--muted: #59636e;
				--accent: #0969da;
			}
			body {
				margin: 0;
				font-family: system-ui;
				background: var(--bg);
				padding: 1.5rem;
			}
			main {
				max-width: 52rem;
				margin: auto;
				background: var(--card);
				padding: 1.5rem;
				border-radius: 8px;
				border: 1px solid var(--border);
			}
			button {
				padding: 0.5rem 1rem;
				border-radius: 6px;
				border: 1px solid var(--border);
				background: var(--accent);
				color: white;
				cursor: pointer;
			}
			.actions {
				display: flex;
				gap: 0.5rem;
				flex-wrap: wrap;
				margin-bottom: 1rem;
			}
			table {
				width: 100%;
				border-collapse: collapse;
				margin-top: 1rem;
			}
			th, td {
				border: 1px solid var(--border);
				padding: 0.4rem;
			}
			th 	{
				background: #f6f8fa;
			}
			.banner {
				padding: 0.75rem 1rem;
				border-radius: 6px;
				margin-top: 1rem;
				font-size: 0.9rem;
			}
			.banner-error {
				background: #ffebe9;
				border: 1px solid #ff8182;
				color: #82071e;
			}
		</style>
	</head>
	<body>
		<main>
			<p><a href="index.php">← Home</a></p>
			<h1>Analytics Dashboard</h1>

			<div class="actions">
				<a href="?report=avg_cost"><button>Avg Cost</button></a>
				<a href="?report=doctor_workload"><button>Doctor Workload</button></a>
				<a href="?report=procedure_cost"><button>Procedures</button></a>
				<a href="?report=common_conditions"><button>Common Conditions</button></a>
				<a href="?report=doctors_by_dept"><button>Doctors by Dept</button></a>
			</div>

			<!-- Optional patient history -->
			<form method="get" style="margin-top:1rem">
				<input type="hidden" name="report" value="patient_history">
				<input type="number" name="patient_id" placeholder="Patient ID" min="1" step="1" required>
				<button type="submit">Patient History</button>
			</form>

			<?php if ($report !== null): ?>
				<h2><?= htmlspecialchars($title) ?></h2>
			<?php if ($error !== null): ?>
				<div class="banner banner-error"><?= htmlspecialchars($error) ?></div>
			<?php elseif (empty($data)): ?>
				<p>No results found.</p>
			<?php else: ?>

			<table>
				<thead>
					<tr>
						<?php foreach (array_keys($data[0]) as $col): ?>
							<th><?= htmlspecialchars($col) ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($data as $row): ?>
						<tr>
							<?php foreach ($row as $val): ?>
								<td><?= htmlspecialchars((string)$val) ?></td>
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