<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Portal - Analytical Reports Dashboard
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Stats metrics
$stat_med = mysqli_query($conn, "SELECT COUNT(*) as count FROM medicines");
$total_meds = mysqli_fetch_assoc($stat_med)['count'];

$stat_inq = mysqli_query($conn, "SELECT COUNT(*) as count FROM inquiries");
$total_inqs = mysqli_fetch_assoc($stat_inq)['count'];

$stat_pending = mysqli_query($conn, "SELECT COUNT(*) as count FROM inquiries WHERE status = 'Pending'");
$pending_inqs = mysqli_fetch_assoc($stat_pending)['count'];

$stat_reviewed = mysqli_query($conn, "SELECT COUNT(*) as count FROM inquiries WHERE status = 'Reviewed'");
$reviewed_inqs = mysqli_fetch_assoc($stat_reviewed)['count'];

$stat_processed = mysqli_query($conn, "SELECT COUNT(*) as count FROM inquiries WHERE status = 'Processed'");
$processed_inqs = mysqli_fetch_assoc($stat_processed)['count'];

// MOST REQUESTED MEDICINES (Uses a direct raw MySQL query pairing GROUP BY medicines with inquiry aggregates)
$most_requested_query = "SELECT m.name, m.category, m.stock, COUNT(i.id) as inquiry_count 
                         FROM inquiries i 
                         JOIN medicines m ON i.medicine_id = m.id 
                         GROUP BY i.medicine_id 
                         ORDER BY inquiry_count DESC 
                         LIMIT 5";

$most_requested_result = mysqli_query($conn, $most_requested_query);
$popular_medicines = [];
if ($most_requested_result) {
    while ($row = mysqli_fetch_assoc($most_requested_result)) {
        $popular_medicines[] = $row;
    }
}

// Group distribution by category for simple insights
$category_query = "SELECT category, COUNT(*) as count, SUM(stock) as total_stock 
                   FROM medicines 
                   GROUP BY category";
