<?php
require_once 'config.php';

// Get all available years
function getYears() {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT DISTINCT année FROM students ORDER BY année DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $years = [];
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['année'];
    }
    
    return $years;
}

// Get all available sections
function getSections() {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT DISTINCT section FROM students ORDER BY section ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row['section'];
    }
    
    return $sections;
}

// Search students based on criteria
function searchStudents($searchTerm = '', $year = '', $section = '') {
    $conn = getDbConnection();
    
    $sql = "SELECT * FROM students WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($searchTerm)) {
        $sql .= " AND (no_ordre LIKE ? OR nom LIKE ? OR prenom LIKE ? OR no_fiche LIKE ?)";
        $searchTerm = "%$searchTerm%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ssss";
    }
    
    if (!empty($year)) {
        $sql .= " AND année = ?";
        $params[] = $year;
        $types .= "i";
    }
    
    if (!empty($section)) {
        $sql .= " AND section = ?";
        $params[] = $section;
        $types .= "s";
    }
    
    $sql .= " ORDER BY nom ASC, prenom ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    return $students;
}

// Get student by ID
function getStudentById($id) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Get student by No d'ordre
function getStudentByNoOrdre($noOrdre) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT * FROM students WHERE no_ordre = ?");
    $stmt->bind_param("s", $noOrdre);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Create new student
