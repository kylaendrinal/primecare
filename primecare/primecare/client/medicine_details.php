<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Medicine Details View & Dual Form Transaction Handler
 */
require_once dirname(__DIR__) . '/database/config.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$medicine_id = intval($_GET['id']);

// Load medicine details from database
$query = "SELECT * FROM medicines WHERE id = $medicine_id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Error: Medicine batch ID not found inside database inventory.");
}

$medicine = mysqli_fetch_assoc($result);

$error = '';
$success = '';

// Handle form submission if user is logged in
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $error = "You must be signed in to execute transactions.";
    } else {
        $user_id = $_SESSION['user_id'];
        $action = isset($_POST['action']) ? $_POST['action'] : 'submit_inquiry';

        if ($action === 'place_order') {
            $fullname = trim(mysqli_real_escape_string($conn, $_POST['fullname']));
            $address = trim(mysqli_real_escape_string($conn, $_POST['address']));
            $contact = trim(mysqli_real_escape_string($conn, $_POST['contact_number']));
            $quantity = intval($_POST['quantity']);
            $notes = isset($_POST['notes']) ? trim(mysqli_real_escape_string($conn, $_POST['notes'])) : '';

            if (empty($fullname) || empty($address) || empty($contact) || $quantity <= 0) {
                $error = "All required order fields must be correctly filled.";
            } else {
                // Subtract stock
                $new_stock = max(0, $medicine['stock'] - $quantity);
                mysqli_query($conn, "UPDATE medicines SET stock = $new_stock WHERE id = $medicine_id");

                // Insert order
                $insert_query = "INSERT INTO orders (user_id, fullname, address, contact_number, medicine_id, quantity, notes, status) 
                                 VALUES ($user_id, '$fullname', '$address', '$contact', $medicine_id, $quantity, '$notes', 'Pending')";
                if (mysqli_query($conn, $insert_query)) {
                    $_SESSION['flash_success'] = "Purchase Order successfully recorded! Track cargo dispatch in your orders dashboard.";
                    header("Location: my_orders.php");
                    exit;
                } else {
                    $error = "Could not record order. Database failure: " . mysqli_error($conn);
                }
            }
        } else {
            // submit_inquiry
            $message = trim(mysqli_real_escape_string($conn, $_POST['message']));

            if (empty($message)) {
                $error = "Inquiry message cannot be left empty.";
            } else {
                // Check if user already has a continuous inquiry thread
                $check_thread_res = mysqli_query($conn, "SELECT id FROM inquiries WHERE user_id = $user_id LIMIT 1");
                if ($check_thread_res && mysqli_num_rows($check_thread_res) > 0) {
                    $thread_row = mysqli_fetch_assoc($check_thread_res);
                    $inquiry_id = intval($thread_row['id']);
                    // Update main thread for backward compatibility / status update
                    mysqli_query($conn, "UPDATE inquiries SET medicine_id = $medicine_id, message = '$message', status = 'Pending', created_at = CURRENT_TIMESTAMP WHERE id = $inquiry_id");
                } else {
                    // Create new main thread
                    mysqli_query($conn, "INSERT INTO inquiries (user_id, medicine_id, message, status) VALUES ($user_id, $medicine_id, '$message', 'Pending')");
                    $inquiry_id = mysqli_insert_id($conn);
                }

                if ($inquiry_id > 0) {
                    // Insert the message with medicine_id attachment
                    mysqli_query($conn, "INSERT INTO inquiry_messages (inquiry_id, sender_id, sender_role, message, medicine_id) VALUES ($inquiry_id, $user_id, 'client', '$message', $medicine_id)");
                    $_SESSION['flash_success'] = "Inquiry successfully sent! Our staff will review and update status shortly.";
                    header("Location: my_inquiries.php");
                    exit;
                } else {
                    $error = "Could not record inquiry. Database failure: " . mysqli_error($conn);
                }
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
    <title><?php echo htmlspecialchars($medicine['name']); ?> - PrimeCare Details</title>
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
            <?php if (isset($_SESSION['user_id'])): ?>
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
            <?php else: ?>
                <li><a href="../index.php">Home</a></li>
                <li><a href="login.php" class="nav-link-active-yellow">Login</a></li>
                <li><a href="signup.php" style="color:#2563eb !important; background:white; padding:4px 12px; border-radius:99px; font-weight:700">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Main Container -->
    <main class="container">
        
        <div style="margin-bottom: 1.5rem">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="home.php" style="font-weight: 600;">&larr; Back to Dashboard Catalog</a>
            <?php else: ?>
                <a href="../index.php" style="font-weight: 600;">&larr; Back to Home Catalog</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Details Grid layout -->
        <div class="details-grid">
            
            <!-- Details LHS Image Card -->
            <div class="details-card">
                <?php 
                    $imgSrc = getMedicineImageSrc($medicine['image'], '../uploads/medicines/');
                ?>
                <div class="details-image-area">
                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($medicine['name']); ?>" onerror="this.onerror=null; this.src='../uploads/medicines/default.png';">
                </div>
                <div style="padding:1.25rem; border-top:1px solid var(--border-color); background-color:#fff">
                    <div style="display:flex; justify-content:space-between; align-items:center">
                        <span class="status-badge" style="background-color: var(--primary-light); color: var(--primary-dark); font-weight:700">Form: <?php echo htmlspecialchars($medicine['category']); ?></span>
                        <span class="status-badge <?php echo ($medicine['stock'] > 0) ? 'status-processed' : 'status-pending'; ?>">
                            <?php echo ($medicine['stock'] > 0) ? 'Stock Available (' . $medicine['stock'] . ')' : 'Out of Stock'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Details RHS Metadata & Form Inquiry Area -->
            <div class="details-card" style="padding: 2.25rem; background-color:#fff">
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="display:flex; gap:0.5rem; justify-content:space-between; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.75rem">
                        <button id="tabOrd" class="btn btn-primary btn-sm" style="flex:1" onclick="switchFormType('order')">🛒 Add to Cart</button>
                        <button id="tabInq" class="btn btn-outline btn-sm" style="flex:1; background:white; border-color:#cbd5e1" onclick="switchFormType('inquiry')">💬 Stock Inquiry</button>
                    </div>

                    <!-- ADD TO CART FORM -->
                    <div id="orderFormBlock">
                        <h1 class="details-title" style="font-size:1.6rem; margin-bottom:0.5rem"><?php echo htmlspecialchars($medicine['brand'] ?: $medicine['name']); ?></h1>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.5rem"><?php echo htmlspecialchars($medicine['description']); ?></p>

                        <div style="margin: 1rem 0 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display:flex; flex-direction:column; gap:0.5rem; font-size:0.9rem">
                            <div><strong>Category / Form:</strong> <span style="font-weight:600"><?php echo htmlspecialchars($medicine['category']); ?></span></div>
                            <div><strong>Pack Form:</strong> <span style="font-weight:600"><?php echo htmlspecialchars($medicine['pack']); ?></span></div>
                            <div><strong>Packaging Size:</strong> <span style="font-weight:600"><?php echo htmlspecialchars($medicine['size']); ?></span></div>
                            <div><strong>Current Lot Stock:</strong> <span style="font-weight:700" class="text-success"><?php echo htmlspecialchars($medicine['stock']); ?> units available</span></div>
                            <div><strong>Lot Batch Expiry:</strong> <span style="color:var(--danger-color); font-weight:600"><?php echo date('Y-m-d', strtotime($medicine['expiry_date'])); ?></span></div>
                            <div><strong>Wholesale Unit Price:</strong> <span style="color:#0055ff; font-weight:750">₱<?php echo number_format($medicine['price'], 2); ?></span></div>
                        </div>

                        <form action="cart.php" method="GET">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id" value="<?php echo $medicine_id; ?>">
                            
                            <div class="form-group" style="margin-bottom:1.5rem">
                                <label class="form-label" style="font-weight:700; margin-bottom:0.5rem; display:block">Select Wholesale Quantity (Boxes)</label>
                                <div style="display:flex; align-items:center; gap:0.75rem">
                                    <input type="number" id="orderQty" name="qty" min="1" max="<?php echo htmlspecialchars($medicine['stock']); ?>" value="1" required style="padding:0.65rem; border-radius:12px; border:1px solid #cbd5e1; font-weight:750; width:100px; font-size:1.1rem; text-align:center" oninput="calculateDetailsOrderPricing()">
                                    <span style="color:#64748b; font-size:0.85rem">Boxes of <?php echo htmlspecialchars($medicine['pieces_per_box']); ?> pcs</span>
                                </div>
                            </div>

                            <div id="inlinePricingBreakdown" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; margin-bottom:1.5rem"></div>

                            <?php if ($medicine['stock'] > 0): ?>
                                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.85rem; background-color:#0055ff; border-color:#0055ff; border-radius:12px; font-size:1rem; font-weight:750; display:inline-flex; align-items:center; justify-content:center; gap:0.5rem">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                    </svg>
                                    Add to Wholesale Cart
                                </button>
                            <?php else: ?>
                                <button type="button" disabled class="btn btn-outline" style="width:100%; padding:0.85rem; border-radius:12px; font-weight:750; cursor:not-allowed">
                                    Currently Out of Stock
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- INQUIRY FORM -->
                    <div id="inquiryFormBlock" style="display:none">
                        <h1 class="details-title" style="font-size:1.6rem; margin-bottom:0.5rem">Stock Inquiry</h1>
                        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.5rem">Send inquiry message.</p>
                        
                        <form action="medicine_details.php?id=<?php echo $medicine_id; ?>" method="POST">
                            <input type="hidden" name="action" value="submit_inquiry">
                            <div class="form-group" style="margin-bottom:1rem">
                                <label class="form-label" style="font-weight:700">Inquiry Message / Request Details</label>
                                <textarea name="message" rows="5" placeholder="Please specify details regarding batch numbers, larger procurement discounts, logistics lead times, or bulk terms..." required style="border-radius:12px; padding:0.8rem"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.85rem; border-radius:12px; font-weight:750">Send Inquiry</button>
                        </form>
                    </div>

                <?php else: ?>
                    <h1 class="details-title"><?php echo htmlspecialchars($medicine['brand'] ?: $medicine['name']); ?></h1>
                    <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.5rem"><?php echo htmlspecialchars($medicine['description']); ?></p>
                    
                    <div style="margin: 1.5rem 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display:flex; flex-direction:column; gap:0.5rem; font-size:0.9rem">
                        <div><strong>Brand Name:</strong> <span style="font-weight:750; color:var(--primary-dark)"><?php echo htmlspecialchars($medicine['brand']); ?></span></div>
                        <div><strong>Category:</strong> <span style="font-weight:600"><?php echo htmlspecialchars($medicine['category']); ?></span></div>
                        <div><strong>Pack Form:</strong> <span style="font-weight:600"><?php echo htmlspecialchars($medicine['pack']); ?></span></div>
                        <div><strong>Packaging Size:</strong> <span style="font-weight:600"><?php echo htmlspecialchars($medicine['size']); ?></span></div>
                        <div><strong>Current Lot Stock:</strong> <span><?php echo htmlspecialchars($medicine['stock']); ?> units available</span></div>
                        <div><strong>Lot Batch Expiry:</strong> <span style="color:var(--danger-color); font-weight:600"><?php echo date('d/m/Y', strtotime($medicine['expiry_date'])); ?></span></div>
                        <div><strong>Wholesale Unit Price:</strong> <span style="color:#0055ff; font-weight:750">₱<?php echo number_format($medicine['price'], 2); ?></span></div>
                    </div>
                    
                    <div style="padding: 2rem 1.25rem; border:2px dashed var(--border-color); border-radius:var(--radius-md); background-color: var(--primary-light); text-align:center">
                        <p style="color:var(--primary-dark); font-weight:700; margin-bottom:1rem">Registered client authorization required to submit inquiries or place wholesale purchase orders.</p>
                        <a href="login.php" class="btn btn-primary btn-sm">Sign In Client Account</a>
                        <a href="signup.php" class="btn btn-outline btn-sm">Register Profile</a>
                    </div>
                <?php endif; ?>

            </div>
            
        </div>

    </main>

    <!-- Footer Area -->
    <footer class="footer">
        <p>&copy; 2026 PrimeCare Pharmaceutical Distributors. Managed System Portal.</p>
    </footer>

    <!-- Scripts Integration -->
    <script>
        const unitPrice = <?php echo floatval($medicine['price']); ?>;

        function calculateDetailsOrderPricing() {
            const qtyInput = document.getElementById("orderQty");
            const breakdown = document.getElementById("inlinePricingBreakdown");
            if (!qtyInput || !breakdown) return;

            const qty = parseInt(qtyInput.value) || 0;
            const subtotal = qty * unitPrice;
            const isEligible = (subtotal >= 1000 || qty >= 20);
            
            breakdown.innerHTML = `
                <div style="display:grid; gap:0.4rem">
                    <div style="display:flex; justify-content:space-between">
                        <span style="color:#475569">Wholesale Subtotal (${qty} Bxs &times; ₱${unitPrice}):</span>
                        <strong style="color:var(--primary-dark)">₱${subtotal.toLocaleString()}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items: center;">
                        <span style="color:#475569">Estimated Shipping:</span>
                        <strong style="color:${isEligible ? '#10b981' : '#1e3a8a'}">${isEligible ? 'FREE PROMO' : '₱75 (Near) / ₱150 (Far)'}</strong>
                    </div>
                    <div style="font-size:0.72rem; color:#475569; padding-top:2px; border-top:1.5px dashed #e2e8f0; margin-top:2px; line-height: 1.3">
                        ${isEligible 
                            ? '✨ <strong>Wholesale Bulk Promo Qualified!</strong> Free shipping is unlocked.' 
                            : `💡 Add <strong>₱${Math.max(0, 1000 - subtotal).toLocaleString()}</strong> more value or buy <strong>${Math.max(0, 20 - qty)}</strong> more box(es) to get <strong>FREE shipping</strong>!`
                        }
                    </div>
                    <div style="display:flex; justify-content:space-between; border-top:1px solid #cbd5e1; margin-top:0.5rem; padding-top:0.5rem; font-weight:800; font-size:0.95rem">
                        <span>Grand Total Estimate:</span>
                        <span style="color:#1e3a8a">${isEligible ? `₱${subtotal.toLocaleString()}` : `₱${(subtotal + 75).toLocaleString()} - ₱${(subtotal + 150).toLocaleString()}`}</span>
                    </div>
                </div>
            `;
        }

        // Run immediately if logged in
        document.addEventListener("DOMContentLoaded", function() {
            if (document.getElementById("orderQty")) {
                calculateDetailsOrderPricing();
            }
        });

        function switchFormType(type) {
            const btnInq = document.getElementById("tabInq");
            const btnOrd = document.getElementById("tabOrd");
            const formInq = document.getElementById("inquiryFormBlock");
            const formOrd = document.getElementById("orderFormBlock");

            if (type === 'inquiry') {
                btnInq.className = 'btn btn-primary btn-sm';
                btnInq.style.background = '#0055ff';
                btnInq.style.borderColor = '#0055ff';
                btnInq.style.color = '#ffffff';

                btnOrd.className = 'btn btn-outline btn-sm';
                btnOrd.style.background = 'white';
                btnOrd.style.borderColor = '#cbd5e1';
                btnOrd.style.color = '#475569';

                formInq.style.display = 'block';
                formOrd.style.display = 'none';
            } else {
                btnOrd.className = 'btn btn-primary btn-sm';
                btnOrd.style.background = '#0055ff';
                btnOrd.style.borderColor = '#0055ff';
                btnOrd.style.color = '#ffffff';

                btnInq.className = 'btn btn-outline btn-sm';
                btnInq.style.background = 'white';
                btnInq.style.borderColor = '#cbd5e1';
                btnInq.style.color = '#475569';

                formInq.style.display = 'none';
                formOrd.style.display = 'block';
            }
        }
    </script>
    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
