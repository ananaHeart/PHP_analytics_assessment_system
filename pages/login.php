<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SMART Assessment System</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background-color: #F4F7F9; }
        .login-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        .logo-circle { width: 72px; height: 72px; background: #EBFBEE; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .logo-circle .brand-logo { width: 42px; height: 42px; }
        h2 { margin-bottom: 5px; color: #2C3E50; }
        p.subtitle { color: #718096; font-size: 14px; margin-bottom: 30px; }
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 8px; color: #2C3E50; }
        .input-group input { width: 100%; padding: 12px 15px; border: 1px solid #E2E8F0; border-radius: 10px; font-size: 14px; outline: none; }
        .input-group input:focus { border-color: var(--primary-green); }
        .btn-login { width: 100%; background: var(--primary-green); color: white; border: none; padding: 14px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .footer-links { margin-top: 20px; font-size: 13px; }
        .footer-links a { color: var(--primary-green); text-decoration: none; font-weight: 600; }
        .copyright { margin-top: 40px; font-size: 11px; color: #A0AEC0; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-circle"><img src="../assets/img/smart-logo.svg" alt="SMART Assessment System" class="brand-logo"></div>
    <h2>SMART Assessment System</h2>
    <p class="subtitle">Please enter your credentials to continue</p>

    <form action="../actions/login_action.php" method="POST">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" id="loginPass" placeholder="Enter password" required>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 13px;">
            <label><input type="checkbox"> Remember me</label>
            <a href="#" style="color: var(--primary-green); text-decoration: none;">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-login">Login →</button>
    </form>

    <div class="footer-links">
        Don't have an account? <a href="register.php">Register</a>
    </div>

    <div class="copyright">
        © 2024 SMART Assessment System.<br>All Rights Reserved.
    </div>
</div>

</body>
<!-- Place this at the bottom of login.php -->
<?php if (isset($_GET['error'])): ?>
    <script>
        // Check what kind of error happened
        const error = "<?php echo $_GET['error']; ?>";
        
        if (error === "pending") {
            alert("❌ Access Denied: Your account is still pending approval from the Principal.");
        } else if (error === "notfound") {
            alert("❌ User not found. Please check your email.");
        } else if (error === "wrongpass") {
            alert("❌ Incorrect password. Please try again.");
        }
        
        // Clean the URL so the pop-up doesn't appear again on refresh
        window.history.replaceState({}, document.title, window.location.pathname);
    </script>
<?php endif; ?>
</html>
