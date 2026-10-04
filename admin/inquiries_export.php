<?php
require_once __DIR__ . '/includes/auth.php';
require_admin_permission('customers.manage');

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM inquiries';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE name LIKE :q OR email LIKE :q OR service_requested LIKE :q';
    $params['q'] = '%' . $search . '%';
}
$sql .= ' ORDER BY created_at DESC';
$stmt = get_db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="gps-inquiries-' . date('Y-m-d') . '.csv"');

/**
 * Neutralize spreadsheet formula injection: if a field starts with a
 * character that Excel/Sheets treats as a formula trigger, prefix it
 * with a leading tab so it's imported as plain text.
 */
function csv_safe(?string $value): string
{
    $value = (string) $value;
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
        return "\t" . $value;
    }
    return $value;
}

$out = fopen('php://output', 'w');
fputcsv($out, ['Date', 'Name', 'Email', 'Phone', 'Service Requested', 'Message', 'Status']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['created_at'],
        csv_safe($r['name']),
        csv_safe($r['email']),
        csv_safe($r['phone']),
        csv_safe($r['service_requested']),
        csv_safe($r['message']),
        $r['status'],
    ]);
}
fclose($out);
exit;
