<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$document_id = $_POST['document_id'] ?? 0;

if (!$document_id) {
    echo json_encode(['status' => 'error', 'message' => 'Document ID required.']);
    exit;
}

if ($action === 'rate') {
    $rating = (int)($_POST['rating'] ?? 0);
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid rating.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO ratings (document_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?");
        $stmt->execute([$document_id, $_SESSION['user_id'], $rating, $rating]);
        echo json_encode(['status' => 'success', 'message' => 'Rating submitted.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'comment') {
    $comment = trim($_POST['comment'] ?? '');
    if (empty($comment)) {
        echo json_encode(['status' => 'error', 'message' => 'Comment cannot be empty.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO comments (document_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$document_id, $_SESSION['user_id'], $comment]);
        echo json_encode(['status' => 'success', 'message' => 'Comment added.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'bookmark') {
    try {
        $check = $pdo->prepare("SELECT id FROM bookmarks WHERE document_id = ? AND user_id = ?");
        $check->execute([$document_id, $_SESSION['user_id']]);
        
        if ($check->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE document_id = ? AND user_id = ?");
            $stmt->execute([$document_id, $_SESSION['user_id']]);
            echo json_encode(['status' => 'success', 'message' => 'Bookmark removed.', 'bookmarked' => false]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO bookmarks (document_id, user_id) VALUES (?, ?)");
            $stmt->execute([$document_id, $_SESSION['user_id']]);
            echo json_encode(['status' => 'success', 'message' => 'Document bookmarked.', 'bookmarked' => true]);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
