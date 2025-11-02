<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/EmailTemplate.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer;
    private $template;
    private $logFile;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->template = new EmailTemplate();
        $this->logFile = __DIR__ . '/logs/email.log';

        // Initialize PHPMailer settings
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = SMTP_USER;
        $this->mailer->Password = SMTP_PASS;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = SMTP_PORT;
        $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $this->mailer->isHTML(true);
    }

    private function sendTemplatedEmail($templateName, $to, $subject, $data) {
        try {
            // Load and render the template
            $content = $this->template
                ->load($templateName)
                ->setData($data)
                ->render();

            // Set email parameters
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $content;

            // Send email
            $success = $this->mailer->send();
            
            // Log the email
            $this->logEmail($success, $to, $subject, $templateName);
            
            return $success;

        } catch (Exception $e) {
            $this->logEmail(false, $to, $subject, $templateName, $e->getMessage());
            error_log("Mail error: " . $e->getMessage());
            return false;
        }
    }

    private function logEmail($success, $to, $subject, $template, $error = null) {
        $timestamp = date('Y-m-d H:i:s');
        $status = $success ? 'SUCCESS' : 'FAILED';
        $logEntry = sprintf(
            "[%s] %s | To: %s | Subject: %s | Template: %s%s\n",
            $timestamp,
            $status,
            $to,
            $subject,
            $template,
            $error ? " | Error: $error" : ""
        );

        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }

        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}

// Global mailer instance
$mailer = new Mailer();

function notifyApplicationSubmitted($application) {
    global $mailer;
    
    // Prepare data for template
    $data = [
        'name' => $application['user_name'],
        'job_title' => $application['job_title'],
        'email' => $application['user_email'],
        'applied_date' => $application['applied_at'],
        'portal_url' => 'http://localhost/JobPortal/application-status.php'
    ];

    // Send to applicant
    $mailer->sendTemplatedEmail(
        'application_received',
        $application['user_email'],
        "Application Received - " . $application['job_title'],
        $data
    );

    // Notify admin if configured
    if (defined('ADMIN_EMAIL')) {
        $adminData = array_merge($data, [
            'portal_url' => 'http://localhost/JobPortal/admin.php'
        ]);
        
        $mailer->sendTemplatedEmail(
            'admin_new_application',
            ADMIN_EMAIL,
            "New Application - " . $application['job_title'],
            $adminData
        );
    }
}

function notifyApplicationStatusChange($application) {
    global $mailer;
    
    $data = [
        'name' => $application['user_name'],
        'job_title' => $application['job_title'],
        'email' => $application['user_email'],
        'status' => $application['status'],
        'status_details' => $application['status_details'] ?? '',
        'next_steps' => $application['status'] === 'approved' ? 'We will contact you soon to schedule an interview.' : '',
        'portal_url' => 'http://localhost/JobPortal/application-status.php'
    ];

    $mailer->sendTemplatedEmail(
        'application_update',
        $application['user_email'],
        "Application Status Update - " . $application['job_title'],
        $data
    );
}

function notifyInterviewScheduled($interview) {
    global $mailer;
    
    $data = [
        'name' => $interview['user_name'],
        'job_title' => $interview['job_title'],
        'email' => $interview['user_email'],
        'date_time' => $interview['scheduled_at'],
        'notes' => $interview['notes'] ?? '',
        'portal_url' => 'http://localhost/JobPortal/interview-details.php'
    ];

    $mailer->sendTemplatedEmail(
        'interview_scheduled',
        $interview['user_email'],
        "Interview Scheduled - " . $interview['job_title'],
        $data
    );
}