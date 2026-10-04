<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$notice = '';
$errors = [];

function operations_request_string(string $key, int $max = 255): string
{
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'create_employee') {
                $name = operations_request_string('full_name', 150);
                $number = operations_request_string('employee_number', 30);
                if ($name === '' || $number === '') throw new InvalidArgumentException('Employee name and number are required.');
                $stmt = $db->prepare('INSERT INTO employees (employee_number, full_name, phone, email, position, department, location, date_joined, training_status) VALUES (:number,:name,:phone,:email,:position,:department,:location,:joined,:training)');
                $stmt->execute(['number' => $number, 'name' => $name, 'phone' => operations_request_string('phone', 40), 'email' => operations_request_string('email', 150), 'position' => $_POST['position'] ?? 'Security Guard', 'department' => $_POST['department'] ?? 'Security', 'location' => operations_request_string('location', 120), 'joined' => $_POST['date_joined'] ?: null, 'training' => $_POST['training_status'] ?? 'Not Started']);
                audit_log('employee.created', 'employees', (string) $db->lastInsertId());
                $notice = 'Employee added.';
            } elseif ($action === 'create_site') {
                $name = operations_request_string('site_name', 150);
                $address = operations_request_string('address', 255);
                if ($name === '' || $address === '') throw new InvalidArgumentException('Site name and address are required.');
                $stmt = $db->prepare('INSERT INTO security_sites (site_name, client_name, address, latitude, longitude, site_status, notes) VALUES (:name,:client,:address,:latitude,:longitude,:status,:notes)');
                $stmt->execute(['name' => $name, 'client' => operations_request_string('client_name', 150), 'address' => $address, 'latitude' => $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null, 'longitude' => $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null, 'status' => $_POST['site_status'] ?? 'Operational', 'notes' => operations_request_string('site_notes', 2000)]);
                audit_log('site.created', 'security_sites', (string) $db->lastInsertId());
                $notice = 'Security site added.';
            } elseif ($action === 'create_shift') {
                $stmt = $db->prepare('INSERT INTO employee_shifts (site_id, employee_id, shift_date, shift_type, start_time, end_time, supervisor_id, status, notes) VALUES (:site,:employee,:date,:type,:start,:end,:supervisor,:status,:notes)');
                $stmt->execute(['site' => (int) $_POST['site_id'], 'employee' => (int) $_POST['employee_id'], 'date' => $_POST['shift_date'], 'type' => $_POST['shift_type'] ?? 'Day', 'start' => $_POST['start_time'], 'end' => $_POST['end_time'], 'supervisor' => !empty($_POST['supervisor_id']) ? (int) $_POST['supervisor_id'] : null, 'status' => $_POST['shift_status'] ?? 'Planned', 'notes' => operations_request_string('shift_notes', 2000)]);
                audit_log('shift.created', 'employee_shifts', (string) $db->lastInsertId());
                $notice = 'Shift scheduled.';
            } elseif ($action === 'attendance') {
                $shiftId = (int) $_POST['shift_id'];
                $shift = $db->prepare('SELECT employee_id, site_id FROM employee_shifts WHERE id = :id');
                $shift->execute(['id' => $shiftId]);
                $shiftRow = $shift->fetch();
                if (!$shiftRow) throw new InvalidArgumentException('Shift not found.');
                $stmt = $db->prepare('INSERT INTO employee_attendance (shift_id, employee_id, site_id, clocked_in_at, attendance_status, location_consent, notes) VALUES (:shift,:employee,:site,NOW(),\'On Duty\',:consent,:notes) ON DUPLICATE KEY UPDATE clocked_in_at = COALESCE(clocked_in_at, NOW()), attendance_status = \'On Duty\', location_consent = VALUES(location_consent), notes = VALUES(notes)');
                $stmt->execute(['shift' => $shiftId, 'employee' => $shiftRow['employee_id'], 'site' => $shiftRow['site_id'], 'consent' => isset($_POST['location_consent']) ? 1 : 0, 'notes' => operations_request_string('attendance_notes', 500)]);
                $db->prepare('UPDATE employee_shifts SET status = \'In Progress\' WHERE id = :id')->execute(['id' => $shiftId]);
                audit_log('attendance.clocked_in', 'employee_shifts', (string) $shiftId);
                $notice = 'Attendance recorded as on duty.';
            } elseif ($action === 'create_incident') {
                $number = 'INC-' . date('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
                $stmt = $db->prepare('INSERT INTO incident_reports (incident_number, site_id, employee_id, incident_date, incident_time, category, severity, description) VALUES (:number,:site,:employee,:date,:time,:category,:severity,:description)');
                $stmt->execute(['number' => $number, 'site' => !empty($_POST['incident_site_id']) ? (int) $_POST['incident_site_id'] : null, 'employee' => !empty($_POST['incident_employee_id']) ? (int) $_POST['incident_employee_id'] : null, 'date' => $_POST['incident_date'], 'time' => $_POST['incident_time'], 'category' => $_POST['category'], 'severity' => $_POST['severity'], 'description' => operations_request_string('description', 5000)]);
                $incidentId = (int) $db->lastInsertId();
                $timeline = $db->prepare('INSERT INTO incident_timeline (incident_id, actor_admin_id, status, note) VALUES (:incident,:actor,\'Reported\',:note)');
                $timeline->execute(['incident' => $incidentId, 'actor' => $_SESSION['admin_id'], 'note' => 'Incident report created.']);
                audit_log('incident.created', 'incident_reports', (string) $incidentId, null, ['severity' => $_POST['severity'], 'incident_number' => $number]);
                $notice = 'Incident ' . $number . ' reported.';
            } elseif ($action === 'incident_status') {
                $incidentId = (int) $_POST['incident_id'];
                $status = $_POST['incident_status'] ?? 'Reported';
                $db->beginTransaction();
                $db->prepare('UPDATE incident_reports SET status = :status WHERE id = :id')->execute(['status' => $status, 'id' => $incidentId]);
                $db->prepare('INSERT INTO incident_timeline (incident_id, actor_admin_id, status, note) VALUES (:incident,:actor,:status,:note)')->execute(['incident' => $incidentId, 'actor' => $_SESSION['admin_id'], 'status' => $status, 'note' => operations_request_string('status_note', 1000) ?: 'Status updated.']);
                $db->commit();
                audit_log('incident.status_changed', 'incident_reports', (string) $incidentId, null, ['status' => $status]);
                $notice = 'Incident status updated.';
            } elseif ($action === 'create_cleaning_job') {
                $jobNumber = 'CLN-' . date('Y') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
                $stmt = $db->prepare('INSERT INTO cleaning_jobs (job_number, client_name, location, start_at, end_at, team_size, supervisor_id, status, notes) VALUES (:number,:client,:location,:start,:end,:team,:supervisor,:status,:notes)');
                $stmt->execute(['number' => $jobNumber, 'client' => operations_request_string('job_client', 150), 'location' => operations_request_string('job_location', 255), 'start' => str_replace('T', ' ', $_POST['job_start']), 'end' => !empty($_POST['job_end']) ? str_replace('T', ' ', $_POST['job_end']) : null, 'team' => max(1, min(100, (int) $_POST['team_size'])), 'supervisor' => !empty($_POST['job_supervisor_id']) ? (int) $_POST['job_supervisor_id'] : null, 'status' => $_POST['job_status'] ?? 'Planned', 'notes' => operations_request_string('job_notes', 2000)]);
                $jobId = (int) $db->lastInsertId();
                $taskStmt = $db->prepare('INSERT INTO cleaning_tasks (cleaning_job_id, task_name) VALUES (:job,:task)');
                foreach (preg_split('/\r?\n/', operations_request_string('tasks', 2000)) as $task) {
                    $task = trim($task);
                    if ($task !== '') $taskStmt->execute(['job' => $jobId, 'task' => mb_substr($task, 0, 150)]);
                }
                audit_log('cleaning_job.created', 'cleaning_jobs', (string) $jobId);
                $notice = 'Cleaning job ' . $jobNumber . ' created.';
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $errors[] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'The operation could not be saved. Check the fields and try again.';
        }
    }
}

