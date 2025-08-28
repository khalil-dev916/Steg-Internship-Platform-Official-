<?php
// Configuration de la base de données
$dbPath = __DIR__ . '/steg_stagiaires.db';
$maxLoginAttempts = 5;
$loginLockDuration = 15 * 60; // 15 minutes en secondes
$staffDomain = '@steg.tn';

// Connexion à la base de données SQLite
function getDbConnection() {
    global $dbPath;
    try {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        die('Échec de connexion: ' . $e->getMessage());
    }
}

function addColumnIfNotExists($db, $table, $column, $type) {
    try {
        $exists = false;
        $stmt = $db->query("PRAGMA table_info($table)");
        foreach ($stmt as $col) {
            if (strcasecmp($col['name'], $column) === 0) { $exists = true; break; }
        }
        if (!$exists) {
            $db->exec("ALTER TABLE $table ADD COLUMN $column $type");
        }
    } catch (Exception $e) {
        // ignore
    }
}

// Initialisation des tables de base de données
function initializeDatabase() {
    $db = getDbConnection();
    
    // Départements
    $db->exec('CREATE TABLE IF NOT EXISTS departments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    )');

    // Table utilisateurs (rôles: admin, directeur, superviseur, stagiaire)
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        user_type TEXT NOT NULL,
        email TEXT NOT NULL,
        full_name TEXT NOT NULL,
        department TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME,
        login_attempts INTEGER DEFAULT 0,
        locked_until DATETIME
    )');
    // Ajouter la relation superviseur->directeur si manquante
    addColumnIfNotExists($db, 'users', 'director_id', 'INTEGER');
    // Fonctionnalité de réinitialisation de mot de passe supprimée (colonnes laissées intactes si elles existent déjà)

    // Table des stagiaires
    $db->exec('CREATE TABLE IF NOT EXISTS interns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        email TEXT NOT NULL,
        id_card TEXT NOT NULL,
        university TEXT NOT NULL,
        department TEXT NOT NULL,
        supervisor_id INTEGER,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status TEXT DEFAULT "active",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (supervisor_id) REFERENCES users(id)
    )');

    // Table des rapports (pour les soumissions des stagiaires)
    $db->exec('CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        intern_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT "submitted",
        FOREIGN KEY (intern_id) REFERENCES interns(id)
    )');

    // Table des évaluations
    $db->exec('CREATE TABLE IF NOT EXISTS evaluations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        intern_id INTEGER NOT NULL,
        supervisor_id INTEGER NOT NULL,
        score INTEGER NOT NULL,
        notes TEXT,
        date TEXT DEFAULT CURRENT_DATE,
        FOREIGN KEY (intern_id) REFERENCES interns(id),
        FOREIGN KEY (supervisor_id) REFERENCES users(id)
    )');

    // Attestations
    $db->exec('CREATE TABLE IF NOT EXISTS attestations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        intern_id INTEGER NOT NULL,
        generated_by INTEGER NOT NULL,
        generation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        sent_by_email BOOLEAN DEFAULT 0,
        downloaded BOOLEAN DEFAULT 0,
        FOREIGN KEY (intern_id) REFERENCES interns(id),
        FOREIGN KEY (generated_by) REFERENCES users(id)
    )');

    // Applications feature removed (schema retained only if pre-existing)

    // Seed departments
    $departments = ['Informatique','Finance','Ressources Humaines','Production'];
    foreach ($departments as $dn) {
        $stmt = $db->prepare('INSERT OR IGNORE INTO departments (name) VALUES (?)');
        $stmt->execute([$dn]);
    }

    // Default admin
    $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, password, user_type, email, full_name, department) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute(['admin', $hashedPassword, 'admin', 'admin@steg.tn', 'Administrateur STEG', 'Informatique']);
    }

    // Sample director
    $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $stmt->execute(['director1']);
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('director123', PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, password, user_type, email, full_name, department) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute(['director1', $hashedPassword, 'director', 'director@steg.tn', 'Directeur Informatique', 'Informatique']);
    }

    // Sample supervisors with director link
    $sampleSupervisors = [
        ['supervisor1', 'supervisor1', 'supervisor', 'samira.bensalah@steg.tn', 'Samira Ben Salah', 'Informatique', 'director1'],
        ['supervisor2', 'supervisor2', 'supervisor', 'mohamed.trabelsi@steg.tn', 'Mohamed Trabelsi', 'Finance', null],
        ['supervisor3', 'supervisor3', 'supervisor', 'fatma.khalil@steg.tn', 'Fatma Khalil', 'Ressources Humaines', null]
    ];
    foreach ($sampleSupervisors as $s) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$s[0]]);
        if ($stmt->fetchColumn() == 0) {
            $directorId = null;
            if ($s[6]) {
                $q = $db->prepare('SELECT id FROM users WHERE username = ?');
                $q->execute([$s[6]]);
                $directorId = ($r = $q->fetch(PDO::FETCH_ASSOC)) ? $r['id'] : null;
            }
            $hashedPassword = password_hash($s[1], PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (username, password, user_type, email, full_name, department, director_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$s[0], $hashedPassword, $s[2], $s[3], $s[4], $s[5], $directorId]);
        }
    }

    // Sample interns (assigned to supervisors)
    $sampleInterns = [
        ['intern1', 'intern1', 'intern', 'firas.welhazi@email.com', 'Firas', 'Welhazi', '12345678', 'Université de Tunis', 'Informatique', 'supervisor1', '2025-06-01', '2025-08-31'],
        ['intern2', 'intern2', 'intern', 'samira.khedher@email.com', 'Samira', 'Khedher', '87654321', 'ENIT', 'Ressources Humaines', 'supervisor3', '2025-07-01', '2025-09-30'],
        ['intern3', 'intern3', 'intern', 'ahmed.bensalah@email.com', 'Ahmed', 'Ben Salah', '23456789', 'ISG Tunis', 'Finance', 'supervisor2', '2025-05-01', '2025-07-31']
    ];
    foreach ($sampleInterns as $intern) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$intern[0]]);
        if ($stmt->fetchColumn() == 0) {
            $hashedPassword = password_hash($intern[1], PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (username, password, user_type, email, full_name, department) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$intern[0], $hashedPassword, $intern[2], $intern[3], $intern[4] . ' ' . $intern[5], $intern[8]]);
            $userId = $db->lastInsertId();
            $q = $db->prepare('SELECT id FROM users WHERE username = ?');
            $q->execute([$intern[9]]);
            $supRow = $q->fetch(PDO::FETCH_ASSOC);
            $supervisorId = $supRow ? $supRow['id'] : null;
            $stmt = $db->prepare('INSERT INTO interns (user_id, first_name, last_name, email, id_card, university, department, supervisor_id, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $intern[4], $intern[5], $intern[3], $intern[6], $intern[7], $intern[8], $supervisorId, $intern[10], $intern[11]]);
        }
    }

    return true;
}

