<?php
session_start();
include('../PHP/db_config.php');

// 1. ADMIN REDIRECT (Prevent Admins from seeing student reset page)
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
    header("Location: admin-dashboard.php");
    exit();
}

// 2. SECURITY GATE: Only allow entry if coming from forgot-password logic
if (!isset($_SESSION['reset_allowed']) || $_SESSION['reset_allowed'] !== true || !isset($_GET['email'])) {
    header("Location: forgot-password.php");
    exit();
}

// Ensure the email in URL matches the email the OTP was sent to
if ($_GET['email'] !== $_SESSION['reset_email_target']) {
    header("Location: forgot-password.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reset Password - MMU Campus Space</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
</head>
<body>
    <div class="auth-container">
        <div class="auth-form-side">
            <div class="auth-form-wrapper">
                <div class="auth-header">
                    <h2>Verify OTP</h2>
                    <p>Please enter the 6-digit code sent to <strong><?php echo htmlspecialchars($_GET['email']); ?></strong></p>
                </div>

                <form action="../PHP/reset_password_process.php" method="POST">
                    <!-- Hidden email field to pass to the process script -->
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
                    
                    <div class="form-group">
                        <label>6-Digit OTP</label>
                        <input type="text" name="otp" class="form-control" placeholder="000000" maxlength="6" required>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required minlength="8">
                    </div>

                    <button type="submit" name="reset_btn" class="btn btn-primary btn-full">Update Password</button>

                    <div style="margin-top: 24px; display: flex; justify-content: flex-start;">
                        <a href="forgot-password.php" class="back-link-custom" style="text-decoration: none; display: flex; align-items: center; gap: 4px; color: var(--text-muted); font-size: 14px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
                            Request new OTP
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="auth-image-side">
            <img src="../public/img/melakalibrary.jpg" alt="Campus">
            <div class="auth-image-text">
                <h3>Secure Access.</h3>
                <p>Verify your identity using the OTP sent to your registered university email address.</p>
            </div>
        </div>
    </div>
</body>
</html>