$employees = $db->query('SELECT * FROM employees ORDER BY full_name')->fetchAll();
$sites = $db->query('SELECT * FROM security_sites ORDER BY site_name')->fetchAll();
$shifts = $db->query('SELECT es.*, e.full_name, s.site_name, a.attendance_status, a.clocked_in_at FROM employee_shifts es INNER JOIN employees e ON e.id = es.employee_id INNER JOIN security_sites s ON s.id = es.site_id LEFT JOIN employee_attendance a ON a.shift_id = es.id ORDER BY es.shift_date DESC, es.start_time DESC LIMIT 50')->fetchAll();
$incidents = $db->query('SELECT ir.*, s.site_name, e.full_name FROM incident_reports ir LEFT JOIN security_sites s ON s.id = ir.site_id LEFT JOIN employees e ON e.id = ir.employee_id ORDER BY ir.created_at DESC LIMIT 50')->fetchAll();
$jobs = $db->query('SELECT cj.*, e.full_name AS supervisor_name FROM cleaning_jobs cj LEFT JOIN employees e ON e.id = cj.supervisor_id ORDER BY cj.start_at DESC LIMIT 50')->fetchAll();
?>
<div class="admin-topbar"><h1>Operations</h1></div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= count($employees) ?></div><div class="lbl">Employees</div></div>
  <div class="stat-card"><div class="num"><?= count($sites) ?></div><div class="lbl">Security Sites</div></div>
  <div class="stat-card"><div class="num"><?= count(array_filter($incidents, static fn(array $row): bool => in_array($row['severity'], ['HIGH','CRITICAL'], true) && !in_array($row['status'], ['Resolved','Closed'], true))) ?></div><div class="lbl">Priority Incidents</div></div>
  <div class="stat-card"><div class="num"><?= count($jobs) ?></div><div class="lbl">Cleaning Jobs</div></div>