function createStudent($data) {
    $conn = getDbConnection();
    
    // Calculate totals and averages for Session 1
    $total_s1 = $data['droit_civil_s1'] + $data['procedure_penale_s1'] + 
                $data['contentieux_administratif_s1'] + $data['securites_sociales_s1'] + 
                $data['voies_execution_s1'] + $data['procedure_commerciale_s1'] + 
                $data['droits_humains_s1'];
    $moyenne_s1 = $total_s1 / 7;
    
    // Calculate totals and averages for Session 2
    $total_s2 = $data['droit_civil_s2'] + $data['procedure_penale_s2'] + 
                $data['contentieux_administratif_s2'] + $data['securites_sociales_s2'] + 
                $data['voies_execution_s2'] + $data['procedure_commerciale_s2'] + 
                $data['droits_humains_s2'];
    $moyenne_s2 = $total_s2 / 7;
    
    // Calculate final grades (average of both sessions)
    $droit_civil = ($data['droit_civil_s1'] + $data['droit_civil_s2']) / 2;
    $procedure_penale = ($data['procedure_penale_s1'] + $data['procedure_penale_s2']) / 2;
    $contentieux_administratif = ($data['contentieux_administratif_s1'] + $data['contentieux_administratif_s2']) / 2;
    $securites_sociales = ($data['securites_sociales_s1'] + $data['securites_sociales_s2']) / 2;
    $voies_execution = ($data['voies_execution_s1'] + $data['voies_execution_s2']) / 2;
    $procedure_commerciale = ($data['procedure_commerciale_s1'] + $data['procedure_commerciale_s2']) / 2;
    $droits_humains = ($data['droits_humains_s1'] + $data['droits_humains_s2']) / 2;
    
    $total = $droit_civil + $procedure_penale + $contentieux_administratif + 
             $securites_sociales + $voies_execution + $procedure_commerciale + 
             $droits_humains;
    $moyenne = $total / 7;
    
    // Determine verdict and reprises
    $reprises = [];
    
    // Check each subject for reprise (failed if <= 64)
    if ($droit_civil <= 64) $reprises[] = 'droit_civil';
    if ($procedure_penale <= 64) $reprises[] = 'procedure_penale';
    if ($contentieux_administratif <= 64) $reprises[] = 'contentieux_administratif';
    if ($securites_sociales <= 64) $reprises[] = 'securites_sociales';
    if ($voies_execution <= 64) $reprises[] = 'voies_execution';
    if ($procedure_commerciale <= 64) $reprises[] = 'procedure_commerciale';
    if ($droits_humains <= 64) $reprises[] = 'droits_humains';
    
    // Determine final verdict based on new rules
    if ($moyenne >= 65) {
        $verdict = 'Réussi';
    } elseif (!empty($reprises) && min($droit_civil, $procedure_penale, $contentieux_administratif, 
            $securites_sociales, $voies_execution, $procedure_commerciale, $droits_humains) >= 55) {
        $verdict = 'Reprise';
    } else {
        $verdict = 'Échec';
    }
    
    // Convert reprises array to JSON
    $reprisesJson = !empty($reprises) ? json_encode($reprises) : null;
    
    $stmt = $conn->prepare("INSERT INTO students (
        no_ordre, 
        nom, 
        prenom, 
        no_fiche, 
        section, 
        année, 
        droit_civil_s1, 
        procedure_penale_s1, 
        contentieux_administratif_s1, 
        securites_sociales_s1, 
        voies_execution_s1, 
        procedure_commerciale_s1, 
        droits_humains_s1, 
        total_s1, 
        moyenne_s1, 
        droit_civil_s2, 
        procedure_penale_s2, 
        contentieux_administratif_s2, 
        securites_sociales_s2, 
        voies_execution_s2, 
        procedure_commerciale_s2, 
        droits_humains_s2, 
        total_s2, 
        moyenne_s2, 
        droit_civil, 
        procedure_penale, 
        contentieux_administratif, 
        securites_sociales, 
        voies_execution, 
        procedure_commerciale, 
        droits_humains, 
        total, 
        moyenne, 
        verdict,
        reprises
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )");
    
    $stmt->bind_param("sssssiiiiiiiidiiiiiiiiiddddddddss", 
        $data['no_ordre'], 
        $data['nom'], 
        $data['prenom'], 
        $data['no_fiche'], 
        $data['section'], 
        $data['année'], 
        $data['droit_civil_s1'], 
        $data['procedure_penale_s1'], 
        $data['contentieux_administratif_s1'], 
        $data['securites_sociales_s1'], 
        $data['voies_execution_s1'], 
        $data['procedure_commerciale_s1'], 
        $data['droits_humains_s1'],
        $total_s1,
        $moyenne_s1,
        $data['droit_civil_s2'], 
        $data['procedure_penale_s2'], 
        $data['contentieux_administratif_s2'], 
        $data['securites_sociales_s2'], 
        $data['voies_execution_s2'], 
        $data['procedure_commerciale_s2'], 
        $data['droits_humains_s2'],
        $total_s2,
        $moyenne_s2,
        $droit_civil,
        $procedure_penale,
        $contentieux_administratif,
        $securites_sociales,
        $voies_execution,
        $procedure_commerciale,
        $droits_humains,
        $total,
        $moyenne,
        $verdict,
        $reprisesJson
    );
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

function updateStudent($id, $data) {
    $conn = getDbConnection();
    
    // Calculate totals and averages for Session 1
    $total_s1 = $data['droit_civil_s1'] + $data['procedure_penale_s1'] + 
                $data['contentieux_administratif_s1'] + $data['securites_sociales_s1'] + 
                $data['voies_execution_s1'] + $data['procedure_commerciale_s1'] + 
                $data['droits_humains_s1'];
    $moyenne_s1 = $total_s1 / 7;
    
    // Calculate totals and averages for Session 2
    $total_s2 = $data['droit_civil_s2'] + $data['procedure_penale_s2'] + 
                $data['contentieux_administratif_s2'] + $data['securites_sociales_s2'] + 
                $data['voies_execution_s2'] + $data['procedure_commerciale_s2'] + 
                $data['droits_humains_s2'];
    $moyenne_s2 = $total_s2 / 7;
    
    // Calculate final grades (average of both sessions)
    $droit_civil = ($data['droit_civil_s1'] + $data['droit_civil_s2']) / 2;
    $procedure_penale = ($data['procedure_penale_s1'] + $data['procedure_penale_s2']) / 2;
    $contentieux_administratif = ($data['contentieux_administratif_s1'] + $data['contentieux_administratif_s2']) / 2;
    $securites_sociales = ($data['securites_sociales_s1'] + $data['securites_sociales_s2']) / 2;
    $voies_execution = ($data['voies_execution_s1'] + $data['voies_execution_s2']) / 2;
    $procedure_commerciale = ($data['procedure_commerciale_s1'] + $data['procedure_commerciale_s2']) / 2;
    $droits_humains = ($data['droits_humains_s1'] + $data['droits_humains_s2']) / 2;
    
    $total = $total_s1 + $total_s2;
    $moyenne = ($moyenne_s1 + $moyenne_s2) / 2;
    
    // Determine verdict and reprises
    $reprises = [];
    
    // Check each subject for reprise (failed if <= 64)
    if ($droit_civil <= 64) $reprises[] = 'droit_civil';
    if ($procedure_penale <= 64) $reprises[] = 'procedure_penale';
    if ($contentieux_administratif <= 64) $reprises[] = 'contentieux_administratif';
    if ($securites_sociales <= 64) $reprises[] = 'securites_sociales';
    if ($voies_execution <= 64) $reprises[] = 'voies_execution';
    if ($procedure_commerciale <= 64) $reprises[] = 'procedure_commerciale';
    if ($droits_humains <= 64) $reprises[] = 'droits_humains';
    
    // Determine final verdict based on new rules
    if ($moyenne >= 65) {
        $verdict = 'Réussi';
    } elseif (!empty($reprises) && min($droit_civil, $procedure_penale, $contentieux_administratif, 
            $securites_sociales, $voies_execution, $procedure_commerciale, $droits_humains) >= 55) {
        $verdict = 'Reprise';
    } else {
        $verdict = 'Échec';
    }
    
    // Convert reprises array to JSON
    $reprisesJson = !empty($reprises) ? json_encode($reprises) : null;
    
    $sql = "UPDATE students SET 
        no_ordre = ?, 
        nom = ?, 
        prenom = ?, 
        no_fiche = ?, 
        section = ?, 
        année = ?, 
        droit_civil_s1 = ?, 
        procedure_penale_s1 = ?, 
        contentieux_administratif_s1 = ?, 
        securites_sociales_s1 = ?, 
        voies_execution_s1 = ?, 
        procedure_commerciale_s1 = ?, 
        droits_humains_s1 = ?, 
        total_s1 = ?, 
        moyenne_s1 = ?, 
        droit_civil_s2 = ?, 
        procedure_penale_s2 = ?, 
        contentieux_administratif_s2 = ?, 
        securites_sociales_s2 = ?, 
        voies_execution_s2 = ?, 
        procedure_commerciale_s2 = ?, 
        droits_humains_s2 = ?, 
        total_s2 = ?, 
        moyenne_s2 = ?, 
        droit_civil = ?, 
        procedure_penale = ?, 
        contentieux_administratif = ?, 
        securites_sociales = ?, 
        voies_execution = ?, 
        procedure_commerciale = ?, 
        droits_humains = ?, 
        total = ?, 
        moyenne = ?, 
        verdict = ?, 
        reprises = ? 
        WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssiiiiiiiiddiiiiiiiiiddddddddsssi", 
            $data['no_ordre'], 
            $data['nom'], 
            $data['prenom'], 
            $data['no_fiche'], 
            $data['section'], 
            $data['année'], 
            $data['droit_civil_s1'], 
            $data['procedure_penale_s1'], 
            $data['contentieux_administratif_s1'], 
            $data['securites_sociales_s1'], 
            $data['voies_execution_s1'], 
            $data['procedure_commerciale_s1'], 
            $data['droits_humains_s1'],
            $total_s1,
            $moyenne_s1,
            $data['droit_civil_s2'], 
            $data['procedure_penale_s2'], 
            $data['contentieux_administratif_s2'], 
            $data['securites_sociales_s2'], 
            $data['voies_execution_s2'], 
            $data['procedure_commerciale_s2'], 
            $data['droits_humains_s2'],
            $total_s2,
            $moyenne_s2,
            $droit_civil,
            $procedure_penale,
            $contentieux_administratif,
            $securites_sociales,
            $voies_execution,
            $procedure_commerciale,
            $droits_humains,
            $total,
            $moyenne,
            $verdict,
            $reprisesJson,
            $id
        );
        
        return $stmt->execute();
    }
    
    return false;
}

