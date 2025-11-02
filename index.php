<?php
$title = 'Home - JobPortal';
require_once __DIR__ . '/includes/header.php';
?>
<main class="container">
    <div class="text-center mb-4">
        <h1>Welcome to JobPortal</h1>
        <p class="lead">Your gateway to connecting job seekers with employers.</p>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="job">
                <h3>For Job Seekers</h3>
                <p>Find your dream job by browsing through our extensive list of job opportunities. Create your profile and apply with ease.</p>
                <a href="/JobPortal/register.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="job">
                <h3>For Employers</h3>
                <p>Post job listings and find the perfect candidates for your company. Manage applications and connect with talent.</p>
                <a href="/JobPortal/login.php" class="btn btn-primary">Post a Job</a>
            </div>
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
