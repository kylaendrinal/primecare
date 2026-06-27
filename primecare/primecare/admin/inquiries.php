<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Administrative Portal - Inquiries Processing Center (Independent)
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if (isset($_GET['success_msg'])) {
    $success = "Staff reply successfully recorded and status updated!";
}

// Handle Inquiry Reply Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $inquiry_id = intval($_POST['inquiry_id']);
    $response = trim($_POST['response']);
    $admin_id = intval($_SESSION['user_id']);
    $client_id = intval($_POST['client_id']);

    if (!empty($response)) {
        $escaped_response = mysqli_real_escape_string($conn, $response);
        
        // 1. Insert new follow-up message from Admin (default is_seen = 0 for client to see as Delivered)
        $insert_msg = "INSERT INTO inquiry_messages (inquiry_id, sender_id, sender_role, message, is_seen) VALUES ($inquiry_id, $admin_id, 'admin', '$escaped_response', 0)";
        mysqli_query($conn, $insert_msg);
        
        // 2. Sync to legacy column for backwards compatibility
        mysqli_query($conn, "UPDATE inquiries SET response = '$escaped_response', status = 'Responded' WHERE id = $inquiry_id");

        header("Location: inquiries.php?client_id=" . $client_id . "&success_msg=1");
        exit;
    } else {
        $error = "Reply message text cannot be left empty.";
    }
}

// Handle active client selection before querying lists
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

