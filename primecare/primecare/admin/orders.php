<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Portal - Purchase Orders Processing center (Independent)
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Handle Order Status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim(mysqli_real_escape_string($conn, $_POST['status']));

    // Check if current status is already Cancelled or Delivered
    $check_q = "SELECT status FROM orders WHERE id = $order_id";
    $check_res = mysqli_query($conn, $check_q);
    $current_status = '';
    if ($check_res && mysqli_num_rows($check_res) > 0) {
        $row_check = mysqli_fetch_assoc($check_res);
        $current_status = strtolower(trim($row_check['status']));
    }

    if ($current_status === 'cancelled' || $current_status === 'canceled' || $current_status === 'delivered') {
        $error = "Procurement Order #PCO-" . sprintf('%04d', $order_id) . " has already been finalized and cannot be further processed.";
    } elseif (in_array($new_status, ['Pending', 'Confirmed', 'Processing', 'Out for Delivery', 'Delivered', 'Cancelled'])) {
        $update_sql = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";
        if (mysqli_query($conn, $update_sql)) {
            $success = "Successfully updated Procurement Order #PCO-" . sprintf('%04d', $order_id) . " to status '$new_status'!";
        } else {
            $error = "Could not update order status inside database: " . mysqli_error($conn);
        }
    } else {
        $error = "Unsupported order status value.";
    }
}

// Load all orders inside database
$orders_query = "SELECT o.*, u.fullname as client_fullname, u.username as client_username, m.name as medicine_name, m.category as medicine_category, m.price as medicine_price 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 JOIN medicines m ON o.medicine_id = m.id 
                 ORDER BY o.created_at DESC";

