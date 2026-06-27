<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Client Checkout Page
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// Get selected item IDs and intersect with existing cart keys
$selected_ids = isset($_SESSION['selected_items']) ? $_SESSION['selected_items'] : [];
$selected_ids = array_intersect($selected_ids, array_keys($_SESSION['cart']));

if (empty($selected_ids)) {
    $_SESSION['flash_error'] = "Please select at least one item to proceed to Checkout.";
    header("Location: cart.php");
    exit;
}

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

// Fetch all selected medicines
$cart_items = [];
$subtotal = 0;
$ids = implode(',', $selected_ids);
$res = mysqli_query($conn, "SELECT * FROM medicines WHERE id IN ($ids)");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $row['price'] = (isset($row['price']) && floatval($row['price']) > 0) ? floatval($row['price']) : getMedicinePrice($row['name']);
        $row['row_total'] = $row['price'] * $row['quantity'];
        $subtotal += $row['row_total'];
        $cart_items[] = $row;
    }
}

$total_qty = 0;
foreach ($cart_items as $item) {
    $total_qty += intval($item['quantity']);
}

$shipping_fee = ($subtotal >= 1000 || $total_qty >= 20) ? 0 : 75;
$grand_total = $subtotal + $shipping_fee;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim(mysqli_real_escape_string($conn, $_POST['fullname']));
    $contact = trim(mysqli_real_escape_string($conn, $_POST['contact_number']));
    $notes = isset($_POST['notes']) ? trim(mysqli_real_escape_string($conn, $_POST['notes'])) : '';

    $province = isset($_POST['province']) ? trim(mysqli_real_escape_string($conn, $_POST['province'])) : '';
    $city = isset($_POST['city']) ? trim(mysqli_real_escape_string($conn, $_POST['city'])) : '';
    $barangay = isset($_POST['barangay']) ? trim(mysqli_real_escape_string($conn, $_POST['barangay'])) : '';
    $street = isset($_POST['street']) ? trim(mysqli_real_escape_string($conn, $_POST['street'])) : '';
    $zip_code = isset($_POST['zip_code']) ? trim(mysqli_real_escape_string($conn, $_POST['zip_code'])) : '';
    $gps_coord = isset($_POST['gps_coord']) ? trim(mysqli_real_escape_string($conn, $_POST['gps_coord'])) : '';

    if (empty($fullname) || empty($contact) || empty($province) || empty($city) || empty($barangay) || empty($street) || empty($zip_code)) {
        $error = "Recipient billing name, contact details, and complete address fields (Province, Municipality/City, Barangay, Street, ZIP Code) are required.";
    } else {
        // Build combined address string in the specified Street → Barangay → Municipality/City → Province format
        $address = "";
        if (!empty($gps_coord)) {
            $address .= $gps_coord . " → ";
        }
        $barangay_formatted = (stripos($barangay, 'barangay') === 0) ? $barangay : "Barangay " . $barangay;
        $address .= "$street → $barangay_formatted → $city → $province → $zip_code";

        // Recalculate shipping fee based on final POST address
        if ($subtotal >= 1000 || $total_qty >= 20) {
            $shipping_fee = 0;
        } else {
            $addr_lower = strtolower($address);
            $near_keywords = ['manila', 'bulacan', 'hagonoy', 'sta. cruz', 'sta cruz', 'mayhaligue', 'halang', 'san agustin', 'parong-parong', 'parong parong'];
            $is_near = false;
            foreach ($near_keywords as $keyword) {
                if (strpos($addr_lower, $keyword) !== false) {
                    $is_near = true;
                    break;
                }
            }
            $shipping_fee = $is_near ? 75 : 150;
        }
        $grand_total = $subtotal + $shipping_fee;

        // Place an order for each item in the cart!
        $db_success = true;
        foreach ($cart_items as $item) {
            $inserted_notes = $notes;
            if ($shipping_fee == 0) {
                $inserted_notes .= " [FREE Delivery Promo]";
            } else {
                $inserted_notes .= " [Shipping: ₱" . $shipping_fee . "]";
            }
            
            $med_id = $item['id'];
            $qty = $item['quantity'];
            
            // Subtract stock
            $new_stock = max(0, $item['stock'] - $qty);
            $update_stock = "UPDATE medicines SET stock = $new_stock WHERE id = $med_id";
            mysqli_query($conn, $update_stock);

            $insert_query = "INSERT INTO orders (user_id, fullname, address, contact_number, medicine_id, quantity, notes, status) 
                             VALUES ({$_SESSION['user_id']}, '$fullname', '$address', '$contact', $med_id, $qty, '$inserted_notes', 'Pending')";
            
            if (!mysqli_query($conn, $insert_query)) {
                $db_success = false;
                $error = "Encountered a database failure while processing order item: " . mysqli_error($conn);
                break;
            }
        }

        if ($db_success) {
            // ONLY remove the checked/ordered items from the cart!
            foreach ($selected_ids as $med_id) {
                unset($_SESSION['cart'][$med_id]);
                if (isset($_SESSION['selected_items']) && ($key = array_search($med_id, $_SESSION['selected_items'])) !== false) {
                    unset($_SESSION['selected_items'][$key]);
                }
            }
            $_SESSION['selected_items'] = array_values($_SESSION['selected_items']);
            $_SESSION['flash_success'] = "Purchase Orders placed successfully! Total Paid: ₱" . number_format($grand_total, 2) . " (Subtotal: ₱" . number_format($subtotal, 2) . ", Shipping: " . ($shipping_fee === 0 ? "FREE" : "₱" . $shipping_fee) . ")";
            header("Location: my_orders.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
        .combobox-wrapper {
            position: relative;
            width: 100%;
        }
        .combobox-dropdown {
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 6px;
            background-color: #ffffff;
            max-height: 200px;
            overflow-y: auto;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 9999;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            margin-top: 4px;
            display: none;
        }
        .combobox-item {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.15s ease;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
        }
        .combobox-item:last-child {
            border-bottom: none;
        }
        .combobox-item:hover, .combobox-item.active {
            background-color: #eff6ff;
            color: #2563eb;
            font-weight: 500;
        }
        .combobox-no-match {
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
            color: #64748b;
            font-style: italic;
            background-color: #f8fafc;
            text-align: left;
        }
    </style>
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

    <main class="container" style="max-width: 900px">
        <div style="margin-bottom: 1.5rem">
            <a href="cart.php" style="font-weight: 600;">&larr; Return to Shopping Cart</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <h1 style="font-size:2rem; margin-bottom:1.5rem">Checkout</h1>

        <div style="display:flex; flex-direction:column; gap:2rem;">
            <div style="background:#fff; border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem">
                <h3 style="font-size:1.25rem; color:var(--primary-dark); border-bottom:1px solid var(--border-color); padding-bottom:0.5rem; margin-bottom:1.25rem">Delivery Details</h3>
                
                <form action="checkout.php" method="POST" style="display:grid; gap:1rem">
                    <div class="form-group">
                        <label class="form-label" style="font-weight:700">Recipient Full Name</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($_SESSION['fullname']); ?>" required style="padding:0.6rem; border:1px solid var(--border-color); border-radius:4px; font-size:0.9rem; width:100%">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:700">Contact Number</label>
                        <input type="text" name="contact_number" placeholder="e.g. 0917-XXX-XXXX" required style="padding:0.6rem; border:1px solid var(--border-color); border-radius:4px; font-size:0.9rem; width:100%">
                    </div>

                    <div class="form-group" style="margin-bottom: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <label class="form-label" style="font-weight:700; margin-bottom: 0;">Delivery Address</label>
                            <button type="button" id="gps-locate-btn" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; font-size: 0.75rem; border-radius: 4px; border: 1px solid var(--primary-color); background: #f0fdf4; color: var(--primary-color); cursor: pointer; font-weight: 600; transition: all 0.2s;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                📍 Auto-Locate GPS (Mobile)
                            </button>
                        </div>
                        <input type="hidden" name="gps_coord" id="gpsCoordInput" value="">
                        <div id="gps-status" style="font-size: 0.75rem; color: #15803d; margin-top: 4px; display: none; font-weight: 500;"></div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0.75rem; text-align: left;">
                        <label class="form-label" style="font-weight:700">Province</label>
                        <input type="text" id="provinceInput" name="province" placeholder="e.g. Metro Manila" required autocomplete="off" style="padding:0.6rem; border:1px solid var(--border-color); border-radius:4px; font-size:0.9rem; width:100%; background-color:#fff;">
                    </div>

                    <div class="form-group" style="margin-bottom: 0.75rem; text-align: left;">
                        <label class="form-label" style="font-weight:700">Municipality/City</label>
                        <input type="text" id="cityInput" name="city" placeholder="e.g. Manila" required autocomplete="off" style="padding:0.6rem; border:1px solid var(--border-color); border-radius:4px; font-size:0.9rem; width:100%; background-color:#fff;">
                    </div>

                    <div class="form-group" style="margin-bottom: 0.75rem; text-align: left;">
                        <label class="form-label" style="font-weight:700">Barangay</label>
                        <input type="text" id="barangayInput" name="barangay" placeholder="e.g. Santa Cruz" required autocomplete="off" style="padding:0.6rem; border:1px solid var(--border-color); border-radius:4px; font-size:0.9rem; width:100%; background-color:#fff;">
                    </div>

                    <div class="form-group" style="margin-bottom: 0.75rem; text-align: left;">
                        <label class="form-label" style="font-weight:700">Street / Bldg / Floor / Zone</label>
                        <input type="text" id="streetInput" name="street" placeholder="Enter your Street / Bldg / Floor / Zone here" required style="padding:0.6rem; border:1px solid var(--border-color); border-radius:4px; font-size:0.9rem; width:100%">
                    </div>

                    <div class="form-group" style="margin-bottom: 0.75rem; text-align: left;">
                        <label class="form-label" style="font-weight:700">ZIP Code</label>
                        <input type="text" id="zipCodeInput" name="zip_code" placeholder="e.g. 1008" required autocomplete="off" style="padding:0.6rem; border:1px solid var(--border-color); border-radius:4px; font-size:0.9rem; width:100%; background-color:#fff;">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:500">Optional Message</label>
                        <input type="text" name="notes" placeholder="e.g. Deliver during business shift, keep in cold vaults" style="padding:0.6rem; border:1px solid var(--border-color); border-radius:4px; font-size:0.9rem; width:100%">
                    </div>

                    <button type="submit" class="btn btn-primary" style="background:#10b981; border-color:#10b981; padding:0.8rem; font-size:1rem; margin-top:0.5rem; border-radius:4px; color:#fff; font-weight:700">
                        Place Order &raquo;
                    </button>
                </form>
            </div>

            <div style="display:grid; gap:1.25rem">
                <div style="background:#fff; border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:1.5rem">
                    <h3 style="font-size:1.25rem; color:var(--primary-dark); border-bottom:1px solid var(--border-color); padding-bottom:0.5rem; margin-bottom:1rem">Order Summary</h3>
                    
                    <div style="display:grid; gap:0.75rem; margin-bottom:1.25rem">
                        <?php foreach ($cart_items as $item): ?>
                            <div style="display:flex; justify-content:space-between; font-size:0.9rem">
                                <span style="font-weight:500"><?php echo htmlspecialchars($item['name']); ?> <span style="font-size:0.75rem; color:var(--text-muted)">(&times;<?php echo $item['quantity']; ?> Bxs)</span></span>
                                <span style="font-weight:600">₱<?php echo number_format($item['row_total']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr style="border:0; border-top:1px solid var(--border-color); margin-bottom:1rem">

                    <div style="display:flex; justify-content:space-between; font-size:0.95rem; margin-bottom:0.35rem">
                        <span style="color:var(--text-muted)">Subtotal:</span>
                        <span style="font-weight:600">₱<?php echo number_format($subtotal); ?></span>
                    </div>

                    <div style="display:flex; justify-content:space-between; font-size:0.95rem; margin-bottom:0.75rem">
                        <span style="color:var(--text-muted)">Shipping Fee:</span>
                        <span id="checkoutShippingFee" style="font-weight:600; color:<?php echo $shipping_fee === 0 ? 'var(--success-color)' : 'inherit'; ?>">
                            <?php echo $shipping_fee === 0 ? "FREE" : "₱" . number_format($shipping_fee); ?>
                        </span>
                    </div>

                    <div style="display:flex; justify-content:space-between; font-size:1.15rem; font-weight:800; border-top:1px solid var(--border-color); padding-top:0.75rem">
                        <span>Grand Total:</span>
                        <span id="checkoutGrandTotal" style="color:var(--primary-dark)">₱<?php echo number_format($grand_total); ?></span>
                    </div>

                    <div id="shippingInfoBox" style="font-size:0.78rem; color:#1e3a8a; background:#eff6ff; padding:12px; border-radius:6px; border:1px solid #bfdbfe; margin-top:15px; line-height:1.4">
                        <strong style="color:#1d4ed8; display:block; margin-bottom:4px">📍 Dynamic Cargo Shipping Rates:</strong>
                        • <strong>Near Hubs (₱75.00)</strong>: Manila & Bulacan zones (Mayhaligue Manila or Hagonoy Bulacan).<br>
                        • <strong>Far Hubs (₱150.00)</strong>: All other delivery locations.<br>
                        • <strong>Free Bulk Promo</strong>: Orders with <strong>20+ Boxes</strong> or subtotals of <strong>₱1,000+</strong> qualify for <strong>FREE shipping</strong>!
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2026 PrimeCare Pharmaceutical Distributors. Managed System Portal.</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // DOM Elements for address input and shipping recalculation
            const provinceInput = document.getElementById("provinceInput");
            const cityInput = document.getElementById("cityInput");
            const barangayInput = document.getElementById("barangayInput");
            const streetInput = document.getElementById("streetInput");
            const zipCodeInput = document.getElementById("zipCodeInput");
            const gpsCoordInput = document.getElementById("gpsCoordInput");

            const shippingFeeSpan = document.getElementById("checkoutShippingFee");
            const grandTotalSpan = document.getElementById("checkoutGrandTotal");
            const gpsBtn = document.getElementById("gps-locate-btn");
            const gpsStatus = document.getElementById("gps-status");

            let gpsShippingFeeMultiplier = null;
            const subtotal = <?php echo floatval($subtotal); ?>;
            const totalQty = <?php echo intval($total_qty); ?>;

            // 3. Dynamic Shipping Calculator
            function updateShipping() {
                let shipping = 75;
                if (subtotal >= 1000 || totalQty >= 20) {
                    shipping = 0;
                } else if (gpsShippingFeeMultiplier !== null) {
                    shipping = gpsShippingFeeMultiplier;
                } else {
                    const provinceVal = (provinceInput?.value || "").toLowerCase().trim();
                    const cityVal = (cityInput?.value || "").toLowerCase().trim();
                    const barangayVal = (barangayInput?.value || "").toLowerCase().trim();
                    const streetVal = (streetInput?.value || "").toLowerCase().trim();
                    const zipVal = (zipCodeInput?.value || "").toLowerCase().trim();
                    const combinedText = `${streetVal} ${barangayVal} ${cityVal} ${provinceVal} ${zipVal}`;

                    if (combinedText.trim() === '') {
                        shipping = 75;
                    } else {
                        const nearKeywords = ['manila', 'bulacan', 'hagonoy', 'sta. cruz', 'sta cruz', 'mayhaligue', 'halang', 'san agustin', 'parong-parong', 'parong parong'];
                        let isNear = false;
                        for (let kw of nearKeywords) {
                            if (combinedText.includes(kw)) {
                                isNear = true;
                                break;
                            }
                        }
                        shipping = isNear ? 75 : 150;
                    }
                }

                if (shipping === 0) {
                    shippingFeeSpan.textContent = "FREE";
                    shippingFeeSpan.style.color = "var(--success-color)";
                } else {
                    shippingFeeSpan.textContent = "₱" + shipping.toFixed(2);
                    shippingFeeSpan.style.color = "inherit";
                }

                const grandTotal = subtotal + shipping;
                grandTotalSpan.textContent = "₱" + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            // Attach event listeners for typing/manual inputs to recalculate shipping dynamically
            if (provinceInput) provinceInput.addEventListener("input", updateShipping);
            if (cityInput) cityInput.addEventListener("input", updateShipping);
            if (barangayInput) barangayInput.addEventListener("input", updateShipping);
            if (streetInput) streetInput.addEventListener("input", updateShipping);
            if (zipCodeInput) zipCodeInput.addEventListener("input", updateShipping);

            // Initial trigger on page load
            updateShipping();

            // 6. Mobile GPS Locate API Handler
            if (gpsBtn && gpsStatus) {
                gpsBtn.addEventListener("click", function() {
                    if (!navigator.geolocation) {
                        gpsStatus.textContent = "⚠ Geolocation is not supported by your browser.";
                        gpsStatus.style.color = "#b91c1c";
                        gpsStatus.style.display = "block";
                        return;
                    }

                    gpsStatus.textContent = "⌛ Acquiring secure satellite lock...";
                    gpsStatus.style.color = "#1d4ed8";
                    gpsStatus.style.display = "block";
                    gpsBtn.disabled = true;

                    function acquireLocation(options) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                
                                // PrimeCare Central Warehouse coordinates (Santa Cruz, Manila)
                                const warehouseLat = 14.6152;
                                const warehouseLng = 120.9818;
                                
                                // Haversine formula to compute great-circle distance in kilometers
                                const R = 6371; // Earth's radius in km
                                const dLat = (lat - warehouseLat) * Math.PI / 180;
                                const dLng = (lng - warehouseLng) * Math.PI / 180;
                                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                                          Math.cos(warehouseLat * Math.PI / 180) * Math.cos(lat * Math.PI / 180) * 
                                          Math.sin(dLng/2) * Math.sin(dLng/2);
                                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                                const distance = R * c; // Distance in km

                                // Store GPS Coordinates in hidden field
                                const gpsText = `[GPS Coord: ${lat.toFixed(4)}, ${lng.toFixed(4)} | Dist: ${distance.toFixed(1)} km]`;
                                if (gpsCoordInput) {
                                    gpsCoordInput.value = gpsText;
                                }

                                // Apply Dynamic rates: under 15 km is Near Hub rate (₱75), otherwise Far Hub rate (₱150)
                                if (distance < 15) {
                                    gpsShippingFeeMultiplier = 75;
                                } else {
                                    gpsShippingFeeMultiplier = 150;
                                }

                                // Query free Nominatim API to get actual address from coordinates
                                gpsStatus.textContent = "⌛ Mapping coordinates to address...";

                                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
                                    headers: {
                                        'User-Agent': 'PrimeCare-Applet',
                                        'Accept-Language': 'en'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data && data.address) {
                                        const addr = data.address;
                                        
                                        // Extract components
                                        const province = addr.state || addr.region || addr.province || addr.county || "Metro Manila";
                                        const city = addr.city || addr.municipality || addr.town || addr.village || addr.city_district || "Manila";
                                        const barangay = addr.suburb || addr.neighbourhood || addr.village || addr.quarter || "Santa Cruz";
                                        const street = addr.road || addr.street || addr.suburb || "";
                                        const postcode = addr.postcode || "";

                                        if (provinceInput) provinceInput.value = province;
                                        if (cityInput) cityInput.value = city;
                                        if (barangayInput) barangayInput.value = barangay;
                                        
                                        let stText = street;
                                        if (addr.house_number) {
                                            stText = addr.house_number + " " + stText;
                                        }
                                        if (streetInput) {
                                            streetInput.value = stText || "";
                                        }
                                        
                                        if (zipCodeInput) {
                                            zipCodeInput.value = postcode || "1000";
                                        }
                                        
                                        const rateInfo = (distance < 15) ? "Near Hub ₱75 applied" : "Far Hub ₱150 applied";
                                        gpsStatus.innerHTML = `✓ Located! Distance to warehouse hub: <strong>${distance.toFixed(1)} km</strong> (${rateInfo}).<br>Resolved: <em>${stText ? stText + ', ' : ''}${barangay}, ${city}, ${province}</em>`;
                                    } else {
                                        useFallback();
                                    }
                                })
                                .catch(err => {
                                    console.error("Reverse geocoding fetch error:", err);
                                    useFallback();
                                })
                                .finally(() => {
                                    gpsStatus.style.color = "#15803d";
                                    gpsBtn.disabled = false;
                                    updateShipping();
                                });

                                function useFallback() {
                                    if (provinceInput) provinceInput.value = "Metro Manila";
                                    if (cityInput) cityInput.value = "Manila";
                                    if (barangayInput) barangayInput.value = "Santa Cruz";
                                    if (zipCodeInput) zipCodeInput.value = "1008";
                                    const rateInfo = (distance < 15) ? "Near Hub ₱75 applied" : "Far Hub ₱150 applied";
                                    gpsStatus.innerHTML = `✓ Located! Distance: <strong>${distance.toFixed(1)} km</strong> (${rateInfo}). (Using fallback defaults for Santa Cruz, Manila).`;
                                }
                            },
                            function(error) {
                                // If high accuracy failed/timed out, try fallback low accuracy (more resilient on weak GPS/cellular)
                                if (options.enableHighAccuracy) {
                                    console.warn("High accuracy GPS failed, retrying with standard location accuracy.");
                                    gpsStatus.textContent = "⌛ Retrying with standard location tracking...";
                                    acquireLocation({ enableHighAccuracy: false, timeout: 12000, maximumAge: 60000 });
                                } else {
                                    gpsBtn.disabled = false;
                                    gpsStatus.style.color = "#b91c1c";
                                    switch(error.code) {
                                        case error.PERMISSION_DENIED:
                                            gpsStatus.textContent = "⚠ GPS lock denied by user. Please enter address details manually.";
                                            break;
                                        case error.POSITION_UNAVAILABLE:
                                            gpsStatus.textContent = "⚠ Location signal unavailable. Please try again.";
                                            break;
                                        case error.TIMEOUT:
                                            gpsStatus.textContent = "⚠ Location request timed out.";
                                            break;
                                        default:
                                            gpsStatus.textContent = "⚠ Location acquisition failed.";
                                    }
                                }
                            },
                            options
                        );
                    }

                    // Start the GPS locator with high accuracy, zero age, and reasonable timeout
                    acquireLocation({ enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
                });
            }
        });
    </script>
    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
