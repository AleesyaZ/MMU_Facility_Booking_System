<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reset Password - MMU Campus Space</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form-side">
            <div class="auth-form-wrapper">
                <div class="auth-header">
                    <h2>Verify OTP</h2>
                    <p>Enter the code sent to your email and your new password.</p>
                </div>

                <form action="../PHP/reset_password_process.php" method="POST">
                    <input type="hidden" name="email" value="<?php echo $_GET['email']; ?>">
                    
                    <div class="form-group">
                        <label>6-Digit OTP</label>
                        <input type="text" name="otp" class="form-control" placeholder="000000" required>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required minlength="8">
                    </div>

                    <button type="submit" name="reset_btn" class="btn btn-primary btn-full">Update Password</button>
                </form>
            </div>
        </div>
        <div class="auth-image-side"><img src="../public/img/melakalibrary.jpg"></div>
    </div>
</body>
</html>