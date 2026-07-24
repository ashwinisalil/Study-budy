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

$document_id = $_POST['document_id'] ?? 0;
$reason_type = $_POST['reason_type'] ?? '';
$reason_text = $_POST['reason_text'] ?? '';

if (empty($document_id) || empty($reason_type)) {
    echo json_encode(['status' => 'error', 'message' => 'Document ID and reason are required.']);
    exit;
}

$full_reason = $reason_type;
if (!empty(trim($reason_text))) {
    $full_reason .= " - " . trim($reason_text);
}

try {
    // Check if the user already reported this document
    $checkStmt = $pdo->prepare("SELECT id FROM document_reports WHERE document_id = ? AND reported_by = ? AND status = 'pending'");
    $checkStmt->execute([$document_id, $_SESSION['user_id']]);
    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'You have already reported this document.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO document_reports (document_id, reported_by, reason) VALUES (?, ?, ?)");
    $stmt->execute([$document_id, $_SESSION['user_id'], $full_reason]);
    echo json_encode(['status' => 'success', 'message' => 'Document reported successfully. Our moderators will review it.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
