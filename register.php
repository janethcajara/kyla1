<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $userData = [
        'name' => trim($_POST['name'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'role' => $_POST['role'] ?? 'jobseeker'
    ];

    // Validate input
    if (empty($userData['name'])) {
        $errors[] = "Name is required";
    }
    if (empty($userData['username'])) {
        $errors[] = "Username is required";
    }
    if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    if (empty($userData['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($userData['password']) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if ($userData['password'] !== ($_POST['confirm_password'] ?? '')) {
        $errors[] = "Passwords do not match";
    }
    if (!in_array($userData['role'], ['jobseeker', 'employer', 'admin'])) {
        $errors[] = "Invalid account type";
    }

    // Create user if no errors
    if (empty($errors)) {
        if (registerUser($userData)) {
            $_SESSION['success_message'] = "Registration successful! You can now log in.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Registration failed. Email or username may already be registered.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/JobPortal/assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Create Account</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="register.php" method="POST" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username" required
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required minlength="8">
                                    <div class="form-text">Minimum 8 characters</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Choose...</option>
                                        <option value="male" <?php echo isset($_POST['gender']) && $_POST['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo isset($_POST['gender']) && $_POST['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo isset($_POST['gender']) && $_POST['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Account Type *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="jobseeker" <?php echo isset($_POST['role']) && $_POST['role'] === 'jobseeker' ? 'selected' : ''; ?>>Job Seeker</option>
                                    <option value="employer" <?php echo isset($_POST['role']) && $_POST['role'] === 'employer' ? 'selected' : ''; ?>>Employer</option>
                                    <option value="admin" <?php echo isset($_POST['role']) && $_POST['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Create Account</button>
                                <a href="login.php" class="btn btn-link">Already have an account? Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>