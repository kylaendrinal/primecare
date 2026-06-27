<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Portal - Delete medicine from directory database
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check id
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    // Perform Delete SQL (Cascade constraint automatically cleans inquiries associated with this medicine)
    $sql_delete = "DELETE FROM medicines WHERE id = $id";
    mysqli_query($conn, $sql_delete);
}

header("Location: medicines.php");
exit;
?>
