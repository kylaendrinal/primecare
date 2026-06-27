<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Portal - Medicines Catalog Management & CSV Bulk Insertion
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session verification
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// 1. Handle Bulk CSV Import (Robust, auto-detecting delimiters and formatting mappings)
if (isset($_POST['import_csv'])) {
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csvFile']['tmp_name'];
        $fileName = $_FILES['csvFile']['name'];
        $fileSize = $_FILES['csvFile']['size'];
        $fileType = $_FILES['csvFile']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        if ($fileExtension === 'csv') {
            // Ensure target folder exists
            $targetDir = '../uploads/csv/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // Create a unique sanitized filename
            $sanitizedName = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $fileName);
            $uniqueFileName = time() . '_' . $sanitizedName;
            $destinationPath = $targetDir . $uniqueFileName;

            // Move the file to the dedicated /uploads/csv/ folder
            if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                // Dynamically detect delimiter (comma, tab, semicolon)
                $firstLine = '';
                if ($testHandle = fopen($destinationPath, "r")) {
                    $firstLine = fgets($testHandle);
                    fclose($testHandle);
                }

                $delimiter = ",";
                $delimiters = ["," => 0, "\t" => 0, ";" => 0, "|" => 0];
                foreach ($delimiters as $delim => &$count) {
                    $count = count(explode($delim, $firstLine));
                }
                arsort($delimiters);
                $detectedDelimiter = key($delimiters);
                if ($delimiters[$detectedDelimiter] > 1) {
                    $delimiter = $detectedDelimiter;
                }

                if (($handle = fopen($destinationPath, "r")) !== false) {
                    // Read Headers and map positions dynamically
                    $headers = fgetcsv($handle, 1000, $delimiter);
                    
                    $headerMap = [];
                    if ($headers) {
                        foreach ($headers as $index => $header) {
                            $headerClean = strtolower(trim($header));
                            // Strip byte order mark (BOM) if exists
                            $headerClean = preg_replace('/[\x{00FF}\x{00FE}\x{EF}\x{BB}\x{BF}]/u', '', $headerClean);
                            
                            // Map the exact cleaned header
                            $headerMap[$headerClean] = $index;

                            // Also create alternate normalized keys (replacing spaces with underscores and vice versa)
                            $normalizedWithUnderscores = str_replace(' ', '_', $headerClean);
                            $normalizedWithSpaces = str_replace('_', ' ', $headerClean);
                            $headerMap[$normalizedWithUnderscores] = $index;
                            $headerMap[$normalizedWithSpaces] = $index;
                        }
                    }

                    $insertedRows = 0;
                    $failedRows = 0;

                    // Helper function to robustly parse expiry dates to format YYYY-MM-DD
                    $parseExpiryDate = function($date_raw) {
                        $date_clean = trim($date_raw);
                        if (empty($date_clean)) {
                            return date('Y-m-d', strtotime('+1 year')); // safe automatic fallback
                        }
                        
                        // If already in YYYY-MM-DD format
                        if (preg_match('/^\d{4}[\/\-]\d{2}[\/\-]\d{2}$/', $date_clean)) {
                            return str_replace('/', '-', $date_clean);
                        }

                        // Try to handle MM/DD/YYYY or DD/MM/YYYY
                        if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}$/', $date_clean)) {
                            $parts = preg_split('/[\/\-]/', $date_clean);
                            $v1 = intval($parts[0]);
                            $v2 = intval($parts[1]);
                            $year = intval($parts[2]);
                            
                            // Check if standard MM/DD/YYYY is likely (left value is month <= 12)
                            if ($v1 <= 12) {
                                return sprintf("%04d-%02d-%02d", $year, $v1, $v2);
                            } else {
                                // Assume DD/MM/YYYY
                                return sprintf("%04d-%02d-%02d", $year, $v2, $v1);
                            }
                        }

                        // Fallback using strtotime
                        $timestamp = strtotime($date_clean);
                        if ($timestamp !== false) {
                            return date('Y-m-d', $timestamp);
                        }
                        return date('Y-m-d', strtotime('+1 year'));
                    };

                    // Loop rows and perform validation
                    while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                        // Skip empty parsed lines
                        if (empty($data) || count($data) === 0 || (count($data) === 1 && empty($data[0]))) {
                            continue;
                        }

                        $category = '';
                        $pack = 'BOX';
                        $size = '100\'s';
                        $description = '';
                        $brand = '';
                        $price = 0.00;
                        $stock = 100;
                        $availability = 'Available';
                        $expiry_raw = '';
                        $image = 'default.png';

                        // Check if we can bind values via headers dynamically
                        if (isset($headerMap['description']) || isset($headerMap['brand']) || isset($headerMap['category'])) {
                            $category = isset($headerMap['category']) && isset($data[$headerMap['category']]) ? trim($data[$headerMap['category']]) : 'Tablet';
                            $pack = isset($headerMap['pack']) && isset($data[$headerMap['pack']]) ? trim($data[$headerMap['pack']]) : 'BOX';
                            $size = isset($headerMap['size']) && isset($data[$headerMap['size']]) ? trim($data[$headerMap['size']]) : '100\'s';
                            $description = isset($headerMap['description']) && isset($data[$headerMap['description']]) ? trim($data[$headerMap['description']]) : '';
                            $brand = isset($headerMap['brand']) && isset($data[$headerMap['brand']]) ? trim($data[$headerMap['brand']]) : '';
                            
                            $price_raw = isset($headerMap['price']) && isset($data[$headerMap['price']]) ? trim($data[$headerMap['price']]) : '0.00';
                            $price = floatval(preg_replace('/[^\d\.]/', '', $price_raw));
                            
                            $expiry_raw = isset($headerMap['expiration']) && isset($data[$headerMap['expiration']]) ? trim($data[$headerMap['expiration']]) : 
                                         (isset($headerMap['expiry_date']) && isset($data[$headerMap['expiry_date']]) ? trim($data[$headerMap['expiry_date']]) : '');
                            
                            $stock_raw = isset($headerMap['stock']) && isset($data[$headerMap['stock']]) ? trim($data[$headerMap['stock']]) : '100';
                            $stock = intval($stock_raw);
                            $availability = isset($headerMap['availability']) && isset($data[$headerMap['availability']]) ? trim($data[$headerMap['availability']]) : ($stock > 0 ? 'Available' : 'Out of Stock');
                            $image = isset($headerMap['image']) && isset($data[$headerMap['image']]) ? trim($data[$headerMap['image']]) : 'default.png';
                            
                            $name = $brand ?: $description;
                        } else if (isset($headerMap['name']) && isset($data[$headerMap['name']])) {
                            $name = trim($data[$headerMap['name']]);
                            $category = isset($headerMap['category']) && isset($data[$headerMap['category']]) ? trim($data[$headerMap['category']]) : 'Tablet';
                            $stock_raw = isset($headerMap['stock']) && isset($data[$headerMap['stock']]) ? trim($data[$headerMap['stock']]) : '100';
                            $stock = intval($stock_raw);
                            $expiry_raw = isset($headerMap['expiry_date']) && isset($data[$headerMap['expiry_date']]) ? trim($data[$headerMap['expiry_date']]) : '';
                            $image = isset($headerMap['image']) && isset($data[$headerMap['image']]) ? trim($data[$headerMap['image']]) : 'default.png';
                            $availability = isset($headerMap['availability']) && isset($data[$headerMap['availability']]) ? trim($data[$headerMap['availability']]) : ($stock > 0 ? 'Available' : 'Out of Stock');
                            
                            $pack = 'BOX';
                            $size = '100\'s';
                            $description = $name;
                            $brand = $name;
                            $price = 150.00;
                        } else {
                            // Fallback to strict column positions matching exact required format:
                            // CATEGORY, PACK, SIZE, DESCRIPTION, BRAND, PRICE, EXPIRATION
                            $category = isset($data[0]) ? trim($data[0]) : 'Tablet';
                            $pack = isset($data[1]) ? trim($data[1]) : 'BOX';
                            $size = isset($data[2]) ? trim($data[2]) : '100\'s';
                            $description = isset($data[3]) ? trim($data[3]) : '';
                            $brand = isset($data[4]) ? trim($data[4]) : '';
                            $price_raw = isset($data[5]) ? trim($data[5]) : '0.00';
                            $price = floatval(preg_replace('/[^\d\.]/', '', $price_raw));
                            $expiry_raw = isset($data[6]) ? trim($data[6]) : '';
                            
                            $stock_raw = isset($data[7]) ? trim($data[7]) : '100';
                            $stock = intval($stock_raw);
                            $availability = ($stock > 0) ? 'Available' : 'Out of Stock';
                            $image = isset($data[8]) ? trim($data[8]) : 'default.png';
                            $name = $brand ?: $description;
                        }

                        if (empty($description) && empty($brand) && empty($name)) {
                            $failedRows++;
                            continue;
                        }

                        $stock = max(0, $stock);
                        $expiry_clean = $parseExpiryDate($expiry_raw);

                        // Escape variables for MySQL safety
                        $name_esc = mysqli_real_escape_string($conn, $name ?: $brand ?: $description);
                        $category_esc = mysqli_real_escape_string($conn, $category);
                        $pack_esc = mysqli_real_escape_string($conn, $pack);
                        $size_esc = mysqli_real_escape_string($conn, $size);
                        $description_esc = mysqli_real_escape_string($conn, $description);
                        $brand_esc = mysqli_real_escape_string($conn, $brand);
                        $availability_esc = mysqli_real_escape_string($conn, $availability);
                        $image_esc = empty($image) ? 'default.png' : mysqli_real_escape_string($conn, $image);

                        // Insert or Update existing medicines (matching on description or brand key)
                        $check_exist = mysqli_query($conn, "SELECT id FROM medicines WHERE description = '$description_esc' OR brand = '$brand_esc' OR name = '$name_esc'");
                        if ($check_exist && mysqli_num_rows($check_exist) > 0) {
                            $row_exist = mysqli_fetch_assoc($check_exist);
                            $exist_id = $row_exist['id'];
                            $sql = "UPDATE medicines 
                                    SET name = '$name_esc', category = '$category_esc', pack = '$pack_esc', size = '$size_esc', description = '$description_esc', brand = '$brand_esc', price = $price, stock = $stock, availability = '$availability_esc', expiry_date = '$expiry_clean', image = '$image_esc' 
                                    WHERE id = $exist_id";
                        } else {
                            $sql = "INSERT INTO medicines (name, category, pack, size, description, brand, price, stock, availability, expiry_date, image) 
                                    VALUES ('$name_esc', '$category_esc', '$pack_esc', '$size_esc', '$description_esc', '$brand_esc', $price, $stock, '$availability_esc', '$expiry_clean', '$image_esc')";
                        }
                        
                        if (mysqli_query($conn, $sql)) {
                            $insertedRows++;
                        } else {
                            $failedRows++;
                        }
                    }
                    fclose($handle);
                    $success = "CSV transmission complete! Saved to system records at /uploads/csv/ and successfully inserted/updated $insertedRows active compound batches." . ($failedRows > 0 ? " ($failedRows rows failed verification or date formatting errors)." : "");
                } else {
                    $error = "Could not open saved CSV data file stream.";
                }
            } else {
                $error = "Failed to copy the uploaded CSV file into /uploads/csv/ container directory. Check permissions.";
            }
        } else {
            $error = "Invalid file format. Please upload a comma-separated or tab-separated CSV file (.csv) only.";
        }
    } else {
        $error = "CSV file select error. Make sure file size is under standard server upload limits.";
    }
}

