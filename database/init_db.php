<?php
// Database initialization script for STEG Intern Management System
// This script creates the SQLite database and populates it with initial test data

// Define database file location
$db_file = __DIR__ . '/steg_interns.db';

// Check if database file already exists
if (file_exists($db_file)) {
    echo "Database already exists. To recreate, delete the file first.\n";
    exit(1);
}

// Create database connection
$db = new SQLite3($db_file);

// Enable foreign keys
$db->exec('PRAGMA foreign_keys = ON');

// Create users table
$db->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    user_type TEXT NOT NULL,
    full_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    department TEXT,
    phone TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    login_attempts INTEGER DEFAULT 0,
    locked_until DATETIME
)');

// Create interns table with relation to users
$db->exec('CREATE TABLE interns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    supervisor_id INTEGER,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status TEXT DEFAULT "pending",
    school TEXT NOT NULL,
    education_level TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES users(id)
)');

// Create reports table
$db->exec('CREATE TABLE reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    intern_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    report_type TEXT NOT NULL,
    file_path TEXT,
    comments TEXT,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT "submitted",
    feedback TEXT,
    FOREIGN KEY (intern_id) REFERENCES interns(id) ON DELETE CASCADE
)');

// Create attestations table
$db->exec('CREATE TABLE attestations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    intern_id INTEGER NOT NULL,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    issue_date DATETIME,
    status TEXT DEFAULT "pending",
    comments TEXT,
    issued_by INTEGER,
    FOREIGN KEY (intern_id) REFERENCES interns(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id)
)');

// Create login_logs table for security tracking
$db->exec('CREATE TABLE login_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    success INTEGER DEFAULT 0,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
)');

// Create index for faster searches
$db->exec('CREATE INDEX idx_users_username ON users(username)');
$db->exec('CREATE INDEX idx_users_user_type ON users(user_type)');
$db->exec('CREATE INDEX idx_interns_status ON interns(status)');
$db->exec('CREATE INDEX idx_reports_intern_id ON reports(intern_id)');
$db->exec('CREATE INDEX idx_attestations_status ON attestations(status)');

// Insert test users
// Admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$db->exec("INSERT INTO users (username, password, user_type, full_name, email, department) 
          VALUES ('admin', '$admin_password', 'admin', 'Admin Utilisateur', 'admin@steg.tn', 'Administration')");

// Supervisor users
$sup_password = password_hash('supervisor123', PASSWORD_DEFAULT);
$db->exec("INSERT INTO users (username, password, user_type, full_name, email, department, phone) 
          VALUES ('samira', '$sup_password', 'supervisor', 'Samira Ben Salah', 'samira.bensalah@steg.tn', 'Informatique', '+216 71 123 456')");
$db->exec("INSERT INTO users (username, password, user_type, full_name, email, department, phone) 
          VALUES ('karim', '$sup_password', 'supervisor', 'Karim Malouli', 'karim.malouli@steg.tn', 'Ressources Humaines', '+216 71 234 567')");

// Intern users
$intern_password = password_hash('intern123', PASSWORD_DEFAULT);
$db->exec("INSERT INTO users (username, password, user_type, full_name, email, department) 
          VALUES ('ahmed', '$intern_password', 'intern', 'Ahmed Belhadj', 'ahmed.belhadj@etudiant.tn', 'Informatique')");
$db->exec("INSERT INTO users (username, password, user_type, full_name, email, department) 
          VALUES ('fatma', '$intern_password', 'intern', 'Fatma Zouari', 'fatma.zouari@etudiant.tn', 'Ressources Humaines')");

// Link interns to supervisors
$db->exec("INSERT INTO interns (user_id, supervisor_id, start_date, end_date, status, school, education_level) 
          VALUES (4, 2, '2023-06-01', '2023-08-31', 'completed', 'Université de Tunis', 'Licence')");
$db->exec("INSERT INTO interns (user_id, supervisor_id, start_date, end_date, status, school, education_level) 
          VALUES (5, 3, '2023-07-01', '2023-09-30', 'completed', 'École Polytechnique de Tunis', 'Master')");

// Insert some sample reports
$db->exec("INSERT INTO reports (intern_id, title, report_type, comments, status) 
          VALUES (1, 'Rapport hebdomadaire - Semaine 1', 'weekly', 'Premier rapport soumis', 'approved')");
$db->exec("INSERT INTO reports (intern_id, title, report_type, comments, status) 
          VALUES (1, 'Rapport hebdomadaire - Semaine 2', 'weekly', 'Deuxième rapport soumis', 'approved')");
$db->exec("INSERT INTO reports (intern_id, title, report_type, comments, status) 
          VALUES (1, 'Rapport de mi-stage', 'midway', 'Analyse des tâches accomplies', 'approved')");
$db->exec("INSERT INTO reports (intern_id, title, report_type, comments, status) 
          VALUES (2, 'Rapport hebdomadaire - Semaine 1', 'weekly', 'Premier rapport', 'approved')");

// Insert some sample attestation requests
$db->exec("INSERT INTO attestations (intern_id, request_date, issue_date, status, issued_by) 
          VALUES (1, '2023-08-25', '2023-08-28', 'issued', 2)");
$db->exec("INSERT INTO attestations (intern_id, request_date, status) 
          VALUES (2, '2023-09-25', 'pending')");

echo "Database successfully created and populated with test data.\n";
echo "Test credentials:\n";
echo "Admin: username='admin', password='admin123'\n";
echo "Supervisor: username='samira', password='supervisor123'\n";
echo "Intern: username='ahmed', password='intern123'\n";

$db->close();
?>
