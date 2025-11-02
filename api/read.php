<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');
$entity = $_GET['entity'] ?? '';

if ($entity === 'jobs') {
    echo json_encode(fetchAllJobs());
    exit;
}

if ($entity === 'job') {
    $id = intval($_GET['id'] ?? 0);
    echo json_encode(getJobById($id));
    exit;
}

if ($entity === 'applications') {
    $job_id = intval($_GET['job_id'] ?? 0);
    echo json_encode(fetchApplicationsByJob($job_id));
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown entity']);
