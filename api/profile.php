<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get') {
    $stmt = $pdo->prepare("SELECT username, email, bio, credits, upload_count FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $user]);
} elseif ($action === 'update') {
    $newUsername = trim($_POST['username'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (empty($newUsername)) {
        echo json_encode(['status' => 'error', 'message' => 'Username cannot be empty.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ? WHERE id = ?");
        $stmt->execute([$newUsername, $bio, $_SESSION['user_id']]);
        $_SESSION['username'] = $newUsername;
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['status' => 'error', 'message' => 'Username already taken.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
