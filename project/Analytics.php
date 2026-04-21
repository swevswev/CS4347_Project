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
						'SELECT c.condition_name, AVG(v.cost) AS avg_cost
						FROM visits v
						JOIN visits_conditions vc ON vc.visit_id = v.visit_id
						JOIN conditions c ON vc.condition_id = c.condition_id
						GROUP BY c.condition_name
						ORDER BY avg_cost DESC'
				)->fetchAll();
		}
		elseif ($report === 'doctor_workload') {
				$title = 'Doctor Workload';
				$data = db()->query(
						'SELECT d.doctor_name, COUNT(v.visit_id) AS total_visits
						FROM doctors d
						LEFT JOIN visits v ON d.doctor_id = v.doctor_id
						GROUP BY d.doctor_name
						ORDER BY total_visits DESC'
				)->fetchAll();
		}
		elseif ($report === 'procedure_cost') {
				$title = 'Procedure Cost Analysis';
				$data = db()->query(
						'SELECT procedure_name, AVG(cost) AS avg_cost
						FROM visits
						GROUP BY procedure_name
						ORDER BY avg_cost DESC'
				)->fetchAll();
		}
		elseif ($report === 'common_conditions') {
				$title = 'Most Common Conditions';
				$data = db()->query(
						'SELECT c.condition_name, COUNT(*) AS occurrences
						FROM visits_conditions vc
						JOIN conditions c ON vc.condition_id = c.condition_id
						GROUP BY c.condition_name
						ORDER BY occurrences DESC'
				)->fetchAll();
		}
		elseif ($report === 'doctors_by_dept') {
				$title = 'Doctors by Department';
				$data = db()->query(
						'SELECT d.doctor_name, dep.department_name
						FROM doctors d
						JOIN departments dep ON d.department_id = dep.department_id
						ORDER BY dep.department_name, d.doctor_name'
				)->fetchAll();
		}
		elseif ($report === 'patient_history') {
				$title = 'Patient History';
				$pid = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
				if ($pid === false) {
					$error = 'Patient ID must be a positive integer.';
				} else {
					$stmt = db()->prepare(
							'SELECT v.visit_id, d.doctor_name,
											GROUP_CONCAT(DISTINCT c.condition_name ORDER BY c.condition_name SEPARATOR ", ") AS condition_names,
											v.procedure_name, v.cost, v.length_of_stay, v.satisfaction, v.outcome, v.re_admission
							FROM visits v
							LEFT JOIN doctors d ON d.doctor_id = v.doctor_id
							LEFT JOIN visits_conditions vc ON vc.visit_id = v.visit_id
							LEFT JOIN conditions c ON c.condition_id = vc.condition_id
							WHERE v.patient_id = ?
							GROUP BY v.visit_id, d.doctor_name, v.procedure_name, v.cost, v.length_of_stay, v.satisfaction, v.outcome, v.re_admission
							ORDER BY v.visit_id DESC'
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