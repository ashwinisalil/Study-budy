<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$tag = trim($_POST['tag'] ?? '');

if (empty($title) || !isset($_FILES['document'])) {
    echo json_encode(['status' => 'error', 'message' => 'Title and file are required.']);
    exit;
}

$file = $_FILES['document'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'File upload error code: ' . $file['error']]);
    exit;
}

// Check size (25MB)
$max_size = 25 * 1024 * 1024;
if ($file['size'] > $max_size) {
    echo json_encode(['status' => 'error', 'message' => 'File exceeds maximum size of 25MB.']);
    exit;
}

// MIME type checking
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = [
    'application/pdf',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/png'
];

if (!in_array($mime, $allowed_mimes)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed: PDF, PPT, JPG, PNG.']);
    exit;
}

// Secure filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('doc_', true) . '.' . $ext;
$upload_dir = __DIR__ . '/../uploads/';

// CRITICAL BUG FIX
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Directory creation failed. Check permissions on parent folder.']);
        exit;
    }
}

$file_path = $upload_dir . $filename;
$db_file_path = 'uploads/' . $filename; // Relative for DB

if (move_uploaded_file($file['tmp_name'], $file_path)) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO documents (user_id, title, description, filename, file_path, file_type, size, category, tag) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $file['name'], $db_file_path, $mime, $file['size'], $category, $tag]);

        // Gamified Credit System Logic
        $userStmt = $pdo->prepare("SELECT upload_count FROM users WHERE id = ? FOR UPDATE");
        $userStmt->execute([$_SESSION['user_id']]);
        $userData = $userStmt->fetch();
        $uploadCount = $userData['upload_count'] ?? 0;

        $creditsAwarded = 0;
        if ($uploadCount < 3) {
            $creditsAwarded = 20; // Phase 1
        } else {
            // Phase 2
            if (strpos($mime, 'pdf') !== false) {
                // PDF Parsing
                $content = file_get_contents($file_path);
                if (preg_match_all('/\/Page\W/', $content, $matches)) {
                    $pages = count($matches[0]);
                    $creditsAwarded = max($pages * 2, 2); // Ensure at least 2 credits
                } else {
                    $creditsAwarded = 2; // Fallback
                }
            } else {
                $creditsAwarded = 2; // Images and PPTs
            }
        }

        $updateUser = $pdo->prepare("UPDATE users SET upload_count = upload_count + 1, credits = credits + ? WHERE id = ?");
        $updateUser->execute([$creditsAwarded, $_SESSION['user_id']]);

        $pdo->commit();

        echo json_encode(['status' => 'success', 'message' => "File uploaded successfully (Pending Approval). You earned $creditsAwarded credits!"]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        unlink($file_path); // Cleanup on DB fail
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} else {
    $error = error_get_last();
    $error_msg = $error ? $error['message'] : 'Unknown error';
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Detail: ' . $error_msg]);
}
