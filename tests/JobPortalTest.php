<?php
use PHPUnit\Framework\TestCase;

class JobPortalTest extends TestCase {
    protected $db;
    
    protected function setUp(): void {
        require_once __DIR__ . '/../functions.php';
        $this->db = connectDB();
        // Start transaction for test isolation
        $this->db->beginTransaction();
    }
    
    protected function tearDown(): void {
        // Rollback transaction to clean up
        $this->db->rollBack();
    }
    
    public function testCreateAndFetchJob() {
        $data = [
            'title' => 'Test Job',
            'company' => 'Test Corp',
            'location' => 'Test City',
            'description' => 'Test Description'
        ];
        
        $ok = createJob($data);
        $this->assertTrue($ok);
        
        $jobs = fetchAllJobs();
        $found = false;
        foreach ($jobs as $job) {
            if ($job['title'] === $data['title']) {
                $found = true;
                $this->assertEquals($data['company'], $job['company']);
                $this->assertEquals($data['location'], $job['location']);
                break;
            }
        }
        $this->assertTrue($found);
    }
    
    public function testCreateAndFetchApplication() {
        // First create a job
        $jobData = [
            'title' => 'Test Job',
            'company' => 'Test Corp'
        ];
        createJob($jobData);
        $jobs = fetchAllJobs();
        $job = end($jobs);
        
        // Create application
        $appData = [
            'job_id' => $job['id'],
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'cover_letter' => 'Test cover letter'
        ];
        
        $ok = createApplication($appData);
        $this->assertTrue($ok);
        
        // Fetch and verify
        $apps = fetchApplicationsByJob($job['id']);
        $this->assertNotEmpty($apps);
        $app = $apps[0];
        $this->assertEquals($appData['user_name'], $app['user_name']);
        $this->assertEquals($appData['user_email'], $app['user_email']);
    }
    
    public function testUpdateApplicationStatus() {
        // Create job and application first
        $jobData = ['title' => 'Test Job', 'company' => 'Test Corp'];
        createJob($jobData);
        $jobs = fetchAllJobs();
        $job = end($jobs);
        
        $appData = [
            'job_id' => $job['id'],
            'user_name' => 'Test User',
            'user_email' => 'test@example.com'
        ];
        createApplication($appData);
        
        $apps = fetchApplicationsByJob($job['id']);
        $app = $apps[0];
        
        // Test status update
        $ok = updateApplicationStatus($app['id'], 'approved');
        $this->assertTrue($ok);
        
        $updatedApp = getApplicationById($app['id']);
        $this->assertEquals('approved', $updatedApp['status']);
    }
    
    public function testCreateAndFetchInterview() {
        // Create job and application first
        $jobData = ['title' => 'Test Job', 'company' => 'Test Corp'];
        createJob($jobData);
        $jobs = fetchAllJobs();
        $job = end($jobs);
        
        $appData = [
            'job_id' => $job['id'],
            'user_name' => 'Test User',
            'user_email' => 'test@example.com'
        ];
        createApplication($appData);
        
        $apps = fetchApplicationsByJob($job['id']);
        $app = $apps[0];
        
        // Schedule interview
        $interviewData = [
            'application_id' => $app['id'],
            'scheduled_at' => '2025-11-15 14:00:00',
            'notes' => 'Test interview'
        ];
        
        $ok = createInterview($interviewData);
        $this->assertTrue($ok);
        
        // Fetch and verify
        $interviews = fetchInterviewsByApplication($app['id']);
        $this->assertNotEmpty($interviews);
        $interview = $interviews[0];
        $this->assertEquals($interviewData['scheduled_at'], $interview['scheduled_at']);
        $this->assertEquals($interviewData['notes'], $interview['notes']);
    }
}