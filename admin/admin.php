<?php
$title = 'Admin Dashboard - JobPortal';
require_once __DIR__ . '/../includes/functions.php';

// Check for user session and admin role
$user = $_SESSION['user'] ?? null;
if (!$user || !isset($user['role'], $user['name'], $user['id']) || $user['role'] !== 'admin') {
    // Session is invalid or incomplete, force re-login
    session_destroy();
    header('Location: /JobPortal/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/JobPortal/assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            background-color: #343a40;
            padding-top: 20px;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: #ffffff;
        }
        .sidebar .nav-link:hover {
            color: #adb5bd;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="container-fluid">
            <a class="navbar-brand text-white mb-4" href="/JobPortal/">JobPortal</a>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="/JobPortal/admin/admin.php">Admin Dashboard</a>
                </li>
                <?php if ($user): ?>
                    <li class="nav-item mt-3">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JobPortal/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/JobPortal/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/JobPortal/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="main-content">
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($_SESSION['flash']); ?>
                <?php unset($_SESSION['flash']); ?>
            </div>
        <?php endif; ?>

// handle job create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_job') {
    $jobData = [
        'title' => $_POST['title'] ?? '',
        'company' => $_POST['company'] ?? '',
        'location' => $_POST['location'] ?? '',
        'description' => $_POST['description'] ?? '',
        'type' => $_POST['type'] ?? 'full-time',
        'category' => $_POST['category'] ?? 'General',
        'requirements' => $_POST['requirements'] ?? '',
        'responsibilities' => $_POST['responsibilities'] ?? '',
        'salary_min' => $_POST['salary_min'] ?? null,
        'salary_max' => $_POST['salary_max'] ?? null,
        'deadline' => $_POST['deadline'] ?? date('Y-m-d', strtotime('+30 days')),
        'vacancies' => $_POST['vacancies'] ?? 1,
        'posted_by' => $user['id']
    ];
    
    $ok = createJob($jobData);
    $msg = $ok ? 'Job created.' : 'Failed to create job.';
}
?>
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-4">Admin Dashboard</h1>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?php echo count(fetchAllJobs()); ?></h5>
                    <p class="card-text">Total Jobs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success"><?php echo count(fetchAllApplications()); ?></h5>
                    <p class="card-text">Total Applications</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?php echo count(fetchAllInterviews()); ?></h5>
                    <p class="card-text">Scheduled Interviews</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info"><?php echo count(array_filter(fetchAllApplications(), function($app) { return $app['status'] === 'approved'; })); ?></h5>
                    <p class="card-text">Approved Applications</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics & Visualizations -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Analytics & Visualizations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6>Application Status Distribution</h6>
                            <canvas id="applicationStatusChart"></canvas>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h6>Jobs by Type</h6>
                            <canvas id="jobsByTypeChart"></canvas>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6>Applications Over Time (Last 30 Days)</h6>
                            <canvas id="applicationsOverTimeChart"></canvas>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h6>Interviews by Status</h6>
                            <canvas id="interviewsByStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6>Users Over Time (Last 30 Days)</h6>
                            <canvas id="usersOverTimeChart"></canvas>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h6>Jobs Over Time (Last 30 Days)</h6>
                            <canvas id="jobsOverTimeChart"></canvas>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6>Applications by Category</h6>
                            <canvas id="applicationsByCategoryChart"></canvas>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h6>Top Companies</h6>
                            <canvas id="topCompaniesChart"></canvas>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6>User Roles Distribution</h6>
                            <canvas id="userRolesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$user || $user['role'] !== 'admin'): ?>
        <div class="alert alert-warning">
            <p>You must be logged in as an <strong>admin</strong> to manage jobs and applications. <a href="/JobPortal/login.php">Login</a> or <a href="/JobPortal/register.php">Register</a> (choose Admin role when registering for demo).</p>
        </div>
    <?php else: ?>
        <!-- Create Job Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Create New Job</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($msg)): ?>
                            <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>"><?=htmlspecialchars($msg)?></div>
                        <?php endif; ?>
                        <form method="post" action="/JobPortal/admin/admin.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create_job">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Job Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="company" class="form-label">Company *</label>
                                    <input type="text" class="form-control" id="company" name="company" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Job Type *</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="full-time">Full Time</option>
                                        <option value="part-time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="internship">Internship</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <input type="text" class="form-control" id="category" name="category" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="vacancies" class="form-label">Number of Vacancies</label>
                                    <input type="number" class="form-control" id="vacancies" name="vacancies" value="1" min="1">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements *</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="responsibilities" class="form-label">Responsibilities *</label>
                                <textarea class="form-control" id="responsibilities" name="responsibilities" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="salary_min" class="form-label">Minimum Salary</label>
                                    <input type="number" class="form-control" id="salary_min" name="salary_min" step="1000">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="salary_max" class="form-label">Maximum Salary</label>
                                    <input type="number" class="form-control" id="salary_max" name="salary_max" step="1000">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="deadline" class="form-label">Application Deadline *</label>
                                <input type="date" class="form-control" id="deadline" name="deadline" required
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Create Job</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Existing Jobs Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Existing Jobs</h5>
                    </div>
                    <div class="card-body">
                        <?php $jobs = fetchAllJobs(); if (empty($jobs)): ?>
                            <p class="text-muted">No jobs posted yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Company</th>
                                            <th>Location</th>
                                            <th>Type</th>
                                            <th>Deadline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobs as $j): ?>
                                            <tr>
                                                <td><strong><?=htmlspecialchars($j['title'])?></strong></td>
                                                <td><?=htmlspecialchars($j['company'])?></td>
                                                <td><?=htmlspecialchars($j['location'])?></td>
                                                <td><span class="badge bg-secondary"><?=htmlspecialchars($j['type'])?></span></td>
                                                <td><?=htmlspecialchars($j['deadline'])?></td>
                                                <td>
                                                    <a href="/JobPortal/admin/edit_job.php?id=<?= $j['id'] ?>" class="btn btn-sm btn-warning me-2">Edit</a>
                                                    <form method="post" action="/JobPortal/api/delete.php?entity=job" style="display:inline">
                                                        <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this job?')">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Applications</h5>
                    </div>
                    <div class="card-body">
                        <?php $apps = fetchAllApplications(); if (empty($apps)): ?>
                            <p class="text-muted">No applications yet.</p>
                        <?php else: ?>
                            <?php foreach ($apps as $a): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="card-title">
                                                    <strong><?=htmlspecialchars($a['applicant_name'])?></strong> applied for <em><?=htmlspecialchars($a['job_title'])?></em>
                                                </h6>
                                                <p class="card-text">
                                                    <small class="text-muted">Applied at: <?=htmlspecialchars($a['applied_at'])?></small><br>
                                                    Email: <?=htmlspecialchars($a['applicant_email'])?><br>
                                                    Experience: <?=htmlspecialchars($a['experience_years'])?> years<br>
                                                    <?php if ($a['current_salary'] || $a['expected_salary']): ?>
                                                        <?php if ($a['current_salary']): ?>Current Salary: $<?=number_format($a['current_salary'], 2)?><br><?php endif; ?>
                                                        <?php if ($a['expected_salary']): ?>Expected Salary: $<?=number_format($a['expected_salary'], 2)?><?php endif; ?>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($a['filename']): ?>
                                                    <p class="mb-1">Resume: <a href="/JobPortal/uploads/<?=rawurlencode($a['filepath'])?>" target="_blank" class="btn btn-sm btn-outline-primary">View Resume</a></p>
                                                <?php endif; ?>
                                                <?php if ($a['additional_documents']): ?>
                                                    <p class="mb-1">Additional Documents: <?=htmlspecialchars($a['additional_documents'])?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <form method="post" action="/JobPortal/api/update.php?entity=application" class="d-flex">
                                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                                        <select name="status" class="form-select me-2">
                                                            <option value="pending" <?= $a['status']=='pending'?'selected':''?>>Pending</option>
                                                            <option value="shortlisted" <?= $a['status']=='shortlisted'?'selected':''?>>Shortlisted</option>
                                                            <option value="rejected" <?= $a['status']=='rejected'?'selected':''?>>Rejected</option>
                                                            <option value="approved" <?= $a['status']=='approved'?'selected':''?>>Approved</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                                    </form>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Status Notes</label>
                                                    <form method="post" action="/JobPortal/api/update.php?entity=application">
                                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                                        <textarea name="status_notes" class="form-control" rows="2" placeholder="Add notes for the status change"></textarea>
                                                        <button type="submit" class="btn btn-sm btn-secondary mt-2">Add Notes</button>
                                                    </form>
                                                </div>
                                                <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#interview-<?= $a['id'] ?>" aria-expanded="false">
                                                    Schedule Interview
                                                </button>
                                            </div>
                                        </div>
                                        <div class="collapse mt-3" id="interview-<?= $a['id'] ?>">
                                            <div class="card card-body">
                                                <form method="post" action="/JobPortal/api/create.php?entity=interview">
                                                    <input type="hidden" name="application_id" value="<?= (int)$a['id'] ?>">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Interview Type</label>
                                                            <select name="type" class="form-select" required>
                                                                <option value="phone">Phone</option>
                                                                <option value="video">Video</option>
                                                                <option value="in-person">In-Person</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Date and Time</label>
                                                            <input type="datetime-local" name="scheduled_at" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Duration (minutes)</label>
                                                            <input type="number" name="duration_minutes" class="form-control" value="60" min="15" step="15">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Location/Link</label>
                                                            <input type="text" name="location" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Notes</label>
                                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-success">Schedule Interview</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interviews Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Interviews</h5>
                    </div>
                    <div class="card-body">
                        <?php $interviews = fetchAllInterviews(); if (empty($interviews)): ?>
                            <p class="text-muted">No interviews scheduled.</p>
                        <?php else: ?>
                            <?php foreach ($interviews as $i): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="card-title">
                                                    <strong><?=htmlspecialchars($i['applicant_name'])?></strong> — <?=htmlspecialchars($i['job_title'])?>
                                                </h6>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <small class="text-muted">
                                                            <strong>Type:</strong> <?=htmlspecialchars(ucfirst($i['type']))?><br>
                                                            <strong>When:</strong> <?=htmlspecialchars($i['scheduled_at'])?><br>
                                                            <strong>Duration:</strong> <?=htmlspecialchars($i['duration_minutes'])?> minutes
                                                        </small>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted">
                                                            <strong>Status:</strong> <span class="badge bg-<?= $i['status'] == 'scheduled' ? 'warning' : ($i['status'] == 'completed' ? 'success' : 'secondary') ?>"><?=htmlspecialchars($i['status'])?></span><br>
                                                            <?php if ($i['location']): ?>
                                                                <strong>Location:</strong> <?=htmlspecialchars($i['location'])?><br>
                                                            <?php endif; ?>
                                                            <?php if ($i['interviewer_name']): ?>
                                                                <strong>Interviewer:</strong> <?=htmlspecialchars($i['interviewer_name'])?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <?php if ($i['notes']): ?>
                                                    <p class="mt-2 mb-1"><strong>Notes:</strong><br><small><?=nl2br(htmlspecialchars($i['notes']))?></small></p>
                                                <?php endif; ?>
                                                <?php if ($i['feedback']): ?>
                                                    <p class="mt-2 mb-1"><strong>Feedback:</strong><br><small><?=nl2br(htmlspecialchars($i['feedback']))?></small></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <form method="post" action="/JobPortal/api/update.php?entity=interview" class="mb-3">
                                                    <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                                    <label class="form-label">Update Status</label>
                                                    <select name="status" class="form-select mb-2">
                                                        <option value="scheduled" <?= $i['status']=='scheduled'?'selected':''?>>Scheduled</option>
                                                        <option value="completed" <?= $i['status']=='completed'?'selected':''?>>Completed</option>
                                                        <option value="cancelled" <?= $i['status']=='cancelled'?'selected':''?>>Cancelled</option>
                                                        <option value="rescheduled" <?= $i['status']=='rescheduled'?'selected':''?>>Rescheduled</option>
                                                    </select>
                                                    <textarea name="feedback" class="form-control mb-2" rows="2" placeholder="Add interview feedback"></textarea>
                                                    <button type="submit" class="btn btn-sm btn-primary">Update Interview</button>
                                                </form>
                                                <form method="post" action="/JobPortal/api/delete.php?entity=interview" style="display:inline">
                                                    <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this interview?')">Delete Interview</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Fetch analytics data from PHP
        const applicationStatusData = <?php echo json_encode(getApplicationStatusCounts()); ?>;
        const jobsByTypeData = <?php echo json_encode(getJobsByType()); ?>;
        const applicationsOverTimeData = <?php echo json_encode(getApplicationsOverTime()); ?>;
        const interviewsByStatusData = <?php echo json_encode(getInterviewsByStatus()); ?>;
        const usersOverTimeData = <?php echo json_encode(getUsersOverTime()); ?>;
        const jobsOverTimeData = <?php echo json_encode(getJobsOverTime()); ?>;
        const applicationsByCategoryData = <?php echo json_encode(getApplicationsByCategory()); ?>;
        const topCompaniesData = <?php echo json_encode(getTopCompanies()); ?>;
        const userRolesData = <?php echo json_encode(getUserRoles()); ?>;

        // Application Status Pie Chart
        const ctx1 = document.getElementById('applicationStatusChart').getContext('2d');
        const gradient1 = ctx1.createLinearGradient(0, 0, 0, 400);
        gradient1.addColorStop(0, '#FF6384');
        gradient1.addColorStop(1, '#FF9F40');
        const gradient2 = ctx1.createLinearGradient(0, 0, 0, 400);
        gradient2.addColorStop(0, '#36A2EB');
        gradient2.addColorStop(1, '#4BC0C0');
        const gradient3 = ctx1.createLinearGradient(0, 0, 0, 400);
        gradient3.addColorStop(0, '#FFCE56');
        gradient3.addColorStop(1, '#9966FF');
        const gradient4 = ctx1.createLinearGradient(0, 0, 0, 400);
        gradient4.addColorStop(0, '#4BC0C0');
        gradient4.addColorStop(1, '#FF6384');
        const gradient5 = ctx1.createLinearGradient(0, 0, 0, 400);
        gradient5.addColorStop(0, '#9966FF');
        gradient5.addColorStop(1, '#36A2EB');
        const gradient6 = ctx1.createLinearGradient(0, 0, 0, 400);
        gradient6.addColorStop(0, '#FF9F40');
        gradient6.addColorStop(1, '#FFCE56');
        const gradient7 = ctx1.createLinearGradient(0, 0, 0, 400);
        gradient7.addColorStop(0, '#FF6384');
        gradient7.addColorStop(1, '#4BC0C0');
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: Object.keys(applicationStatusData),
                datasets: [{
                    data: Object.values(applicationStatusData),
                    backgroundColor: [gradient1, gradient2, gradient3, gradient4, gradient5, gradient6, gradient7],
                    borderColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384'],
                    borderWidth: 3,
                    hoverBorderWidth: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Jobs by Type Bar Chart
        const ctx2 = document.getElementById('jobsByTypeChart').getContext('2d');
        const barGradients = Object.keys(jobsByTypeData).map((_, index) => {
            const gradient = ctx2.createLinearGradient(0, 0, 0, 400);
            const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'];
            gradient.addColorStop(0, colors[index % colors.length]);
            gradient.addColorStop(1, colors[(index + 1) % colors.length]);
            return gradient;
        });
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: Object.keys(jobsByTypeData),
                datasets: [{
                    label: 'Number of Jobs',
                    data: Object.values(jobsByTypeData),
                    backgroundColor: barGradients,
                    borderColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderWidth: 2,
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Applications Over Time Line Chart
        const ctx3 = document.getElementById('applicationsOverTimeChart').getContext('2d');
        const lineGradient = ctx3.createLinearGradient(0, 0, 0, 400);
        lineGradient.addColorStop(0, '#FF6384');
        lineGradient.addColorStop(1, 'rgba(255, 99, 132, 0.1)');
        new Chart(ctx3, {
            type: 'line',
            data: {
                labels: applicationsOverTimeData.map(item => item.date),
                datasets: [{
                    label: 'Applications',
                    data: applicationsOverTimeData.map(item => item.count),
                    borderColor: '#FF6384',
                    backgroundColor: lineGradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FF6384',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Interviews by Status Bar Chart
        const ctx4 = document.getElementById('interviewsByStatusChart').getContext('2d');
        const interviewGradients = Object.keys(interviewsByStatusData).map((_, index) => {
            const gradient = ctx4.createLinearGradient(0, 0, 0, 400);
            const colors = ['#FFCE56', '#36A2EB', '#FF6384', '#4BC0C0', '#9966FF'];
            gradient.addColorStop(0, colors[index % colors.length]);
            gradient.addColorStop(1, colors[(index + 1) % colors.length]);
            return gradient;
        });
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: Object.keys(interviewsByStatusData),
                datasets: [{
                    label: 'Number of Interviews',
                    data: Object.values(interviewsByStatusData),
                    backgroundColor: interviewGradients,
                    borderColor: ['#FFCE56', '#36A2EB', '#FF6384', '#4BC0C0', '#9966FF'],
                    borderWidth: 2,
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Users Over Time Line Chart
        const ctx5 = document.getElementById('usersOverTimeChart').getContext('2d');
        const usersLineGradient = ctx5.createLinearGradient(0, 0, 0, 400);
        usersLineGradient.addColorStop(0, '#36A2EB');
        usersLineGradient.addColorStop(1, 'rgba(54, 162, 235, 0.1)');
        new Chart(ctx5, {
            type: 'line',
            data: {
                labels: usersOverTimeData.map(item => item.date),
                datasets: [{
                    label: 'Users',
                    data: usersOverTimeData.map(item => item.count),
                    borderColor: '#36A2EB',
                    backgroundColor: usersLineGradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#36A2EB',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Jobs Over Time Line Chart
        const ctx6 = document.getElementById('jobsOverTimeChart').getContext('2d');
        const jobsLineGradient = ctx6.createLinearGradient(0, 0, 0, 400);
        jobsLineGradient.addColorStop(0, '#4BC0C0');
        jobsLineGradient.addColorStop(1, 'rgba(75, 192, 192, 0.1)');
        new Chart(ctx6, {
            type: 'line',
            data: {
                labels: jobsOverTimeData.map(item => item.date),
                datasets: [{
                    label: 'Jobs',
                    data: jobsOverTimeData.map(item => item.count),
                    borderColor: '#4BC0C0',
                    backgroundColor: jobsLineGradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4BC0C0',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Applications by Category Bar Chart
        const ctx7 = document.getElementById('applicationsByCategoryChart').getContext('2d');
        const categoryGradients = Object.keys(applicationsByCategoryData).map((_, index) => {
            const gradient = ctx7.createLinearGradient(0, 0, 0, 400);
            const colors = ['#9966FF', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];
            gradient.addColorStop(0, colors[index % colors.length]);
            gradient.addColorStop(1, colors[(index + 1) % colors.length]);
            return gradient;
        });
        new Chart(ctx7, {
            type: 'bar',
            data: {
                labels: Object.keys(applicationsByCategoryData),
                datasets: [{
                    label: 'Applications',
                    data: Object.values(applicationsByCategoryData),
                    backgroundColor: categoryGradients,
                    borderColor: ['#9966FF', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                    borderWidth: 2,
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Top Companies Bar Chart
        const ctx8 = document.getElementById('topCompaniesChart').getContext('2d');
        const companyGradients = Object.keys(topCompaniesData).map((_, index) => {
            const gradient = ctx8.createLinearGradient(0, 0, 0, 400);
            const colors = ['#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];
            gradient.addColorStop(0, colors[index % colors.length]);
            gradient.addColorStop(1, colors[(index + 1) % colors.length]);
            return gradient;
        });
        new Chart(ctx8, {
            type: 'bar',
            data: {
                labels: Object.keys(topCompaniesData),
                datasets: [{
                    label: 'Jobs Posted',
                    data: Object.values(topCompaniesData),
                    backgroundColor: companyGradients,
                    borderColor: ['#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                    borderWidth: 2,
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // User Roles Distribution Pie Chart
        const ctx9 = document.getElementById('userRolesChart').getContext('2d');
        const roleGradients = Object.keys(userRolesData).map((_, index) => {
            const gradient = ctx9.createLinearGradient(0, 0, 0, 400);
            const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'];
            gradient.addColorStop(0, colors[index % colors.length]);
            gradient.addColorStop(1, colors[(index + 1) % colors.length]);
            return gradient;
        });
        new Chart(ctx9, {
            type: 'pie',
            data: {
                labels: Object.keys(userRolesData),
                datasets: [{
                    data: Object.values(userRolesData),
                    backgroundColor: roleGradients,
                    borderColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderWidth: 3,
                    hoverBorderWidth: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
  </main>
</body>
</html>
