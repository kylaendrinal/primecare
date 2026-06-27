<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Global Inquiry submission form page
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Load all medicines for dropdown selector
$med_query = "SELECT id, name, category, stock FROM medicines ORDER BY name ASC";
$med_result = mysqli_query($conn, $med_query);
$medicines = [];
if ($med_result) {
    while ($row = mysqli_fetch_assoc($med_result)) {
        $medicines[] = $row;
    }
}

// Prefill medicine ID if provided via GET query
$selected_med_id = isset($_GET['medicine_id']) ? intval($_GET['medicine_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine_id = intval($_POST['medicine_id']);
    $message = trim(mysqli_real_escape_string($conn, $_POST['message']));

    if (empty($medicine_id) || empty($message)) {
        $error = "Please choose a medicine compound and describe your inquiry details.";
    } else {
        // Check if user already has a continuous inquiry thread
        $check_thread_res = mysqli_query($conn, "SELECT id FROM inquiries WHERE user_id = $client_id LIMIT 1");
        if ($check_thread_res && mysqli_num_rows($check_thread_res) > 0) {
            $thread_row = mysqli_fetch_assoc($check_thread_res);
            $inquiry_id = intval($thread_row['id']);
            // Update main thread for backward compatibility / status update
            mysqli_query($conn, "UPDATE inquiries SET medicine_id = $medicine_id, message = '$message', status = 'Pending', created_at = CURRENT_TIMESTAMP WHERE id = $inquiry_id");
        } else {
            // Create new main thread
            mysqli_query($conn, "INSERT INTO inquiries (user_id, medicine_id, message, status) VALUES ($client_id, $medicine_id, '$message', 'Pending')");
            $inquiry_id = mysqli_insert_id($conn);
        }

        if ($inquiry_id > 0) {
            // Insert message with medicine_id attachment
            mysqli_query($conn, "INSERT INTO inquiry_messages (inquiry_id, sender_id, sender_role, message, medicine_id) VALUES ($inquiry_id, $client_id, 'client', '$message', $medicine_id)");
            $success = "Inquiry successfully recorded. Our staff has been notified.";
            $selected_med_id = 0; // Reset selected ID
        } else {
            $error = "Failed to dispatch inquiry. Code error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Inquiries - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css?v=2.1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
</head>
<body>

    <!-- Header Navigation -->
    <nav class="premium-capsule-nav">
        <div class="brand" style="cursor:pointer" onclick="window.location.href='../index.php'">
            <div class="ppd-capsule-logo">
                <span>ppd</span>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="../index.php">Home</a></li>
            <li><a href="home.php">Available Medicine</a></li>
            <li><a href="my_inquiries.php">My Inquiries</a></li>
            <li><a href="my_orders.php">My Orders</a></li>
            <li>
                <a href="cart.php" title="Cart" style="display: inline-flex; align-items: center; vertical-align: middle;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <span>(<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</span>
                </a>
            </li>
            <li><a href="logout.php" style="color:#ef4444 !important;">Logout</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <main class="container" style="max-width: 600px">
        
        <div style="margin-bottom: 1.5rem">
            <a href="home.php" style="font-weight: 600;">&larr; Back to Catalog Dashboard</a>
        </div>

        <div class="details-card" style="padding: 2.5rem; background-color: #fff">
            <h2 style="font-size:1.5rem; margin-bottom:0.5rem; color:var(--primary-dark)">Submit Stock Inquiry Form</h2>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem">Request bulk discount quotes, batch details, or delivery timelines directly from our distribution centers.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form action="inquiry.php" method="POST">
                
                <div class="form-group">
                    <label class="form-label">Select Medical Compound / Stock Batch</label>
                    <select name="medicine_id" required>
                        <option value="">Select a medicine...</option>
                        <?php foreach ($medicines as $med): ?>
                            <?php 
                                $selected = ($med['id'] == $selected_med_id) ? 'selected' : '';
                                $stock_info = ($med['stock'] > 0) ? "({$med['stock']} available)" : "(Out of stock)";
                            ?>
                            <option value="<?php echo $med['id']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($med['name']); ?> [<?php echo htmlspecialchars($med['category']); ?>] - <?php echo $stock_info; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Describe Specific Inquiries / Purchase Notes</label>
                    <textarea name="message" rows="5" placeholder="Specify pack counts, dispatch methods, delivery hub preferences, or chemical purity requirements..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem; margin-top:1rem">Send Official Request</button>

            </form>
        </div>

    </main>

    <!-- Footer Area -->
    <footer class="footer">
        <p>&copy; 2026 PrimeCare Pharmaceutical Distributors. Managed System Portal.</p>
    </footer>

    <!-- Scripts Integration -->
    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
