-- JobPortal database schema

CREATE DATABASE IF NOT EXISTS `jobportal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jobportal`;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `username` VARCHAR(191) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `role` ENUM('admin','jobseeker','employer') DEFAULT 'jobseeker',
  `status` ENUM('active','inactive','banned') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(191) NOT NULL,
  `company` VARCHAR(191) NOT NULL,
  `location` VARCHAR(191) NOT NULL,
  `type` ENUM('full-time','part-time','contract','internship') NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `requirements` TEXT NOT NULL,
  `responsibilities` TEXT NOT NULL,
  `salary_min` DECIMAL(12,2) DEFAULT NULL,
  `salary_max` DECIMAL(12,2) DEFAULT NULL,
  `deadline` DATE NOT NULL,
  `vacancies` INT DEFAULT 1,
  `experience_required` VARCHAR(50) DEFAULT NULL,
  `education_required` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active','filled','expired','draft') DEFAULT 'active',
  `posted_by` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Resumes
CREATE TABLE IF NOT EXISTS `resumes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_name` VARCHAR(191) DEFAULT NULL,
  `user_email` VARCHAR(191) DEFAULT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `filepath` VARCHAR(255) NOT NULL,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Applications
CREATE TABLE IF NOT EXISTS `applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `resume_id` INT DEFAULT NULL,
  `cover_letter` TEXT,
  `experience_years` DECIMAL(4,1) DEFAULT NULL,
  `current_salary` DECIMAL(12,2) DEFAULT NULL,
  `expected_salary` DECIMAL(12,2) DEFAULT NULL,
  `notice_period` VARCHAR(50) DEFAULT NULL,
  `additional_documents` TEXT DEFAULT NULL,
  `status` ENUM('pending','shortlisted','rejected','approved','withdrawn') DEFAULT 'pending',
  `status_notes` TEXT DEFAULT NULL,
  `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Job Skills Required
CREATE TABLE IF NOT EXISTS `job_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_id` INT NOT NULL,
  `skill` VARCHAR(100) NOT NULL,
  `level` ENUM('beginner','intermediate','expert') DEFAULT NULL,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Skills
CREATE TABLE IF NOT EXISTS `user_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `skill` VARCHAR(100) NOT NULL,
  `level` ENUM('beginner','intermediate','expert') DEFAULT NULL,
  `years_experience` DECIMAL(4,1) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Education
CREATE TABLE IF NOT EXISTS `education` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `degree` VARCHAR(100) NOT NULL,
  `institution` VARCHAR(191) NOT NULL,
  `field_of_study` VARCHAR(100) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `grade` VARCHAR(20) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Work Experience
CREATE TABLE IF NOT EXISTS `work_experience` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company` VARCHAR(191) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `location` VARCHAR(191) DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `is_current` BOOLEAN DEFAULT FALSE,
  `description` TEXT DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Interviews
CREATE TABLE IF NOT EXISTS `interviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `interviewer_id` INT DEFAULT NULL,
  `type` ENUM('phone','video','in-person') NOT NULL,
  `location` TEXT DEFAULT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `duration_minutes` INT DEFAULT 60,
  `status` ENUM('scheduled','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  `feedback` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  FOREIGN KEY (interviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample admin user
INSERT IGNORE INTO users (name, username, email, password_hash, role, status) VALUES
('Admin User', 'admin', 'admin@jobportal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Sample job
INSERT INTO jobs (title, company, location, type, category, description, requirements, responsibilities, deadline, posted_by) VALUES
('Web Developer', 'City College', 'Calapan City', 'full-time', 'Information Technology',
'Build and maintain web applications using PHP and MySQL', 
'- Bachelor degree in Computer Science or related field\n- 2+ years experience with PHP\n- Strong knowledge of MySQL',
'- Develop and maintain web applications\n- Collaborate with the team\n- Write clean, maintainable code',
DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY),
1);
