<?php
require_once __DIR__ . '/security.php';
session_start();
$user = $_SESSION['user'] ?? null;
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
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="/JobPortal/">JobPortal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($user && $user['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/JobPortal/admin/admin.php">Admin</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($user): ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?= h($user['name']) ?></span>
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
    </nav>
    <main class="container mb-4">
        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-info">
                <?= h($_SESSION['flash']) ?>
                <?php unset($_SESSION['flash']); ?>
            </div>
        <?php endif; ?>
