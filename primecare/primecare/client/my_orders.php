<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Client Portal - My Orders Monitor (Independent Section)
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['user_id'];

// Handle cancel order POST action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $order_id = intval($_POST['order_id']);
    // Verify that the order belongs to this client and status is not "out for delivery" or "delivered" or "cancelled"
    $check_q = "SELECT status, medicine_id, quantity FROM orders WHERE id = $order_id AND user_id = $client_id";
    $check_res = mysqli_query($conn, $check_q);
    if ($check_res && mysqli_num_rows($check_res) > 0) {
        $order_data = mysqli_fetch_assoc($check_res);
        $current_status = strtolower(trim($order_data['status']));
        if ($current_status !== 'out for delivery' && $current_status !== 'delivered' && $current_status !== 'cancelled' && $current_status !== 'canceled') {
            // Update status to 'Cancelled'
            $update_q = "UPDATE orders SET status = 'Cancelled' WHERE id = $order_id";
            if (mysqli_query($conn, $update_q)) {
                // Restore stock of medicine to warehouse!
                $med_id = intval($order_data['medicine_id']);
                $qty = intval($order_data['quantity']);
                $restore_stock = "UPDATE medicines SET stock = stock + $qty, availability = 'Available' WHERE id = $med_id";
                mysqli_query($conn, $restore_stock);

                $_SESSION['flash_success'] = "Wholesale procurement order has been successfully cancelled, and stocks returned to active warehouse inventories.";
            } else {
                $_SESSION['flash_error'] = "Failed to cancel order due to a database issue.";
            }
        } else {
            $_SESSION['flash_error'] = "This order cannot be cancelled as it is already " . $current_status . ".";
        }
    } else {
        $_SESSION['flash_error'] = "Invalid order or unauthorized request.";
    }
    header("Location: my_orders.php");
    exit;
}

// Query my orders
$orders_query = "SELECT o.*, m.name as medicine_name, m.category as medicine_category, m.price as medicine_price 
                 FROM orders o 
                 JOIN medicines m ON o.medicine_id = m.id 
                 WHERE o.user_id = $client_id 
                 ORDER BY o.created_at DESC";

$orders_result = mysqli_query($conn, $orders_query);
$orders = [];
$total_orders_count = 0;
$total_pending_amount = 0.00;
$total_settled_amount = 0.00;

