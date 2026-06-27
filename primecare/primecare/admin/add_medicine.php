<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Portal - Add Medicine with Image Upload handles
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

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
    $image_filename = 'default.png';

    // Set fallback name column
    $name = trim(mysqli_real_escape_string($conn, $brand ?: $description));

    // File Upload handling
    if (isset($_FILES['med_image']) && $_FILES['med_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['med_image']['tmp_name'];
        $fileName = $_FILES['med_image']['name'];
        $fileSize = $_FILES['med_image']['size'];
        $fileType = $_FILES['med_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed extensions
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($fileExtension, $allowedExtensions)) {
            // Generate clean unique filename
            $newFileName = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $name)) . '_' . time() . '.' . $fileExtension;
            
            // Upload directory path
            $uploadFileDir = '../uploads/medicines/';
            
            // Create folder if not exists
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_filename = $newFileName;
            } else {
                $error = "Error transferring file. Check write folder permissions.";
            }
        } else {
            $error = "Unsupported image format. Upload JPG, JPEG, or PNG.";
        }
    }

    if (empty($category) || empty($pack) || empty($size) || empty($description) || empty($brand) || empty($expiry_date)) {
        $error = "All mandatory fields must be specified.";
    } elseif (empty($error)) {
        // SQL Insert
        $insert_query = "INSERT INTO medicines (name, category, pack, size, description, brand, price, stock, availability, expiry_date, image) 
                         VALUES ('$name', '$category', '$pack', '$size', '$description', '$brand', $price, $stock, '$availability', '$expiry_date', '$image_filename')";

        if (mysqli_query($conn, $insert_query)) {
            $success = "Successfully registered compound '$brand' inside database catalog!";
            // Reset input values
            $_POST = array();
        } else {
            $error = "Failed to record compound. Error details: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medicine - PrimeCare Admin</title>
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
            <li><a href="logout.php" style="color:#ef4444 !important;">Logout</a></li>
        </ul>
    </nav>

    <!-- Admin Grid Sidebar Frame -->
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
                <h2 style="font-size:1.5rem; margin-bottom:0.5rem; color:var(--primary-dark)">Add New Medicine Stock Batch</h2>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem">Complete the active pharmaceutical details and upload batch packaging images for client review catalogs.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form action="add_medicine.php" method="POST" enctype="multipart/form-data">
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Brand Name</label>
                            <input type="text" name="brand" placeholder="e.g., SAPHRIVAX-400" required style="width:100%" value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" required style="width:100%">
                                <option value="" disabled <?php echo !isset($_POST['category']) ? 'selected' : ''; ?>>Select Category</option>
                                <option value="Tablet" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Tablet') ? 'selected' : ''; ?>>Tablet</option>
                                <option value="Capsule" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Capsule') ? 'selected' : ''; ?>>Capsule</option>
                                <option value="Suspension" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Suspension') ? 'selected' : ''; ?>>Suspension</option>
                                <option value="Ampule" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Ampule') ? 'selected' : ''; ?>>Ampule</option>
                                <option value="Nebule" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Nebule') ? 'selected' : ''; ?>>Nebule</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Pack Form</label>
                            <input type="text" name="pack" placeholder="e.g., BOX" required style="width:100%" value="<?php echo isset($_POST['pack']) ? htmlspecialchars($_POST['pack']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Packaging Size</label>
                            <input type="text" name="size" placeholder="e.g., 100'S" required style="width:100%" value="<?php echo isset($_POST['size']) ? htmlspecialchars($_POST['size']) : ''; ?>">
                        </div>
                    </div>

                    <div style="margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Description (Full Formula Name)</label>
                            <input type="text" name="description" placeholder="e.g., ACICLOVIR 400MG TAB (SAPHRIVAX-400)" required style="width:100%" value="<?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Price (Php)</label>
                            <input type="number" name="price" step="0.01" min="0" placeholder="495.00" required style="width:100%" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock" min="0" placeholder="100" required style="width:100%" value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '100'; ?>">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem">
                        <div class="form-group">
                            <label class="form-label">Batch Expiration Date</label>
                            <input type="date" name="expiry_date" required style="width:100%" value="<?php echo isset($_POST['expiry_date']) ? htmlspecialchars($_POST['expiry_date']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supplier Availability status</label>
                            <select name="availability" style="width:100%">
                                <option value="Available">Available</option>
                                <option value="Unavailable">Unavailable</option>
                                <option value="Delayed Stock">Delayed Stock</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:2rem">
                        <label class="form-label">Select Packaging Thumbnail Image</label>
                        <div class="file-upload-container" onclick="document.getElementById('med_image').click()">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary-color); margin:0 auto 0.5rem auto"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                            <span style="font-weight:600; font-size:0.9rem" id="filePlaceholder">Click to browse batch packaging photo (JPG, PNG)</span>
                            <input type="file" id="med_image" name="med_image" accept="image/*" style="display:none" onchange="document.getElementById('filePlaceholder').textContent = this.files[0].name">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem">Submit to Distribution Catalog</button>

                </form>
            </div>

        </main>
    </div>

</body>
</html>
