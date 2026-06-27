<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Client Sign Up Portal Engine
 */
require_once dirname(__DIR__) . '/database/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim(mysqli_real_escape_string($conn, $_POST['fullname']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $username = strtolower(trim(mysqli_real_escape_string($conn, $_POST['username'])));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validations
    if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
        $error = "Please fill in all core credentials.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($username) < 4) {
        $error = "Username must be at least 4 characters long.";
    } elseif ($username === 'admin') {
        $error = "Admin registration is prohibited. Administrator accounts must already exist inside our database.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must include at least one special character (e.g. @, #, $, %, etc.).";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Query to check if username is unique
        $check_query = "SELECT id FROM users WHERE username = '$username'";
        $check_result = mysqli_query($conn, $check_query);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error = "This username is already registered inside our distributor database.";
        } else {
            // Hash password with modern crypt/bcrypt
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (fullname, email, username, password, role) VALUES ('$fullname', '$email', '$username', '$hashed_pass', 'client')";

            if (mysqli_query($conn, $insert_query)) {
                $success = "Registration complete! You can now log in using your client credentials.";
            } else {
                $error = "Could not execute registration. Database failure: " . mysqli_error($conn);
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
    <title>Register Client - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css?v=2.5">
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

        /* Password Strength Requirement styles visually enhanced */
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
            color: #dc2626 !important;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .req.ok {
            color: #15803d !important;
        }

        /* Input styling */
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
    </style>
</head>
<body class="pc-redesign-body" style="background-color: var(--bg-color);">

    <!-- Header Navigation Capsule -->
    <nav class="premium-capsule-nav">
        <div class="brand" style="cursor:pointer" onclick="window.location.href='../index.php'">
            <div class="ppd-capsule-logo">
                <span>ppd</span>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="../index.php#about">About Us</a></li>
            <li><a href="../index.php#features">Services</a></li>
            <li><a href="../index.php#products">Products</a></li>
            <li><a href="../index.php#contact">Contacts</a></li>
        </ul>
        <div class="nav-actions-capsule">
            <a href="../login.php" class="nav-login-link">Login</a>
            <a href="signup.php" class="nav-register-pill">Register</a>
        </div>
    </nav>

    <!-- Unified Auth Card Wrapper -->
    <div class="auth-wrapper" style="margin: 6rem auto 4rem; max-width: 450px; border-top: 8px solid var(--blue); border-radius: 16px; box-shadow: var(--shadow-md); display: flex; flex-direction: column; align-items: center; padding: 2.5rem 2rem; background: #ffffff;">
        
        <!-- PPD circular cover logo consistent across system -->
        <div style="width: 85px; height: 85px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #ffffff; border: 3px solid #eff6ff; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
            <img src="../images/ppd_logo.png" alt="PPD Logo" style="height: 48px; max-width: 100%; object-fit: contain;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=100&auto=format&fit=crop&q=80';">
        </div>

        <div style="text-align: center; width: 100%;">
            <h2 style="color: var(--dark); font-weight: 800; font-size: 1.55rem; margin-bottom: 4px; font-family: 'Space Grotesk', sans-serif;">Create Account</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;"></p>

            <!-- Alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="padding:10px 14px; font-size:0.8rem; text-align:left; margin-bottom:1.25rem; color: #b91c1c; background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="padding:10px 14px; font-size:0.8rem; text-align:left; margin-bottom:1.25rem; color: #15803d; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                    <?php echo htmlspecialchars($success); ?><br>
                    <a href="../login.php" style="font-weight:700; text-decoration:underline; color:#15803d">Proceed to login portal &raquo;</a>
                </div>
            <?php endif; ?>

            <form action="signup.php" method="POST" style="width: 100%;">
                <div class="form-group" style="text-align: left;">
                    <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">Full Name</label>
                    <div class="input-group">
                        <input type="text" name="fullname" placeholder="e.g., Juan Dela Cruz" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group" style="text-align: left;">
                    <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">Email Address</label>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="e.g., juandelacruz@gmail.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group" style="text-align: left;">
                    <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">Username</label>
                    <div class="input-group">
                        <input type="text" name="username" placeholder="e.g., juan delacruz" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group" style="text-align: left;">
                    <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">Password</label>
                    <div class="input-group">
                        <input type="password" id="reg-p" name="password" placeholder="••••••••" required oninput="checkPassStrength()">
                        <span class="eye" onclick="togglePasswordVisibility('reg-p', this)">👁️</span>
                    </div>
                </div>

                <div class="form-group" style="text-align: left;">
                    <label class="form-label" style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 6px;">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" id="reg-confirm-p" name="confirm_password" placeholder="••••••••" required oninput="checkPasswordsMatch()">
                        <span class="eye" onclick="togglePasswordVisibility('reg-confirm-p', this)">👁️</span>
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

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.7rem; font-weight: 700; border-radius: 8px; background-color: var(--blue); color: white; border: none; cursor: pointer; transition: background 0.2s;">Register Account</button>
            </form>

            <div class="auth-footer" style="font-size: 0.85rem; margin-top: 1.5rem; color: #475569;">
                Joined already? <a href="../login.php" style="font-weight: 700; color: var(--blue); text-decoration: none;">Sign In</a>
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
            const p = document.getElementById('reg-p').value;
            const cp = document.getElementById('reg-confirm-p').value;
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
            const v = document.getElementById('reg-p').value;
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
                        el.style.setProperty('color', '#15803d', 'important');
                    } else {
                        el.classList.remove('ok');
                        el.textContent = "❌ " + el.textContent.substring(2);
                        el.style.setProperty('color', '#dc2626', 'important');
                    }
                }
            }
            checkPasswordsMatch();
        }
    </script>
    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
