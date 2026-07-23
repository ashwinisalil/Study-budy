<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $subject = $_GET['subject'] ?? '';
    $tag = $_GET['tag'] ?? '';
    
    $query = "SELECT d.*, u.username, 
              (SELECT COALESCE(AVG(rating), 0) FROM ratings WHERE document_id = d.id) as avg_rating,
              (SELECT COUNT(*) FROM comments WHERE document_id = d.id) as comment_count
              FROM documents d 
              JOIN users u ON d.user_id = u.id 
              WHERE d.status = 'approved'";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (d.title LIKE ? OR d.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($subject)) {
        $query .= " AND d.subject = ?";
        $params[] = $subject;
    }
    
    if (!empty($tag)) {
        $query .= " AND d.tag = ?";
        $params[] = $tag;
    }
    
    $query .= " ORDER BY d.created_at DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $documents = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $documents]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'details') {
    $id = $_GET['id'] ?? 0;
    try {
        $stmt = $pdo->prepare("SELECT d.*, u.username FROM documents d JOIN users u ON d.user_id = u.id WHERE d.id = ? AND d.status = 'approved'");
        $stmt->execute([$id]);
        $document = $stmt->fetch();
        
        if ($document) {
            // Fetch comments
            $c_stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.document_id = ? ORDER BY c.created_at DESC");
            $c_stmt->execute([$id]);
            $document['comments'] = $c_stmt->fetchAll();
            
            // Fetch avg rating
            $r_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM ratings WHERE document_id = ?");
            $r_stmt->execute([$id]);
            $document['avg_rating'] = $r_stmt->fetchColumn() ?? 0;
            
            echo json_encode(['status' => 'success', 'data' => $document]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Document not found or pending approval.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