// Delete student
function deleteStudent($id) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

// Payment functions
function createPayment($studentId, $montant, $reference, $datePaiement, $userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO payments (student_id, montant, reference, date_paiement, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idssi", $studentId, $montant, $reference, $datePaiement, $userId);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

function getPaymentById($id) {
    $conn = getDbConnection();
    
    $sql = "SELECT p.*, 
            s.nom as student_nom, s.prenom as student_prenom, s.no_ordre,
            u1.username as created_by_username,
            u2.username as updated_by_username
            FROM payments p
            LEFT JOIN students s ON p.student_id = s.id
            LEFT JOIN users u1 ON p.created_by = u1.id
            LEFT JOIN users u2 ON p.updated_by = u2.id
            WHERE p.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

function updatePaymentStatus($id, $status, $commentaire, $userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE payments SET status = ?, commentaire = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("ssii", $status, $commentaire, $userId, $id);
    
    return $stmt->execute();
}

function getPendingPayments() {
    $conn = getDbConnection();
    
    $sql = "SELECT p.*, 
            s.nom as student_nom, s.prenom as student_prenom, s.no_ordre,
            u.username as created_by_username
            FROM payments p
            LEFT JOIN students s ON p.student_id = s.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.status = 'en_attente'
            ORDER BY p.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    return $payments;
}

function getAllPayments($limit = 20, $offset = 0, $filters = []) {
    $conn = getDbConnection();
    
    $sql = "SELECT p.*, 
            s.nom as student_nom, s.prenom as student_prenom, s.no_ordre,
            u.username as created_by_username
            FROM payments p
            LEFT JOIN students s ON p.student_id = s.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (isset($filters['student_id']) && !empty($filters['student_id'])) {
        $sql .= " AND p.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= "i";
    }
    
    if (isset($filters['status']) && !empty($filters['status'])) {
        $sql .= " AND p.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (isset($filters['date_from']) && !empty($filters['date_from'])) {
        $sql .= " AND p.date_paiement >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (isset($filters['date_to']) && !empty($filters['date_to'])) {
        $sql .= " AND p.date_paiement <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    return $payments;
}

function countAllPayments($filters = []) {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as total FROM payments p WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (isset($filters['student_id']) && !empty($filters['student_id'])) {
        $sql .= " AND p.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= "i";
    }
    
    if (isset($filters['status']) && !empty($filters['status'])) {
        $sql .= " AND p.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (isset($filters['date_from']) && !empty($filters['date_from'])) {
        $sql .= " AND p.date_paiement >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (isset($filters['date_to']) && !empty($filters['date_to'])) {
        $sql .= " AND p.date_paiement <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

function getStudentPayments($studentId) {
    $conn = getDbConnection();
    
    $sql = "SELECT p.*, u.username as created_by_username
            FROM payments p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.student_id = ?
            ORDER BY p.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    return $payments;
}

function hasApprovedPayment($studentId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payments WHERE student_id = ? AND status = 'approuve'");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

function getTotalPaidAmount($studentId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT SUM(montant) as total FROM payments WHERE student_id = ? AND status = 'approuve'");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'] ?? 0;
}

function getRemainingAmount($studentId) {
    $totalPaid = getTotalPaidAmount($studentId);
    $requiredAmount = 6000; // Montant fixe requis
    
    return $requiredAmount - $totalPaid;
}

// Document access logging
function logDocumentAccess($userId, $studentId, $documentType) {
    $conn = getDbConnection();
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO document_access_logs (user_id, student_id, document_type, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $userId, $studentId, $documentType, $ipAddress);
    
    return $stmt->execute();
}

function getDocumentAccessLogs($limit = 20, $offset = 0, $filters = []) {
    $conn = getDbConnection();
    
    $sql = "SELECT l.*, 
            u.username,
            s.nom, s.prenom, s.no_ordre
            FROM document_access_logs l
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN students s ON l.student_id = s.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (isset($filters['user_id']) && !empty($filters['user_id'])) {
        $sql .= " AND l.user_id = ?";
        $params[] = $filters['user_id'];
        $types .= "i";
    }
    
    if (isset($filters['student_id']) && !empty($filters['student_id'])) {
        $sql .= " AND l.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= "i";
    }
    
    if (isset($filters['document_type']) && !empty($filters['document_type'])) {
        $sql .= " AND l.document_type = ?";
        $params[] = $filters['document_type'];
        $types .= "s";
    }
    
    if (isset($filters['date_from']) && !empty($filters['date_from'])) {
        $sql .= " AND DATE(l.access_time) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (isset($filters['date_to']) && !empty($filters['date_to'])) {
        $sql .= " AND DATE(l.access_time) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $sql .= " ORDER BY l.access_time DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    return $logs;
}

function countDocumentAccessLogs($filters = []) {
    $conn = getDbConnection();
    
    $sql = "SELECT COUNT(*) as total FROM document_access_logs l WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (isset($filters['user_id']) && !empty($filters['user_id'])) {
        $sql .= " AND l.user_id = ?";
        $params[] = $filters['user_id'];
        $types .= "i";
    }
    
    if (isset($filters['student_id']) && !empty($filters['student_id'])) {
        $sql .= " AND l.student_id = ?";
        $params[] = $filters['student_id'];
        $types .= "i";
    }
    
    if (isset($filters['document_type']) && !empty($filters['document_type'])) {
        $sql .= " AND l.document_type = ?";
        $params[] = $filters['document_type'];
        $types .= "s";
    }
    
    if (isset($filters['date_from']) && !empty($filters['date_from'])) {
        $sql .= " AND DATE(l.access_time) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (isset($filters['date_to']) && !empty($filters['date_to'])) {
        $sql .= " AND DATE(l.access_time) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

// Ajouter une note de reprise
function addRepriseNote($studentId, $matiere, $noteReprise) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO student_reprises (student_id, matiere, note_reprise, date_reprise) VALUES (?, ?, ?, CURRENT_DATE)");
    $stmt->bind_param("isi", $studentId, $matiere, $noteReprise);
    
    if ($stmt->execute()) {
        // Mettre à jour la moyenne de l'étudiant
        updateStudentAverageAfterReprise($studentId);
        return true;
    }
    return false;
}

// Course management functions
function getAllCourses() {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT * FROM courses ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    return $courses;
}

function getCourseById($id) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

function createCourse($code, $name, $coefficient = 1, $description = '') {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO courses (code, name, coefficient, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $code, $name, $coefficient, $description);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

function updateCourse($id, $code, $name, $coefficient = 1, $description = '') {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE courses SET code = ?, name = ?, coefficient = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $code, $name, $coefficient, $description, $id);
    
    return $stmt->execute();
}

function deleteCourse($id) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

// Student reprise functions
function getStudentReprises($studentId) {
    $conn = getDbConnection();
    
    $sql = "SELECT r.*, c.name as course_name, c.code as course_code 
            FROM student_reprises r
            LEFT JOIN courses c ON r.course_id = c.id
            WHERE r.student_id = ?
            ORDER BY r.reprise_date DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reprises = [];
    while ($row = $result->fetch_assoc()) {
        $reprises[] = $row;
    }
    
    return $reprises;
}

function addStudentReprise($studentId, $courseId, $originalGrade, $repriseGrade, $repriseDate) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO student_reprises (student_id, course_id, original_grade, reprise_grade, reprise_date) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $studentId, $courseId, $originalGrade, $repriseGrade, $repriseDate);
    
    if ($stmt->execute()) {
        // Update student's grade for this course
        updateStudentGradeAfterReprise($studentId, $courseId, $repriseGrade);
        return $conn->insert_id;
    }
    
    return false;
}

function updateStudentGradeAfterReprise($studentId, $courseId, $newGrade) {
    $conn = getDbConnection();
    
    // Get course information
    $course = getCourseById($courseId);
    if (!$course) return false;
    
    // Determine which column to update based on course code
    $columnName = strtolower(str_replace(' ', '_', $course['name']));
    
    $sql = "UPDATE students SET $columnName = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $newGrade, $studentId);
    
    if ($stmt->execute()) {
        // Recalculate student's total and average
        recalculateStudentGrades($studentId);
        return true;
    }
    
    return false;
}

function recalculateStudentGrades($studentId) {
    $conn = getDbConnection();
    
    // Get student's current grades
    $student = getStudentById($studentId);
    if (!$student) return false;
    
    // Recalculate totals and averages
    $total = $student['droit_civil'] + $student['procedure_penale'] + 
             $student['contentieux_administratif'] + $student['securites_sociales'] + 
             $student['voies_execution'] + $student['procedure_commerciale'] + 
             $student['droits_humains'];
    $moyenne = $total / 7;
    
    // Update student's total and average
    $stmt = $conn->prepare("UPDATE students SET total = ?, moyenne = ? WHERE id = ?");
    $stmt->bind_param("ddi", $total, $moyenne, $studentId);
    
    return $stmt->execute();
}


// Mettre à jour la moyenne après une reprise
function updateStudentAverageAfterReprise($studentId) {
    $conn = getDbConnection();
    
    // Obtenir l'étudiant
    $student = getStudentById($studentId);
    if (!$student) return false;
    
    // Obtenir les notes de reprise
    $reprises = getStudentReprises($studentId);
    $reprisesNotes = [];
    foreach ($reprises as $reprise) {
        $reprisesNotes[$reprise['matiere']] = $reprise['note_reprise'];
    }
    
    // Calculer les nouvelles moyennes en tenant compte des notes de reprise
    $matieres = ['droit_civil', 'procedure_penale', 'contentieux_administratif', 
                 'securites_sociales', 'voies_execution', 'procedure_commerciale', 'droits_humains'];
    
    $total = 0;
    foreach ($matieres as $matiere) {
        if (isset($reprisesNotes[$matiere])) {
            $total += $reprisesNotes[$matiere];
        } else {
            $total += $student[$matiere];
        }
    }
    
    $moyenne = $total / 7;
    
    // Mettre à jour le verdict
    $verdict = $moyenne >= 65 ? 'Réussi' : 'Échec';
    
    // Mettre à jour la base de données
    $stmt = $conn->prepare("UPDATE students SET total = ?, moyenne = ?, verdict = ? WHERE id = ?");
    $stmt->bind_param("ddsi", $total, $moyenne, $verdict, $studentId);
    
    return $stmt->execute();
}


// Notification functions
function createNotification($userId, $message, $link = null) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $message, $link);
    
    return $stmt->execute();
}

function getUserNotifications($userId, $limit = 20) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

function getUnreadNotificationsCount($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

function markNotificationAsRead($notificationId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    
    return $stmt->execute();
}

function markAllNotificationsAsRead($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    
    return $stmt->execute();
}

// User management functions
function authenticateUser($username, $password) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    
    return false;
}

function getUserById($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

function getAllUsers() {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users ORDER BY username ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

function createUser($username, $password, $role = 'user_admin') {
    $conn = getDbConnection();
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $role);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

function updateUserWithPassword($id, $username, $password, $role) {
    $conn = getDbConnection();
    
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $hashedPassword, $role, $id);
    
    return $stmt->execute();
}

function updateUserWithoutPassword($id, $username, $role) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $role, $id);
    
    return $stmt->execute();
}

function deleteUser($id) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

function getUserPermissions($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT permission FROM user_permissions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission'];
    }
    
    return $permissions;
}

function updateUserPermissions($userId, $permissions) {
    $conn = getDbConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing permissions
        $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Insert new permissions
        if (!empty($permissions)) {
            $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, permission) VALUES (?, ?)");
            foreach ($permissions as $permission) {
                $stmt->bind_param("is", $userId, $permission);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return false;
    }
}

// Document generation functions
function generateDocument($student, $type) {
    // Log document access
    logDocumentAccess($_SESSION['user_id'], $student['id'], $type);
    
    // Generate appropriate document
    if ($type === 'pdf') {
        generatePDF($student);
    } else {
        generateWord($student);
    }
}

function generatePDF($student) {
    // Require the TCPDF library
    require_once('tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Your System');
    $pdf->SetAuthor('Your System');
    $pdf->SetTitle('Fiche Étudiant');
    
    // Set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Fiche Étudiant', $student['nom'] . ' ' . $student['prenom']);
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Start building HTML content
    $html = '<h2>Informations personnelles</h2>';
    $html .= '<table border="1" cellspacing="0" cellpadding="4">';
    $html .= '<tr><td width="30%"><b>No d\'ordre</b></td><td width="70%">' . $student['no_ordre'] . '</td></tr>';
    $html .= '<tr><td><b>No de fiche</b></td><td>' . $student['no_fiche'] . '</td></tr>';
    $html .= '<tr><td><b>Section</b></td><td>' . $student['section'] . '</td></tr>';
    $html .= '<tr><td><b>Année académique</b></td><td>' . $student['année'] . '</td></tr>';
    $html .= '</table>';
    
    // Session 1
    $html .= '<h3>Notes - Session 1</h3>';
    $html .= '<table border="1" cellspacing="0" cellpadding="4">';
    $html .= '<tr><th width="70%">Matière</th><th width="30%">Note</th></tr>';
    $html .= '<tr><td>Droit Civil</td><td>' . $student['droit_civil_s1'] . '</td></tr>';
    $html .= '<tr><td>Procédure Pénale</td><td>' . $student['procedure_penale_s1'] . '</td></tr>';
    $html .= '<tr><td>Contentieux Administratif</td><td>' . $student['contentieux_administratif_s1'] . '</td></tr>';
    $html .= '<tr><td>Sécurités Sociales</td><td>' . $student['securites_sociales_s1'] . '</td></tr>';
    $html .= '<tr><td>Voies d\'Exécution</td><td>' . $student['voies_execution_s1'] . '</td></tr>';
    $html .= '<tr><td>Procédure Commerciale</td><td>' . $student['procedure_commerciale_s1'] . '</td></tr>';
    $html .= '<tr><td>Droits Humains</td><td>' . $student['droits_humains_s1'] . '</td></tr>';
    $html .= '<tr><td><b>Total Session 1</b></td><td><b>' . $student['total_s1'] . '</b></td></tr>';
    $html .= '<tr><td><b>Moyenne Session 1</b></td><td><b>' . $student['moyenne_s1'] . '</b></td></tr>';
    $html .= '</table>';
    
    // Session 2
    $html .= '<h3>Notes - Session 2</h3>';
    $html .= '<table border="1" cellspacing="0" cellpadding="4">';
    $html .= '<tr><th width="70%">Matière</th><th width="30%">Note</th></tr>';
    $html .= '<tr><td>Droit Civil</td><td>' . $student['droit_civil_s2'] . '</td></tr>';
    $html .= '<tr><td>Procédure Pénale</td><td>' . $student['procedure_penale_s2'] . '</td></tr>';
    $html .= '<tr><td>Contentieux Administratif</td><td>' . $student['contentieux_administratif_s2'] . '</td></tr>';
    $html .= '<tr><td>Sécurités Sociales</td><td>' . $student['securites_sociales_s2'] . '</td></tr>';
    $html .= '<tr><td>Voies d\'Exécution</td><td>' . $student['voies_execution_s2'] . '</td></tr>';
    $html .= '<tr><td>Procédure Commerciale</td><td>' . $student['procedure_commerciale_s2'] . '</td></tr>';
    $html .= '<tr><td>Droits Humains</td><td>' . $student['droits_humains_s2'] . '</td></tr>';
    $html .= '<tr><td><b>Total Session 2</b></td><td><b>' . $student['total_s2'] . '</b></td></tr>';
    $html .= '<tr><td><b>Moyenne Session 2</b></td><td><b>' . $student['moyenne_s2'] . '</b></td></tr>';
    $html .= '</table>';
    
    // Final results
    $html .= '<h3>Résultats Finaux</h3>';
    $html .= '<table border="1" cellspacing="0" cellpadding="4">';
    $html .= '<tr><th width="70%">Matière</th><th width="30%">Moyenne</th></tr>';
    $html .= '<tr><td>Droit Civil</td><td>' . $student['droit_civil'] . '</td></tr>';
    $html .= '<tr><td>Procédure Pénale</td><td>' . $student['procedure_penale'] . '</td></tr>';
    $html .= '<tr><td>Contentieux Administratif</td><td>' . $student['contentieux_administratif'] . '</td></tr>';
    $html .= '<tr><td>Sécurités Sociales</td><td>' . $student['securites_sociales'] . '</td></tr>';
    $html .= '<tr><td>Voies d\'Exécution</td><td>' . $student['voies_execution'] . '</td></tr>';
    $html .= '<tr><td>Procédure Commerciale</td><td>' . $student['procedure_commerciale'] . '</td></tr>';
    $html .= '<tr><td>Droits Humains</td><td>' . $student['droits_humains'] . '</td></tr>';
    $html .= '<tr><td><b>Total</b></td><td><b>' . $student['total'] . '</b></td></tr>';
    $html .= '<tr><td><b>Moyenne Finale</b></td><td><b>' . $student['moyenne'] . '</b></td></tr>';
    $html .= '</table>';
    
    $html .= '<h3>Verdict</h3>';
    $html .= '<p style="font-size:16px;font-weight:bold;';
    
    if ($student['verdict'] === 'Réussi') {
        $html .= 'color:green;';
    } elseif ($student['verdict'] === 'Reprise') {
        $html .= 'color:blue;';
    } else {
        $html .= 'color:red;';
    }
    
    $html .= '">' . $student['verdict'] . '</p>';
    
    // Add reprises if applicable
    if ($student['verdict'] === 'Reprise' && !empty($student['reprises'])) {
        $reprises = json_decode($student['reprises'], true);
        if (!empty($reprises)) {
            $html .= '<h3>Matières à reprendre</h3>';
            $html .= '<ul>';
            foreach ($reprises as $matiere) {
                $matiereName = str_replace('_', ' ', $matiere);
                $matiereName = ucfirst($matiereName);
                $html .= '<li>' . $matiereName . '</li>';
            }
            $html .= '</ul>';
        }
    }
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('etudiant_' . $student['no_ordre'] . '.pdf', 'I');
    exit();
}

function generateWord($student) {
    // Require the PHPWord library
    if (file_exists('vendor/phpoffice/phpword/bootstrap.php')) {
        require_once('vendor/phpoffice/phpword/bootstrap.php');
    } elseif (file_exists('phpword/bootstrap.php')) {
        require_once('phpword/bootstrap.php');
    } else {
        die("PHPWord library not found. Please install it using Composer or download it manually.");
    }
    
    // Create new Word document
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    
    // Set default font
    $phpWord->setDefaultFontName('Arial');
    $phpWord->setDefaultFontSize(12);
    
    // Add a section
    $section = $phpWord->addSection();
    
    // Add title
    $section->addText('Fiche Étudiant', ['bold' => true, 'size' => 18], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
    $section->addText($student['nom'] . ' ' . $student['prenom'], ['bold' => true, 'size' => 16], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
    $section->addTextBreak(1);
    
    // Add personal information
    $section->addText('Informations personnelles', ['bold' => true, 'size' => 14]);
    
    $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 80]);
    $table->addRow();
    $table->addCell(3000)->addText('No d\'ordre:', ['bold' => true]);
    $table->addCell(6000)->addText($student['no_ordre']);
    $table->addRow();
    $table->addCell(3000)->addText('No de fiche:', ['bold' => true]);
    $table->addCell(6000)->addText($student['no_fiche']);
    $table->addRow();
    $table->addCell(3000)->addText('Section:', ['bold' => true]);
    $table->addCell(6000)->addText($student['section']);
    $table->addRow();
    $table->addCell(3000)->addText('Année académique:', ['bold' => true]);
    $table->addCell(6000)->addText($student['année']);
    
    $section->addTextBreak(1);
    
    // Add Session 1 grades
    $section->addText('Notes - Session 1', ['bold' => true, 'size' => 14]);
    
    $table = $section->addTable(['borderSize' => 1, 'borderColor' => '000000', 'cellMargin' => 80]);
    
    // Header row
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Matière', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText('Note', ['bold' => true]);
    
    // Data rows for Session 1
    $table->addRow();
    $table->addCell(7000)->addText('Droit Civil');
    $table->addCell(2000)->addText($student['droit_civil_s1']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Procédure Pénale');
    $table->addCell(2000)->addText($student['procedure_penale_s1']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Contentieux Administratif');
    $table->addCell(2000)->addText($student['contentieux_administratif_s1']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Sécurités Sociales');
    $table->addCell(2000)->addText($student['securites_sociales_s1']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Voies d\'Exécution');
    $table->addCell(2000)->addText($student['voies_execution_s1']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Procédure Commerciale');
    $table->addCell(2000)->addText($student['procedure_commerciale_s1']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Droits Humains');
    $table->addCell(2000)->addText($student['droits_humains_s1']);
    
    // Total and average for Session 1
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Total Session 1', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText($student['total_s1'], ['bold' => true]);
    
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Moyenne Session 1', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText($student['moyenne_s1'], ['bold' => true]);
    
    $section->addTextBreak(1);
    
    // Add Session 2 grades
    $section->addText('Notes - Session 2', ['bold' => true, 'size' => 14]);
    
    $table = $section->addTable(['borderSize' => 1, 'borderColor' => '000000', 'cellMargin' => 80]);
    
    // Header row
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Matière', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText('Note', ['bold' => true]);
    
    // Data rows for Session 2
    $table->addRow();
    $table->addCell(7000)->addText('Droit Civil');
    $table->addCell(2000)->addText($student['droit_civil_s2']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Procédure Pénale');
    $table->addCell(2000)->addText($student['procedure_penale_s2']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Contentieux Administratif');
    $table->addCell(2000)->addText($student['contentieux_administratif_s2']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Sécurités Sociales');
    $table->addCell(2000)->addText($student['securites_sociales_s2']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Voies d\'Exécution');
    $table->addCell(2000)->addText($student['voies_execution_s2']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Procédure Commerciale');
    $table->addCell(2000)->addText($student['procedure_commerciale_s2']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Droits Humains');
    $table->addCell(2000)->addText($student['droits_humains_s2']);
    
    // Total and average for Session 2
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Total Session 2', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText($student['total_s2'], ['bold' => true]);
    
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Moyenne Session 2', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText($student['moyenne_s2'], ['bold' => true]);
    
    $section->addTextBreak(1);
    
    // Add final averages
    $section->addText('Moyennes par matière', ['bold' => true, 'size' => 14]);
    
    $table = $section->addTable(['borderSize' => 1, 'borderColor' => '000000', 'cellMargin' => 80]);
    
    // Header row
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Matière', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText('Moyenne', ['bold' => true]);
    
    // Data rows for averages
    $table->addRow();
    $table->addCell(7000)->addText('Droit Civil');
    $table->addCell(2000)->addText($student['droit_civil']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Procédure Pénale');
    $table->addCell(2000)->addText($student['procedure_penale']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Contentieux Administratif');
    $table->addCell(2000)->addText($student['contentieux_administratif']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Sécurités Sociales');
    $table->addCell(2000)->addText($student['securites_sociales']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Voies d\'Exécution');
    $table->addCell(2000)->addText($student['voies_execution']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Procédure Commerciale');
    $table->addCell(2000)->addText($student['procedure_commerciale']);
    
    $table->addRow();
    $table->addCell(7000)->addText('Droits Humains');
    $table->addCell(2000)->addText($student['droits_humains']);
    
    // Final total and average
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Total', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText($student['total'], ['bold' => true]);
    
    $table->addRow();
    $table->addCell(7000, ['bgColor' => 'EEEEEE'])->addText('Moyenne Finale', ['bold' => true]);
    $table->addCell(2000, ['bgColor' => 'EEEEEE'])->addText($student['moyenne'], ['bold' => true]);
    
    $section->addTextBreak(1);
    
    // Add verdict
    $section->addText('Verdict', ['bold' => true, 'size' => 14]);
    
    $textRun = $section->addTextRun();
    
    $verdictStyle = ['bold' => true, 'size' => 14];
    
    if ($student['verdict'] === 'Réussi') {
        $verdictStyle['color'] = '008800';
    } else {
        $verdictStyle['color'] = 'FF0000';
    }
    
    $textRun->addText($student['verdict'], $verdictStyle);
    
    // Show reprises if any
    if ($student['verdict'] === 'Reprise') {
        $section->addTextBreak(1);
        $section->addText('Matières à reprendre', ['bold' => true, 'size' => 14]);
        
        $reprises = json_decode($student['reprises'], true);
        if (!empty($reprises)) {
            foreach ($reprises as $matiere) {
                $matiereName = str_replace('_', ' ', $matiere);
                $matiereName = ucfirst($matiereName);
                $section->addListItem($matiereName, 0);
            }
        }
    }
    
    // Save file
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit();
}

// Data management functions
function createData($name, $description) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO data (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $description);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

function updateData($id, $name, $description) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE data SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $description, $id);
    
    return $stmt->execute();
}

function deleteData($id) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("DELETE FROM data WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

function getDataById($id) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT * FROM data WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

// Payment functions
function addPayment($studentId, $montant, $reference, $datePaiement, $userId) {
    $conn = getDbConnection();
    
    // Vérifier si le montant est valide
    if ($montant <= 0) {
        return false;
    }
    
    // Vérifier si l'étudiant existe
    $student = getStudentById($studentId);
    if (!$student) {
        return false;
    }
    
    // Insérer le paiement
    $stmt = $conn->prepare("INSERT INTO payments (
        student_id, 
        montant, 
        reference, 
        date_paiement, 
        status,
        created_by,
        created_at
    ) VALUES (?, ?, ?, ?, 'en_attente', ?, NOW())");
    
    $stmt->bind_param("idssi", 
        $studentId, 
        $montant, 
        $reference, 
        $datePaiement, 
        $userId
    );
    
    if ($stmt->execute()) {
        $paymentId = $conn->insert_id;
        
        // Créer une notification pour les administrateurs
        $admins = getAllAdminUsers();
        foreach ($admins as $admin) {
            createNotification(
                $admin['id'],
                "Nouveau paiement en attente de validation pour l'étudiant " . $student['nom'] . ' ' . $student['prenom'],
                "validate_payment.php?id=" . $paymentId
            );
        }
        
        return $paymentId;
    }
    
    return false;
}

// Fonction utilitaire pour récupérer tous les administrateurs
function getAllAdminUsers() {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    
    return $admins;
}

// Fonction pour vérifier si un paiement existe déjà
function checkExistingPayment($studentId, $reference) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id FROM payments WHERE student_id = ? AND reference = ?");
    $stmt->bind_param("is", $studentId, $reference);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Fonction pour obtenir le statut d'un paiement
function getPaymentStatus($paymentId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT status FROM payments WHERE id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['status'];
    }
    
    return null;
}

// Fonction pour mettre à jour un paiement
function updatePayment($paymentId, $montant, $reference, $datePaiement) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE payments SET 
        montant = ?,
        reference = ?,
        date_paiement = ?,
        updated_at = NOW()
        WHERE id = ? AND status = 'en_attente'");
        
    $stmt->bind_param("dssi", 
        $montant, 
        $reference, 
        $datePaiement,
        $paymentId
    );
    
    return $stmt->execute();
}

?>