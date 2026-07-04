<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Forgot Password - MMU Facility Booking</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
</head>
<body>
    <div class="auth-container">
        <div class="auth-form-side">
            <div class="auth-form-wrapper">
                <div class="auth-header">
                    <h2>Reset Password</h2>
                    <p>Enter your email address and we'll send you an OTP to reset your password.</p>
                </div>

                <form action="../PHP/forgot_password_process.php" method="POST">
                    <div class="form-group">
                        <label>MMU Email</label>
                        <input type="email" name="email" class="form-control" placeholder="studentID@student.mmu.edu.my" required>
                    </div>

                    <button type="submit" name="request_otp_btn" class="btn btn-primary btn-full">Send OTP</button>

                    <div style="margin-top: 24px; display: flex; justify-content: flex-start;">
                        <a href="login.php" class="back-link-custom">
                            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
                            Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="auth-image-side">
            <img src="../public/img/melakalibrary.jpg" alt="Campus">
        </div>
    </div>
</body>
</html>