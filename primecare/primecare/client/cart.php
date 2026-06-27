<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Client Shopping Cart
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Helper function to get medicine price
function getMedicinePrice($name) {
    switch (strtolower(trim($name))) {
        case 'paracetamol': return 120;
        case 'bioflu': return 180;
        case 'neozep': return 150;
        case 'ibuprofen': return 210;
        case 'amoxicillin': return 250;
        default: return 100;
    }
}

if ($action === 'add') {
    $med_id = intval($_GET['id']);
    $qty = isset($_GET['qty']) ? intval($_GET['qty']) : 10;
    
    // Check stock
    $res = mysqli_query($conn, "SELECT * FROM medicines WHERE id = $med_id");
    $added_successfully = false;
    $msg = "";
    if ($res && mysqli_num_rows($res) > 0) {
        $med = mysqli_fetch_assoc($res);
        if ($med['stock'] > 0) {
            // Cap quantity by stock
            if ($qty > $med['stock']) {
                $qty = $med['stock'];
            }
            // Add or update quantity
            if (isset($_SESSION['cart'][$med_id])) {
                $_SESSION['cart'][$med_id] += $qty;
                if ($_SESSION['cart'][$med_id] > $med['stock']) {
                    $_SESSION['cart'][$med_id] = $med['stock'];
                }
            } else {
                $_SESSION['cart'][$med_id] = $qty;
            }
            // Automatically make it selected
            if (!isset($_SESSION['selected_items'])) {
                $_SESSION['selected_items'] = [];
            }
            $_SESSION['selected_items'][] = $med_id;
            $_SESSION['selected_items'] = array_unique($_SESSION['selected_items']);
            $_SESSION['flash_success'] = "{$med['name']} added to wholesale cart successfully!";
            $added_successfully = true;
            $msg = "{$med['name']} added to wholesale cart successfully!";
        } else {
            $_SESSION['flash_error'] = "Medicine {$med['name']} is currently out of stock.";
            $msg = "Medicine {$med['name']} is currently out of stock.";
        }
    } else {
        $msg = "Invalid medicine specified.";
    }

    // Capture AJAX request and output JSON to allow non-interrupting browsing
    if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $added_successfully,
            'cart_count' => count($_SESSION['cart']),
            'message' => $msg
        ]);
        exit;
    }

    header("Location: cart.php");
    exit;
}

if ($action === 'update') {
    $med_id = intval($_POST['id']);
    $qty = intval($_POST['qty']);
    
    $res = mysqli_query($conn, "SELECT stock FROM medicines WHERE id = $med_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $med = mysqli_fetch_assoc($res);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$med_id]);
            if (isset($_SESSION['selected_items']) && ($key = array_search($med_id, $_SESSION['selected_items'])) !== false) {
                unset($_SESSION['selected_items'][$key]);
            }
        } else {
            if ($qty > $med['stock']) {
                $qty = $med['stock'];
            }
            $_SESSION['cart'][$med_id] = $qty;
        }
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'remove') {
    $med_id = intval($_GET['id']);
    unset($_SESSION['cart'][$med_id]);
    if (isset($_SESSION['selected_items']) && ($key = array_search($med_id, $_SESSION['selected_items'])) !== false) {
        unset($_SESSION['selected_items'][$key]);
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'remove_selected') {
    if (isset($_SESSION['selected_items']) && !empty($_SESSION['selected_items'])) {
        foreach ($_SESSION['selected_items'] as $med_id) {
            unset($_SESSION['cart'][$med_id]);
        }
        $_SESSION['selected_items'] = [];
        $_SESSION['flash_success'] = "Selected items removed from cart successfully.";
    } else {
        $_SESSION['flash_error'] = "No items selected to remove.";
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'toggle_select') {
    $med_id = intval($_GET['id']);
    if (!isset($_SESSION['selected_items'])) {
        $_SESSION['selected_items'] = array_keys($_SESSION['cart']);
    }
    
    if (($key = array_search($med_id, $_SESSION['selected_items'])) !== false) {
        unset($_SESSION['selected_items'][$key]);
    } else {
        $_SESSION['selected_items'][] = $med_id;
    }
    $_SESSION['selected_items'] = array_intersect(array_values($_SESSION['selected_items']), array_keys($_SESSION['cart']));
    
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'selected' => $_SESSION['selected_items']]);
        exit;
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'toggle_select_all') {
    $select_all = isset($_GET['select']) ? intval($_GET['select']) : 0;
    if ($select_all === 1) {
        $_SESSION['selected_items'] = array_keys($_SESSION['cart']);
    } else {
        $_SESSION['selected_items'] = [];
    }
    
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'selected' => $_SESSION['selected_items']]);
        exit;
    }
    header("Location: cart.php");
    exit;
}