// 2. Fetch all medicines inside catalog
$query = "SELECT * FROM medicines ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$medicines = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $medicines[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Medicines - PrimeCare Admin</title>
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

    <!-- Admin dashboard sidebar setup -->
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

        <!-- Main Content Area -->
        <main class="admin-main">
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem">
                <h2 style="font-size:1.75rem; font-weight:800; color:var(--text-color)">Medicine Inventory Catalog</h2>
                <a href="add_medicine.php" class="btn btn-primary">+ Add New Compound</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Table Search input bar -->
            <div class="search-filter-bar" style="display: flex; gap: 1rem; align-items: center; padding: 1rem; margin-bottom: 1.5rem;">
                <div class="form-group-flex" style="flex: 1;">
                    <input type="text" id="adminTableSearchMedicines" placeholder="Type pattern to filter medicine inventory instant live..." style="width: 100%;">
                </div>
                <div class="form-group-flex" style="max-width: 250px; min-width: 180px;">
                    <select id="adminFilterCategory" style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(15, 23, 42, 0.4); color: var(--text-color);">
                        <option value="all">All Categories</option>
                        <option value="tablet">Tablet</option>
                        <option value="capsule">Capsule</option>
                        <option value="suspension">Suspension</option>
                        <option value="ampule">Ampule</option>
                        <option value="nebule">Nebule</option>
                    </select>
                </div>
            </div>

            <!-- Medicines Directory Data table -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">Image</th>
                            <th style="text-align: left;">Category</th>
                            <th style="text-align: left;">Pack</th>
                            <th style="text-align: left;">Size</th>
                            <th style="text-align: left;">Description</th>
                            <th style="text-align: left;">Brand</th>
                            <th style="text-align: right; width: 90px;">Price</th>
                            <th style="text-align: center; width: 110px;">Expiration</th>
                            <th style="text-align: center; width: 90px;">Stock</th>
                            <th style="text-align: center; width: 140px;">Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($medicines)): ?>
                            <tr>
                                <td colspan="10" style="text-align:center; padding:3rem; color:var(--text-muted)">
                                    No medicines found. Click "+ Add New Compound" to seed a single batch.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($medicines as $med): ?>
                                <?php 
                                    $lvlClass = ($med['stock'] > 40) ? 'status-processed' : (($med['stock'] > 10) ? 'status-reviewed' : 'status-pending');
                                    $lvlText = ($med['stock'] > 40) ? 'Healthy' : (($med['stock'] > 10) ? 'Moderate' : 'Critical');
                                    
                                    $imgSrc = getMedicineImageSrc($med['image'], '../uploads/medicines/');
                                ?>
                                <tr data-category="<?php echo htmlspecialchars(strtolower($med['category'])); ?>">
                                    <td style="vertical-align: middle;">
                                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="img" style="width:40px; height:40px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid var(--border-color);" onerror="this.onerror=null; this.src='../uploads/medicines/default.png';">
                                    </td>
                                    <td style="text-transform:uppercase; font-size:0.8rem; font-weight:700; color:var(--primary-color); vertical-align: middle;"><?php echo htmlspecialchars($med['category']); ?></td>
                                    <td style="text-transform:uppercase; font-size:0.8rem; font-weight:600; color:var(--text-color); vertical-align: middle;"><?php echo htmlspecialchars($med['pack']); ?></td>
                                    <td style="font-size:0.85rem; font-weight:600; color:var(--text-color); vertical-align: middle;"><?php echo htmlspecialchars($med['size']); ?></td>
                                    <td style="font-size:0.85rem; font-weight:700; color:var(--text-color); vertical-align: middle;"><?php echo htmlspecialchars($med['description']); ?></td>
                                    <td style="font-size:0.85rem; font-weight:600; color:var(--text-muted); vertical-align: middle;"><?php echo htmlspecialchars($med['brand'] ? $med['brand'] : '-'); ?></td>
                                    <td style="font-family:monospace; font-weight:700; text-align: right; vertical-align: middle;"><?php echo number_format($med['price'], 2); ?></td>
                                    <td style="font-size:0.85rem; font-weight:700; color:var(--primary-dark); text-align: center; vertical-align: middle;"><?php echo date('d/m/Y', strtotime($med['expiry_date'])); ?></td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <span class="status-badge <?php echo $lvlClass; ?>" style="font-size: 0.75rem; padding: 2px 6px; font-family: monospace; font-weight: 700; display: inline-block; min-width: 60px;" title="<?php echo $lvlText; ?>"><?php echo $med['stock']; ?></span>
                                    </td>
                                    <td style="text-align:center; vertical-align: middle;">
                                        <div style="display:flex; gap:0.4rem; justify-content:center">
                                            <a href="edit_medicine.php?id=<?php echo $med['id']; ?>" class="btn btn-outline btn-sm" style="padding:4px 8px; font-size:0.75rem;">Modify</a>
                                            <a href="delete_medicine.php?id=<?php echo $med['id']; ?>" class="btn btn-danger btn-sm confirm-delete" style="padding:4px 8px; font-size:0.75rem;">Remove</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- CSV upload file input block -->
            <section id="importSection" style="background-color:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:2rem; box-shadow:var(--shadow-sm)">
                <h3 style="font-weight:800; color:var(--primary-dark); margin-bottom:0.5rem">Bulk Seed Medicine rows using CSV</h3>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.5rem">
                    Upload a raw comma separated values CSV list matching this exact layout column order:<br>
                    <code style="background-color:var(--primary-light); color:var(--primary-dark); padding:2px 8px; border-radius:4px; font-family:monospace; font-size:0.85rem">CATEGORY,PACK,SIZE,DESCRIPTION,BRAND,PRICE,EXPIRATION</code>
                </p>

                <form action="medicines.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-wrap:wrap; gap:1.5rem; align-items:flex-end">
                    <div style="flex:1; min-width:260px">
                        <label class="form-label">Select CSV Database Document</label>
                        <input type="file" id="csvFile" name="csvFile" accept=".csv" required style="padding:0.4rem">
                    </div>
                    <button type="submit" name="import_csv" class="btn btn-primary" style="padding:0.65rem 1.75rem">Execute Bulk Insert</button>
                </form>
            </section>

        </main>
    </div>

    <!-- Scripts Integration -->
    <script src="../js/script.js?v=2.1"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const maxVisible = 5;
            const table = document.querySelector(".table-responsive table");
            if (!table) return;
            const rows = Array.from(table.querySelectorAll("tbody tr"));
            // If the only row is "No medicines found", don't limit
            if (rows.length === 1 && rows[0].cells.length === 1) return;
            if (rows.length <= maxVisible) return;

            // Hide rows past maxVisible initially
            rows.forEach((row, idx) => {
                if (idx >= maxVisible) {
                    row.classList.add("med-row-hidden");
                    row.style.display = "none";
                }
            });

            // Create See All Button Container
            const seeAllContainer = document.createElement("div");
            seeAllContainer.style.textAlign = "center";
            seeAllContainer.style.marginTop = "1.5rem";
            seeAllContainer.style.marginBottom = "2.5rem";

            const seeAllBtn = document.createElement("button");
            seeAllBtn.className = "btn btn-outline";
            seeAllBtn.style.padding = "0.65rem 2rem";
            seeAllBtn.style.fontWeight = "750";
            seeAllBtn.style.borderRadius = "12px";
            seeAllBtn.style.borderColor = "#0055ff";
            seeAllBtn.style.color = "#0055ff";
            seeAllBtn.style.background = "#ffffff";
            seeAllBtn.style.cursor = "pointer";
            seeAllBtn.innerHTML = "See All Medicines (" + rows.length + ") &darr;";

            seeAllBtn.addEventListener("click", function() {
                rows.forEach(row => {
                    row.classList.remove("med-row-hidden");
                    row.style.display = "";
                });
                seeAllContainer.remove();
            });

            seeAllContainer.appendChild(seeAllBtn);
            table.parentNode.appendChild(seeAllContainer);

            // Handle interactions with search & category filter
            const searchInput = document.getElementById("adminTableSearchMedicines");
            const categorySelect = document.getElementById("adminFilterCategory");

            function filterMedicinesList() {
                const searchText = searchInput ? searchInput.value.toLowerCase().trim() : "";
                const selectedCategory = categorySelect ? categorySelect.value.toLowerCase() : "all";
                const hasActiveFilter = (searchText.length > 0) || (selectedCategory !== "all");

                if (hasActiveFilter) {
                    seeAllContainer.style.display = "none";
                    rows.forEach(row => {
                        const textContent = row.textContent.toLowerCase();
                        const rowCategory = row.getAttribute("data-category") || "";

                        const matchesText = textContent.includes(searchText);
                        const matchesCategory = (selectedCategory === "all") || (rowCategory === selectedCategory);

                        if (matchesText && matchesCategory) {
                            row.style.display = "";
                            row.classList.remove("med-row-hidden");
                        } else {
                            row.style.display = "none";
                        }
                    });
                } else {
                    seeAllContainer.style.display = "block";
                    rows.forEach((row, idx) => {
                        if (idx >= maxVisible) {
                            row.classList.add("med-row-hidden");
                            row.style.display = "none";
                        } else {
                            row.style.display = "";
                            row.classList.remove("med-row-hidden");
                        }
                    });
                }
            }

            if (searchInput) {
                searchInput.addEventListener("input", filterMedicinesList);
            }
            if (categorySelect) {
                categorySelect.addEventListener("change", filterMedicinesList);
            }
        });
    </script>
</body>
</html>
