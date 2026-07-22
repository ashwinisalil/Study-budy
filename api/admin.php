<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list_pending') {
    try {
        $stmt = $pdo->prepare("SELECT d.*, u.username FROM documents d JOIN users u ON d.user_id = u.id WHERE d.status = 'pending' ORDER BY d.created_at ASC");
        $stmt->execute();
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
        
        // 1. Update the document status
        $stmt = $pdo->prepare("UPDATE documents SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        // 2. If approved, calculate and award credits
        if ($status === 'approved') {
            // Fetch the document details to get the user and file info
            $docStmt = $pdo->prepare("SELECT user_id, file_path, file_type FROM documents WHERE id = ?");
            $docStmt->execute([$id]);
            $doc = $docStmt->fetch();
            
            if ($doc) {
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
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
