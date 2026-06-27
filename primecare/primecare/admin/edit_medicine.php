<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Portal - Edit & Update existing medicine item details
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session verification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: medicines.php");
    exit;
}

$id = intval($_GET['id']);
$error = '';
$success = '';

// Load existing medicine record
$query = "SELECT * FROM medicines WHERE id = $id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Error: Medicine compound ID not found inside database registry.");
}

$medicine = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim(mysqli_real_escape_string($conn, $_POST['category']));
    $pack = trim(mysqli_real_escape_string($conn, $_POST['pack']));
    $size = trim(mysqli_real_escape_string($conn, $_POST['size']));
    $description = trim(mysqli_real_escape_string($conn, $_POST['description']));
    $brand = trim(mysqli_real_escape_string($conn, $_POST['brand']));
    $price = floatval($_POST['price']);
    $expiry_date = trim(mysqli_real_escape_string($conn, $_POST['expiry_date']));
    $stock = intval($_POST['stock']);
    $availability = trim(mysqli_real_escape_string($conn, $_POST['availability']));
    $image_filename = $medicine['image']; // Default to old image

    // Set fallback name column
    $name = trim(mysqli_real_escape_string($conn, $brand ?: $description));

    // Handle new image upload is specified
    if (isset($_FILES['med_image']) && $_FILES['med_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['med_image']['tmp_name'];
        $fileName = $_FILES['med_image']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $name)) . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = '../uploads/medicines/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_filename = $newFileName;
            } else {
                $error = "Error transferring file.";
            }
        } else {
            $error = "Supporting file formats only (JPG, PNG).";
        }
    }

    if (empty($category) || empty($pack) || empty($size) || empty($description) || empty($brand) || empty($expiry_date)) {
        $error = "Category, Pack, Size, Description, Brand, and Expiration Date must be specified.";
    } elseif (empty($error)) {
        // SQL Update
        $update_query = "UPDATE medicines 
                         SET name = '$name', category = '$category', pack = '$pack', size = '$size', description = '$description', brand = '$brand', price = $price, stock = $stock, availability = '$availability', expiry_date = '$expiry_date', image = '$image_filename' 
                         WHERE id = $id";

        if (mysqli_query($conn, $update_query)) {
            $success = "Successfully updated details of compound '$brand'!";
            
            // Reload updated entity
            $re_result = mysqli_query($conn, "SELECT * FROM medicines WHERE id = $id");
            $medicine = mysqli_fetch_assoc($re_result);
        } else {
            $error = "Failed to update database record. " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medicine - PrimeCare Admin</title>
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
            <li class="admin-mobile-only"><a href="medicines.php" class="nav-link-active-yellow">Manage Medicines</a></li>
            <li class="admin-mobile-only"><a href="orders.php">Purchase Orders</a></li>
            <li class="admin-mobile-only"><a href="inquiries.php">Client Inquiries</a></li>
            <li class="admin-mobile-only"><a href="reports.php">System Reports</a></li>
            <li><a href="logout.php" style="color:#ef4444 !important;">Logout</a></li>
        </ul>
    </nav>

    <!-- Side layout frame -->
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
                <li class="sidebar-item active">
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
                <li class="sidebar-item">
                    <a href="reports.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12v6H3V3h6"/><path d="M12 17V9h4M16 17v-4h4M8 17v-6h4M3 3l20 20"/></svg>
                        System Reports
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Form Area -->
        <main class="admin-main">
            <div style="margin-bottom:1.5rem">
                <a href="medicines.php" style="font-weight: 600;">&larr; Back to Catalog Grid</a>
            </div>

            <div class="details-card" style="padding: 2.5rem; background-color:#fff; max-width:800px; margin: 0 auto">
                <h2 style="font-size:1.5rem; margin-bottom:0.5rem; color:var(--primary-dark)">Modify Medicine Batch Details</h2>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem">Edit name properties, expiration timelines or availability tags. Uploading a new image will replace the packaging photo.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form action="edit_medicine.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Brand Name</label>
                            <input type="text" name="brand" placeholder="e.g., SAPHRIVAX-400" required style="width:100%" value="<?php echo htmlspecialchars($medicine['brand']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" required style="width:100%">
                                <option value="" disabled>Select Category</option>
                                <option value="Tablet" <?php echo (strtolower($medicine['category']) === 'tablet') ? 'selected' : ''; ?>>Tablet</option>
                                <option value="Capsule" <?php echo (strtolower($medicine['category']) === 'capsule') ? 'selected' : ''; ?>>Capsule</option>
                                <option value="Suspension" <?php echo (strtolower($medicine['category']) === 'suspension') ? 'selected' : ''; ?>>Suspension</option>
                                <option value="Ampule" <?php echo (strtolower($medicine['category']) === 'ampule') ? 'selected' : ''; ?>>Ampule</option>
                                <option value="Nebule" <?php echo (strtolower($medicine['category']) === 'nebule') ? 'selected' : ''; ?>>Nebule</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Pack Form</label>
                            <input type="text" name="pack" placeholder="e.g., BOX" required style="width:100%" value="<?php echo htmlspecialchars($medicine['pack']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Packaging Size</label>
                            <input type="text" name="size" placeholder="e.g., 100'S" required style="width:100%" value="<?php echo htmlspecialchars($medicine['size']); ?>">
                        </div>
                    </div>

                    <div style="margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Description (Full Formula Name)</label>
                            <input type="text" name="description" placeholder="e.g., ACICLOVIR 400MG TAB (SAPHRIVAX-400)" required style="width:100%" value="<?php echo htmlspecialchars($medicine['description']); ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Price (Php)</label>
                            <input type="number" name="price" step="0.01" min="0" placeholder="495.00" required style="width:100%" value="<?php echo htmlspecialchars($medicine['price']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock" min="0" placeholder="100" required style="width:100%" value="<?php echo htmlspecialchars($medicine['stock']); ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Batch Expiration Date</label>
                            <input type="date" name="expiry_date" required style="width:100%" value="<?php echo htmlspecialchars($medicine['expiry_date']); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supplier Availability status</label>
                            <select name="availability" style="width:100%">
                                <option value="Available" <?php echo ($medicine['availability'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                                <option value="Unavailable" <?php echo ($medicine['availability'] === 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                                <option value="Delayed Stock" <?php echo ($medicine['availability'] === 'Delayed Stock') ? 'selected' : ''; ?>>Delayed Stock</option>
                            </select>
                        </div>
                    </div>

                    <!-- Current packaging preview -->
                    <div style="margin-bottom: 1.5rem; display:flex; align-items:center; gap:1.25rem; padding: 1rem; border:1px solid rgba(255, 255, 255, 0.12); border-radius:var(--radius-md); background-color:rgba(15, 23, 42, 0.6) !important; width:100%; box-sizing:border-box; overflow:hidden; min-width:0">
                        <?php 
                            $currImg = getMedicineImageSrc($medicine['image'], '../uploads/medicines/');
                        ?>
                        <img src="<?php echo htmlspecialchars($currImg); ?>" alt="current" style="width:60px; height:60px; flex-shrink:0; object-fit:cover; border-radius:var(--radius-sm); border:1px solid rgba(255, 255, 255, 0.12);" onerror="this.onerror=null; this.src='../uploads/medicines/default.png';">
                        <div style="min-width:0; flex:1; overflow:hidden">
                            <span class="form-label" style="display:inline-block; margin-bottom:0.25rem; color:#ffffff !important; font-weight:700">Active Container photo</span><br>
                            <span style="font-family:monospace; font-size:0.8rem; color:#cbd5e1 !important; word-break:break-all; overflow-wrap:break-word; display:block"><?php echo htmlspecialchars($medicine['image']); ?></span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:2rem">
                        <label class="form-label">Replace packaging photograph (optional)</label>
                        <div class="file-upload-container" onclick="document.getElementById('med_image').click()">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary-color); margin:0 auto 0.5rem auto"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                            <span style="font-weight:600; font-size:0.9rem" id="filePlaceholder">Click to select new replacement photo (JPG, PNG)</span>
                            <input type="file" id="med_image" name="med_image" accept="image/*" style="display:none" onchange="document.getElementById('filePlaceholder').textContent = this.files[0].name">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem">Apply Changes</button>

                </form>
            </div>

        </main>
    </div>

    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
