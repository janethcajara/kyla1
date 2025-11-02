<?php
require_once __DIR__ . '/db_connect.php';

// Simple sanitizer
function sanitize($v) {
    if (is_string($v)) return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
    return $v;
}

// JOBS
function fetchAllJobs() {
    $db = connectDB();
    $stmt = $db->query('SELECT * FROM jobs ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function getJobById($id) {
    $db = connectDB();
    $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createJob($data) {
    $db = connectDB();
    $stmt = $db->prepare('INSERT INTO jobs (title, company, location, type, category, description, requirements, responsibilities, salary_min, salary_max, deadline, vacancies, posted_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    return $stmt->execute([
        sanitize($data['title']),
        sanitize($data['company']),
        sanitize($data['location']),
        sanitize($data['type']),
        sanitize($data['category']),
        sanitize($data['description']),
        sanitize($data['requirements']),
        sanitize($data['responsibilities']),
        $data['salary_min'] ?? null,
        $data['salary_max'] ?? null,
        $data['deadline'],
        $data['vacancies'] ?? 1,
        $data['posted_by']
    ]);
}

function updateJob($id, $data) {
    $db = connectDB();
    $stmt = $db->prepare('UPDATE jobs SET title=?, company=?, location=?, description=? WHERE id=?');
    return $stmt->execute([sanitize($data['title']), sanitize($data['company']), sanitize($data['location']), sanitize($data['description']), $id]);
}

function deleteJob($id) {
    $db = connectDB();
    $stmt = $db->prepare('DELETE FROM jobs WHERE id=?');
    return $stmt->execute([$id]);
}

// RESUMES & APPLICATIONS
function saveResumeFile($file, $user_name = null, $user_email = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return false;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $name;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    
    // Insert into resumes table with provided user details
    $db = connectDB();
    $stmt = $db->prepare('INSERT INTO resumes (user_name, user_email, filename, filepath, uploaded_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([
        sanitize($user_name),
        sanitize($user_email),
        sanitize($file['name']),
        $name
    ]);
    return $db->lastInsertId();
}

