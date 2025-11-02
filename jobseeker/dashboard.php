<?php
$title = 'JobPortal - Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
session_start();
$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'jobseeker') {
    header('Location: /JobPortal/login.php');
    exit;
}

// Handle resume upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume'])) {
    $resume_id = saveResumeFile($_FILES['resume'], $user['name'], $user['email']);
    if ($resume_id) {
        $_SESSION['flash'] = 'Resume uploaded successfully.';
    } else {
        $_SESSION['flash'] = 'Resume upload failed.';
    }
    header('Location: /JobPortal/jobseeker/dashboard.php');
    exit;
}

// Fetch user's resumes
$db = connectDB();

// Handle resume edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_resume_id'])) {
    $resume_id = (int)$_POST['edit_resume_id'];
    $new_filename = trim($_POST['new_filename'] ?? '');
    if ($new_filename !== '') {
        $stmt = $db->prepare('UPDATE resumes SET filename = ? WHERE id = ? AND user_email = ?');
        $stmt->execute([$new_filename, $resume_id, $user['email']]);
        $_SESSION['flash'] = 'Resume filename updated successfully.';
    } else {
        $_SESSION['flash'] = 'New filename cannot be empty.';
    }
    header('Location: /JobPortal/jobseeker/dashboard.php#resumes');
    exit;
}

// Handle resume delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resume_id'])) {
    $resume_id = (int)$_POST['delete_resume_id'];
    $stmt = $db->prepare('SELECT filepath FROM resumes WHERE id = ? AND user_email = ?');
    $stmt->execute([$resume_id, $user['email']]);
    $resume = $stmt->fetch();
    if ($resume) {
        // Delete file from filesystem
        if (file_exists(__DIR__ . '/../uploads/' . $resume['filepath'])) {
            unlink(__DIR__ . '/../uploads/' . $resume['filepath']);
        }
        // Delete from database
        $stmt = $db->prepare('DELETE FROM resumes WHERE id = ? AND user_email = ?');
        $stmt->execute([$resume_id, $user['email']]);
        $_SESSION['flash'] = 'Resume deleted successfully.';
    } else {
        $_SESSION['flash'] = 'Resume not found.';
    }
    header('Location: /JobPortal/jobseeker/dashboard.php#resumes');
    exit;
}
$stmt = $db->prepare('SELECT * FROM resumes WHERE user_email = ? ORDER BY uploaded_at DESC');
$stmt->execute([$user['email']]);
$user_resumes = $stmt->fetchAll();