</div>

<div class="operations-grid">
  <section class="admin-panel"><h2>Add Employee</h2><form method="post" class="form-grid"><input type="hidden" name="action" value="create_employee"><?= csrf_field() ?><div class="form-row"><div><label>Employee ID</label><input name="employee_number" required maxlength="30" placeholder="GPS-0042"></div><div><label>Full name</label><input name="full_name" required maxlength="150"></div></div><div class="form-row"><div><label>Phone</label><input name="phone" maxlength="40"></div><div><label>Email</label><input name="email" type="email" maxlength="150"></div></div><div class="form-row"><div><label>Position</label><select name="position"><option>Security Guard</option><option>Security Supervisor</option><option>Cleaner</option><option>Cleaning Supervisor</option><option>Manager</option><option>Administrator</option></select></div><div><label>Department</label><select name="department"><option>Security</option><option>Cleaning</option><option>Administration</option></select></div></div><div class="form-row"><div><label>Location</label><input name="location" maxlength="120"></div><div><label>Date joined</label><input name="date_joined" type="date"></div></div><button class="btn btn-dark">Add employee</button></form></section>
  <section class="admin-panel"><h2>Add Security Site</h2><form method="post" class="form-grid"><input type="hidden" name="action" value="create_site"><?= csrf_field() ?><div class="form-row"><div><label>Site name</label><input name="site_name" required maxlength="150"></div><div><label>Client name</label><input name="client_name" maxlength="150"></div></div><div><label>Address</label><input name="address" required maxlength="255"></div><div class="form-row"><div><label>Latitude</label><input name="latitude" type="number" step="0.0000001"></div><div><label>Longitude</label><input name="longitude" type="number" step="0.0000001"></div></div><div><label>Notes</label><textarea name="site_notes" maxlength="2000"></textarea></div><button class="btn btn-dark">Add site</button></form></section>
</div>

<div class="operations-grid">
  <section class="admin-panel"><h2>Schedule Shift</h2><form method="post" class="form-grid"><input type="hidden" name="action" value="create_shift"><?= csrf_field() ?><div class="form-row"><div><label>Site</label><select name="site_id" required><?php foreach ($sites as $site): ?><option value="<?= (int) $site['id'] ?>"><?= e($site['site_name']) ?></option><?php endforeach; ?></select></div><div><label>Employee</label><select name="employee_id" required><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['id'] ?>"><?= e($employee['full_name']) ?></option><?php endforeach; ?></select></div></div><div class="form-row"><div><label>Date</label><input name="shift_date" type="date" required value="<?= e(date('Y-m-d')) ?>"></div><div><label>Shift type</label><select name="shift_type"><option>Day</option><option>Night</option><option>Custom</option></select></div></div><div class="form-row"><div><label>Start</label><input name="start_time" type="time" required></div><div><label>End</label><input name="end_time" type="time" required></div></div><div><label>Supervisor</label><select name="supervisor_id"><option value="">Not assigned</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['id'] ?>"><?= e($employee['full_name']) ?></option><?php endforeach; ?></select></div><button class="btn btn-dark">Schedule shift</button></form></section>
  <section class="admin-panel"><h2>Report Incident</h2><form method="post" class="form-grid"><input type="hidden" name="action" value="create_incident"><?= csrf_field() ?><div class="form-row"><div><label>Site</label><select name="incident_site_id"><option value="">Unknown / not assigned</option><?php foreach ($sites as $site): ?><option value="<?= (int) $site['id'] ?>"><?= e($site['site_name']) ?></option><?php endforeach; ?></select></div><div><label>Employee</label><select name="incident_employee_id"><option value="">Not assigned</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['id'] ?>"><?= e($employee['full_name']) ?></option><?php endforeach; ?></select></div></div><div class="form-row"><div><label>Date</label><input name="incident_date" type="date" required value="<?= e(date('Y-m-d')) ?>"></div><div><label>Time</label><input name="incident_time" type="time" required value="<?= e(date('H:i')) ?>"></div></div><div class="form-row"><div><label>Category</label><select name="category"><option>Unauthorized Access</option><option>Theft</option><option>Suspicious Activity</option><option>Property Damage</option><option>Fire</option><option>Medical Emergency</option><option>Workplace Incident</option><option>Security Breach</option><option>Other</option></select></div><div><label>Severity</label><select name="severity"><option>LOW</option><option>MEDIUM</option><option>HIGH</option><option>CRITICAL</option></select></div></div><div><label>Description</label><textarea name="description" required maxlength="5000"></textarea></div><button class="btn btn-dark">Report incident</button></form></section>
