<?php
// This file contains CRUD functions for the STEG intern management system

// Ensure we don't redeclare functions that are already in db.php
if (!function_exists('addIntern')) {

/**
 * Add a new intern to the database
 * @param array $internData The intern data
 * @return array Success status and message
 */
function addIntern($internData) {
    try {
        $db = getDbConnection();
        // Resolve department id from provided name
        $deptName = isset($internData['department']) ? $internData['department'] : null;
        $deptId = null;
        if ($deptName) {
            $q = $db->prepare('SELECT id FROM departments WHERE name = ?');
            $q->execute([$deptName]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            if ($r) { $deptId = (int)$r['id']; }
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Create user account for the intern
        $hashedPassword = password_hash($internData['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, password, user_type, email, full_name, department_id) 
                              VALUES (?, ?, ?, ?, ?, ?)');
        $username = generateUsername($internData['first_name'], $internData['last_name']);
        $fullName = $internData['first_name'] . ' ' . $internData['last_name'];
        $stmt->execute([$username, $hashedPassword, 'intern', $internData['email'], $fullName, $deptId]);
        $userId = $db->lastInsertId();
        
        // Add intern record
        $stmt = $db->prepare('INSERT INTO interns (user_id, first_name, last_name, email, id_card, university, 
                             department_id, supervisor_id, start_date, end_date) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $internData['first_name'],
            $internData['last_name'],
            $internData['email'],
            $internData['id_card'],
            $internData['university'],
            $deptId,
            $internData['supervisor_id'],
            $internData['start_date'],
            $internData['end_date']
        ]);
        
        // Commit transaction
        $db->commit();
        
        return ['success' => true, 'message' => 'Stagiaire ajouté avec succès', 'id' => $db->lastInsertId()];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout du stagiaire: ' . $e->getMessage()];
    }
}

/**
 * Update an existing intern's information
 * @param int $internId The intern ID
 * @param array $internData The updated intern data
 * @return array Success status and message
 */
function updateIntern($internId, $internData) {
    try {
        $db = getDbConnection();
        // Resolve department id from provided name
        $deptName = isset($internData['department']) ? $internData['department'] : null;
        $deptId = null;
        if ($deptName) {
            $q = $db->prepare('SELECT id FROM departments WHERE name = ?');
            $q->execute([$deptName]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            if ($r) { $deptId = (int)$r['id']; }
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Get user ID associated with this intern
        $stmt = $db->prepare('SELECT user_id FROM interns WHERE id = ?');
        $stmt->execute([$internId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Stagiaire non trouvé'];
        }
        
        $userId = $user['user_id'];
        
        // Update user info
    $stmt = $db->prepare('UPDATE users SET email = ?, full_name = ?, department_id = ? WHERE id = ?');
        $fullName = $internData['first_name'] . ' ' . $internData['last_name'];
    $stmt->execute([$internData['email'], $fullName, $deptId, $userId]);
        
        // Update intern info
    $stmt = $db->prepare('UPDATE interns SET 
                            first_name = ?, 
                            last_name = ?, 
                            email = ?, 
                            id_card = ?, 
                            university = ?, 
                department_id = ?, 
                            supervisor_id = ?, 
                            start_date = ?, 
                            end_date = ?, 
                            status = ? 
                            WHERE id = ?');
        $stmt->execute([
            $internData['first_name'],
            $internData['last_name'],
            $internData['email'],
            $internData['id_card'],
            $internData['university'],
        $deptId,
            $internData['supervisor_id'],
            $internData['start_date'],
            $internData['end_date'],
            $internData['status'],
            $internId
        ]);
        
        // Commit transaction
        $db->commit();
        
        return ['success' => true, 'message' => 'Informations du stagiaire mises à jour'];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
    }
}

/**
 * Delete an intern and their user account
 * @param int $internId The intern ID
 * @return array Success status and message
 */
function deleteIntern($internId) {
    try {
        $db = getDbConnection();
        
        // Begin transaction
        $db->beginTransaction();
        
        // Get user ID associated with this intern
        $stmt = $db->prepare('SELECT user_id FROM interns WHERE id = ?');
        $stmt->execute([$internId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Stagiaire non trouvé'];
        }
        
        $userId = $user['user_id'];
        
        // Delete from interns table
        $stmt = $db->prepare('DELETE FROM interns WHERE id = ?');
        $stmt->execute([$internId]);
        
        // Delete from users table
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        
        // Delete related data (reports, evaluations)
        $stmt = $db->prepare('DELETE FROM reports WHERE intern_id = ?');
        $stmt->execute([$internId]);
        
        $stmt = $db->prepare('DELETE FROM evaluations WHERE intern_id = ?');
        $stmt->execute([$internId]);
        
        // Commit transaction
        $db->commit();
        
        return ['success' => true, 'message' => 'Stagiaire supprimé avec succès'];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
    }
}

/**
 * Add a new supervisor to the database
 * @param array $supervisorData The supervisor data
 * @return array Success status and message
 */
function addSupervisor($supervisorData) {
    try {
        $db = getDbConnection();
        // Resolve department id from provided name
        $deptId = null;
        if (!empty($supervisorData['department'])) {
            $q = $db->prepare('SELECT id FROM departments WHERE name = ?');
            $q->execute([$supervisorData['department']]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            if ($r) { $deptId = (int)$r['id']; }
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Check if email is a valid STEG email
        if (!isStaffEmail($supervisorData['email'])) {
            return ['success' => false, 'message' => 'L\'email doit être un email STEG valide (@steg.tn)'];
        }
        
        // Create user account for the supervisor
        $hashedPassword = password_hash($supervisorData['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password, user_type, email, full_name, department_id, director_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)');
        $username = generateUsername($supervisorData['first_name'], $supervisorData['last_name']);
        $fullName = $supervisorData['first_name'] . ' ' . $supervisorData['last_name'];
        $stmt->execute([
            $username,
            $hashedPassword,
            'supervisor',
            $supervisorData['email'],
            $fullName,
            $deptId,
            $supervisorData['director_id']
        ]);
        
        // Commit transaction
        $db->commit();
        
        return ['success' => true, 'message' => 'Superviseur ajouté avec succès', 'id' => $db->lastInsertId()];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout du superviseur: ' . $e->getMessage()];
    }
}

/**
 * Update an existing supervisor's information
 * @param int $supervisorId The supervisor ID (user ID)
 * @param array $supervisorData The updated supervisor data
 * @return array Success status and message
 */
function updateSupervisor($supervisorId, $supervisorData) {
    try {
        $db = getDbConnection();
        
        // Check if supervisor exists
        $stmt = $db->prepare('SELECT id FROM users WHERE id = ? AND user_type = "supervisor"');
        $stmt->execute([$supervisorId]);
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Superviseur non trouvé'];
        }
        
        // Check if email is a valid STEG email
        if (!isStaffEmail($supervisorData['email'])) {
            return ['success' => false, 'message' => 'L\'email doit être un email STEG valide (@steg.tn)'];
        }
        
        // Update user info
        // Resolve department id from provided name
        $deptId = null;
        if (!empty($supervisorData['department'])) {
            $q = $db->prepare("SELECT id FROM departments WHERE name = ?");
            $q->execute([$supervisorData['department']]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            if ($r) { $deptId = (int)$r['id']; }
        }
        $stmt = $db->prepare('UPDATE users SET 
                             email = ?, 
                             full_name = ?, 
                             department_id = ?, 
                             director_id = ? 
                             WHERE id = ?');
        $fullName = $supervisorData['first_name'] . ' ' . $supervisorData['last_name'];
        $stmt->execute([
            $supervisorData['email'],
            $fullName,
            $deptId,
            $supervisorData['director_id'],
            $supervisorId
        ]);
        
        return ['success' => true, 'message' => 'Informations du superviseur mises à jour'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
    }
}

/**
 * Delete a supervisor
 * @param int $supervisorId The supervisor ID (user ID)
 * @return array Success status and message
 */
function deleteSupervisor($supervisorId) {
    try {
        $db = getDbConnection();
        
        // Begin transaction
        $db->beginTransaction();
        
        // Check if supervisor exists
        $stmt = $db->prepare('SELECT id FROM users WHERE id = ? AND user_type = "supervisor"');
        $stmt->execute([$supervisorId]);
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Superviseur non trouvé'];
        }
        
        // Check if supervisor has interns
        $stmt = $db->prepare('SELECT COUNT(*) FROM interns WHERE supervisor_id = ?');
        $stmt->execute([$supervisorId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer ce superviseur car il a des stagiaires assignés'];
        }
        
        // Delete from users table
        $stmt = $db->prepare('DELETE FROM users WHERE id = ? AND user_type = "supervisor"');
        $stmt->execute([$supervisorId]);
        
        // Commit transaction
        $db->commit();
        
        return ['success' => true, 'message' => 'Superviseur supprimé avec succès'];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
    }
}

/**
 * Add a new department
 * @param string $departmentName The department name
 * @return array Success status and message
 */
function addDepartment($departmentName) {
    try {
        $db = getDbConnection();
        
        // Check if department already exists
        $stmt = $db->prepare('SELECT id FROM departments WHERE name = ?');
        $stmt->execute([$departmentName]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Ce département existe déjà'];
        }
        
        // Add department
        $stmt = $db->prepare('INSERT INTO departments (name) VALUES (?)');
        $stmt->execute([$departmentName]);
        
        return ['success' => true, 'message' => 'Département ajouté avec succès', 'id' => $db->lastInsertId()];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout du département: ' . $e->getMessage()];
    }
}

/**
 * Update a department name
 * @param int $departmentId The department ID
 * @param string $departmentName The new department name
 * @return array Success status and message
 */
function updateDepartment($departmentId, $departmentName) {
    try {
        $db = getDbConnection();
        
        // Check if department exists
        $stmt = $db->prepare('SELECT id FROM departments WHERE id = ?');
        $stmt->execute([$departmentId]);
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Département non trouvé'];
        }
        
        // Check if name already exists
        $stmt = $db->prepare('SELECT id FROM departments WHERE name = ? AND id != ?');
        $stmt->execute([$departmentName, $departmentId]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Ce nom de département existe déjà'];
        }
        
        // Update department
        $stmt = $db->prepare('UPDATE departments SET name = ? WHERE id = ?');
        $stmt->execute([$departmentName, $departmentId]);
        
        return ['success' => true, 'message' => 'Département mis à jour avec succès'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
    }
}

/**
 * Delete a department
 * @param int $departmentId The department ID
 * @return array Success status and message
 */
function deleteDepartment($departmentId) {
    try {
        $db = getDbConnection();
        
        // Check if department exists
        $stmt = $db->prepare('SELECT id FROM departments WHERE id = ?');
        $stmt->execute([$departmentId]);
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Département non trouvé'];
        }
        
        // Check if department is in use
        $stmt = $db->prepare('SELECT 1 FROM users WHERE department_id = ? LIMIT 1');
        $stmt->execute([$departmentId]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer ce département car il est utilisé'];
        }
        $stmt = $db->prepare('SELECT 1 FROM interns WHERE department_id = ? LIMIT 1');
        $stmt->execute([$departmentId]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer ce département car il est utilisé par des stagiaires'];
        }
        
        // Delete department
        $stmt = $db->prepare('DELETE FROM departments WHERE id = ?');
        $stmt->execute([$departmentId]);
        
        return ['success' => true, 'message' => 'Département supprimé avec succès'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
    }
}

/**
 * Add a report submission
 * @param array $reportData The report data
 * @return array Success status and message
 */
function addReport($reportData) {
    try {
        $db = getDbConnection();
        
        // Add report
        $stmt = $db->prepare('INSERT INTO reports (intern_id, title, submission_date, status) 
                             VALUES (?, ?, CURRENT_TIMESTAMP, "submitted")');
        $stmt->execute([$reportData['intern_id'], $reportData['title']]);
        
        return ['success' => true, 'message' => 'Rapport soumis avec succès', 'id' => $db->lastInsertId()];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors de la soumission du rapport: ' . $e->getMessage()];
    }
}

/**
 * Update report status
 * @param int $reportId The report ID
 * @param string $status The new status
 * @return array Success status and message
 */
function updateReportStatus($reportId, $status) {
    try {
        $db = getDbConnection();
        
        // Check if report exists
        $stmt = $db->prepare('SELECT id FROM reports WHERE id = ?');
        $stmt->execute([$reportId]);
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Rapport non trouvé'];
        }
        
        // Update status
        $stmt = $db->prepare('UPDATE reports SET status = ? WHERE id = ?');
        $stmt->execute([$status, $reportId]);
        
        return ['success' => true, 'message' => 'Statut du rapport mis à jour'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
    }
}

/**
 * Add an evaluation for an intern
 * @param array $evaluationData The evaluation data
 * @return array Success status and message
 */
function addEvaluation($evaluationData) {
    try {
        $db = getDbConnection();
        
        // Add evaluation
        $stmt = $db->prepare('INSERT INTO evaluations (intern_id, supervisor_id, score, notes, date) 
                             VALUES (?, ?, ?, ?, CURRENT_DATE)');
        $stmt->execute([
            $evaluationData['intern_id'], 
            $evaluationData['supervisor_id'], 
            $evaluationData['score'], 
            $evaluationData['notes']
        ]);
        
        return ['success' => true, 'message' => 'Évaluation ajoutée avec succès', 'id' => $db->lastInsertId()];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'évaluation: ' . $e->getMessage()];
    }
}

/**
 * Update an evaluation
 * @param int $evaluationId The evaluation ID
 * @param array $evaluationData The updated evaluation data
 * @return array Success status and message
 */
function updateEvaluation($evaluationId, $evaluationData) {
    try {
        $db = getDbConnection();
        
        // Check if evaluation exists
        $stmt = $db->prepare('SELECT id FROM evaluations WHERE id = ?');
        $stmt->execute([$evaluationId]);
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Évaluation non trouvée'];
        }
        
        // Update evaluation
        $stmt = $db->prepare('UPDATE evaluations SET score = ?, notes = ? WHERE id = ?');
        $stmt->execute([$evaluationData['score'], $evaluationData['notes'], $evaluationId]);
        
        return ['success' => true, 'message' => 'Évaluation mise à jour avec succès'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
    }
}

/**
 * Generate a username based on first name and last name
 * @param string $firstName The first name
 * @param string $lastName The last name
 * @return string The generated username
 */
function generateUsername($firstName, $lastName) {
    // Remove accents and special characters
    $firstName = preg_replace('/[^a-zA-Z0-9]/', '', $firstName);
    $lastName = preg_replace('/[^a-zA-Z0-9]/', '', $lastName);
    
    // Create base username
    $username = strtolower(substr($firstName, 0, 1) . $lastName);
    
    // Check if username exists
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username LIKE ?');
    $stmt->execute([$username . '%']);
    $count = $stmt->fetchColumn();
    
    // If username exists, add a number
    if ($count > 0) {
        $username .= ($count + 1);
    }
    
    return $username;
}

// isStaffEmail is already defined in db.php, so we don't define it here

} // End of !function_exists('addIntern') block

?>
