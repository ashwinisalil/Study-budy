<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once 'config/database.php';

$target_user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
$is_own_profile = $target_user_id == $_SESSION['user_id'];
$is_principle = $_SESSION['role'] === 'principle';

if (!$is_own_profile && !$is_principle) {
    header("Location: dashboard.php");
    exit;
}

$read_only = !$is_own_profile && $is_principle;

// Fetch user stats
$stmt = $pdo->prepare("
    SELECT u.username, u.email, u.bio, u.credits, u.upload_count, u.role, u.primary_subject, GROUP_CONCAT(fas.subject) as additional_subjects 
    FROM users u
    LEFT JOIN faculty_additional_subjects fas ON u.id = fas.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$target_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: dashboard.php");
    exit;
}

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
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);"><?= $read_only ? htmlspecialchars($user['username']) . "'s Profile" : 'My Profile' ?></h2>
            <form id="<?= $read_only ? '' : 'profile-form' ?>">
                <div class="form-group">
                    <label>Email (Read-only)</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background: rgba(0,0,0,0.02); color: var(--text-secondary);">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" <?= $read_only ? 'readonly style="background: rgba(0,0,0,0.02); color: var(--text-secondary);"' : 'required' ?>>
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" rows="4" placeholder="Tell us about your academic interests..." <?= $read_only ? 'readonly style="background: rgba(0,0,0,0.02); color: var(--text-secondary);"' : '' ?>><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
                <?php if ($user['role'] === 'faculty'): ?>
                <div class="form-group">
                    <label>Permanent Subject</label>
                    <input type="text" value="<?= htmlspecialchars($user['primary_subject'] ?? 'None') ?>" readonly style="background: rgba(0,0,0,0.02); color: var(--text-secondary);">
                </div>
                <div class="form-group">
                    <label>Additional Subjects (Read-only)</label>
                    <input type="text" value="<?= htmlspecialchars($user['additional_subjects'] ?? 'None') ?>" readonly style="background: rgba(0,0,0,0.02); color: var(--text-secondary);">
                </div>
                <?php endif; ?>
                <?php if (!$read_only): ?>
                <button type="submit" class="btn btn-primary" style="width:100%;">Save Profile</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Gamification Card -->
        <div class="profile-card glass">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);"><?= $read_only ? 'Academic Rewards' : 'My Academic Rewards' ?></h2>
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
        
        <!-- My Uploads Section -->
        <?php if ($is_own_profile): ?>
        <div class="profile-card glass" style="margin-top: 2rem; grid-column: 1 / -1;">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">My Uploads</h2>
            <div id="my-uploads-list">
                <!-- Loaded via JS -->
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="toast-container"></div>
<script src="assets/js/main.js"></script>
</body>
</html>
