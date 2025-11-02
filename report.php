<?php
require_once __DIR__ . '/functions.php';
session_start();
$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo "Forbidden - admin only.";
    exit;
}

// Stream CSV of applications
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="applications_report.csv"');
$out = fopen('php://output', 'w');
// header row
fputcsv($out, ['Application ID','Job Title','Applicant Name','Applicant Email','Status','Applied At','Resume Filename','Cover Letter']);

foreach (fetchAllApplications() as $a) {
    fputcsv($out, [
        $a['id'],
        $a['job_title'],
        $a['user_name'],
        $a['user_email'],
        $a['status'],
        $a['applied_at'],
        $a['filename'],
        $a['cover_letter']
    ]);
}
fclose($out);
exit;
