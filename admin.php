<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['principle', 'faculty'])) {
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
        <h2><?= $_SESSION['role'] === 'principle' ? 'Principle Dashboard' : 'Faculty Dashboard - ' . htmlspecialchars($_SESSION['assigned_subject'] ?? '') ?></h2>
        <a href="dashboard.php" class="btn btn-secondary" style="text-decoration: none;">Back to Repository</a>
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
                    <th>Subject / Tag</th>
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
    <h2 style="margin-top: 2rem;">Reported Documents</h2>
    <div style="overflow-x: auto;">
        <table class="admin-table" id="admin-reports-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Reported By</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated by JS -->
            </tbody>
        </table>
    </div>
    
    <?php if ($_SESSION['role'] === 'principle'): ?>
    <div class="card glass" style="margin-top: 2rem;">
        <h3>Manage Faculty Assignments</h3>
        <table class="data-table" id="faculty-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Assigned Subject</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Loaded via JS -->
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>

<!-- Rejection Modal -->
<div class="modal-overlay" id="rejection-modal">
    <div class="modal-content glass">
        <div class="modal-header">
            <h2>Provide Rejection Feedback</h2>
            <button class="close-btn">&times;</button>
        </div>
        <form id="rejection-form">
            <input type="hidden" id="reject-doc-id" name="id">
            <div class="form-group">
                <label>What should the student improve before re-uploading?</label>
                <textarea name="rejection_reason" id="rejection-reason" rows="4" required></textarea>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Confirm Rejection</button>
                <button type="button" class="btn btn-secondary close-modal-btn" style="flex: 1;" onclick="closeModal('rejection-modal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="toast-container"></div>
<script src="assets/js/main.js"></script>
</body>
</html>
