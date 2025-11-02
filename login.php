<?php
require_once __DIR__ . '/includes/functions.php';
session_start();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = verifyUser($username, $password);
    if ($user) {
        // Store all necessary user data in session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'phone' => $user['phone'],
            'address' => $user['address'],
            'gender' => $user['gender'],
            'status' => $user['status']
        ];

        // Redirect based on user role
        if ($user['role'] === 'admin') {
            header('Location: /JobPortal/admin/admin.php');
        } else {
            header('Location: /JobPortal/jobseeker/dashboard.php');
        }
        exit;
    } else {
        $msg = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/JobPortal/assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title h4 mb-0">Login to JobPortal</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($msg): ?>
                            <div class="alert alert-danger"><?=htmlspecialchars($msg)?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_COOKIE['logout_message'])): ?>
                            <div class="alert alert-success">
                                <?=htmlspecialchars($_COOKIE['logout_message'])?>
                                <?php setcookie('logout_message', '', time() - 3600, '/'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?=htmlspecialchars($_SESSION['success_message'])?>
                                <?php unset($_SESSION['success_message']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="/JobPortal/login.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <p class="mb-0">No account? <a href="/JobPortal/register.php" class="text-primary">Register</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>