$category_result = mysqli_query($conn, $category_query);
$category_distribution = [];
if ($category_result) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $category_distribution[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - PrimeCare Admin</title>
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
            <li class="admin-mobile-only"><a href="dashboard.php">Dashboard Home</a></li>
            <li class="admin-mobile-only"><a href="medicines.php">Manage Medicines</a></li>
            <li class="admin-mobile-only"><a href="orders.php">Purchase Orders</a></li>
            <li class="admin-mobile-only"><a href="inquiries.php">Client Inquiries</a></li>
            <li class="admin-mobile-only"><a href="reports.php" class="nav-link-active-yellow">System Reports</a></li>
            <li><a href="logout.php" style="color:#ef4444 !important;">Logout</a></li>
        </ul>
    </nav>

    <!-- Side Layout framework -->
    <div class="admin-layout">
        
        <!-- Left Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">Core Navigation</div>
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
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="orders.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        Purchase Orders
                    </a>
                </li>
                <li class="sidebar-item active">
                    <a href="reports.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12v6H3V3h6"/><path d="M12 17V9h4M16 17v-4h4M8 17v-6h4M3 3l20 20"/></svg>
                        System Reports
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Right Main Board -->
        <main class="admin-main">
            <h2 style="font-size:1.75rem; margin-bottom:1.5rem; font-weight:800; color:var(--text-color)">Analytical Data Reports</h2>

            <!-- Summary overview metrics -->
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-title">Catalog Volume</span>
                    <span class="stat-number"><?php echo $total_meds; ?></span>
                    <span style="font-size:0.75rem; color:var(--text-muted); margin-top:0.40rem">Total unique medicines</span>
                </div>
                <div class="stat-box">
                    <span class="stat-title">Total Inquiries Received</span>
                    <span class="stat-number"><?php echo $total_inqs; ?></span>
                    <span style="font-size:0.75rem; color:var(--text-muted); margin-top:0.40rem">Dispatched client forms</span>
                </div>
                <div class="stat-box">
                    <span class="stat-title">Resolution Ratio</span>
                    <?php 
                        $ratio = $total_inqs > 0 ? round(($processed_inqs / $total_inqs) * 100) : 0;
                    ?>
                    <span class="stat-number" style="color:var(--success-color)"><?php echo $ratio; ?>%</span>
                    <span style="font-size:0.75rem; color:var(--text-muted); margin-top:0.40rem"><?php echo $processed_inqs; ?> of <?php echo $total_inqs; ?> processed</span>
                </div>
            </div>

            <!-- Double Column layout for insights -->
            <div style="display:grid; grid-template-columns: 1fr; gap:1.5rem; margin-bottom:2rem">
                <?php if (true): ?>
                    <!-- Most Requested medicines card list -->
                    <div style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; box-shadow:var(--shadow-sm)">
                        <h3 style="font-weight:800; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem; margin-bottom:1.25rem; color:var(--primary-dark)">
                            Most Requested Medicines (Popular Compounds)
                        </h3>

                        <div class="table-responsive" style="margin-bottom:0; box-shadow:none; border:none">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Compound Formula Name</th>
                                        <th>Drug Form</th>
                                        <th>Current Stock Unit Available</th>
                                        <th style="width:160px; text-align:center">Total Dispatched Inquiries</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($popular_medicines)): ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted)">
                                                No inquiries dispatched to any medicine compound yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($popular_medicines as $pop): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($pop['name']); ?></strong></td>
                                                <td><span style="font-size: 0.8rem; text-transform:uppercase; font-weight:600; color:var(--primary-color)"><?php echo htmlspecialchars($pop['category']); ?></span></td>
                                                <td style="font-family:monospace; font-weight:700"><?php echo htmlspecialchars($pop['stock']); ?> units</td>
                                                <td style="text-align:center; font-family:monospace; font-weight:800; color:var(--primary-dark); font-size:1.15rem">
                                                    <?php echo htmlspecialchars($pop['inquiry_count']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem">
                <!-- Inquiry progression ratios -->
                <div style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; box-shadow:var(--shadow-sm)">
                    <h4 style="font-weight:800; margin-bottom:1rem; color:var(--text-color)">Inquiry Pipeline Progression</h4>
                    
                    <div style="display:flex; flex-direction:column; gap:0.75rem">
                        <div>
                            <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem">
                                <span>Pending Backlog</span>
                                <span style="font-weight:700"><?php echo $pending_inqs; ?> (<?php echo $total_inqs > 0 ? round(($pending_inqs/$total_inqs)*100) : 0; ?>%)</span>
                            </div>
                            <div style="height:8px; background-color:var(--border-color); border-radius:99px; overflow:hidden">
                                <div style="height:100%; width:<?php echo $total_inqs > 0 ? ($pending_inqs/$total_inqs)*100 : 0; ?>%; background-color:#f1c40f"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem">
                                <span>Reviewed By Staff</span>
                                <span style="font-weight:700"><?php echo $reviewed_inqs; ?> (<?php echo $total_inqs > 0 ? round(($reviewed_inqs/$total_inqs)*100) : 0; ?>%)</span>
                            </div>
                            <div style="height:8px; background-color:var(--border-color); border-radius:99px; overflow:hidden">
                                <div style="height:100%; width:<?php echo $total_inqs > 0 ? ($reviewed_inqs/$total_inqs)*100 : 0; ?>%; background-color:#3498db"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem">
                                <span>Finalized / Processed</span>
                                <span style="font-weight:700"><?php echo $processed_inqs; ?> (<?php echo $total_inqs > 0 ? round(($processed_inqs/$total_inqs)*100) : 0; ?>%)</span>
                            </div>
                            <div style="height:8px; background-color:var(--border-color); border-radius:99px; overflow:hidden">
                                <div style="height:100%; width:<?php echo $total_inqs > 0 ? ($processed_inqs/$total_inqs)*100 : 0; ?>%; background-color:#2ecc71"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Volume Breakdown by type -->
                <div style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.5rem; box-shadow:var(--shadow-sm)">
                    <h4 style="font-weight:800; margin-bottom:1rem; color:var(--text-color)">Stock Volumetry by Drug Form Class</h4>
                    
                    <div class="table-responsive" style="margin-bottom:0; box-shadow:none; border:none">
                        <table style="font-size:0.85rem">
                            <thead>
                                <tr>
                                    <th>Drug Class</th>
                                    <th>Formulas Count</th>
                                    <th>Aggregate Stock Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($category_distribution)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center">No records.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($category_distribution as $dist): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($dist['category']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($dist['count']); ?> compounds</td>
                                            <td style="font-weight:700"><?php echo htmlspecialchars($dist['total_stock']); ?> units</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
