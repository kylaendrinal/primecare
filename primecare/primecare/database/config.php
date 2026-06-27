<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * MySQL Database Configuration & Auto-Bootstrap
 * Fits perfectly on XAMPP defaults (localhost, root, empty password)
 */

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'primecare_db');

// Connect to MySQL server
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

if (!$conn) {
    die("ERROR: Could not connect to database server. Check XAMPP MySQL. Error: " . mysqli_connect_error());
}

// Create database if not exists
$sql_db = "CREATE DATABASE IF NOT EXISTS `primecare_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!mysqli_query($conn, $sql_db)) {
    die("ERROR: Could not create database. " . mysqli_error($conn));
}

// Select database
if (!mysqli_select_db($conn, DB_NAME)) {
    die("ERROR: Could not select database primecare_db. " . mysqli_error($conn));
}

// Ensure Users Table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fullname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Robust dynamic column migrations for `users` table in case it existed previously with different schema
$check_fullname = mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'fullname'");
if (mysqli_num_rows($check_fullname) == 0) {
    mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `fullname` VARCHAR(100) NOT NULL");
}

$check_email = mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'email'");
if (mysqli_num_rows($check_email) == 0) {
    mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `email` VARCHAR(100) NOT NULL");
}

$check_role = mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'role'");
if (mysqli_num_rows($check_role) == 0) {
    mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'client'");
}

$check_cart = mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'cart'");
if (mysqli_num_rows($check_cart) == 0) {
    mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `cart` TEXT NULL");
}

// Explicitly guarantee default admin and client exist in the table
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$user_pass = password_hash('user123', PASSWORD_DEFAULT);

