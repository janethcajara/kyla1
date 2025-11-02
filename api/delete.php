<?php
require_once __DIR__ . '/../functions.php';
$entity = $_REQUEST['entity'] ?? '';
if ($entity === 'job') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jsonResponse(['error' => 'Method not allowed']); }
    $id = intval($_POST['id'] ?? 0);
    $ok = deleteJob($id);
    jsonResponse(['success' => (bool)$ok]);
}

if ($entity === 'interview') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jsonResponse(['error' => 'Method not allowed']); }
    $id = intval($_POST['id'] ?? 0);
    $ok = deleteInterview($id);
    jsonResponse(['success' => (bool)$ok]);
}

http_response_code(400);
jsonResponse(['error' => 'Unknown entity']);
