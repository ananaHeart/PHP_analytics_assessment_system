<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Sign Up - SMART</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 20px 0; background-color: #F4F7F9; }
        .register-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; text-align: center; }
        h2 { color: #2C3E50; margin-bottom: 5px; }
        .subtitle { color: #718096; font-size: 14px; margin-bottom: 30px; }
        .form-row { display: flex; gap: 15px; }
        .input-group { text-align: left; margin-bottom: 15px; flex: 1; }
        label { display: block; font-size: 12px; font-weight: 700; color: #2C3E50; margin-bottom: 5px; }
        input, select { width: 100%; padding: 11px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; outline: none; }
        .btn-signup { width: 100%; background: var(--primary-green); color: white; border: none; padding: 14px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>

<div class="register-card">
    <h2>Teacher Sign Up</h2>
    <p class="subtitle">Create your SMART Assessment account</p>

    <form action="../actions/register_action.php" method="POST">
        <div class="form-row">
            <div class="input-group">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder="e.g. John" required>
            </div>
            <div class="input-group">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder="e.g. Doe" required>
            </div>
        </div>

        <div class="input-group">
            <label>Gender</label>
            <select name="gender" required>
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>

        <div class="input-group">
            <label>Birth Date</label>
            <input type="date" name="date_birth" required>
        </div>

        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="teacher@school.edu" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <div class="input-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-signup">Sign Up</button>
    </form>

    <div style="margin-top: 25px; font-size: 14px; color: #718096;">
        Already have an account? <a href="login.php" style="color: var(--primary-green); font-weight: 600; text-decoration: none;">Log in</a>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <script>
        const error = "<?php echo $_GET['error']; ?>";
        if (error === "exists") {
            alert("This email is already registered.");
        } else if (error === "failed") {
            alert("Unable to complete registration right now. Please try again.");
        }
        window.history.replaceState({}, document.title, window.location.pathname);
    </script>
<?php endif; ?>

</body>
</html>