// Fetch user's applications
$stmt = $db->prepare('
    SELECT a.*, j.title AS job_title, j.company, j.location
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
');
$stmt->execute([$user['id']]);
$user_applications = $stmt->fetchAll();

// Fetch user's interviews
$stmt = $db->prepare('
    SELECT i.*, j.title AS job_title, j.company, j.location, a.status AS application_status
    FROM interviews i
    JOIN applications a ON i.application_id = a.id
    JOIN jobs j ON a.job_id = j.id
    WHERE a.user_id = ?
    ORDER BY i.scheduled_at ASC
');
$stmt->execute([$user['id']]);
$user_interviews = $stmt->fetchAll();

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
  // simple search across title, company, location
  $db = connectDB();
  $like = '%' . $q . '%';
  $stmt = $db->prepare('SELECT * FROM jobs WHERE title LIKE ? OR company LIKE ? OR location LIKE ? ORDER BY created_at DESC');
  $stmt->execute([$like, $like, $like]);
  $jobs = $stmt->fetchAll();
} else {
  $jobs = fetchAllJobs();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title ?? 'JobPortal') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/JobPortal/assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function editResume(id, currentFilename) {
            document.getElementById('edit_resume_id').value = id;
            document.getElementById('new_filename').value = currentFilename;
            document.getElementById('editForm').style.display = 'block';
        }
        function cancelEdit() {
            document.getElementById('editForm').style.display = 'none';
        }
    </script>
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            background-color: black;
            padding-top: 20px;
            overflow-y: auto;
        }
        .sidebar h5 {
            color: blue;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar .nav-link {
            color: white;
            background: transparent;
        }
        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h5 class="px-3">JobPortal</h5>
        <nav class="nav flex-column">
            <a class="nav-link active" href="/JobPortal/jobseeker/dashboard.php">Dashboard</a>
            <a class="nav-link" href="#resumes">My Resumes</a>
            <a class="nav-link" href="#applications">My Applications</a>
            <a class="nav-link" href="#interviews">My Interviews</a>
            <a class="nav-link" href="#jobs">Available Jobs</a>
            <a class="nav-link" href="/JobPortal/logout.php">Logout</a>
        </nav>
    </div>
    <div class="main-content">
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-info">
                <?= h($_SESSION['flash']) ?>
                <?php unset($_SESSION['flash']); ?>
            </div>
        <?php endif; ?>
        <section id="resumes">
      <h2>My Resumes</h2>
      <form method="post" enctype="multipart/form-data">
        <label>Upload New Resume (PDF or DOCX)<br><input type="file" name="resume" accept=".pdf,.doc,.docx" required></label><br>
        <button type="submit">Upload Resume</button>
      </form>
      <?php if (!empty($user_resumes)): ?>
        <h3>Your Uploaded Resumes</h3>
        <ul>
          <?php foreach ($user_resumes as $resume): ?>
            <li>
              <?= htmlspecialchars($resume['filename']) ?> (Uploaded: <?= htmlspecialchars($resume['uploaded_at']) ?>)
              <a href="/JobPortal/uploads/<?= htmlspecialchars($resume['filepath']) ?>" target="_blank">Download</a>
              <button onclick="editResume(<?= (int)$resume['id'] ?>, '<?= htmlspecialchars($resume['filename']) ?>')">Edit</button>
              <form method="post" style="display:inline;">
                <input type="hidden" name="delete_resume_id" value="<?= (int)$resume['id'] ?>">
                <button type="submit" onclick="return confirm('Are you sure you want to delete this resume?')">Delete</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
        <div id="editForm" style="display:none;">
          <form method="post">
            <input type="hidden" name="edit_resume_id" id="edit_resume_id">
            <label>New Filename<br><input type="text" name="new_filename" id="new_filename" required></label><br>
            <button type="submit">Update</button>
            <button type="button" onclick="cancelEdit()">Cancel</button>
          </form>
        </div>
      <?php else: ?>
        <p>No resumes uploaded yet.</p>
      <?php endif; ?>
    </section>

    <section id="applications">
      <h2>My Applications</h2>
      <?php if (!empty($user_applications)): ?>
        <?php foreach ($user_applications as $app): ?>
          <article class="job">
            <h3><?= htmlspecialchars($app['job_title']) ?> at <?= htmlspecialchars($app['company']) ?> — <?= htmlspecialchars($app['location']) ?></h3>
            <p>Status: <?= htmlspecialchars($app['status']) ?> | Applied: <?= htmlspecialchars($app['applied_at']) ?></p>
            <?php if ($app['cover_letter']): ?>
              <p>Cover Letter: <?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No applications submitted yet.</p>
      <?php endif; ?>
    </section>

    <section id="interviews">
      <h2>My Interviews</h2>
      <?php if (!empty($user_interviews)): ?>
        <?php foreach ($user_interviews as $interview): ?>
          <article class="job">
            <h3>Interview for <?= htmlspecialchars($interview['job_title']) ?> at <?= htmlspecialchars($interview['company']) ?></h3>
            <p>Scheduled: <?= htmlspecialchars($interview['scheduled_at']) ?> | Type: <?= htmlspecialchars($interview['type']) ?> | Status: <?= htmlspecialchars($interview['status']) ?></p>
            <?php if ($interview['location']): ?>
              <p>Location: <?= htmlspecialchars($interview['location']) ?></p>
            <?php endif; ?>
            <details>
              <summary>Reschedule Interview</summary>
              <form method="post" action="/JobPortal/api/update.php?entity=interview">
                <input type="hidden" name="id" value="<?= (int)$interview['id'] ?>">
                <label>New Date/Time<br><input type="datetime-local" name="scheduled_at" required></label><br>
                <label>Notes<br><textarea name="notes"></textarea></label><br>
                <button type="submit">Reschedule</button>
              </form>
            </details>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No interviews scheduled yet.</p>
      <?php endif; ?>
    </section>

    <section id="jobs">
      <h2>Available Jobs</h2>
      <form method="get" action="/JobPortal/jobseeker/dashboard.php">
        <input name="q" placeholder="Search by title, company or location" value="<?=htmlspecialchars($q)?>">
        <button type="submit">Search</button>
        <?php if ($q !== ''): ?><a href="/JobPortal/jobseeker/dashboard.php">Clear</a><?php endif; ?>
      </form>
      <?php if (empty($jobs)): ?>
        <p>No jobs posted yet.</p>
      <?php else: ?>
        <?php foreach ($jobs as $job): ?>
          <article class="job">
            <h3><?=htmlspecialchars($job['title'])?> <small><?=htmlspecialchars($job['company'])?> — <?=htmlspecialchars($job['location'])?></small></h3>
            <p><?=nl2br(htmlspecialchars($job['description']))?></p>

            <details>
              <summary>Apply</summary>
              <form method="post" action="/JobPortal/api/create.php?entity=application" enctype="multipart/form-data">
                <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                <label>Cover letter<br><textarea name="cover_letter"></textarea></label><br>
                <label>Select Resume<br>
                  <select name="resume_id" required>
                    <option value="">Choose a resume</option>
                    <?php foreach ($user_resumes as $resume): ?>
                      <option value="<?= (int)$resume['id'] ?>"><?= htmlspecialchars($resume['filename']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label><br>
                <button type="submit">Submit Application</button>
              </form>
            </details>

          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
