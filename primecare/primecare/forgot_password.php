<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Password Reset / Forgot Password Portal
 * Support offline XAMPP testing out-of-the-box
 */
require_once __DIR__ . '/database/config.php';

$error = '';
$success = '';

// Determine current step
if (!isset($_SESSION['reset_step'])) {
    $_SESSION['reset_step'] = 1;
}

$step = $_SESSION['reset_step'];

// Cancel reset flow and start over
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_code']);
    unset($_SESSION['reset_expiry']);
    $_SESSION['reset_step'] = 1;
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1 && isset($_POST['submit_email'])) {
        $email = trim(mysqli_real_escape_string($conn, $_POST['email']));

        if (empty($email)) {
            $error = "Please enter your registered email address.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if email exists in users
            $query = "SELECT * FROM users WHERE LOWER(email) = LOWER('$email')";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                // Generate a 6-digit verification code
                $code = rand(100000, 999999);
                
                // Store in session
                $_SESSION['reset_email'] = $user['email'];
                $_SESSION['reset_code'] = $code;
                $_SESSION['reset_expiry'] = time() + 900; // 15 minutes
                $_SESSION['reset_step'] = 2;
                $step = 2;

                // 1. Attempt standard PHP mail send
                $to = $user['email'];
                $subject = "PrimeCare Password Reset Verification Code";
                $headers = "From: no-reply@primecare.com\r\n";
                $headers .= "Reply-To: support@primecare.com\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                $message_html = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;'>
                    <h2 style='color: #001270;'>PrimeCare Password Recovery</h2>
                    <p>We received a request to reset the password for your distributor account associated with this email.</p>
                    <p>Your password reset verification code is:</p>
                    <div style='background-color: #f1f5f9; padding: 15px; border-radius: 8px; font-size: 24px; font-weight: bold; text-align: center; color: #0055ff; letter-spacing: 5px; margin: 20px 0;'>
                        {$code}
                    </div>
                    <p>This code is valid for 15 minutes. If you did not request this, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #cbd5e1; margin-top: 30px;' />
                    <p style='font-size: 11px; color: #64748b;'>PrimeCare Pharmaceutical Distributors Managed System Portal.</p>
                </div>";
                
                @mail($to, $subject, $message_html, $headers);

                // 2. Write verification code to a local file for fallback testing
                $log_dir = __DIR__ . '/uploads';
                if (!is_dir($log_dir)) {
                    @mkdir($log_dir, 0777, true);
                }
                $log_file = $log_dir . '/reset_codes.log';
                $log_entry = "[" . date('Y-m-d H:i:s') . "] Email: " . $user['email'] . " | Code: " . $code . "\n";
                @file_put_contents($log_file, $log_entry, FILE_APPEND);

                $success = "Verification code has been dispatched to your email address.";
            } else {
                $error = "This email address is not registered in our distributor database.";
            }
        }
    } 
    elseif ($step === 2 && isset($_POST['submit_code'])) {
        $entered_code = trim($_POST['code']);

        if (empty($entered_code)) {
            $error = "Please enter the 6-digit verification code.";
        } elseif (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_expiry'])) {
            $error = "Session expired. Please request a new verification code.";
            $_SESSION['reset_step'] = 1;
            $step = 1;
        } elseif (time() > $_SESSION['reset_expiry']) {
            $error = "The verification code has expired. Please try again.";
            $_SESSION['reset_step'] = 1;
            $step = 1;
        } elseif (intval($entered_code) === intval($_SESSION['reset_code'])) {
            $_SESSION['reset_step'] = 3;
            $step = 3;
            $success = "Verification successful! You can now create your new password key.";
        } else {
            $error = "Incorrect verification code. Please try again.";
        }
    } 
    elseif ($step === 3 && isset($_POST['submit_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $email = $_SESSION['reset_email'];

        // Standard complex validations consistent with signup.php
        if (empty($new_password)) {
            $error = "Please enter a new password.";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
        } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $error = "Password must include at least one special character (e.g. @, #, $, %, etc.).";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            // Update password in users table
            $hashed_pass = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_pass' WHERE LOWER(email) = LOWER('$email')";
            
            if (mysqli_query($conn, $update_query)) {
                // Clear reset session state entirely
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_code']);
                unset($_SESSION['reset_expiry']);
                $_SESSION['reset_step'] = 4;
                $step = 4;
                $success = "Your password has been successfully updated!";
            } else {
                $error = "Could not update password. Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Password - PrimeCare</title>
    <link rel="stylesheet" href="css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap">
    <style>
        :root {
            --blue: #0055ff;
            --dark: #001270;
            --bg: #f0f7ff;
            --white: #fff;
            --error: #ef4444;
            --success: #10b981;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.25rem;
            text-align: left;
        }
        input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px !important;
            outline: none;
            box-sizing: border-box;
            transition: all 0.2s;
        }
        input:focus {
            border-color: var(--blue) !important;
            box-shadow: 0 0 0 3px rgba(0, 85, 255, 0.15);
        }
        input::-ms-reveal, input::-webkit-password-reveal {
            display: none;
        }
        .eye {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
            font-size: 1.1rem;
            user-select: none;
            transition: color 0.15s;
        }
        .eye:hover {
            color: var(--blue);
        }

        /* Password strength card visually consistent with signup.php */
        .req-box {
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: left;
            font-size: 0.75rem;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .req {
            color: var(--error);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .req.ok {
            color: var(--success);
        }
    </style>
</head>
<body class="pc-redesign-body" style="background-color: var(--bg-color);">

    <!-- Header Navigation Capsule -->
    <nav class="premium-capsule-nav">
        <div class="brand" style="cursor:pointer" onclick="window.location.href='index.php'">
            <div class="ppd-capsule-logo">
                <span>ppd</span>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="index.php#about">About Us</a></li>
            <li><a href="index.php#features">Services</a></li>
            <li><a href="index.php#products">Products</a></li>
            <li><a href="index.php#contact">Contacts</a></li>
        </ul>
        <div class="nav-actions-capsule">
            <a href="login.php" class="nav-login-link">Login</a>
            <a href="client/signup.php" class="nav-register-pill">Register</a>
        </div>
    </nav>

    <!-- Unified Card Wrapper -->
    <div class="auth-wrapper" style="margin: 6rem auto 4rem; max-width: 440px; border-top: 8px solid var(--blue); border-radius: 16px; box-shadow: var(--shadow-md); display: flex; flex-direction: column; align-items: center; padding: 2.5rem 2rem; background: #ffffff;">
        
        <!-- PPD Cover Logo -->
        <div style="width: 85px; height: 85px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #ffffff; border: 3px solid #eff6ff; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
            <img src="images/ppd_logo.png" alt="PPD Logo" style="height: 48px; max-width: 100%; object-fit: contain;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=100&auto=format&fit=crop&q=80';">
        </div>

        <div style="text-align: center; width: 100%;">
            <h2 style="color: var(--dark); font-weight: 800; font-size: 1.55rem; margin-bottom: 4px; font-family: 'Space Grotesk', sans-serif;">Account Recovery</h2>
            <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 1.5rem;"></p>

            <!-- Alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="padding:10px 14px; font-size:0.8rem; text-align:left; margin-bottom:1.25rem; color: #b91c1c; background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="padding:10px 14px; font-size:0.8rem; text-align:left; margin-bottom:1.25rem; color: #15803d; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- 6-digit verification code display for verification -->
            <?php if (($step === 2 || $step === 3) && isset($_SESSION['reset_code'])): ?>
                <div style="background-color: #f8fafc !important; border: 1px solid #e2e8f0 !important; padding: 1.25rem !important; border-radius: 12px !important; margin-bottom: 1.5rem !important; text-align: center !important;">
                    <span style="display: block !important; font-size: 0.85rem !important; font-weight: 800 !important; color: #1e293b !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; margin-bottom: 0.5rem !important;">Your Verification Code</span>
                    <span style="font-size: 2.4rem !important; font-weight: 800 !important; color: #0055ff !important; display: block !important; letter-spacing: 6px !important; background: #e0f2fe !important; padding: 10px 20px !important; border-radius: 8px !important; font-family: monospace !important; border: 2px solid #3b82f6 !important; margin-bottom: 0.25rem !important; text-shadow: none !important;">
                        <?php echo $_SESSION['reset_code']; ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- STEP 1: ENTER REGISTERED EMAIL -->
            <?php if ($step === 1): ?>
                <form action="forgot_password.php" method="POST" style="width: 100%;">
                    <div class="form-group" style="text-align: left; margin-bottom: 1.25rem;">
                        <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">Registered Email Address</label>
                        <div class="input-group">
                            <input type="email" name="email" placeholder="e.g., client@primecare.com" required autofocus value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <button type="submit" name="submit_email" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.7rem; font-weight: 700; border-radius: 8px; background-color: var(--blue); color: white; border: none; cursor: pointer; transition: background 0.2s;">Send Verification Code</button>
                </form>

            <!-- STEP 2: ENTER VERIFICATION CODE -->
            <?php elseif ($step === 2): ?>
                <form action="forgot_password.php" method="POST" style="width: 100%;">
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1.25rem; text-align: center;">
                        Please enter the 6-digit verification code below.
                    </p>
                    <div class="form-group" style="text-align: left; margin-bottom: 1.25rem;">
                        <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">6-Digit Code</label>
                        <div class="input-group">
                            <input type="text" name="code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required autofocus style="text-align: center; font-size: 1.2rem; letter-spacing: 4px; font-weight: 700;">
                        </div>
                    </div>

                    <button type="submit" name="submit_code" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem; padding: 0.7rem; font-weight: 700; border-radius: 8px; background-color: var(--blue); color: white; border: none; cursor: pointer; transition: background 0.2s;">Verify Code &raquo;</button>
                    
                    <div style="margin-top: 1.25rem;">
                        <a href="forgot_password.php?action=cancel" style="font-size: 0.8rem; color: #ef4444; font-weight: 600; text-decoration: none;">&larr; Cancel and start over</a>
                    </div>
                </form>

            <!-- STEP 3: CREATE NEW PASSWORD -->
            <?php elseif ($step === 3): ?>
                <form action="forgot_password.php" method="POST" style="width: 100%;">
                    <div class="form-group" style="text-align: left;">
                        <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">New Password</label>
                        <div class="input-group">
                            <input type="password" id="reset-p" name="new_password" placeholder="••••••••" required oninput="checkPassStrength()">
                            <span class="eye" onclick="togglePasswordVisibility('reset-p', this)">👁️</span>
                        </div>
                    </div>

                    <div class="form-group" style="text-align: left;">
                        <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" id="reset-confirm-p" name="confirm_password" placeholder="••••••••" required oninput="checkPasswordsMatch()">
                            <span class="eye" onclick="togglePasswordVisibility('reset-confirm-p', this)">👁️</span>
                        </div>
                    </div>

                    <div id="confirm-error-msg" style="color: var(--error); font-size: 0.75rem; text-align: left; margin-top: -10px; margin-bottom: 10px; font-weight: 600; display: none;"></div>

                    <div class="req-box">
                        <span id="r-len" class="req">❌ 8+ characters</span>
                        <span id="r-upp" class="req">❌ One uppercase letter</span>
                        <span id="r-low" class="req">❌ One lowercase letter</span>
                        <span id="r-num" class="req">❌ One number</span>
                        <span id="r-spec" class="req">❌ One special character</span>
                    </div>

                    <button type="submit" name="submit_password" class="btn btn-primary" style="width: 100%; padding: 0.7rem; font-weight: 700; border-radius: 8px; background-color: var(--blue); color: white; border: none; cursor: pointer; transition: background 0.2s;">Save & Update Password</button>
                    
                    <div style="margin-top: 1.25rem;">
                        <a href="forgot_password.php?action=cancel" style="font-size: 0.8rem; color: #ef4444; font-weight: 600; text-decoration: none;">&larr; Cancel and start over</a>
                    </div>
                </form>

            <!-- STEP 4: SUCCESS RECOVERY -->
            <?php elseif ($step === 4): ?>
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #f0fdf4; border: 2px solid #bbf7d0; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <p style="font-size: 0.9rem; color: #475569; line-height: 1.5;">
                        Your password recovery process is complete! You can now authenticate with your secure updated credentials.
                    </p>
                </div>

                <a href="login.php" class="btn btn-primary" style="width: 100%; padding: 0.7rem; font-weight: 700; border-radius: 8px; background-color: var(--blue); color: white; border: none; text-align: center; text-decoration: none; display: block;">Return to Sign In Portal &raquo;</a>
            <?php endif; ?>

            <div class="auth-footer" style="font-size: 0.85rem; margin-top: 2rem; color: #475569; border-top: 1px solid #f1f5f9; padding-top: 1.25rem;">
                Remembered your password? <a href="login.php" style="font-weight: 700; color: var(--blue); text-decoration: none;">Sign In</a>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId, iconEl) {
            const field = document.getElementById(fieldId);
            if (field) {
                if (field.type === "password") {
                    field.type = "text";
                    iconEl.textContent = "🙈";
                } else {
                    field.type = "password";
                    iconEl.textContent = "👁️";
                }
            }
        }

        function checkPasswordsMatch() {
            const p = document.getElementById('reset-p').value;
            const cp = document.getElementById('reset-confirm-p').value;
            const target = document.getElementById('confirm-error-msg');
            if (cp && p !== cp) {
                target.style.display = "block";
                target.textContent = "Passwords do not match.";
                return false;
            } else {
                target.style.display = "none";
                return true;
            }
        }

        function checkPassStrength() {
            const v = document.getElementById('reset-p').value;
            const reqs = {
                'r-len': v.length >= 8,
                'r-upp': /[A-Z]/.test(v),
                'r-low': /[a-z]/.test(v),
                'r-num': /[0-9]/.test(v),
                'r-spec': /[^A-Za-z0-9]/.test(v)
            };

            for (let id in reqs) {
                const el = document.getElementById(id);
                if (el) {
                    if (reqs[id]) {
                        el.classList.add('ok');
                        el.textContent = "✓ " + el.textContent.substring(2);
                        el.style.color = "var(--success)";
                    } else {
                        el.classList.remove('ok');
                        el.textContent = "❌ " + el.textContent.substring(2);
                        el.style.color = "var(--error)";
                    }
                }
            }
            checkPasswordsMatch();
        }
    </script>
</body>
</html>
