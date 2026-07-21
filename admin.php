<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Study Budy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">Study Budy</a>
    <div class="nav-links">
        <span>Admin Mode</span>
        <a href="index.php">Back to App</a>
        <button class="btn btn-secondary" id="logout-btn">Logout</button>
    </div>
</nav>

<main>
    <h2>Pending Approvals</h2>
    <div style="overflow-x: auto;">
        <table class="admin-table" id="admin-pending-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>User</th>
                    <th>Category / Tag</th>
                    <th>Size</th>
                    <th>File</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated by JS -->
            </tbody>
        </table>
    </div>
</main>

<div id="toast-container"></div>
<script src="assets/js/main.js"></script>
</body>
</html>