$orders_result = mysqli_query($conn, $orders_query);
$orders = [];
if ($orders_result) {
    while ($row = mysqli_fetch_assoc($orders_result)) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders Processing - PrimeCare</title>
    <link class="styles" rel="stylesheet" href="../css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
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

    <!-- Header Navbar -->
    <nav class="premium-capsule-nav">
        <div class="brand" style="cursor:pointer" onclick="window.location.href='dashboard.php'">
            <div class="ppd-capsule-logo">
                <span>ppd</span>
            </div>
            <span style="font-weight: 750; margin-left: 0.5rem; color: #ffffff !important;">Administration</span>
        </div>
        <ul class="nav-links">
            <li class="admin-mobile-only"><a href="dashboard.php">Dashboard Home</a></li>
            <li class="admin-mobile-only"><a href="medicines.php">Manage Medicines</a></li>
            <li class="admin-mobile-only"><a href="orders.php" class="nav-link-active-yellow">Purchase Orders</a></li>
            <li class="admin-mobile-only"><a href="inquiries.php">Client Inquiries</a></li>
            <li class="admin-mobile-only"><a href="reports.php">System Reports</a></li>
            <li><a href="logout.php" style="color:#ef4444 !important;">Logout</a></li>
        </ul>
    </nav>

    <!-- Admin Sidebar Frame layout -->
    <div class="admin-layout">
        
        <!-- Left Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header" style="color:#f8fafc; font-weight:700">Core Navigation</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="dashboard.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        Dashboard Home
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="medicines.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                        Manage Medicines
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="inquiries.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Client Inquiries
                        <?php 
                        $pending_inq_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM inquiries WHERE status='Pending'"));
                        if ($pending_inq_count > 0): ?>
                            <span style="background-color:#ef4444; color:white; font-size:0.75rem; font-weight:700; padding:1px 6px; border-radius:99px; margin-left:auto"><?php echo $pending_inq_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="sidebar-item active">
                    <a href="orders.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        Purchase Orders
                        <?php 
                        $pending_ord_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE status='Pending'"));
                        if ($pending_ord_count > 0): ?>
                            <span style="background-color:#ef4444; color:white; font-size:0.75rem; font-weight:700; padding:1px 6px; border-radius:99px; margin-left:auto"><?php echo $pending_ord_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="reports.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12H3"/><path d="M12 21V3"/></svg>
                        System Reports
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Right Main Board -->
        <main class="admin-main">
            <h2 style="font-size:1.75rem; margin-bottom:1.5rem; font-weight:800; color:var(--text-color)">Wholesale Procurement Dispatch Center</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; box-shadow:var(--shadow-sm)">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem">
                    <input type="text" id="adminOrdSearch" placeholder="Search targets order compound or client..." style="max-width:350px; padding:0.4rem 0.75rem; font-size:0.85rem; width: 100%;" onkeyup="filterOrdRows()">
                </div>

                <div class="table-responsive" style="margin-bottom:0">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:100px">Order ID</th>
                                <th style="width:180px">Distributor / Contact</th>
                                <th style="width:160px">Ordered Compound</th>
                                <th style="width:105px; text-align:center">Qty</th>
                                <th style="width:125px; text-align:center">Total Price</th>
                                <th>Shipping Destination & Notes</th>
                                <th style="width:160px; text-align:center">Order Status Badge</th>
                                <th style="text-align:center; width:260px">Staff Actions Dispatcher</th>
                            </tr>
                        </thead>
                        <tbody id="adminOrdersTableBody">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:3.5rem; color:var(--text-muted)">
                                        No wholesale client orders found in datastore.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): ?>
                                    <?php 
                                        $status_lower = strtolower($ord['status']);
                                        $badgeClass = "status-pending";
                                        if ($status_lower === 'confirmed' || $status_lower === 'processing') $badgeClass = "status-reviewed";
                                        elseif ($status_lower === 'out for delivery') $badgeClass = "status-out-for-delivery";
                                        elseif ($status_lower === 'delivered') $badgeClass = "status-processed";
                                        elseif ($status_lower === 'cancelled' || $status_lower === 'canceled') $badgeClass = "status-cancelled";
                                        
                                        $client_disp = !empty($ord['client_fullname']) ? $ord['client_fullname'] : $ord['fullname'];
                                    ?>
                                    <tr class="order-table-row" data-search="<?php echo strtolower($client_disp . ' ' . $ord['medicine_name']); ?>">
                                        <td><strong>#PCO-<?php echo sprintf('%04d', $ord['id']); ?></strong></td>
                                        <td>
                                            <strong style="color:var(--primary-dark)"><?php echo htmlspecialchars($client_disp); ?></strong><br>
                                            <span style="font-size:0.75rem; color:var(--text-muted)">Tel: <?php echo htmlspecialchars($ord['contact_number']); ?></span>
                                        </td>
                                        <td>
                                            <span style="font-weight:700; color:#2563eb"><?php echo htmlspecialchars($ord['medicine_name']); ?></span><br>
                                            <span style="font-size:0.75rem; color:var(--text-muted)">Unit Price: ₱<?php echo number_format($ord['medicine_price'], 2); ?></span>
                                        </td>
                                        <td style="text-align:center"><strong><?php echo intval($ord['quantity']); ?> Box</strong></td>
                                        <td style="text-align:center">
                                            <strong style="color:#0055ff; font-family:monospace; font-size:1rem">₱<?php echo number_format(floatval($ord['medicine_price']) * intval($ord['quantity']), 2); ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                                $disp_address = $ord['address'];
                                                if (strpos($disp_address, ' → ') === false) {
                                                    // Convert existing commas to arrows for seamless backward compatibility
                                                    $disp_address = str_replace(', ', ' → ', $disp_address);
                                                }
                                            ?>
                                            <div style="font-size:0.85rem; white-space: nowrap;"><?php echo htmlspecialchars($disp_address); ?></div>
                                            <?php if (!empty($ord['notes'])): ?>
                                                <div style="font-size:0.75rem; color:#f97316; font-style:italic; margin-top:3px">Staff Note: "<?php echo htmlspecialchars($ord['notes']); ?>"</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center">
                                            <span class="status-badge <?php echo $badgeClass; ?>" style="text-transform:uppercase; font-weight:800"><?php echo htmlspecialchars($ord['status']); ?></span>
                                            <div style="font-size:0.7rem; color:var(--text-muted); margin-top:3px"><?php echo date('M d, Y h:i A', strtotime($ord['created_at'])); ?></div>
                                        </td>
                                        <td style="text-align:center">
                                            <?php if ($status_lower === 'cancelled' || $status_lower === 'canceled'): ?>
                                                <span style="font-size:0.75rem; color:#ef4444; font-weight:800; text-transform:uppercase">❌ Finalized (Cancelled)</span>
                                            <?php elseif ($status_lower === 'delivered'): ?>
                                                <span style="font-size:0.75rem; color:#10b981; font-weight:800; text-transform:uppercase">✅ Finalized (Delivered)</span>
                                            <?php else: ?>
                                                <form action="orders.php" method="POST" style="display:flex; gap:0.35rem; justify-content:center; align-items:center">
                                                <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                                <input type="hidden" name="update_order_status" value="1">
                                                
                                                <select name="status" style="padding:0.35rem; font-size:0.8rem; border-radius:4px; max-width:145px; background:white; color:#0f172a !important">
                                                    <option value="Pending" <?php echo ($ord['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Confirmed" <?php echo ($ord['status'] === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="Processing" <?php echo ($ord['status'] === 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="Out for Delivery" <?php echo ($ord['status'] === 'Out for Delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                                                    <option value="Delivered" <?php echo ($ord['status'] === 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="Cancelled" <?php echo ($ord['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" class="btn btn-outline btn-sm" style="padding:0.4rem 0.6rem; font-size:0.75rem; border-color:#10b981; color:#059669">
                                                    Update
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

            </div>
        </main>
    </div>

    <!-- Scripts Integration -->
    <script src="../js/script.js?v=2.1"></script>
    <script>
        function filterOrdRows() {
            const q = document.getElementById("adminOrdSearch").value.toLowerCase().trim();
            const rows = document.querySelectorAll(".order-table-row");
            rows.forEach(row => {
                const text = row.getAttribute("data-search");
                if (text.includes(q)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>
