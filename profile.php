<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'config/database.php';

// Fetch user stats
$stmt = $pdo->prepare("SELECT username, email, bio, credits, upload_count FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$credits = (int)($user['credits'] ?? 0);
$next_milestone = (floor($credits / 50) + 1) * 50;
$progress_percent = ($credits % 50) / 50 * 100;
$vouchers_earned = floor($credits / 50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Study Budy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-body"> <!-- Reuse cream landing background -->

<nav>
    <a href="dashboard.php" class="brand">Study Budy</a>
    <div class="nav-links">
        <a href="dashboard.php" class="btn btn-secondary" style="text-decoration: none;">Dashboard</a>
        <button class="btn btn-secondary" id="logout-btn">Logout</button>
    </div>
</nav>

<div class="landing-container" style="margin-top: 2rem;">
    <div class="profile-grid">
        
        <!-- Profile Details Card -->
        <div class="profile-card glass">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">My Profile</h2>
            <form id="profile-form">
                <div class="form-group">
                    <label>Email (Read-only)</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background: rgba(0,0,0,0.02); color: var(--text-secondary);">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" rows="4" placeholder="Tell us about your academic interests..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Save Profile</button>
            </form>
        </div>

        <!-- Gamification Card -->
        <div class="profile-card glass">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">My Academic Rewards</h2>
            <div class="stats-row" style="display:flex; justify-content:space-around; margin-bottom:2rem; text-align:center; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3 style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.2rem;"><?= $user['upload_count'] ?></h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;">Documents Uploaded</p>
                </div>
                <div>
                    <h3 style="font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.2rem;"><?= $credits ?></h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;">Total Credits</p>
                </div>
            </div>

            <div class="progress-section">
                <p style="margin-bottom: 0.8rem; font-weight: 600; color: var(--text-primary); display: flex; justify-content: space-between;">
                    <span>Progress to next ₹10 Voucher</span>
                    <span style="color: var(--accent-color);"><?= $credits ?> / <?= $next_milestone ?></span>
                </p>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?= $progress_percent ?>%;"></div>
                </div>
            </div>

            <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--text-primary);">Unlocked Rewards</h3>
            <ul class="milestones-list">
                <?php if ($vouchers_earned > 0): ?>
                    <?php for ($i = 1; $i <= $vouchers_earned; $i++): ?>
                        <li class="milestone-item unlocked">
                            <span style="font-size: 1.2rem; margin-right: 0.5rem;">🏆</span> 
                            ₹10 Google Play Voucher <span style="color:var(--text-secondary); font-size:0.85rem; margin-left:auto;">(Unlocked at <?= $i * 50 ?> credits)</span>
                        </li>
                    <?php endfor; ?>
                <?php else: ?>
                    <li class="milestone-item locked" style="text-align: center; color: var(--text-secondary);">
                        Upload documents to earn your first voucher!
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div id="toast-container"></div>
<script src="assets/js/main.js"></script>
</body>
</html>
