<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$role = $_SESSION['role'] ?? 'student';
$is_principle = $role === 'principle';
$is_faculty = $role === 'faculty';
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Study Budy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav>
    <a href="dashboard.php" class="brand">Study Budy</a>
    <div class="nav-links">
        <span>Welcome, <?= htmlspecialchars($username) ?></span>
        <?php if ($is_principle): ?>
            <a href="admin.php">Principle Dashboard</a>
        <?php elseif ($is_faculty): ?>
            <a href="admin.php">Faculty Dashboard</a>
        <?php endif; ?>
        <a href="profile.php" class="btn btn-secondary" style="text-decoration: none;">My Profile</a>
        <button class="btn btn-primary" onclick="openModal('upload-modal')">Upload Document</button>
        <button class="btn btn-secondary" id="logout-btn">Logout</button>
    </div>
</nav>

<main>
    <div class="search-container">
        <input type="text" id="search-input" placeholder="Search for documents...">
        <select id="subject-filter">
            <option value="">All Subjects</option>
            <option value="FCSN">FCSN</option>
            <option value="DSDA">DSDA</option>
            <option value="FCPP">FCPP</option>
            <option value="Physics">Physics</option>
            <option value="EM-2">EM-2</option>
        </select>
        <select id="tag-filter">
            <option value="">All Tags</option>
            <option value="SYBTECH - Sem 3">SYBTECH - Sem 3</option>
            <option value="SYBTECH - Sem 4">SYBTECH - Sem 4</option>
        </select>
    </div>

    <div class="grid" id="documents-grid">
        <!-- Documents loaded via JS -->
    </div>
</main>

<div id="toast-container"></div>

<!-- Upload Modal -->
<div class="modal-overlay" id="upload-modal">
    <div class="modal-content glass">
        <div class="modal-header">
            <h2>Upload Document</h2>
            <button class="close-btn">&times;</button>
        </div>
        <form id="upload-form" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <select name="subject" required>
                    <option value="FCSN">FCSN</option>
                    <option value="DSDA">DSDA</option>
                    <option value="FCPP">FCPP</option>
                    <option value="Physics">Physics</option>
                    <option value="EM-2">EM-2</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tag</label>
                <select name="tag" required>
                    <option value="SYBTECH - Sem 3">SYBTECH - Sem 3</option>
                    <option value="SYBTECH - Sem 4">SYBTECH - Sem 4</option>
                </select>
            </div>
            <div class="form-group">
                <label>File (PDF, PPT, Images - Max 25MB)</label>
                <input type="file" name="document" accept=".pdf,.ppt,.pptx,.jpg,.jpeg,.png" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Upload for Approval</button>
        </form>
    </div>
</div>

<!-- View Document Modal -->
<div class="modal-overlay" id="view-modal">
    <div class="modal-content glass" style="max-width: 800px;">
        <div class="modal-header">
            <h2 id="view-title">Document Title</h2>
            <button class="close-btn">&times;</button>
        </div>
        <div id="view-meta" style="color: var(--text-secondary); margin-bottom: 1rem;"></div>
        <div id="viewer-container"></div>
        <p id="view-desc" style="margin-bottom: 1rem;"></p>
        
        <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
            <form id="rate-form" style="display:flex; gap:0.5rem; align-items:center;">
                <select name="rating" style="padding:0.4rem; border-radius:4px; border:1px solid var(--border-color);">
                    <option value="5">5 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="2">2 Stars</option>
                    <option value="1">1 Star</option>
                </select>
                <button type="submit" class="btn btn-secondary">Rate</button>
            </form>
        </div>

        <div class="comment-section">
            <h3>Comments</h3>
            <form id="comment-form" style="margin-bottom: 1rem; display:flex; gap:0.5rem; flex-direction:column;">
                <textarea name="comment" rows="2" placeholder="Add a comment..." required style="padding:0.5rem; border-radius:8px; border:1px solid var(--border-color);"></textarea>
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Post Comment</button>
            </form>
            <div id="comments-list"></div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal-overlay" id="report-modal">
    <div class="modal-content glass">
        <div class="modal-header">
            <h2>Why are you reporting this document?</h2>
            <button class="close-btn">&times;</button>
        </div>
        <form id="report-form">
            <input type="hidden" name="document_id" id="report-doc-id">
            <div class="form-group">
                <label>Reason</label>
                <select name="reason_type" required>
                    <option value="">Select a reason...</option>
                    <option value="Duplicate Content">Duplicate Content</option>
                    <option value="Inappropriate Content">Inappropriate Content</option>
                    <option value="Wrong Subject/Tag">Wrong Subject/Tag</option>
                    <option value="Poor Quality / Unreadable">Poor Quality / Unreadable</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Additional Details (Optional)</label>
                <textarea name="reason_text" rows="3" placeholder="Provide more context..."></textarea>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Submit Report</button>
                <button type="button" class="btn btn-secondary close-modal-btn" style="flex: 1;" onclick="closeModal('report-modal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
