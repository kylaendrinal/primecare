<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Core Dashboard Control
 */
require_once dirname(__DIR__) . '/database/config.php';

// Check auth and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Stats metrics
$stat_med = mysqli_query($conn, "SELECT COUNT(*) as count FROM medicines");
$total_meds = mysqli_fetch_assoc($stat_med)['count'];

$stat_pending_inq = mysqli_query($conn, "SELECT COUNT(*) as count FROM inquiries WHERE status = 'Pending'");
$pending_inqs = mysqli_fetch_assoc($stat_pending_inq)['count'];

$stat_pending_ord = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
$pending_ords = mysqli_fetch_assoc($stat_pending_ord)['count'];

$total_pending_actions = $pending_inqs + $pending_ords;

// Get 5 most recent inquiries for dashboard table view
$recent_query = "SELECT i.*, u.fullname as client_name, m.name as medicine_name 
                 FROM inquiries i 
                 LEFT JOIN users u ON i.user_id = u.id 
                 JOIN medicines m ON i.medicine_id = m.id 
                 ORDER BY i.created_at DESC LIMIT 5";
$recent_result = mysqli_query($conn, $recent_query);
$recent_inquiries = [];
if ($recent_result) {
    while ($row = mysqli_fetch_assoc($recent_result)) {
        $recent_inquiries[] = $row;
    }
}

// Get 5 most recent orders for dashboard table view
$recent_orders_query = "SELECT o.*, u.fullname as client_name, m.name as medicine_name 
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id 
                        JOIN medicines m ON o.medicine_id = m.id 
                        ORDER BY o.created_at DESC LIMIT 5";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);
