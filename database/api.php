<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';
require_once 'functions.php';

// Check if the method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    exit;
}

// Get the action from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $data['action'];

switch ($action) {
    case 'login':
        // Email-only authentication
        $email = isset($data['email']) ? trim($data['email']) : null;
        $password = isset($data['password']) ? $data['password'] : null;
        if (!$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
            exit;
        }
        $result = checkLoginByEmail($email, $password);
        
        if ($result['success']) {
            // Set session data (onboarding removed with application feature)
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['user_type'] = $result['user_type'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['full_name'] = $result['full_name'];
            $_SESSION['department'] = $result['department'];
        }
        
        echo json_encode($result);
        break;
        
    case 'logout':
        // Destroy the session
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        break;
        
    case 'check_session':
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => true, 
                'user_id' => $_SESSION['user_id'],
                'user_type' => $_SESSION['user_type'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No active session']);
        }
        break;
        
    case 'get_interns':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        $role = $_SESSION['user_type'];
    $dept = isset($_SESSION['department']) ? $_SESSION['department'] : null; // department retained for display but not for director scoping
        $userId = $_SESSION['user_id'];
        if (!in_array($role, ['admin','director','supervisor'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit;
        }
        $interns = getAllInterns($userId, $role, $dept);
        echo json_encode(['success' => true, 'interns' => $interns]);
        break;
        
    case 'get_supervisors':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        $role = $_SESSION['user_type'];
    $dept = isset($_SESSION['department']) ? $_SESSION['department'] : null;
    $userId = $_SESSION['user_id'];
        if (!in_array($role, ['admin','director'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit;
        }
    $supervisors = getSupervisors($role, $dept, $userId);
        echo json_encode(['success' => true, 'supervisors' => $supervisors]);
        break;

    case 'get_departments':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        echo json_encode(['success' => true, 'departments' => getDepartments()]);
        break;
        
    case 'get_attestation_stats':
        // Check if user is admin or supervisor
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'supervisor')) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit;
        }
        
        $stats = getAttestationStats();
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;


        
    case 'get_reports':
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        try {
            $db = getDbConnection();
            $stmt = $db->prepare('SELECT r.id, r.intern_id, r.title, r.submission_date, r.status, i.first_name, i.last_name, d.name AS department 
                                  FROM reports r 
                                  JOIN interns i ON r.intern_id = i.id 
                                  LEFT JOIN departments d ON d.id = i.department_id
                                  WHERE r.status IN ("submitted","approved")
                                  ORDER BY r.submission_date DESC');
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'reports' => $rows]);
        } catch (Exception $ex) {
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $ex->getMessage()]);
        }
        break;
        
    // CRUD operations for interns
    case 'add_intern':
        // Check if user is authorized (admin or supervisor)
        if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'supervisor'])) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        $requiredFields = ['first_name', 'last_name', 'email', 'id_card', 'university', 
                           'department', 'supervisor_id', 'start_date', 'end_date', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
                exit;
            }
        }
        
        $result = addIntern($data);
        echo json_encode($result);
        break;
        
    case 'update_intern':
        // Check if user is authorized (admin or supervisor)
        if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'supervisor'])) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['id']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => "ID du stagiaire requis"]);
            exit;
        }
        
        $requiredFields = ['first_name', 'last_name', 'email', 'id_card', 'university', 
                           'department', 'supervisor_id', 'start_date', 'end_date', 'status'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
                exit;
            }
        }
        
        $result = updateIntern($data['id'], $data);
        echo json_encode($result);
        break;
        
    case 'delete_intern':
        // Check if user is authorized (admin only)
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['id']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => "ID du stagiaire requis"]);
            exit;
        }
        
        $result = deleteIntern($data['id']);
        echo json_encode($result);
        break;
        
    // CRUD operations for supervisors
    case 'add_supervisor':
        // Check if user is authorized (admin or director)
        if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'director'])) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        $requiredFields = ['first_name', 'last_name', 'email', 'department', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
                exit;
            }
        }
        
        $result = addSupervisor($data);
        echo json_encode($result);
        break;
        
    case 'update_supervisor':
        // Check if user is authorized (admin or director)
        if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'director'])) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['id']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => "ID du superviseur requis"]);
            exit;
        }
        
        $requiredFields = ['first_name', 'last_name', 'email', 'department', 'director_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
                exit;
            }
        }
        
        $result = updateSupervisor($data['id'], $data);
        echo json_encode($result);
        break;
        
    case 'delete_supervisor':
        // Check if user is authorized (admin only)
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['id']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => "ID du superviseur requis"]);
            exit;
        }
        
        $result = deleteSupervisor($data['id']);
        echo json_encode($result);
        break;
        
    // CRUD operations for departments
    case 'add_department':
        // Check if user is authorized (admin only)
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['name']) || empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => "Nom du département requis"]);
            exit;
        }
        
        $result = addDepartment($data['name']);
        echo json_encode($result);
        break;
        
    case 'update_department':
        // Check if user is authorized (admin only)
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['id']) || empty($data['id']) || !isset($data['name']) || empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => "ID et nom du département requis"]);
            exit;
        }
        
        $result = updateDepartment($data['id'], $data['name']);
        echo json_encode($result);
        break;
        
    case 'delete_department':
        // Check if user is authorized (admin only)
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['id']) || empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => "ID du département requis"]);
            exit;
        }
        
        $result = deleteDepartment($data['id']);
        echo json_encode($result);
        break;
        
    // CRUD operations for reports
    case 'add_report':
        // Check if user is logged in and is an intern
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'intern') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['intern_id']) || empty($data['intern_id']) || !isset($data['title']) || empty($data['title'])) {
            echo json_encode(['success' => false, 'message' => "ID du stagiaire et titre du rapport requis"]);
            exit;
        }
        
        $result = addReport($data);
        echo json_encode($result);
        break;
        
    case 'update_report_status':
        // Check if user is authorized (admin or supervisor)
        if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'supervisor'])) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['id']) || empty($data['id']) || !isset($data['status']) || empty($data['status'])) {
            echo json_encode(['success' => false, 'message' => "ID du rapport et statut requis"]);
            exit;
        }
        
        $result = updateReportStatus($data['id'], $data['status']);
        echo json_encode($result);
        break;
        
    // CRUD operations for evaluations
    case 'add_evaluation':
        // Check if user is authorized (supervisor only)
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'supervisor') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        $requiredFields = ['intern_id', 'supervisor_id', 'score', 'notes'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
                exit;
            }
        }
        
        $result = addEvaluation($data);
        echo json_encode($result);
        break;
        
    case 'update_evaluation':
        // Check if user is authorized (supervisor only)
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'supervisor') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }
        
        // Validate data
        if (!isset($data['id']) || empty($data['id']) || !isset($data['score']) || !isset($data['notes'])) {
            echo json_encode(['success' => false, 'message' => "ID de l'évaluation, score et notes requis"]);
            exit;
        }
        
        $result = updateEvaluation($data['id'], $data);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue: ' . $action]);
}
?>