</div>

<section class="admin-panel"><h2>Create Cleaning Job</h2><form method="post" class="form-grid"><input type="hidden" name="action" value="create_cleaning_job"><?= csrf_field() ?><div class="form-row"><div><label>Client</label><input name="job_client" required maxlength="150"></div><div><label>Location</label><input name="job_location" required maxlength="255"></div></div><div class="form-row"><div><label>Start</label><input name="job_start" type="datetime-local" required></div><div><label>End</label><input name="job_end" type="datetime-local"></div></div><div class="form-row"><div><label>Team size</label><input name="team_size" type="number" min="1" max="100" value="1"></div><div><label>Supervisor</label><select name="job_supervisor_id"><option value="">Not assigned</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['id'] ?>"><?= e($employee['full_name']) ?></option><?php endforeach; ?></select></div></div><div><label>Checklist tasks</label><textarea name="tasks" placeholder="Floors&#10;Windows&#10;Bathrooms&#10;Waste disposal"></textarea></div><button class="btn btn-dark">Create cleaning job</button></form></section>

<section class="admin-panel"><h2>Scheduled Shifts</h2><div class="table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>Site</th><th>Employee</th><th>Shift</th><th>Status</th><th>Attendance</th><th>Action</th></tr></thead><tbody><?php foreach ($shifts as $shift): ?><tr><td><?= e($shift['shift_date']) ?></td><td><?= e($shift['site_name']) ?></td><td><?= e($shift['full_name']) ?></td><td><?= e(substr($shift['start_time'], 0, 5)) ?>–<?= e(substr($shift['end_time'], 0, 5)) ?></td><td><?= e($shift['status']) ?></td><td><?= e($shift['attendance_status'] ?: 'Not Started') ?></td><td><?php if (!$shift['clocked_in_at']): ?><form method="post"><input type="hidden" name="action" value="attendance"><?= csrf_field() ?><input type="hidden" name="shift_id" value="<?= (int) $shift['id'] ?>"><button class="icon-btn" type="submit">Clock in</button></form><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$shifts): ?><tr><td colspan="7">No shifts scheduled.</td></tr><?php endif; ?></tbody></table></div></section>

<section class="admin-panel"><h2>Incident Register</h2><div class="table-wrap"><table class="admin-table"><thead><tr><th>Incident</th><th>Date</th><th>Site</th><th>Severity</th><th>Status</th><th>Update</th></tr></thead><tbody><?php foreach ($incidents as $incident): ?><tr><td><strong><?= e($incident['incident_number']) ?></strong><br><?= e($incident['category']) ?><br><small><?= e($incident['description']) ?></small></td><td><?= e($incident['incident_date']) ?><br><?= e(substr($incident['incident_time'], 0, 5)) ?></td><td><?= e($incident['site_name'] ?: 'Unassigned') ?></td><td><span class="badge badge-<?= strtolower($incident['severity']) ?>"><?= e($incident['severity']) ?></span></td><td><?= e($incident['status']) ?></td><td><form method="post"><input type="hidden" name="action" value="incident_status"><?= csrf_field() ?><input type="hidden" name="incident_id" value="<?= (int) $incident['id'] ?>"><select name="incident_status"><option>Under Investigation</option><option>Escalated</option><option>Resolved</option><option>Closed</option></select><input name="status_note" placeholder="Timeline note"><button class="icon-btn" type="submit">Update</button></form></td></tr><?php endforeach; ?><?php if (!$incidents): ?><tr><td colspan="6">No incidents reported.</td></tr><?php endif; ?></tbody></table></div></section>

<section class="admin-panel"><h2>Cleaning Jobs</h2><div class="table-wrap"><table class="admin-table"><thead><tr><th>Job</th><th>Client</th><th>Location</th><th>Schedule</th><th>Team</th><th>Status</th></tr></thead><tbody><?php foreach ($jobs as $job): ?><tr><td><?= e($job['job_number']) ?></td><td><?= e($job['client_name']) ?></td><td><?= e($job['location']) ?></td><td><?= e(date('d M Y H:i', strtotime($job['start_at']))) ?></td><td><?= (int) $job['team_size'] ?><?= $job['supervisor_name'] ? '<br>' . e($job['supervisor_name']) : '' ?></td><td><?= e($job['status']) ?></td></tr><?php endforeach; ?><?php if (!$jobs): ?><tr><td colspan="6">No cleaning jobs created.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
