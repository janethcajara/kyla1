@echo off
"C:\xampp\mysql\bin\mysql.exe" -u root -D jobportal -e "ALTER TABLE users ADD COLUMN username VARCHAR(191) NOT NULL UNIQUE AFTER name;"
pause