// Fetch all medicines currently in cart
$cart_items = [];
if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $res = mysqli_query($conn, "SELECT * FROM medicines WHERE id IN ($ids)");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $row['quantity'] = $_SESSION['cart'][$row['id']];
            $row['price'] = (isset($row['price']) && floatval($row['price']) > 0) ? floatval($row['price']) : getMedicinePrice($row['name']);
            $row['row_total'] = $row['price'] * $row['quantity'];
            $cart_items[] = $row;
        }
    }
}

// Initialize selected_items with all keys in cart if not set
if (!isset($_SESSION['selected_items'])) {
    $_SESSION['selected_items'] = array_keys($_SESSION['cart']);
}
$_SESSION['selected_items'] = array_intersect($_SESSION['selected_items'], array_keys($_SESSION['cart']));

$selected_subtotal = 0;
$selected_qty = 0;
foreach ($cart_items as $item) {
    if (in_array($item['id'], $_SESSION['selected_items'])) {
        $selected_subtotal += $item['row_total'];
        $selected_qty += intval($item['quantity']);
    }
}

$allSelected = !empty($cart_items) && count($_SESSION['selected_items']) === count($cart_items);
$shipping_fee = ($selected_subtotal >= 1000 || $selected_qty >= 20 || $selected_subtotal == 0) ? 0 : 75;
$grand_total = $selected_subtotal + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wholesale Cart - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
</head>
<body>
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
                <a href="cart.php" title="Cart" class="nav-link-active-yellow" style="display: inline-flex; align-items: center; vertical-align: middle;">
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

    <main class="container">
        <div style="margin-bottom: 1.5rem">
            <a href="home.php" style="font-weight: 600;">&larr; Back to Catalog</a>
        </div>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>

        <h1 style="font-size:2rem; margin-bottom:1.5rem">Wholesale Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div style="text-align:center; padding:4rem 2rem; background:#fff; border:1px solid var(--border-color); border-radius:var(--radius-lg)">
                <h3 style="margin-bottom:0.5rem">Your cart is empty</h3>
                <p style="color:var(--text-muted); margin-bottom:1.5rem">Browse our high-quality pharmaceutical stock list and select wholesale bundles to start ordering.</p>
                <a href="home.php" class="btn btn-primary">Go to Catalog</a>
            </div>
        <?php else: ?>
            <!-- Select All Header -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:0.85rem 1.25rem; display:flex; align-items:center; justify-content:space-between; gap:0.75rem; margin-bottom:1rem; box-shadow:0 1px 2px rgba(0,0,0,0.02)">
                <div style="display:flex; align-items:center; gap:0.75rem">
                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" style="width: 20px; height: 20px; accent-color: #0055ff; cursor: pointer;" <?php echo $allSelected ? 'checked' : ''; ?>>
                    <label for="selectAllCheckbox" style="font-weight:700; color:#475569; font-size:0.95rem; cursor:pointer; user-select:none; display:flex; align-items:center; gap:0.55rem">
                        Select All (<?php echo count($cart_items); ?> items)
                    </label>
                </div>
                <div>
                    <?php if (!empty($_SESSION['selected_items'])): ?>
                        <a href="cart.php?action=remove_selected" class="btn btn-sm" style="background:#fef2f2; border:1px solid #fee2e2; color:var(--danger-color); font-weight:700; border-radius:8px; padding:0.4rem 0.85rem; text-decoration:none; display:inline-flex; align-items:center; gap:0.35rem; font-size:0.85rem; height:34px; box-sizing:border-box" onclick="return confirm('Are you sure you want to remove the <?php echo count($_SESSION['selected_items']); ?> selected items?'); font-weight: inherit;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            Remove Selected (<?php echo count($_SESSION['selected_items']); ?>)
                        </a>
                    <?php else: ?>
                        <button disabled class="btn btn-sm" style="background:#f8fafc; border:1px solid #e2e8f0; color:#94a3b8; font-weight:700; border-radius:8px; padding:0.4rem 0.85rem; font-size:0.85rem; height:34px; box-sizing:border-box; cursor:not-allowed; display:inline-flex; align-items:center; gap:0.35rem">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            Remove Selected
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cart-grid">
                <div style="display:grid; gap:1rem">
                    <?php foreach ($cart_items as $item): ?>
                        <?php $isChecked = in_array($item['id'], $_SESSION['selected_items']); ?>
                        <div class="cart-item-row" style="<?php echo !$isChecked ? 'opacity: 0.75;' : ''; ?>">
                            <!-- Checkbox Column -->
                            <div class="cart-item-checkbox-container" style="display:flex; align-items:center; flex-shrink:0">
                                <input type="checkbox" class="cart-item-checkbox" onchange="toggleItemSelect(<?php echo $item['id']; ?>, this)" style="width:20px; height:20px; accent-color:#0055ff; cursor:pointer" <?php echo $isChecked ? 'checked' : ''; ?>>
                            </div>
                            
                            <!-- Main Product Content Wrapper (Image + Details) -->
                            <div class="cart-item-main-content" style="display:flex; align-items:center; gap:1rem; flex:1">
                                <!-- Medicine Image Display -->
                                <div class="cart-item-image-container" style="width:70px; height:70px; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; overflow:hidden">
                                    <img src="<?php echo htmlspecialchars(getMedicineImageSrc($item['image'], '../uploads/medicines/')); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="max-width:100%; max-height:100%; object-fit:contain" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=150&auto=format&fit=crop&q=60';">
                                </div>
                                
                                <!-- Product Details -->
                                <div class="cart-item-details" style="flex:1">
                                    <h3 style="font-size:1.1rem; color:var(--primary-dark); margin:0 0 0.35rem 0; font-weight:700"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="cart-item-meta" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center; font-size:0.8rem">
                                        <span style="background:var(--primary-light); color:var(--primary-dark); padding:2px 8px; border-radius:6px; font-weight:700"><?php echo htmlspecialchars($item['category']); ?></span>
                                        <span style="color:var(--text-muted); font-weight:500">Pieces: <?php echo htmlspecialchars($item['pieces_per_box']); ?>/box</span>
                                        <span style="font-weight:700; color:#0055ff">₱<?php echo number_format($item['price'], 2); ?> / Box</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quantity Form Container -->
                            <div class="cart-item-form-container" style="flex-shrink:0">
                                <form action="cart.php?action=update" method="POST" class="cart-item-actions-form" style="display:flex; align-items:center; gap:0.4rem; margin:0">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <div style="display:flex; align-items:center; border:1px solid #cbd5e1; border-radius:8px; padding:0 0.4rem; background:#f8fafc; height:34px; box-sizing:border-box">
                                        <span style="font-size:0.75rem; color:#64748b; font-weight:600; margin-right:4px">Qty:</span>
                                        <input type="number" name="qty" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" style="width:50px; border:none; padding:0; text-align:center; font-weight:700; color:#1e293b; background:transparent; font-size:0.9rem; outline:none">
                                    </div>
                                    <button type="submit" class="btn btn-outline btn-sm" style="height:34px; border-radius:8px; padding:0 0.75rem; font-weight:700; background:#ffffff; border-color:#0055ff; color:#0055ff">Update</button>
                                </form>
                            </div>
                            
                            <!-- Item Subtotal Column -->
                            <div class="cart-item-subtotal" style="text-align:right; min-width:110px">
                                <span style="font-weight:750; font-size:1.15rem; color:#1e293b">₱<?php echo number_format($item['row_total'], 2); ?></span>
                            </div>
                            
                            <!-- Remove Item Column -->
                            <div class="cart-item-delete-btn-container">
                                <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" style="color:var(--danger-color); display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; background:#fef2f2; border:1px solid #fee2e2; transition:all 0.1s ease" title="Remove Item" onmouseover="this.style.background='#fee2e2'; this.style.transform='scale(1.05)';" onmouseout="this.style.background='#fef2f2'; this.style.transform='scale(1)';" onclick="return confirm('Remove <?php echo htmlspecialchars($item['name']); ?> from cart?');">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:1.75rem; display:grid; gap:1.1rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05)">
                    <h3 style="font-size:1.25rem; color:var(--primary-dark); border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem; margin-bottom:0.25rem; font-weight:800">Order Pricing Summary</h3>
                    
                    <div style="display:flex; justify-content:space-between; font-size:0.95rem">
                        <span style="color:var(--text-muted); font-weight:500">Subtotal:</span>
                        <span style="font-weight:700; color:#1e293b">₱<?php echo number_format($selected_subtotal, 2); ?></span>
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; font-size:0.95rem">
                        <span style="color:var(--text-muted); font-weight:500">Shipping Fee:</span>
                        <span style="font-weight:700; color:<?php echo $shipping_fee === 0 ? 'var(--success-color)' : '#1e293b'; ?>">
                            <?php echo ($selected_subtotal == 0) ? "₱0.00" : ($shipping_fee === 0 ? "FREE" : "₱" . number_format($shipping_fee, 2)); ?>
                        </span>
                    </div>
 
                    <?php if ($selected_subtotal > 0): ?>
                        <?php if ($shipping_fee > 0): ?>
                            <div style="font-size:0.78rem; color:#1e3a8a; background:#f0f7ff; padding:10px 14px; border-radius:8px; border:1px solid #d0e4ff; line-height:1.4">
                                💡 Purchase <strong>₱<?php echo number_format(max(0, 1000 - $selected_subtotal), 2); ?></strong> more OR buy a total of <strong><?php echo max(0, 20 - $selected_qty); ?></strong> more boxes for <strong>FREE Shipping</strong>!<br>
                                <span style="font-size:0.7rem; color:var(--text-muted); display:block; margin-top:4px">Standard Rates: ₱75.00 (Manila/Bulacan Zones) / ₱150.00 (Far Zones)</span>
                            </div>
                        <?php else: ?>
                            <div style="font-size:0.78rem; color:#065f46; background:#f0fdf4; padding:10px 14px; border-radius:8px; border:1px solid #bbf7d0; line-height:1.4">
                                ✨ Wholesale Bulk Promo: You've unlocked <strong>FREE Shipping</strong> for this purchase order!
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
 
                    <div style="display:flex; justify-content:space-between; font-size:1.25rem; font-weight:800; border-top:1px solid #f1f5f9; padding-top:1rem; margin-top:0.25rem">
                        <span>Grand Total:</span>
                        <span style="color:#0055ff">₱<?php echo number_format($grand_total, 2); ?></span>
                    </div>
 
                    <?php if ($selected_subtotal > 0): ?>
                        <a href="checkout.php" class="btn btn-primary" style="width:100%; text-align:center; padding:0.9rem; margin-top:0.5rem; border-radius:12px; font-weight:750; font-size:1rem; background-color:#0055ff; border-color:#0055ff">Proceed to Checkout (<?php echo $selected_qty; ?> Bxs) &rarr;</a>
                    <?php else: ?>
                        <button disabled class="btn btn-outline" style="width:100%; text-align:center; padding:0.9rem; margin-top:0.5rem; border-radius:12px; font-weight:750; font-size:1rem; cursor:not-allowed; border-color:#cbd5e1; color:#94a3b8; background-color:#f8fafc">Select Items to Checkout</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
    function toggleItemSelect(id, checkbox) {
        checkbox.disabled = true;
        fetch('cart.php?action=toggle_select&ajax=1&id=' + id)
            .then(response => response.json())
            .then(data => {
                window.location.reload();
            })
            .catch(err => {
                console.error(err);
                window.location.reload();
            });
    }

    function toggleSelectAll(checkbox) {
        checkbox.disabled = true;
        const select = checkbox.checked ? 1 : 0;
        fetch('cart.php?action=toggle_select_all&ajax=1&select=' + select)
            .then(response => response.json())
            .then(data => {
                window.location.reload();
            })
            .catch(err => {
                console.error(err);
                window.location.reload();
            });
    }
    </script>

    <footer class="footer">
        <p>&copy; 2026 PrimeCare Pharmaceutical Distributors. Managed System Portal.</p>
    </footer>
    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