if ($orders_result) {
    while ($row = mysqli_fetch_assoc($orders_result)) {
        $orders[] = $row;
        $status_lower = strtolower($row['status']);
        if ($status_lower !== 'cancelled' && $status_lower !== 'canceled') {
            $total_orders_count++;
            $order_total = floatval($row['medicine_price']) * intval($row['quantity']);
            if ($status_lower === 'delivered') {
                $total_settled_amount += $order_total;
            } else {
                $total_pending_amount += $order_total;
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
    <title>My Orders - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            flex: 1;
        }
        .status-responded {
            background-color: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .status-out-for-delivery {
            background-color: #faf5ff;
            color: #7c3aed;
            border: 1px solid #e9d5ff;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
    </style>
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
            <li><a href="my_orders.php" class="nav-link-active-yellow">My Orders</a></li>
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

    <!-- Main Content Container -->
    <main class="container">
        
        <div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem">
            <a href="home.php" style="font-weight: 600;">&larr; Back to Dashboard</a>
        </div>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem; background:#d1fae5; color:#065f46; border:1px solid #10b981; padding:12px; border-radius:6px; font-weight: 600; font-size:0.9rem">
                ✓ <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem; background:#fee2e2; color:#991b1b; border:1px solid #ef4444; padding:12px; border-radius:6px; font-weight: 600; font-size:0.9rem">
                ⚠ <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 2rem;">
            <h1 style="font-size:1.75rem; font-weight:800; color:var(--primary-dark)">My Orders</h1>
            <p style="color:var(--text-muted); font-size:0.9rem">Audit procurement delivery status, billing sums, and wholesale orders history.</p>
        </div>

        <!-- ORDERS GRID/TABLE -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:105px">Order ID</th>
                        <th style="width:180px">Recipient</th>
                        <th style="width:180px">Drug Compound</th>
                        <th style="width:110px; text-align:center">Quantity</th>
                        <th style="width:130px; text-align:center">Total Price</th>
                        <th>Shipping Details</th>
                        <th style="width:170px; text-align:center">Current Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted)">
                                <h3 style="margin-bottom:0.25rem">No procurement orders placed yet.</h3>
                                <p style="font-size:0.85rem">View active drug details to place your first wholesale batch purchase order.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
                            <?php 
                                $status_lower = strtolower(trim($ord['status']));
                                $badgeClass = "status-pending";
                                if ($status_lower === 'confirmed') $badgeClass = "status-reviewed";
                                elseif ($status_lower === 'processing') $badgeClass = "status-reviewed";
                                elseif ($status_lower === 'out for delivery') $badgeClass = "status-out-for-delivery";
                                elseif ($status_lower === 'delivered') $badgeClass = "status-processed";
                                elseif ($status_lower === 'cancelled' || $status_lower === 'canceled') $badgeClass = "status-cancelled";
                            ?>
                            <tr>
                                <td><strong>#PCO-<?php echo sprintf('%04d', $ord['id']); ?></strong></td>
                                <td>
                                    <span style="font-weight:700"><?php echo htmlspecialchars($ord['fullname']); ?></span><br>
                                    <span style="font-size:0.75rem; color:var(--text-muted)"><?php echo htmlspecialchars($ord['contact_number']); ?></span>
                                </td>
                                <td>
                                    <span style="font-weight:600; color:var(--primary-dark)"><?php echo htmlspecialchars($ord['medicine_name']); ?></span><br>
                                    <span style="font-size:0.75rem; color:var(--text-muted)">Unit: ₱<?php echo number_format($ord['medicine_price'], 2); ?></span>
                                </td>
                                <td style="text-align:center">
                                    <strong><?php echo intval($ord['quantity']); ?> Bxs</strong>
                                </td>
                                <td style="text-align:center">
                                    <strong style="color:#0055ff; font-family:monospace; font-size:1rem">₱<?php echo number_format(floatval($ord['medicine_price']) * intval($ord['quantity']), 2); ?></strong>
                                </td>
                                <td>
                                    <div style="font-size:0.85rem; color:var(--text-color); max-width:260px"><?php echo htmlspecialchars($ord['address']); ?></div>
                                    <?php if (!empty($ord['notes'])): ?>
                                        <div style="font-size:0.75rem; color:#ea580c; font-style:italic; margin-top:2px">Note: "<?php echo htmlspecialchars($ord['notes']); ?>"</div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center; min-width: 130px;">
                                    <span class="status-badge <?php echo $badgeClass; ?>" style="padding:0.35rem 0.75rem; font-weight:800; text-transform:uppercase"><?php echo htmlspecialchars($ord['status']); ?></span>
                                    <div style="font-size:0.7rem; color:var(--text-muted); margin-top:4px; margin-bottom:0.5rem"><?php echo date('M d, Y h:i A', strtotime($ord['created_at'])); ?></div>
                                    <?php if ($status_lower !== 'out for delivery' && $status_lower !== 'delivered' && $status_lower !== 'cancelled' && $status_lower !== 'canceled'): ?>
                                        <form method="POST" action="my_orders.php" id="cancelForm-<?php echo $ord['id']; ?>">
                                            <input type="hidden" name="action" value="cancel_order">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <button type="button" onclick="window.primecare_confirm('Are you sure you want to cancel this wholesale procurement order? This will restore medicine box levels in warehouse.', () => document.getElementById('cancelForm-<?php echo $ord['id']; ?>').submit())" class="btn btn-sm" style="font-size:0.75rem; padding: 2px 8px; border: 1px solid #ef4444; background: none; color: #ef4444; border-radius: 4px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; width: 100%; box-sizing: border-box; text-align: center;">
                                                Cancel Order
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <!-- Footer Area -->
    <footer class="footer">
        <p>&copy; 2026 PrimeCare Pharmaceutical Distributors. Managed System Portal.</p>
    </footer>

    <!-- Scripts Integration -->
    <script src="../js/script.js"></script>
</body>
</html>
