# JobPortal (Demo)

A minimal JobPortal demo built with PHP and MySQL to satisfy the SRS and rubric requirements.

Features
- Job posting (Admin)
- Job listing (public)
- Application submission with resume upload
- REST-like endpoints: `api/create.php`, `api/read.php`, `api/update.php`, `api/delete.php`
- Modular functions stored in `functions.php`

Setup (XAMPP on Windows)
1. Copy the `JobPortal` folder to `C:\xampp\htdocs\` (already placed if you used this repo).
2. Start Apache and MySQL from XAMPP Control Panel.
3. Create a database named `jobportal` and import `database.sql` (see below) using phpMyAdmin or mysql CLI.
4. Edit `config.php` if your DB username/password differ.
5. Visit `http://localhost/JobPortal/index.php` to see jobs; `http://localhost/JobPortal/admin.php` to add jobs.

Database
- See `database.sql` for table definitions for `users`, `jobs`, `resumes`, `applications`, and `interviews`.

PHP functions
- `connectDB()` in `db_connect.php` — returns a PDO connection.
- CRUD functions in `functions.php`: `fetchAllJobs`, `getJobById`, `createJob`, `updateJob`, `deleteJob`, `saveResumeFile`, `createApplication`, `fetchApplicationsByJob`.

API endpoints
- `api/create.php?entity=job` (POST) — create job (admin form)
- `api/create.php?entity=application` (POST, multipart/form-data) — submit application with `resume` file
- `api/read.php?entity=jobs` (GET) — list jobs
- `api/read.php?entity=job&id=1` (GET) — single job
- `api/read.php?entity=applications&job_id=1` (GET) — applications for a job
- `api/update.php?entity=job` (POST) — update job (id + fields)
- `api/delete.php?entity=job` (POST) — delete job (id)
 - `api/read.php?entity=jobs` (GET) — list jobs
 - `api/read.php?entity=job&id=1` (GET) — single job
 - `api/read.php?entity=applications&job_id=1` (GET) — applications for a job
 - `api/update.php?entity=job` (POST) — update job (id + fields)
 - `api/delete.php?entity=job` (POST) — delete job (id)
 - `api/update.php?entity=application` (POST) — update application status (id + status)
 - `api/create.php?entity=interview` (POST) — schedule interview (application_id, scheduled_at, notes)
 - `api/update.php?entity=interview` (POST) — update interview (id + scheduled_at + status + notes)
 - `api/delete.php?entity=interview` (POST) — delete interview (id)

Authentication
- `register.php` — register user (choose role `admin` or `jobseeker` for demo)
- `login.php` / `logout.php` — simple session-based login

Notes & Assumptions
- This demo intentionally keeps authentication minimal (no login). For production, add proper user management, password hashing, role-based access, CSRF protection and input validation.
- File uploads are stored in `uploads/` with randomized filenames and original filename saved in the DB.

Optional next steps
- Add authentication and admin roles, interview scheduling UI, email notifications, and unit tests.
