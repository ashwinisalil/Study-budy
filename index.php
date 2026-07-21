<?php
session_start();
// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Budy - Connect, Collaborate, Elevate</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-body">

<nav>
    <a href="index.php" class="brand">Study Budy</a>
</nav>

<div class="landing-container">
    <div class="landing-hero">
        <h1>Welcome to Study Budy</h1>
        <p class="tagline">Your Centralized Academic Ecosystem — Connect, Collaborate, and Elevate Your Learning.</p>
        
        <div class="features-grid">
            <div class="feature-card glass">
                <h3>Peer-to-Peer Sharing</h3>
                <p>Access structured notes, lecture slides, and past papers across engineering disciplines (Electronics, Sustainability, System Architecture, etc.).</p>
            </div>
            <div class="feature-card glass">
                <h3>Verified Quality</h3>
                <p>Moderated uploads to ensure accurate and high-standard academic resources.</p>
            </div>
            <div class="feature-card glass">
                <h3>Interactive Community</h3>
                <p>Rate, comment, bookmark, and discover top-trending study materials.</p>
            </div>
        </div>
    </div>

    <div class="auth-section">
        <div class="auth-card glass">
            <div class="tabs">
                <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Login</button>
                <button class="tab-btn" id="tab-register" onclick="switchTab('register')">Register</button>
            </div>

            <!-- Login Form -->
            <form id="login-form" class="auth-form">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="your.email@university.edu">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login to Dashboard</button>
            </form>

            <!-- Register Form -->
            <form id="register-form" class="auth-form" style="display: none;">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Academic Alias">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="Academic Email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
            </form>
        </div>
    </div>
</div>

<div id="toast-container"></div>
<script src="assets/js/main.js"></script>
</body>
</html>
