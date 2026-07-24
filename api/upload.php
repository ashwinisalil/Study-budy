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
$subject = trim($_POST['subject'] ?? '');
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

// Fingerprinting Duplicate Prevention
$file_hash = hash_file('sha256', $file['tmp_name']);
$hash_check = $pdo->prepare("SELECT id FROM documents WHERE file_hash = ?");
$hash_check->execute([$file_hash]);
if ($hash_check->fetch()) {
    unlink($file['tmp_name']);
    echo json_encode(['status' => 'error', 'message' => 'Copyright Error: An identical file already exists on the platform.']);
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
        $stmt = $pdo->prepare("INSERT INTO documents (user_id, title, description, filename, file_path, file_type, size, subject, tag, file_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $file['name'], $db_file_path, $mime, $file['size'], $subject, $tag, $file_hash]);
        echo json_encode(['status' => 'success', 'message' => 'File uploaded successfully and is pending admin approval.']);
        
        if ($_SESSION['role'] === 'principle') {
            try {
                $msg = "Principal has uploaded a new document: " . htmlspecialchars($title);
                $usersStmt = $pdo->query("SELECT id FROM users WHERE role IN ('student', 'faculty')");
                $users = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($users)) {
                    $pdo->beginTransaction();
                    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    foreach ($users as $uid) {
                        $notifStmt->execute([$uid, $msg]);
                    }
                    $pdo->commit();
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            }
        }
    } catch (PDOException $e) {
        unlink($file_path); // Cleanup on DB fail
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} else {
    $error = error_get_last();
    $error_msg = $error ? $error['message'] : 'Unknown error';
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Detail: ' . $error_msg]);
}