function str_ends_with_custom($haystack, $needle) {
    $len = strlen($needle);
    if ($len === 0) return true;
    return substr($haystack, -$len) === $needle;
}
function isStaffEmail($email) {
    global $staffDomain;
    $e = strtolower((string)$email);
    $d = strtolower((string)$staffDomain);
    if (function_exists('str_ends_with')) return str_ends_with($e, $d);
    return str_ends_with_custom($e, $d);
}

// Function to check login credentials using email and user type (with basic domain enforcement)
function checkLoginByEmail($email, $password) {
    global $maxLoginAttempts, $loginLockDuration;
    $db = getDbConnection();
    
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Email incorrect'];
    }
    
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remainingTime = ceil((strtotime($user['locked_until']) - time()) / 60);
        return ['success' => false, 'message' => "Compte temporairement bloqué. Réessayez dans $remainingTime minutes."];
    }
    
    if (password_verify($password, $user['password'])) {
        // Enforce domain for staff roles
        $role = strtolower($user['user_type']);
        if (in_array($role, ['admin','director','supervisor']) && !isStaffEmail($user['email'])) {
            return ['success' => false, 'message' => 'Domaine email invalide pour un compte interne'];
        }
        // Reset login attempts on successful login
        $stmt = $db->prepare('UPDATE users SET login_attempts = 0, last_login = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$user['id']]);
        
        return [
            'success' => true,
            'user_id' => $user['id'],
            'user_type' => $user['user_type'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'department' => $user['department'],
            // onboarding removed
        ];
    } else {
        $newAttempts = ($user['login_attempts'] + 1);
        if ($newAttempts >= $maxLoginAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + $loginLockDuration);
            $stmt = $db->prepare('UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?');
            $stmt->execute([$newAttempts, $lockedUntil, $user['id']]);
            return ['success' => false, 'message' => "Trop de tentatives échouées. Compte bloqué pendant 15 minutes."];
        } else {
            $stmt = $db->prepare('UPDATE users SET login_attempts = ? WHERE id = ?');
            $stmt->execute([$newAttempts, $user['id']]);
            $remainingAttempts = $maxLoginAttempts - $newAttempts;
            return ['success' => false, 'message' => "Mot de passe incorrect. Tentatives restantes: $remainingAttempts"];
        }
    }
}