function createApplication($data) {
    $db = connectDB();
    $stmt = $db->prepare('
        INSERT INTO applications (
            job_id, user_id, resume_id, cover_letter, 
            experience_years, current_salary, expected_salary,
            notice_period, additional_documents, status,
            applied_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW()
        )
    ');
    
    return $stmt->execute([
        $data['job_id'],
        $data['user_id'],
        $data['resume_id'] ?? null,
        sanitize($data['cover_letter'] ?? ''),
        $data['experience_years'] ?? null,
        $data['current_salary'] ?? null,
        $data['expected_salary'] ?? null,
        sanitize($data['notice_period'] ?? null),
        sanitize($data['additional_documents'] ?? null)
    ]);
}

function fetchApplicationsByJob($job_id) {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT 
            a.*, 
            u.name as applicant_name,
            u.email as applicant_email,
            r.filename, 
            r.filepath 
        FROM applications a 
        JOIN users u ON a.user_id = u.id
        LEFT JOIN resumes r ON a.resume_id = r.id 
        WHERE a.job_id = ? 
        ORDER BY a.applied_at DESC
    ');
    $stmt->execute([$job_id]);
    return $stmt->fetchAll();
}

function fetchAllApplications() {
    $db = connectDB();
    $stmt = $db->query('
        SELECT 
            a.*, 
            j.title AS job_title,
            u.name as applicant_name,
            u.email as applicant_email,
            r.filename,
            r.filepath 
        FROM applications a 
        LEFT JOIN jobs j ON a.job_id = j.id 
        JOIN users u ON a.user_id = u.id
        LEFT JOIN resumes r ON a.resume_id = r.id 
        ORDER BY a.applied_at DESC
    ');
    return $stmt->fetchAll();
}

function getApplicationById($id) {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT 
            a.*, 
            j.title AS job_title,
            u.name as applicant_name,
            u.email as applicant_email,
            r.filename,
            r.filepath 
        FROM applications a 
        LEFT JOIN jobs j ON a.job_id = j.id 
        JOIN users u ON a.user_id = u.id
        LEFT JOIN resumes r ON a.resume_id = r.id 
        WHERE a.id = ?
    ');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateApplicationStatus($id, $status) {
    $allowed = ['pending','approved','rejected'];
    if (!in_array($status, $allowed)) return false;
    $db = connectDB();
    $stmt = $db->prepare('UPDATE applications SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

// Interviews
function createInterview($data) {
    $db = connectDB();
    $stmt = $db->prepare('
        INSERT INTO interviews (
            application_id, interviewer_id, type,
            location, scheduled_at, duration_minutes,
            status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    return $stmt->execute([
        $data['application_id'],
        $data['interviewer_id'] ?? null,
        $data['type'] ?? 'in-person',
        sanitize($data['location'] ?? null),
        $data['scheduled_at'],
        $data['duration_minutes'] ?? 60,
        $data['status'] ?? 'scheduled',
        sanitize($data['notes'] ?? null)
    ]);
}

function fetchInterviewsByApplication($application_id) {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT 
            i.*,
            u.name as applicant_name,
            u.email as applicant_email,
            j.title as job_title,
            ui.name as interviewer_name
        FROM interviews i 
        JOIN applications a ON i.application_id = a.id 
        JOIN users u ON a.user_id = u.id
        JOIN jobs j ON a.job_id = j.id 
        LEFT JOIN users ui ON i.interviewer_id = ui.id
        WHERE i.application_id = ? 
        ORDER BY i.scheduled_at DESC
    ');
    $stmt->execute([$application_id]);
    return $stmt->fetchAll();
}

function fetchAllInterviews() {
    $db = connectDB();
    $stmt = $db->query('
        SELECT 
            i.*,
            u.name as applicant_name,
            u.email as applicant_email,
            j.title as job_title,
            ui.name as interviewer_name
        FROM interviews i 
        JOIN applications a ON i.application_id = a.id 
        JOIN users u ON a.user_id = u.id
        JOIN jobs j ON a.job_id = j.id 
        LEFT JOIN users ui ON i.interviewer_id = ui.id
        ORDER BY i.scheduled_at DESC
    ');
    return $stmt->fetchAll();
}

function updateInterview($id, $data) {
    $db = connectDB();
    $updates = [];
    $params = [];

    // Build dynamic update query based on provided data
    if (isset($data['scheduled_at'])) {
        $updates[] = 'scheduled_at = ?';
        $params[] = $data['scheduled_at'];
    }
    if (isset($data['status'])) {
        $updates[] = 'status = ?';
        $params[] = $data['status'];
    }
    if (isset($data['notes'])) {
        $updates[] = 'notes = ?';
        $params[] = sanitize($data['notes']);
    }
    if (isset($data['interviewer_id'])) {
        $updates[] = 'interviewer_id = ?';
        $params[] = $data['interviewer_id'];
    }
    if (isset($data['type'])) {
        $updates[] = 'type = ?';
        $params[] = $data['type'];
    }
    if (isset($data['location'])) {
        $updates[] = 'location = ?';
        $params[] = sanitize($data['location']);
    }
    if (isset($data['duration_minutes'])) {
        $updates[] = 'duration_minutes = ?';
        $params[] = $data['duration_minutes'];
    }
    if (isset($data['feedback'])) {
        $updates[] = 'feedback = ?';
        $params[] = sanitize($data['feedback']);
    }

    if (empty($updates)) return false;

    $params[] = $id;
    $sql = 'UPDATE interviews SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function deleteInterview($id) {
    $db = connectDB();
    $stmt = $db->prepare('DELETE FROM interviews WHERE id = ?');
    return $stmt->execute([$id]);
}

// User management
function getUserByUsername($username) {
    $db = connectDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function getUserByEmail($email) {
    $db = connectDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function registerUser($data) {
    $db = connectDB();
    if (getUserByEmail($data['email']) || getUserByUsername($data['username'])) return false;

    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare('
        INSERT INTO users (
            name, username, email, password_hash, phone, address,
            gender, role, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "active", NOW())
    ');

    return $stmt->execute([
        sanitize($data['name']),
        sanitize($data['username']),
        sanitize($data['email']),
        $hash,
        sanitize($data['phone'] ?? null),
        sanitize($data['address'] ?? null),
        sanitize($data['gender'] ?? null),
        sanitize($data['role'] ?? 'jobseeker')
    ]);
}

function updateUser($id, $data) {
    $db = connectDB();
    $updates = [];
    $params = [];

    // Build dynamic update query based on provided data
    if (isset($data['name'])) { 
        $updates[] = 'name = ?';
        $params[] = sanitize($data['name']);
    }
    if (isset($data['phone'])) {
        $updates[] = 'phone = ?';
        $params[] = sanitize($data['phone']);
    }
    if (isset($data['address'])) {
        $updates[] = 'address = ?';
        $params[] = sanitize($data['address']);
    }
    if (isset($data['gender'])) {
        $updates[] = 'gender = ?';
        $params[] = sanitize($data['gender']);
    }
    if (isset($data['password'])) {
        $updates[] = 'password_hash = ?';
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    if (empty($updates)) return false;

    $params[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function verifyUser($username, $password) {
    // First try username
    $user = getUserByUsername($username);
    if (!$user) {
        // If not found by username, try email
        $user = getUserByEmail($username);
    }
    if (!$user) return false;
    if ($user['status'] !== 'active') return false;
    if (password_verify($password, $user['password_hash'])) return $user;
    return false;
}

// Analytics functions for charts
function getApplicationStatusCounts() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT status, COUNT(*) as count
        FROM applications
        GROUP BY status
        ORDER BY count DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getJobsByType() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT type, COUNT(*) as count
        FROM jobs
        GROUP BY type
        ORDER BY count DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getApplicationsOverTime() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT DATE(applied_at) as date, COUNT(*) as count
        FROM applications
        WHERE applied_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(applied_at)
        ORDER BY date ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInterviewsByStatus() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT status, COUNT(*) as count
        FROM interviews
        GROUP BY status
        ORDER BY count DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getUsersOverTime() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM users
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getJobsOverTime() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM jobs
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getApplicationsByCategory() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT j.category, COUNT(a.id) as count
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        GROUP BY j.category
        ORDER BY count DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getTopCompanies() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT company, COUNT(*) as count
        FROM jobs
        GROUP BY company
        ORDER BY count DESC
        LIMIT 10
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getUserRoles() {
    $db = connectDB();
    $stmt = $db->query("
        SELECT role, COUNT(*) as count
        FROM users
        GROUP BY role
        ORDER BY count DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Utility response
function jsonResponse($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
