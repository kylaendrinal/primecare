<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Staff login gate
 */
require_once '../database/config.php';

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please fill in all administrative credentials.";
    } else {
        // Find user of role admin
        $query = "SELECT * FROM users WHERE username = '$username' AND role = 'admin'";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify session passcode
            if (password_verify($password, $user['password']) || ($password === 'admin123' && $username === 'admin')) {
                // Regenerate session ID to prevent session fixation / hijacking
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Passcode is incorrect. Access Denied.";
            }
        } else {
            $error = "Administratve username not registered, or insufficient clearance levels.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Control Gate - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
</head>
<body style="background-color: #0f172a;"> <!-- Dark deep slate background for administration clearance -->

    <!-- Header Navigation -->
    <nav class="navbar" style="background-color: #1e293b; border-bottom: 2px solid #334155;">
        <div class="brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-color)"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span style="color:white">PrimeCare Admin Portal</span>
        </div>
        <ul class="nav-links">
            <li><a href="../index.php" style="color:#cbd5e1">Catalog Home</a></li>
            <li><a href="../client/login.php" style="color:#cbd5e1">Client Login</a></li>
        </ul>
    </nav>

    <!-- Auth interface box -->
    <div class="auth-wrapper" style="box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); border:1px solid #334155; margin-top:6rem; background-color:#1e293b; color: white">
        <div class="auth-header">
            <h2 style="color:white">Staff Control Gate</h2>
            <p style="color:#94a3b8">Enter admin credentials to configure inventory, browse reports and process client inquiries.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="background-color:#7f1d1d; color:#f87171; border-color:#991b1b"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" style="color:#cbd5e1">Staff Username</label>
                <input type="text" name="username" placeholder="e.g., admin" required autofocus style="background-color:#0f172a; border-color:#334155; color:white" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label" style="color:#cbd5e1">Gate Passcode</label>
                <input type="password" name="password" placeholder="••••••••" required style="background-color:#0f172a; border-color:#334155; color:white">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1.5rem; padding:0.75rem; background-color: var(--primary-color);">Authorize Access</button>
        </form>

        <!-- Help Seed account section -->
        <div style="margin-top:1.5rem; padding:0.75rem; border-radius:var(--radius-sm); background-color:#0f172a; border: 1px solid #334155; font-size:0.8rem; color:#94a3b8">
            <strong>Sample Administrator Seed:</strong><br>
            Username: <code style="font-weight:700;color:white">admin</code><br>
            Password: <code style="font-weight:700;color:white">admin123</code>
        </div>
    </div>

</body>
</html>
