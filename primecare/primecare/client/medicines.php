<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Client Portal - Catalog list
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

// Read medicines database
$result = mysqli_query($conn, "SELECT * FROM medicines");
$medicines = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $medicines[] = $row;
    }
}

// Extract distinct categories dynamically for the dropdown
$categories = ['Tablet', 'Capsule', 'Suspension', 'Ampule', 'Nebule'];
foreach ($medicines as $med) {
    $cat = trim($med['category']);
    if ($cat !== '') {
        $norm_cat = ucfirst(strtolower($cat));
        if (!in_array($norm_cat, $categories)) {
            $categories[] = $norm_cat;
        }
    }
}
sort($categories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Medicine - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css?v=2.5">
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
            <li><a href="home.php" class="nav-link-active-yellow">Available Medicine</a></li>
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

    <!-- Main Content -->
    <main class="container">
        <div style="margin-bottom:1.5rem">
            <a href="home.php" style="font-weight: 600;display:inline-flex;align-items:center;gap:0.25rem">&larr; Back to Dashboard Home</a>
        </div>
        
        <h2 style="font-size:1.75rem;margin-bottom:1.5rem;font-weight:800;color:var(--primary-dark)">Comprehensive Medicines Catalogue</h2>

        <!-- Search catalogue filter -->
        <section class="search-filter-bar">
            <div class="form-group-flex" style="display: flex; gap: 0.5rem;">
                <input type="text" id="searchMed" placeholder="Filter medicines live by name, category, or chemical identifier..." style="flex: 1;">
                <button type="button" id="medSearchBtn" style="background: var(--primary-color, #0055ff); color: white; border: none; border-radius: var(--radius-md, 12px); padding: 0.625rem 1.25rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; transition: filter 0.2s ease; font-size: 0.9rem;" onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    🔍 Search
                </button>
            </div>
            <div class="form-group-flex" style="max-width:250px">
                <select id="filterCategory">
                    <option value="all">All Drug Forms</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>

        <!-- Catalog List -->
        <div class="medicines-grid">
            <?php if (empty($medicines)): ?>
                <div style="grid-column: 1/-1;text-align:center;padding:3rem 0;color:var(--text-muted)">
                    <h3>No pharmaceutical records found.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($medicines as $med): ?>
                    <?php 
                        $statusClass = ($med['stock'] > 0) ? 'badge-available' : 'badge-outofstock';
                        $statusText = ($med['stock'] > 0) ? 'Available (' . $med['stock'] . ')' : 'Out of Stock';
                        
                        $imgSrc = getMedicineImageSrc($med['image'], '../uploads/medicines/');
                    ?>
                    <div class="ppd-product-card" id="med_<?php echo $med['id']; ?>" 
                         data-name="<?php echo htmlspecialchars(strtolower($med['name'])); ?>"
                         data-brand="<?php echo htmlspecialchars(strtolower($med['brand'])); ?>"
                         data-category="<?php echo htmlspecialchars(strtolower($med['category'])); ?>"
                         data-description="<?php echo htmlspecialchars(strtolower($med['description'])); ?>"
                         style="cursor: default;">
                        <div class="ppd-product-img-holder" onclick="window.location.href='medicine_details.php?id=<?php echo $med['id']; ?>'" style="cursor: pointer;">
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($med['brand'] ? $med['brand'] : $med['name']); ?>" onerror="this.onerror=null; this.src='../uploads/medicines/default.png';">
                        </div>
                        <div class="ppd-product-info">
                            <div>
                                <div class="ppd-product-title-row" onclick="window.location.href='medicine_details.php?id=<?php echo $med['id']; ?>'" style="cursor: pointer;">
                                    <h3 class="med-title" style="font-size:0.95rem; font-weight:800; color:var(--primary-dark); margin:0; line-height:1.2;"><?php echo htmlspecialchars($med['brand'] ? $med['brand'] : $med['name']); ?></h3>
                                    <span class="ppd-product-form-span" style="font-size:0.65rem; padding:0.15rem 0.5rem;"><?php echo htmlspecialchars(ucfirst(strtolower($med['category']))); ?></span>
                                </div>
                                <p class="ppd-product-desc" style="margin-bottom:0.5rem; font-size:0.75rem; line-height:1.3; height: 2.6em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?php echo htmlspecialchars($med['description']); ?></p>
                                <div style="font-size:0.78rem; color:#475569; margin-bottom:0.5rem; border-top:1px dashed #e2e8f0; padding-top:0.4rem">
                                    <span style="color:#0055ff; font-weight:800; font-size:0.9rem;">Price: ₱<?php echo number_format($med['price'], 2); ?></span>
                                </div>
                            </div>
                            <div class="ppd-product-action-row" style="display:flex; align-items:center; gap:0.4rem; width:100%; border-top:1px solid #f1f5f9; padding-top:0.6rem; margin-top:auto">
                                <a href="medicine_details.php?id=<?php echo $med['id']; ?>" class="btn btn-outline btn-sm" style="flex:1; text-align:center; border-color:#001270; color:#001270; border-radius:8px; font-weight:750; font-size:0.8rem; background:#ffffff; height:32px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none" title="View Details">Details</a>
                                <?php if ($med['stock'] > 0): ?>
                                    <form action="cart.php" method="get" style="display:flex; margin:0">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="id" value="<?php echo $med['id']; ?>">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" class="btn btn-sm" style="background:#0055ff; color:#ffffff; border:none; border-radius:8px; width:44px; height:32px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer" title="Add to Cart">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="9" cy="21" r="1"></circle>
                                                <circle cx="20" cy="21" r="1"></circle>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button disabled class="btn btn-sm" style="border-color:#cbd5e1; color:#94a3b8; border-radius:8px; width:44px; height:32px; display:inline-flex; align-items:center; justify-content:center; cursor:not-allowed; background:#f8fafc" title="Out of Stock">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="9" cy="21" r="1"></circle>
                                            <circle cx="20" cy="21" r="1"></circle>
                                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            <line x1="1" y1="1" x2="23" y2="23" stroke="#ef4444" stroke-width="2"></line>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