// Backward-compatible alias: if older code passes username, map to email if it looks like one
function checkLogin($usernameOrEmail, $password) {
    // If it contains '@', treat as email directly
    if (strpos((string)$usernameOrEmail, '@') !== false) {
        return checkLoginByEmail($usernameOrEmail, $password);
    }
    // Try to find by username and then reuse email-based logic
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT email FROM users WHERE username = ?');
    $stmt->execute([$usernameOrEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['success' => false, 'message' => 'Identifiants incorrects'];
    }
    return checkLoginByEmail($row['email'], $password);
}

// Password reset helpers removed per offline policy

// Create intern user from application (limited access until paperwork verified)
// createInternFromApplication removed with application feature

// Get interns: admin=all, director=by dept, supervisor=by self
function getAllInterns($scopeUserId = null, $scopeRole = null, $scopeDept = null) {
    $db = getDbConnection();
    // Join supervisors (s) to allow director scoping via director_id
    $sql = 'SELECT i.*, sup.full_name AS supervisor_name 
            FROM interns i 
            LEFT JOIN users sup ON i.supervisor_id = sup.id';
    $params = [];
    if ($scopeRole === 'supervisor' && $scopeUserId) {
        $sql .= ' WHERE i.supervisor_id = ?';
        $params[] = $scopeUserId;
    } elseif ($scopeRole === 'director' && $scopeUserId) {
        $sql .= ' WHERE sup.director_id = ?';
        $params[] = $scopeUserId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get supervisors: admin=all, director=by dept
function getSupervisors($scopeRole = null, $scopeDept = null, $scopeUserId = null) {
    $db = getDbConnection();
    $sql = 'SELECT id, username, full_name, email, department, director_id FROM users WHERE user_type = "supervisor"';
    $params = [];
    if ($scopeRole === 'director' && $scopeUserId) {
        $sql .= ' AND director_id = ?';
        $params[] = $scopeUserId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDepartments() {
    $db = getDbConnection();
    $stmt = $db->query('SELECT id, name FROM departments ORDER BY name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Attestation stats unchanged
function getAttestationStats() {
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT COUNT(*) FROM attestations');
    $stmt->execute();
    $total = $stmt->fetchColumn();
    $stmt = $db->prepare('SELECT COUNT(*) FROM attestations WHERE strftime("%m", generation_date) = strftime("%m", "now")');
    $stmt->execute();
    $currentMonth = $stmt->fetchColumn();
    $stmt = $db->prepare('SELECT COUNT(*) FROM attestations WHERE sent_by_email = 1');
    $stmt->execute();
    $emailsSent = $stmt->fetchColumn();
    $stmt = $db->prepare('SELECT COUNT(*) FROM attestations WHERE downloaded = 1');
    $stmt->execute();
    $downloaded = $stmt->fetchColumn();
    $stmt = $db->prepare('SELECT i.department, COUNT(*) as count 
                         FROM attestations a 
                         JOIN interns i ON a.intern_id = i.id 
                         GROUP BY i.department');
    $stmt->execute();
    $byDepartment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return [
        'total' => $total,
        'current_month' => $currentMonth,
        'emails_sent' => $emailsSent,
        'downloads' => $downloaded,
        'by_department' => $byDepartment
    ];
}

// Initialize database if it doesn't exist
if (!file_exists($dbPath)) {
    initializeDatabase();
}
?>
