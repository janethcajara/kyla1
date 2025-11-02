<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
$entity = $_REQUEST['entity'] ?? '';
if ($entity === 'job') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jsonResponse(['error' => 'Method not allowed']); }
    $id = intval($_POST['id'] ?? 0);
    $ok = updateJob($id, $_POST);
    jsonResponse(['success' => (bool)$ok]);
}

if ($entity === 'application') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jsonResponse(['error' => 'Method not allowed']); }
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!$id || !$status) { http_response_code(400); jsonResponse(['error' => 'id and status required']); }
    $ok = updateApplicationStatus($id, $status);
    jsonResponse(['success' => (bool)$ok]);
}

if ($entity === 'interview') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jsonResponse(['error' => 'Method not allowed']); }
    $id = intval($_POST['id'] ?? 0);
    $data = [
        'scheduled_at' => $_POST['scheduled_at'] ?? null,
        'status' => $_POST['status'] ?? null,
        'notes' => $_POST['notes'] ?? null
    ];
    if (!$id) { http_response_code(400); jsonResponse(['error' => 'id required']); }
    $ok = updateInterview($id, $data);
    jsonResponse(['success' => (bool)$ok]);
}

http_response_code(400);
jsonResponse(['error' => 'Unknown entity']);
