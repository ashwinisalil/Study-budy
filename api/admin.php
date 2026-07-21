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
        $stmt = $pdo->prepare("UPDATE documents SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Document ' . $status]);
    } catch (PDOException $e) {
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
