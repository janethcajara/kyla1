<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Allow both form-data and JSON
$entity = $_REQUEST['entity'] ?? '';

if ($entity === 'job') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jsonResponse(['error' => 'Method not allowed']); }
    $ok = createJob($_POST);
    jsonResponse(['success' => (bool)$ok]);
}

if ($entity === 'application') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jsonResponse(['error' => 'Method not allowed']); }

    // Check if user is logged in (for dashboard applications)
    $user = $_SESSION['user'] ?? null;
    if ($user) {
        // Logged-in user application
        $data = [
            'job_id' => intval($_POST['job_id'] ?? 0),
            'user_id' => $user['id'],
            'resume_id' => intval($_POST['resume_id'] ?? 0),
            'cover_letter' => $_POST['cover_letter'] ?? '',
            'experience_years' => $_POST['experience_years'] ?? null,
            'current_salary' => $_POST['current_salary'] ?? null,
            'expected_salary' => $_POST['expected_salary'] ?? null,
            'notice_period' => $_POST['notice_period'] ?? null,
            'additional_documents' => $_POST['additional_documents'] ?? null
        ];
    } else {
        // Anonymous application (original logic)
        $applicant_name = $_POST['applicant_name'] ?? 'Anonymous';
        $applicant_email = $_POST['applicant_email'] ?? '';

        if (!$applicant_email) {
            http_response_code(400);
            jsonResponse(['error' => 'Email is required']);
        }

        // Try to find existing user or create new one
        $user = getUserByEmail($applicant_email);
        if (!$user) {
            // Create a new user account with a random password (they can reset it later if needed)
            $random_password = bin2hex(random_bytes(8));
            $user_data = [
                'name' => $applicant_name,
                'email' => $applicant_email,
                'password' => $random_password,
                'role' => 'jobseeker'
            ];
            if (!registerUser($user_data)) {
                http_response_code(400);
                jsonResponse(['error' => 'Failed to create user account']);
            }
            $user = getUserByEmail($applicant_email);
        }

        if (!$user) {
            http_response_code(500);
            jsonResponse(['error' => 'Failed to process application']);
        }

        // Handle resume upload
        $resume_id = null;
        if (isset($_FILES['resume'])) {
            $resume_id = saveResumeFile($_FILES['resume'], $user['name'], $user['email']);
            if (!$resume_id) {
                http_response_code(400);
                jsonResponse(['error' => 'Resume upload failed']);
            }
        }

        // Create application with user_id
        $data = [
            'job_id' => intval($_POST['job_id'] ?? 0),
            'user_id' => $user['id'],
            'resume_id' => $resume_id,
            'cover_letter' => $_POST['cover_letter'] ?? '',
            'experience_years' => $_POST['experience_years'] ?? null,
            'current_salary' => $_POST['current_salary'] ?? null,
            'expected_salary' => $_POST['expected_salary'] ?? null,
            'notice_period' => $_POST['notice_period'] ?? null,
            'additional_documents' => $_POST['additional_documents'] ?? null
        ];
    }

    if (!$data['job_id']) {
        http_response_code(400);
        jsonResponse(['error' => 'Job ID is required']);
    }

    $ok = createApplication($data);
    jsonResponse(['success' => (bool)$ok]);
}

if ($entity === 'interview') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); jsonResponse(['error' => 'Method not allowed']); }
    // Expect application_id, scheduled_at, notes
    $data = [
        'application_id' => intval($_POST['application_id'] ?? 0),
        'scheduled_at' => $_POST['scheduled_at'] ?? null,
        'notes' => $_POST['notes'] ?? ''
    ];
    if (!$data['application_id'] || !$data['scheduled_at']) {
        http_response_code(400); jsonResponse(['error' => 'application_id and scheduled_at required']);
    }
    $ok = createInterview($data);
    jsonResponse(['success' => (bool)$ok]);
}

http_response_code(400);
jsonResponse(['error' => 'Unknown entity']);