$recent_orders = [];
if ($recent_orders_result) {
    while ($row = mysqli_fetch_assoc($recent_orders_result)) {
        $recent_orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core Administrative Control - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
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
            <li class="admin-mobile-only"><a href="dashboard.php" class="nav-link-active-yellow">Dashboard Home</a></li>
            <li class="admin-mobile-only"><a href="medicines.php">Manage Medicines</a></li>
            <li class="admin-mobile-only"><a href="orders.php">Purchase Orders</a></li>
            <li class="admin-mobile-only"><a href="inquiries.php">Client Inquiries</a></li>
            <li class="admin-mobile-only"><a href="reports.php">System Reports</a></li>
            <li><a href="logout.php" style="color:#ef4444 !important;">Logout</a></li>
        </ul>
    </nav>

    <!-- Admin dashboard sidebar setup -->
    <div class="admin-layout">
        
        <!-- Left Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header" style="color:#f8fafc; font-weight:700">Core Navigation</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item active">
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
                        <?php if ($pending_inqs > 0): ?>
                            <span style="background-color:#ef4444; color:white; font-size:0.75rem; font-weight:700; padding:1px 6px; border-radius:99px; margin-left:auto"><?php echo $pending_inqs; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="orders.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        Purchase Orders
                        <?php if ($pending_ords > 0): ?>
                            <span style="background-color:#ef4444; color:white; font-size:0.75rem; font-weight:700; padding:1px 6px; border-radius:99px; margin-left:auto"><?php echo $pending_ords; ?></span>
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

        <!-- Right Main View Control -->
        <main class="admin-main">
            <h2 style="font-size:1.75rem; margin-bottom:1.5rem; font-weight:800; color:var(--text-color)">Distributor Control Dashboard</h2>

            <!-- Metrics grid blocks -->
            <section class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; margin-bottom:2rem">
                <div class="stat-box" style="border-top: 4px solid var(--primary-color)">
                    <span class="stat-title">Catalogued Medicines</span>
                    <span class="stat-number"><?php echo $total_meds; ?></span>
                </div>
                <div class="stat-box" style="border-top: 4px solid var(--warning-color)">
                    <span class="stat-title">New Inquiries</span>
                    <span class="stat-number" style="color:#d97706"><?php echo $pending_inqs; ?></span>
                </div>
                <div class="stat-box" style="border-top: 4px solid #10b981">
                    <span class="stat-title">New Orders</span>
                    <span class="stat-number" style="color:#059669"><?php echo $pending_ords; ?></span>
                </div>
                <div class="stat-box" style="border-top: 4px solid #ef4444">
                    <span class="stat-title">Pending Actions</span>
                    <span class="stat-number" style="color:#dc2626"><?php echo $total_pending_actions; ?></span>
                </div>
            </section>

            <!-- Latest Customer Inquiries Card -->
            <div style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; box-shadow:var(--shadow-sm); margin-bottom:2rem">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:1rem">
                    <h3 style="font-size:1.25rem; font-weight:800; color:var(--primary-dark)">Latest Customer Inquiries</h3>
                    <a href="inquiries.php#inquiries" class="btn btn-outline btn-sm">Process All Inquiries &raquo;</a>
                </div>

                <div class="table-responsive" style="margin-bottom:0">
                    <table>
                        <thead>
                            <tr>
                                <th>Ref ID</th>
                                <th>Client Name</th>
                                <th>Inquiry Compound</th>
                                <th>Message Particulars</th>
                                <th>Date Received</th>
                                <th style="text-align:center">Action Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_inquiries)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted)">
                                        No recent inquiries recorded inside database.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_inquiries as $inq): ?>
                                    <?php 
                                        $badgeStyle = "status-pending";
                                        $lbl = strtolower($inq['status']);
                                        if ($lbl === 'reviewed' || $lbl === 'responded') {
                                            $badgeStyle = "status-reviewed";
                                        } elseif ($lbl === 'processed') {
                                            $badgeStyle = "status-processed";
                                        }
                                        $client_display = !empty($inq['client_name']) ? $inq['client_name'] : "Guest Client";
                                    ?>
                                    <tr>
                                        <td style="font-family:monospace; font-weight:700">#PCQ-<?php echo sprintf('%04d', $inq['id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($client_display); ?></strong></td>
                                        <td style="color:var(--primary-color); font-weight:600"><?php echo htmlspecialchars($inq['medicine_name']); ?></td>
                                        <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:0.9rem">
                                            <?php echo htmlspecialchars($inq['message']); ?>
                                        </td>
                                        <td style="font-size:0.85rem; color:var(--text-muted)"><?php echo date('M d, Y h:i A', strtotime($inq['created_at'])); ?></td>
                                        <td style="text-align:center">
                                            <span class="status-badge <?php echo $badgeStyle; ?>"><?php echo htmlspecialchars($inq['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Latest Wholesale Purchase Orders Card -->
            <div style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; box-shadow:var(--shadow-sm); margin-bottom:2rem">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:1rem">
                    <h3 style="font-size:1.25rem; font-weight:800; color:#10b981">Latest Wholesale Orders</h3>
                    <a href="inquiries.php#orders" class="btn btn-outline btn-sm" style="border-color:#10b981; color:#059669">Process All Orders &raquo;</a>
                </div>

                <div class="table-responsive" style="margin-bottom:0">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Client Name</th>
                                <th>Ordered Compound</th>
                                <th style="text-align:center">Quantity</th>
                                <th>Delivery Destination</th>
                                <th style="text-align:center">Order Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted)">
                                        No wholesale pharmaceutical orders catalogued in database.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $ord): ?>
                                    <?php 
                                        $badgeStyle = "status-pending";
                                        $lbl_ord = strtolower($ord['status']);
                                        if ($lbl_ord === 'confirmed' || $lbl_ord === 'processing') {
                                            $badgeStyle = "status-reviewed";
                                        } elseif ($lbl_ord === 'delivered') {
                                            $badgeStyle = "status-processed";
                                        }
                                        $client_display = !empty($ord['client_name']) ? $ord['client_name'] : "Guest Client";
                                    ?>
                                    <tr>
                                        <td style="font-family:monospace; font-weight:700">#PCO-<?php echo sprintf('%04d', $ord['id']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($client_display); ?></strong></td>
                                        <td style="color:#2563eb; font-weight:600"><?php echo htmlspecialchars($ord['medicine_name']); ?></td>
                                        <td style="text-align:center"><strong><?php echo intval($ord['quantity']); ?> Box</strong></td>
                                        <td style="max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:0.9rem">
                                            <?php echo htmlspecialchars($ord['address']); ?>
                                        </td>
                                        <td style="text-align:center">
                                            <span class="status-badge <?php echo $badgeStyle; ?>"><?php echo htmlspecialchars($ord['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Action controls -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem">
                <div style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; box-shadow:var(--shadow-sm)">
                    <h4 style="font-weight:700; margin-bottom:0.75rem; color:var(--primary-dark)">Shortcut Operations</h4>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem">Maintain distribution details, update expiration batches and availability flags.</p>
                    <div style="display:flex; gap:0.5rem">
                        <a href="add_medicine.php" class="btn btn-primary btn-sm">+ Add New Medicine</a>
                        <a href="medicines.php" class="btn btn-outline btn-sm">Grid Inventory</a>
                    </div>
                </div>

                <div style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; box-shadow:var(--shadow-sm)">
                    <h4 style="font-weight:700; margin-bottom:0.75rem; color:var(--primary-dark)">Bulk Import (CSV)</h4>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem">Incorporate external Excel sheet rows of pharmaceutical batches directly inside database.</p>
                    <a href="medicines.php#importSection" class="btn btn-success btn-sm">CSV BULK UPLOAD</a>
                </div>
            </div>

        </main>
    </div>

    <!-- Scripts Integration -->
    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
