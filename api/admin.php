<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['principle', 'faculty'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list_pending') {
    try {
        if ($_SESSION['role'] === 'principle') {
            $stmt = $pdo->prepare("SELECT d.*, u.username FROM documents d JOIN users u ON d.user_id = u.id WHERE d.status = 'pending' ORDER BY d.created_at ASC");
            $stmt->execute();
        } else {
            $primary = $_SESSION['primary_subject'] ?? null;
            $additional = $_SESSION['additional_subjects'] ?? [];
            
            $subjects = [];
            if ($primary) $subjects[] = $primary;
            $subjects = array_merge($subjects, $additional);
            
            if (empty($subjects)) {
                echo json_encode(['status' => 'success', 'data' => []]);
                exit;
            }
            $inQuery = implode(',', array_fill(0, count($subjects), '?'));
            $stmt = $pdo->prepare("SELECT d.*, u.username FROM documents d JOIN users u ON d.user_id = u.id WHERE d.status = 'pending' AND d.subject IN ($inQuery) ORDER BY d.created_at ASC");
            $stmt->execute($subjects);
        }
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'update_status') {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if (!in_array($status, ['approved', 'rejected'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status.']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Fetch the document details to check subject
        $docStmt = $pdo->prepare("SELECT user_id, file_path, file_type, subject FROM documents WHERE id = ? FOR UPDATE");
        $docStmt->execute([$id]);
        $doc = $docStmt->fetch();
        
        if (!$doc) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Document not found.']);
            exit;
        }
        
        // Faculty check
        $primary = $_SESSION['primary_subject'] ?? null;
        $additional = $_SESSION['additional_subjects'] ?? [];
        if ($_SESSION['role'] === 'faculty' && $doc['subject'] !== $primary && !in_array($doc['subject'], $additional)) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized subject.']);
            exit;
        }
        
        // 1. Update the document status
        $stmt = $pdo->prepare("UPDATE documents SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        // 2. If approved, calculate and award credits
        if ($status === 'approved') {
            $docUserId = $doc['user_id'];
            
            // Fetch the user's current upload count with a lock
            $userStmt = $pdo->prepare("SELECT upload_count FROM users WHERE id = ? FOR UPDATE");
            $userStmt->execute([$docUserId]);
            $userData = $userStmt->fetch();
            $uploadCount = $userData['upload_count'] ?? 0;
            
            $creditsAwarded = 0;
            if ($uploadCount < 3) {
                $creditsAwarded = 20; // Phase 1
            } else {
                // Phase 2
                if (strpos($doc['file_type'], 'pdf') !== false) {
                    $full_path = __DIR__ . '/../' . $doc['file_path'];
                    if (file_exists($full_path)) {
                        $content = file_get_contents($full_path);
                        if (preg_match_all('/\/Page\W/', $content, $matches)) {
                            $pages = count($matches[0]);
                            $creditsAwarded = max($pages * 2, 2);
                        } else {
                            $creditsAwarded = 2; // Fallback
                        }
                    } else {
                        $creditsAwarded = 2; // Fallback if file missing
                    }
                } else {
                    $creditsAwarded = 2; // Images/PPTs
                }
            }
            
            // Update the user's upload count and credits
            $updateUser = $pdo->prepare("UPDATE users SET upload_count = upload_count + 1, credits = credits + ? WHERE id = ?");
            $updateUser->execute([$creditsAwarded, $docUserId]);
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Document ' . $status]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'delete') {
    if ($_SESSION['role'] !== 'principle') {
        echo json_encode(['status' => 'error', 'message' => 'Only principle can delete.']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        
        if ($doc) {
            $path = '../' . $doc['file_path'];
            if (file_exists($path)) {
                unlink($path);
            }
            
            $del = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $del->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Document deleted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Document not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'list_faculty') {
    if ($_SESSION['role'] !== 'principle') {
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }
    try {
        $stmt = $pdo->query("SELECT u.id, u.username, u.email, u.primary_subject, GROUP_CONCAT(fas.subject) as additional_subjects 
                             FROM users u 
                             LEFT JOIN faculty_additional_subjects fas ON u.id = fas.user_id 
                             WHERE u.role = 'faculty' 
                             GROUP BY u.id 
                             ORDER BY u.username ASC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'update_faculty') {
    if ($_SESSION['role'] !== 'principle') {
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $subjects = isset($_POST['subjects']) ? json_decode($_POST['subjects'], true) : [];
    
    if (!is_array($subjects)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid subjects format.']);
        exit;
    }
    
    $valid_subjects = ['FCSN', 'DSDA', 'FCPP', 'Physics', 'EM-2'];
    foreach ($subjects as $sub) {
        if (!in_array($sub, $valid_subjects)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid subject in selection.']);
            exit;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'faculty'");
        $check->execute([$id]);
        if (!$check->fetch()) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'User is not faculty.']);
            exit;
        }
        
        $del = $pdo->prepare("DELETE FROM faculty_additional_subjects WHERE user_id = ?");
        $del->execute([$id]);
        
        if (!empty($subjects)) {
            $insert = $pdo->prepare("INSERT INTO faculty_additional_subjects (user_id, subject) VALUES (?, ?)");
            foreach ($subjects as $sub) {
                $insert->execute([$id, $sub]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Faculty assigned subjects updated successfully.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
