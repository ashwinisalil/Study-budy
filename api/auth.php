<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a Username, Email, and Password to complete your account setup.']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password]);
        
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'student';
        
        echo json_encode(['status' => 'success', 'message' => 'Registration successful.', 'role' => 'student']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            echo json_encode(['status' => 'error', 'message' => 'Username or email already exists.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    }
} elseif ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            echo json_encode(['status' => 'success', 'message' => 'Login successful.', 'role' => $user['role']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} elseif ($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Logged out successfully.']);
} elseif ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'success', 'logged_in' => true, 'username' => $_SESSION['username'], 'role' => $_SESSION['role']]);
    } else {
        echo json_encode(['status' => 'success', 'logged_in' => false]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