$check_admin = mysqli_query($conn, "SELECT id FROM `users` WHERE `username` = 'admin'");
if (mysqli_num_rows($check_admin) == 0) {
    mysqli_query($conn, "INSERT INTO `users` (`fullname`, `email`, `username`, `password`, `role`) VALUES 
        ('PrimeCare System Administrator', 'admin@primecare.com', 'admin', '$admin_pass', 'admin')");
}

$check_user1 = mysqli_query($conn, "SELECT id FROM `users` WHERE `username` = 'user1'");
if (mysqli_num_rows($check_user1) == 0) {
    mysqli_query($conn, "INSERT INTO `users` (`fullname`, `email`, `username`, `password`, `role`) VALUES 
        ('Sample Pharmacy Client', 'client@primecare.com', 'user1', '$user_pass', 'client')");
}

// Ensure Medicines Table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `medicines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `pack` VARCHAR(100) NOT NULL DEFAULT 'BOX',
    `size` VARCHAR(100) NOT NULL DEFAULT '100\'s',
    `description` TEXT NOT NULL,
    `brand` VARCHAR(100) NOT NULL DEFAULT '',
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `pieces_per_box` INT NOT NULL DEFAULT 10,
    `availability` VARCHAR(50) NOT NULL DEFAULT 'Available',
    `expiry_date` DATE NOT NULL,
    `image` VARCHAR(255) NOT NULL DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Robust dynamic column migrations for `medicines` in case it existed previously with different schema
$cols_to_add = [
    'pack' => "VARCHAR(100) NOT NULL DEFAULT 'BOX'",
    'size' => "VARCHAR(100) NOT NULL DEFAULT '100\'s'",
    'description' => "TEXT NOT NULL DEFAULT ''",
    'brand' => "VARCHAR(100) NOT NULL DEFAULT ''",
    'price' => "DECIMAL(10, 2) NOT NULL DEFAULT 0.00"
];

foreach ($cols_to_add as $col_name => $col_def) {
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM `medicines` LIKE '$col_name'");
    if (mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE `medicines` ADD COLUMN `$col_name` $col_def");
    }
}

// Ensure column pieces_per_box is present in case of an older schema
$check_pieces = mysqli_query($conn, "SHOW COLUMNS FROM `medicines` LIKE 'pieces_per_box'");
if (mysqli_num_rows($check_pieces) == 0) {
    mysqli_query($conn, "ALTER TABLE `medicines` ADD COLUMN `pieces_per_box` INT NOT NULL DEFAULT 10");
}

// Eliminate any previous hardcoded seed items from the database so it starts completely empty.
// This executes during boot-up to transition the database safely without disrupting customuploaded CSV goods.
mysqli_query($conn, "DELETE FROM `medicines` WHERE `image` IN ('para.jpg', 'bioflu.jpg', 'neozep.jpg', 'ibuprofen.jpg', 'amoxicillin.jpg') OR `name` IN ('Paracetamol', 'Bioflu', 'Neozep', 'Ibuprofen', 'Amoxicillin')");

// Ensure Inquiries Table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `medicine_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `response` TEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ensure column response is present in case of an older schema
$check_response = mysqli_query($conn, "SHOW COLUMNS FROM `inquiries` LIKE 'response'");
if (mysqli_num_rows($check_response) == 0) {
    mysqli_query($conn, "ALTER TABLE `inquiries` ADD COLUMN `response` TEXT NULL");
}

// Ensure inquiry_messages exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `inquiry_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `inquiry_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `sender_role` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ensure medicine_id column exists on inquiry_messages
$check_msg_med = mysqli_query($conn, "SHOW COLUMNS FROM `inquiry_messages` LIKE 'medicine_id'");
if ($check_msg_med && mysqli_num_rows($check_msg_med) == 0) {
    mysqli_query($conn, "ALTER TABLE `inquiry_messages` ADD COLUMN `medicine_id` INT NULL DEFAULT NULL");
}

// Ensure is_seen column exists on inquiry_messages
$check_msg_seen = mysqli_query($conn, "SHOW COLUMNS FROM `inquiry_messages` LIKE 'is_seen'");
if ($check_msg_seen && mysqli_num_rows($check_msg_seen) == 0) {
    mysqli_query($conn, "ALTER TABLE `inquiry_messages` ADD COLUMN `is_seen` TINYINT(1) NOT NULL DEFAULT 0");
}

// Migrate old inquiries to inquiry_messages if the table is empty
$count_msg_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM `inquiry_messages`");
if ($count_msg_res) {
    $row_count = mysqli_fetch_assoc($count_msg_res);
    if ($row_count['count'] == 0) {
        $old_inq_res = mysqli_query($conn, "SELECT id, user_id, message, response, created_at FROM `inquiries`");
        if ($old_inq_res) {
            while ($old_inq = mysqli_fetch_assoc($old_inq_res)) {
                $inq_id = intval($old_inq['id']);
                $user_id = $old_inq['user_id'] ? intval($old_inq['user_id']) : 0;
                $msg_text = mysqli_real_escape_string($conn, $old_inq['message']);
                $created_at = $old_inq['created_at'];
                
                if ($user_id > 0 && !empty($msg_text)) {
                    mysqli_query($conn, "INSERT INTO `inquiry_messages` (inquiry_id, sender_id, sender_role, message, created_at) VALUES ($inq_id, $user_id, 'client', '$msg_text', '$created_at')");
                }
                
                if (!empty($old_inq['response'])) {
                    $resp_text = mysqli_real_escape_string($conn, $old_inq['response']);
                    $admin_user_id = 0;
                    $admin_res = mysqli_query($conn, "SELECT id FROM `users` WHERE `role` = 'admin' LIMIT 1");
                    if ($admin_res && mysqli_num_rows($admin_res) > 0) {
                        $admin_row = mysqli_fetch_assoc($admin_res);
                        $admin_user_id = intval($admin_row['id']);
                    }
                    mysqli_query($conn, "INSERT INTO `inquiry_messages` (inquiry_id, sender_id, sender_role, message, created_at) VALUES ($inq_id, $admin_user_id, 'admin', '$resp_text', '$created_at')");
                }
            }
        }
    }
}

// Ensure Orders Table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `fullname` VARCHAR(100) NOT NULL,
    `address` TEXT NOT NULL,
    `contact_number` VARCHAR(50) NOT NULL,
    `medicine_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `notes` TEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Start session securely if not started
if (session_status() == PHP_SESSION_NONE) {
    // Configure secure, HTTP-only session cookie parameters to mitigate hijack / hijacking and XSS risks
    $secure = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1 || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    // Check PHP version or support before setting samesite
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0, // Till browser closes
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(0, '/; SameSite=Lax', '', $secure, true);
    }
    session_start();
}

// Session inactivity timeout implementation (e.g. 1800 seconds = 30 minutes of idle time)
if (isset($_SESSION['user_id'])) {
    $timeout_duration = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Session expired! Clear and destroy session safely
        session_unset();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Dynamic directory-aware redirect to unified login
        $current_path = $_SERVER['PHP_SELF'];
        if (strpos($current_path, '/admin/') !== false || strpos($current_path, '/client/') !== false) {
            header("Location: ../login.php?error=expired");
        } else {
            header("Location: login.php?error=expired");
        }
        exit;
    }
    // Update active user last activity timestamp
    $_SESSION['last_activity'] = time();
}

// Load cart from database once when user logs in and session started
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
    if (!isset($_SESSION['cart_loaded'])) {
        $user_id = intval($_SESSION['user_id']);
        $cart_res = mysqli_query($conn, "SELECT cart FROM users WHERE id = $user_id");
        if ($cart_res && mysqli_num_rows($cart_res) > 0) {
            $cart_row = mysqli_fetch_assoc($cart_res);
            if (!empty($cart_row['cart'])) {
                $decoded = json_decode($cart_row['cart'], true);
                if (is_array($decoded)) {
                    $_SESSION['cart'] = $decoded;
                } else {
                    $_SESSION['cart'] = [];
                }
            } else {
                $_SESSION['cart'] = [];
            }
        } else {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart_loaded'] = true;
    }
}

// Register a PHP shutdown function to automatically sync any session cart modifications back to the database
register_shutdown_function(function() use ($conn) {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
        if (isset($_SESSION['cart'])) {
            $user_id = intval($_SESSION['user_id']);
            $cart_json = mysqli_real_escape_string($conn, json_encode($_SESSION['cart']));
            mysqli_query($conn, "UPDATE users SET cart = '$cart_json' WHERE id = $user_id");
        }
    }
});

/**
 * Robust image helper function to handle both local file names and external direct public URLs.
 * Dynamically parses Google Drive sharing links into valid direct hotlinks.
 */
if (!function_exists('getMedicineImageSrc')) {
    function getMedicineImageSrc($image_name, $default_local_path = 'uploads/medicines/') {
        if (empty($image_name)) {
            return $default_local_path . 'default.png';
        }

        $image_name = trim($image_name);

        // If it's already an absolute URL
        if (preg_match('/^https?:\/\//i', $image_name)) {
            // Automatically convert standard Google Drive web page links into direct hotlinks.
            if (stripos($image_name, 'drive.google.com') !== false || stripos($image_name, 'docs.google.com') !== false) {
                $fileId = '';
                // Match sharing link format: /file/d/{fileId}/view
                if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/i', $image_name, $matches)) {
                    $fileId = $matches[1];
                } 
                // Match standard query parameters: id={fileId}
                else {
                    $parsed = parse_url($image_name);
                    if (isset($parsed['query'])) {
                        parse_str($parsed['query'], $query_params);
                        if (isset($query_params['id'])) {
                            $fileId = $query_params['id'];
                        }
                    }
                }

                if (!empty($fileId)) {
                    // Use the highly reliable Google Drive thumbnail preview API with size 1000 pixels.
                    // This bypasses standard auth restrictions and Chrome's third-party cookie blocking.
                    return "https://drive.google.com/thumbnail?id=" . $fileId . "&sz=w1000";
                }
            }
            return $image_name;
        }

        if ($image_name === 'default.png') {
            return $default_local_path . 'default.png';
        }

        // Default local file path
        return $default_local_path . $image_name;
    }
}
?>