if ($selected_client_id > 0) {
    // Mark all client messages in this conversation as seen (since admin is viewing)
    mysqli_query($conn, "UPDATE inquiry_messages 
                         SET is_seen = 1 
                         WHERE sender_role = 'client' 
                           AND inquiry_id IN (SELECT id FROM inquiries WHERE user_id = $selected_client_id)");
}

// Load list of unique clients who have actually started a conversation (sent at least one message)
$clients_query = "SELECT u.id as client_id, u.fullname as client_fullname, u.username as client_username, 
                         MAX(i.id) as inquiry_id, 
                         MAX(i.created_at) as last_active
                  FROM users u
                  JOIN inquiries i ON u.id = i.user_id
                  WHERE u.role = 'client'
                    AND (
                      EXISTS (SELECT 1 FROM inquiry_messages im WHERE im.inquiry_id IN (SELECT id FROM inquiries WHERE user_id = u.id) AND im.sender_role = 'client')
                      OR EXISTS (SELECT 1 FROM inquiries i2 WHERE i2.user_id = u.id AND i2.message IS NOT NULL AND i2.message != '' AND i2.message != 'Chat room initialized')
                    )
                  GROUP BY u.id, u.fullname, u.username
                  ORDER BY last_active DESC";
$clients_result = mysqli_query($conn, $clients_query);
$clients = [];
if ($clients_result) {
    while ($row = mysqli_fetch_assoc($clients_result)) {
        $cid = intval($row['client_id']);
        
        // Fetch the last message text, sender role, and is_seen status for the list preview
        $last_msg_text = "No messages";
        $last_sender = '';
        $last_is_seen = 0;
        
        $last_msg_q = mysqli_query($conn, "SELECT message, sender_role, is_seen FROM inquiry_messages WHERE inquiry_id IN (SELECT id FROM inquiries WHERE user_id = $cid) ORDER BY created_at DESC LIMIT 1");
        if ($last_msg_q && mysqli_num_rows($last_msg_q) > 0) {
            $last_msg_row = mysqli_fetch_assoc($last_msg_q);
            $last_msg_text = $last_msg_row['message'];
            $last_sender = $last_msg_row['sender_role'];
            $last_is_seen = intval($last_msg_row['is_seen']);
        } else {
            // Check inquiries table for legacy message
            $last_msg_q2 = mysqli_query($conn, "SELECT message, response FROM inquiries WHERE user_id = $cid ORDER BY created_at DESC LIMIT 1");
            if ($last_msg_q2 && mysqli_num_rows($last_msg_q2) > 0) {
                $last_msg_row2 = mysqli_fetch_assoc($last_msg_q2);
                if (!empty($last_msg_row2['response'])) {
                    $last_msg_text = $last_msg_row2['response'];
                    $last_sender = 'admin';
                    $last_is_seen = 1;
                } else {
                    $last_msg_text = $last_msg_row2['message'];
                    $last_sender = 'client';
                    $last_is_seen = 0;
                }
            }
        }
        
        // Get total unread messages from this client
        $unread_count = 0;
        $unread_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inquiry_messages WHERE sender_role = 'client' AND is_seen = 0 AND inquiry_id IN (SELECT id FROM inquiries WHERE user_id = $cid)");
        if ($unread_q) {
            $unread_row = mysqli_fetch_assoc($unread_q);
            $unread_count = intval($unread_row['cnt']);
        }
        
        $row['last_msg'] = $last_msg_text;
        $row['last_sender'] = $last_sender;
        $row['last_is_seen'] = $last_is_seen;
        $row['unread_count'] = $unread_count;
        $clients[] = $row;
    }
}

// Handle active client chat loading
$messages = [];
$active_inquiry = null;
$selected_client_info = null;

if ($selected_client_id > 0) {
    // Fetch user details
    $client_q = mysqli_query($conn, "SELECT id, fullname, username FROM users WHERE id = $selected_client_id LIMIT 1");
    if ($client_q && mysqli_num_rows($client_q) > 0) {
        $selected_client_info = mysqli_fetch_assoc($client_q);
        
        // Find their latest inquiry thread
        $inq_q = mysqli_query($conn, "SELECT * FROM inquiries WHERE user_id = $selected_client_id ORDER BY id DESC LIMIT 1");
        if ($inq_q && mysqli_num_rows($inq_q) > 0) {
            $active_inquiry = mysqli_fetch_assoc($inq_q);
        } else {
            // Auto-create a main continuous thread if none exists so the admin can always reply safely
            $med_res = mysqli_query($conn, "SELECT id FROM medicines LIMIT 1");
            $fallback_med_id = 0;
            if ($med_res && mysqli_num_rows($med_res) > 0) {
                $m_row = mysqli_fetch_assoc($med_res);
                $fallback_med_id = intval($m_row['id']);
            }
            mysqli_query($conn, "INSERT INTO inquiries (user_id, medicine_id, message, status) VALUES ($selected_client_id, $fallback_med_id, 'Chat room initialized', 'Pending')");
            $new_inq_id = mysqli_insert_id($conn);
            $inq_q2 = mysqli_query($conn, "SELECT * FROM inquiries WHERE id = $new_inq_id LIMIT 1");
            if ($inq_q2 && mysqli_num_rows($inq_q2) > 0) {
                $active_inquiry = mysqli_fetch_assoc($inq_q2);
            }
        }
        
        if ($active_inquiry) {
            $inquiry_id = intval($active_inquiry['id']);
            
            // Load messages from ALL of this user's inquiry threads to provide a continuous thread
            $msgs_res = mysqli_query($conn, "SELECT im.*, m.name as medicine_name, m.category as medicine_category, m.price as medicine_price, m.image as medicine_image
                                             FROM inquiry_messages im
                                             LEFT JOIN medicines m ON im.medicine_id = m.id
                                             WHERE im.inquiry_id IN (SELECT id FROM inquiries WHERE user_id = $selected_client_id)
                                             ORDER BY im.created_at ASC");
            if ($msgs_res) {
                while ($m_row = mysqli_fetch_assoc($msgs_res)) {
                    $messages[] = $m_row;
                }
            }
            
            // Fallback if messages table is empty
            if (empty($messages)) {
                // Fetch legacy messages for all inquiries of this user
                $legacy_q = mysqli_query($conn, "SELECT i.*, m.name as medicine_name, m.category as medicine_category, m.price as medicine_price, m.image as medicine_image 
                                                  FROM inquiries i 
                                                  LEFT JOIN medicines m ON i.medicine_id = m.id 
                                                  WHERE i.user_id = $selected_client_id 
                                                  ORDER BY i.created_at ASC");
                if ($legacy_q) {
                    while ($legacy_row = mysqli_fetch_assoc($legacy_q)) {
                        if (!empty($legacy_row['message'])) {
                            $messages[] = [
                                'sender_role' => 'client',
                                'message' => $legacy_row['message'],
                                'created_at' => $legacy_row['created_at'],
                                'medicine_id' => $legacy_row['medicine_id'],
                                'medicine_name' => $legacy_row['medicine_name'] ?? 'Compound Product',
                                'medicine_category' => $legacy_row['medicine_category'] ?? '',
                                'medicine_price' => $legacy_row['medicine_price'] ?? 0,
                                'medicine_image' => $legacy_row['medicine_image'] ?? ''
                            ];
                        }
                        if (!empty($legacy_row['response'])) {
                            $messages[] = [
                                'sender_role' => 'admin',
                                'message' => $legacy_row['response'],
                                'created_at' => $legacy_row['created_at']
                            ];
                        }
                    }
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
    <title>Client Inquiries Processing - PrimeCare</title>
    <link class="premium-theme-marker" rel="stylesheet" href="../css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
        /* Lock HTML, body, and layout outer container heights to avoid vertical scrolling of the entire page */
        html, body {
            height: 100%;
            overflow: hidden;
        }
        .admin-layout {
            height: calc(100vh - 65px);
            min-height: 0;
            overflow: hidden;
        }
        .admin-main {
            height: 100%;
            overflow: hidden !important;
            display: flex;
            flex-direction: column;
            padding: 1.5rem !important;
        }
        
        /* Modern Shopee-like Webchat Split Layout */
        .admin-chat-layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            flex: 1;
            height: auto;
            min-height: 0;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        /* Left Pane - Customer list */
        .admin-chat-sidebar {
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
        }
        .admin-chat-sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            background-color: #f8fafc;
        }
        .admin-chat-search {
            width: 100%;
            padding: 0.6rem 0.9rem;
            font-size: 0.875rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s ease;
        }
        .admin-chat-search:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .admin-client-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .admin-client-item {
            display: flex;
            align-items: center;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background-color 0.15s ease;
            text-decoration: none;
            color: inherit;
            gap: 0.75rem;
        }
        .admin-client-item:hover {
            background-color: #f8fafc;
        }
        .admin-client-item.active {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
        }
        .admin-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
            flex-shrink: 0;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }
        .admin-client-info {
            flex: 1;
            min-width: 0;
        }
        .admin-client-name-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0.25rem;
        }
        .admin-client-name {
            font-weight: 750;
            font-size: 0.9rem;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .admin-client-meta {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 500;
        }
        .admin-client-last-msg {
            font-size: 0.8rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }

        /* Right Pane - Conversation window */
        .admin-chat-content {
            display: flex;
            flex-direction: column;
            background-color: #f1f5f9;
            height: 100%;
            min-height: 0;
        }
        .admin-chat-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            padding: 3rem;
            text-align: center;
            background-color: #f8fafc;
            color: #64748b;
        }
        
        .chat-thread-header {
            background-color: #1e293b;
            color: white;
            padding: 1.15rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            flex-shrink: 0;
        }
        .chat-thread-body {
            padding: 1.5rem;
            background-color: #f1f5f9;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scroll-behavior: smooth;
        }
        .chat-bubble-wrapper {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .chat-bubble {
            max-width: 75%;
            padding: 0.9rem 1.1rem;
            border-radius: 18px;
            font-size: 0.9rem;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-bottom: 0.25rem;
        }
        .chat-bubble.client {
            align-self: flex-start;
            background-color: white;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }
        .chat-bubble.admin {
            align-self: flex-end;
            background-color: #3b82f6;
            color: white;
            border-bottom-right-radius: 4px;
        }
        .chat-meta {
            font-size: 0.725rem;
            margin-top: 0.25rem;
            display: block;
        }
        .chat-bubble.client .chat-meta {
            color: #64748b;
        }
        .chat-bubble.admin .chat-meta {
            color: rgba(255, 255, 255, 0.8);
            text-align: right;
        }
        
        .chat-thread-footer {
            background-color: #ffffff;
            border-top: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            flex-shrink: 0;
        }
        .chat-input-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .chat-input-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        .chat-textarea {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 24px;
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            outline: none;
            resize: none;
            transition: all 0.2s ease;
            font-family: inherit;
            line-height: 1.4;
            background-color: #f8fafc;
        }
        .chat-textarea:focus {
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .chat-send-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }
        .chat-send-btn:hover {
            background-color: #2563eb;
            transform: scale(1.05);
        }
        .chat-send-btn:active {
            transform: scale(0.95);
        }
        .chat-send-btn svg {
            margin-left: 2px;
        }

        /* Shopee-style product attachment card in admin chat log */
        .shopee-product-card {
            display: flex;
            align-items: center;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem;
            margin-bottom: 0.6rem;
            max-width: 100%;
            gap: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            text-align: left;
        }
        .shopee-product-card img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: white;
            flex-shrink: 0;
        }
        .shopee-product-info {
            flex: 1;
            min-width: 0;
        }
        .shopee-product-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 0.15rem 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .shopee-product-category {
            font-size: 0.7rem;
            color: #64748b;
            background: #e2e8f0;
            padding: 1px 5px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 0.15rem;
        }
        .shopee-product-price {
            font-size: 0.8rem;
            font-weight: 700;
            color: #2563eb;
        }

        /* Back to list button defaults (hidden on desktop, visible on mobile) */
        .back-to-list-btn {
            display: none !important;
        }

        /* Responsive overrides to ensure Client list and Conversations are Messenger-style on Mobile & Tablet */
        @media (max-width: 768px) {
            html, body {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                height: 100% !important;
                overflow: hidden !important;
                width: 100% !important;
            }
            body {
                display: flex !important;
                flex-direction: column !important;
            }
            .premium-capsule-nav {
                flex-shrink: 0 !important;
            }
            .admin-layout {
                flex: 1 !important;
                height: auto !important;
                min-height: 0 !important;
                overflow: hidden !important;
                display: flex !important;
                flex-direction: column !important;
            }
            .admin-main {
                flex: 1 !important;
                height: 100% !important;
                min-height: 0 !important;
                overflow: hidden !important;
                padding: 0.5rem !important;
                gap: 0.25rem !important;
                display: flex !important;
                flex-direction: column !important;
            }
            .admin-main h2, .admin-main p {
                display: none !important; /* Hide headings on mobile to maximize chat window area */
            }
            
            .admin-chat-layout {
                flex: 1 !important;
                height: 100% !important;
                min-height: 0 !important;
                margin-top: 0.25rem !important;
                margin-bottom: 0.5rem !important;
                overflow: hidden !important;
                display: flex !important;
                flex-direction: column !important;
                background-color: #ffffff !important;
                border-radius: 0 !important;
                border: none !important;
            }
            
            /* If NO active chat is selected: show sidebar (full screen), hide content */
            .admin-chat-layout.no-active-chat .admin-chat-sidebar {
                display: flex !important;
                width: 100% !important;
                height: 100% !important;
                flex: 1 !important;
            }
            .admin-chat-layout.no-active-chat .admin-chat-content {
                display: none !important;
            }
            
            /* If active chat IS selected: hide sidebar, show conversation view (full screen) */
            .admin-chat-layout.has-active-chat .admin-chat-sidebar {
                display: none !important;
            }
            .admin-chat-layout.has-active-chat .admin-chat-content {
                display: flex !important;
                width: 100% !important;
                height: 100% !important;
                flex: 1 !important;
                flex-direction: column !important;
            }
            
            /* Sidebar items styling as a beautiful vertical list on mobile */
            .admin-chat-sidebar {
                border-right: none !important;
                background-color: #ffffff !important;
            }
            .admin-chat-sidebar-header {
                padding: 0.75rem 1rem !important;
                border-bottom: 1px solid #f1f5f9 !important;
            }
            .admin-chat-search {
                padding: 0.5rem 0.85rem !important;
                font-size: 0.85rem !important;
                height: auto !important;
            }
            .admin-client-list {
                display: flex !important;
                flex-direction: column !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                height: auto !important;
                flex: 1 !important;
                background-color: #ffffff !important;
                padding: 0 !important;
            }
            .admin-client-item {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                padding: 1rem 1.25rem !important;
                border-bottom: 1px solid #f1f5f9 !important;
                background-color: #ffffff !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 0 !important;
                border: none !important;
                gap: 0.75rem !important;
                height: auto !important;
            }
            .admin-client-item.active {
                background-color: #f1f5f9 !important;
                border-left: 4px solid #3b82f6 !important;
            }
            .admin-avatar {
                width: 40px !important;
                height: 40px !important;
                font-size: 0.85rem !important;
                box-shadow: none !important;
            }
            .admin-client-info {
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                min-width: 0 !important;
            }
            .admin-client-name-row {
                margin-bottom: 0.15rem !important;
                gap: 0.25rem !important;
            }
            .admin-client-name {
                font-size: 0.85rem !important;
                font-weight: 750 !important;
            }
            .admin-client-meta {
                display: inline !important; /* show date cleanly */
                font-size: 0.7rem !important;
            }
            .admin-client-last-msg {
                font-size: 0.775rem !important;
                max-width: 200px !important;
            }
            
            /* Conversation items styling */
            .back-to-list-btn {
                display: inline-flex !important;
                background-color: rgba(255, 255, 255, 0.15) !important;
                padding: 4px 10px !important;
                border-radius: 12px !important;
                transition: background-color 0.2s ease !important;
            }
            .back-to-list-btn:hover {
                background-color: rgba(255, 255, 255, 0.25) !important;
            }
            .chat-thread-header {
                padding: 0.75rem 1rem !important;
            }
            .chat-thread-header h4 {
                font-size: 1rem !important;
            }
            .chat-thread-body {
                padding: 1rem !important;
                flex: 1 !important;
                overflow-y: auto !important;
            }
            .chat-bubble {
                max-width: 80% !important;
                padding: 0.75rem 0.9rem !important;
                font-size: 0.875rem !important;
            }
            .chat-thread-footer {
                padding: 0.75rem 1rem calc(2.5rem + env(safe-area-inset-bottom, 50px)) 1rem !important;
                background-color: #ffffff !important;
                border-top: 1px solid #f1f5f9 !important;
            }
            .chat-textarea {
                padding: 0.6rem 1.1rem !important;
                font-size: 0.875rem !important;
            }
            .chat-send-btn {
                width: 40px !important;
                height: 40px !important;
            }
        }
    </style>
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
            <li class="admin-mobile-only"><a href="inquiries.php" class="nav-link-active-yellow">Client Inquiries</a></li>
            <li class="admin-mobile-only"><a href="reports.php">System Reports</a></li>
            <li><a href="logout.php" style="color:#ef4444 !important;">Logout</a></li>
        </ul>
    </nav>

    <!-- Admin Sidebar Frame layout -->
    <div class="admin-layout">
        
        <!-- Left Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header" style="color:#f8fafc; font-weight:700">Core Navigation</div>
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
                <li class="sidebar-item active">
                    <a href="inquiries.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Client Inquiries
                        <?php 
                        $pending_inq_count_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM inquiry_messages WHERE sender_role='client' AND is_seen=0");
                        $pending_inq_count = 0;
                        if ($pending_inq_count_res) {
                            $row_cnt = mysqli_fetch_assoc($pending_inq_count_res);
                            $pending_inq_count = intval($row_cnt['cnt']);
                        }
                        if ($pending_inq_count > 0): ?>
                            <span style="background-color:#ef4444; color:white; font-size:0.75rem; font-weight:700; padding:1px 6px; border-radius:99px; margin-left:auto"><?php echo $pending_inq_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="orders.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        Purchase Orders
                        <?php 
                        $pending_ord_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM orders WHERE status='Pending'"));
                        if ($pending_ord_count > 0): ?>
                            <span style="background-color:#ef4444; color:white; font-size:0.75rem; font-weight:700; padding:1px 6px; border-radius:99px; margin-left:auto"><?php echo $pending_ord_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="reports.php">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12H3"/><path d="M12 21V3"/></svg>
                        System Reports
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Right Main Board -->
        <main class="admin-main" style="display: flex; flex-direction: column;">
            <h2 style="font-size:1.75rem; margin-bottom:0.5rem; font-weight:800; color:var(--text-color)">Client Inquiries Center</h2>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom: 0.5rem;">Process customer quotes, volume inquiries, and chat in unified continuous rooms.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-top: 0.5rem;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="margin-top: 0.5rem;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="admin-chat-layout <?php echo ($selected_client_id > 0) ? 'has-active-chat' : 'no-active-chat'; ?>">
                
                <!-- Left Sidebar Client List -->
                <div class="admin-chat-sidebar">
                    <div class="admin-chat-sidebar-header">
                        <input type="text" id="adminInqSearch" class="admin-chat-search" placeholder="Search clients by name..." onkeyup="filterClients()">
                    </div>
                    
                    <div class="admin-client-list" id="clientListContainer">
                        <?php if (empty($clients)): ?>
                            <div style="text-align:center; padding:3rem 1rem; color: #94a3b8; font-size: 0.85rem;">
                                No customer message threads catalogued yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                                <?php 
                                    $is_active = ($client['client_id'] == $selected_client_id);
                                    $initials = substr($client['client_fullname'], 0, 2);
                                    
                                    // Status pill color coding
                                    $status_lbl = ($client['last_is_seen'] === 1) ? 'Seen' : 'Delivered';
                                    
                                    if ($client['last_is_seen'] === 1) {
                                        $badgeStyle = "background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;";
                                    } else {
                                        // Highlight delivered unread messages prominently!
                                        if ($client['last_sender'] === 'client' && $client['unread_count'] > 0) {
                                            $badgeStyle = "background-color: #ef4444; color: white; font-weight: 800; border: 1px solid #f87171;";
                                            $status_lbl = 'Delivered (' . $client['unread_count'] . ')';
                                        } else {
                                            $badgeStyle = "background-color: #eff6ff; color: #2563eb; border: 1px solid #93c5fd; font-weight: 700;";
                                        }
                                    }
                                ?>
                                <a href="inquiries.php?client_id=<?php echo $client['client_id']; ?>" class="admin-client-item <?php echo $is_active ? 'active' : ''; ?>" data-fullname="<?php echo htmlspecialchars(strtolower($client['client_fullname'])); ?>">
                                    <div class="admin-avatar" style="<?php echo ($client['unread_count'] > 0 && $client['last_sender'] === 'client') ? 'border: 2px solid #2563eb;' : ''; ?>">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                    <div class="admin-client-info">
                                        <div class="admin-client-name-row">
                                            <span class="admin-client-name" style="<?php echo ($client['unread_count'] > 0 && $client['last_sender'] === 'client') ? 'font-weight: 800; color: #1e3a8a;' : ''; ?>"><?php echo htmlspecialchars($client['client_fullname']); ?></span>
                                            <span class="admin-client-meta">
                                                <?php 
                                                    $is_default_active = (strtotime($client['last_active']) < strtotime('2010-01-01'));
                                                    echo $is_default_active ? 'New' : date('M d', strtotime($client['last_active'])); 
                                                ?>
                                            </span>
                                        </div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-top: 0.15rem;">
                                            <span class="admin-client-last-msg" style="<?php echo ($client['unread_count'] > 0 && $client['last_sender'] === 'client') ? 'font-weight: 700; color: #0f172a;' : ''; ?>"><?php echo htmlspecialchars($client['last_msg']); ?></span>
                                            <?php if ($client['unread_count'] > 0 && $client['last_sender'] === 'client'): ?>
                                                <span style="font-size: 0.65rem; font-weight: 800; padding: 2px 6px; border-radius: 10px; background-color: #ef4444; color: white; min-width: 18px; text-align: center;">
                                                    <?php echo $client['unread_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Conversation Thread View -->
                <div class="admin-chat-content">
                    <?php if ($selected_client_id <= 0 || !$selected_client_info): ?>
                        <div class="admin-chat-empty">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 1.25rem; opacity: 0.4; color: #3b82f6;">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <h3 style="font-size: 1.2rem; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem;">No Active Chat Room Selected</h3>
                            <p style="font-size: 0.875rem; max-width: 360px; margin: 0 auto; color: #64748b; line-height: 1.5;">Click any customer from the conversations panel on the left to review their medicine inquiries and send real-time follow-ups.</p>
                        </div>
                    <?php else: ?>
                        <!-- Header bar of selected chat -->
                        <div class="chat-thread-header" style="display: flex; align-items: center; gap: 1rem;">
                            <a href="inquiries.php" class="back-to-list-btn" style="color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                                <span style="font-weight: 700; font-size: 0.95rem;">Back</span>
                            </a>
                            <div>
                                <h4 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: white;"><?php echo htmlspecialchars($selected_client_info['fullname']); ?></h4>
                            </div>
                        </div>
                        
                        <!-- Messages Body -->
                        <div class="chat-thread-body" id="admin_chat_body">
                            <?php foreach ($messages as $msg): ?>
                                <?php 
                                    $is_client = ($msg['sender_role'] === 'client'); 
                                    $bubble_class = $is_client ? 'client' : 'admin';
                                ?>
                                <div class="chat-bubble-wrapper">
                                    <div class="chat-bubble <?php echo $bubble_class; ?>">
                                        
                                        <!-- Render Shopee-style product card if medicine_id exists and is not empty -->
                                        <?php if (!empty($msg['medicine_id'])): ?>
                                            <div class="shopee-product-card">
                                                <img src="<?php echo !empty($msg['medicine_image']) ? '../uploads/medicines/' . $msg['medicine_image'] : 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=300&auto=format&fit=crop&q=60'; ?>" alt="" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=300&auto=format&fit=crop&q=60';">
                                                <div class="shopee-product-info">
                                                    <h4 class="shopee-product-name"><?php echo htmlspecialchars($msg['medicine_name'] ?? 'Compound Product'); ?></h4>
                                                    <span class="shopee-product-category"><?php echo htmlspecialchars(ucfirst(strtolower($msg['medicine_category'] ?? ''))); ?></span>
                                                    <div class="shopee-product-price">₱<?php echo number_format(floatval($msg['medicine_price'] ?? 0), 2); ?> / Box</div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div style="word-wrap:break-word; white-space:pre-wrap; font-weight: 500;"><?php echo htmlspecialchars($msg['message'] ?? ''); ?></div>
                                        <span class="chat-meta">
                                            <?php echo date('M d, y h:i A', strtotime($msg['created_at'])); ?>
                                            <?php if (!$is_client): ?>
                                                &bull; <span style="font-weight: 700; color: #ffffff; opacity: 0.95;"><?php echo (intval($msg['is_seen'] ?? 0) === 1) ? 'Seen' : 'Delivered'; ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Reply Panel -->
                        <div class="chat-thread-footer">
                            <form action="inquiries.php" method="POST" class="chat-input-form">
                                <input type="hidden" name="inquiry_id" value="<?php echo ($active_inquiry ? intval($active_inquiry['id']) : 0); ?>">
                                <input type="hidden" name="client_id" value="<?php echo $selected_client_id; ?>">
                                <input type="hidden" name="submit_reply" value="1">
                                
                                <div class="chat-input-row" style="display: flex; gap: 0.75rem; align-items: center; width: 100%;">
                                    <textarea name="response" class="chat-textarea" rows="1" placeholder="Type your message here" required onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); this.form.submit(); }" style="flex: 1; border: 1px solid #cbd5e1; border-radius: 24px; padding: 0.75rem 1.25rem; font-size: 0.9rem; outline: none; resize: none; transition: all 0.2s ease; line-height: 1.4; background-color: #f8fafc; font-family: inherit;"></textarea>
                                    <button type="submit" class="chat-send-btn" title="Send Reply" style="background: var(--primary-color); border: none; outline: none; color: white; width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all 0.2s ease;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="22" y1="2" x2="11" y2="13"></line>
                                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

    <!-- Scripts Integration -->
    <script>
        // Filter clients list sidebar
        function filterClients() {
            const q = document.getElementById("adminInqSearch").value.toLowerCase().trim();
            const items = document.querySelectorAll(".admin-client-item");
            items.forEach(item => {
                const name = item.getAttribute("data-fullname");
                if (name.includes(q)) {
                    item.style.display = "flex";
                } else {
                    item.style.display = "none";
                }
            });
        }

        // Auto-scroll active chat box to bottom
        const chatBody = document.getElementById("admin_chat_body");
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        // Dynamic height adjustment for chat input textareas
        document.querySelectorAll(".chat-textarea").forEach(textarea => {
            textarea.addEventListener("input", function() {
                this.style.height = "auto";
                this.style.height = (this.scrollHeight) + "px";
                if (this.scrollHeight > 150) {
                    this.style.overflowY = "auto";
                    this.style.height = "150px";
                } else {
                    this.style.overflowY = "hidden";
                }
            });
        });
    </script>
    <script src="../js/script.js?v=2.1"></script>
</body>
</html>
