<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Client Portal - My Inquiries Monitor (Independent Section)
 */
require_once dirname(__DIR__) . '/database/config.php';

// Session protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Update all messages from admin in this client's inquiries to be "Seen" (is_seen = 1)
mysqli_query($conn, "UPDATE inquiry_messages 
                     SET is_seen = 1 
                     WHERE sender_role = 'admin' 
                       AND inquiry_id IN (SELECT id FROM inquiries WHERE user_id = $client_id)");

// Ensure there is at least one active inquiries thread for this client
$thread_q = mysqli_query($conn, "SELECT id, status FROM inquiries WHERE user_id = $client_id LIMIT 1");
if ($thread_q && mysqli_num_rows($thread_q) > 0) {
    $thread_row = mysqli_fetch_assoc($thread_q);
    $inquiry_id = intval($thread_row['id']);
    $status = $thread_row['status'];
} else {
    // If no thread exists, we pre-create a main one with medicine_id = 0 (or find first medicine to be safe)
    $med_res = mysqli_query($conn, "SELECT id FROM medicines LIMIT 1");
    $fallback_med_id = 0;
    if ($med_res && mysqli_num_rows($med_res) > 0) {
        $m_row = mysqli_fetch_assoc($med_res);
        $fallback_med_id = intval($m_row['id']);
    }
    mysqli_query($conn, "INSERT INTO inquiries (user_id, medicine_id, message, status) VALUES ($client_id, $fallback_med_id, 'Chat room initialized', 'Pending')");
    $inquiry_id = mysqli_insert_id($conn);
    $status = 'Pending';
}

// If AJAX get_messages request is made
if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    $query = "SELECT im.*, m.name as medicine_name, m.category as medicine_category, m.price as medicine_price, m.image as medicine_image
              FROM inquiry_messages im
              LEFT JOIN medicines m ON im.medicine_id = m.id
              WHERE im.inquiry_id = $inquiry_id
              ORDER BY im.created_at ASC";
    $msgs_res = mysqli_query($conn, $query);
    $messages_list = [];
    if ($msgs_res) {
        while ($row = mysqli_fetch_assoc($msgs_res)) {
            $messages_list[] = [
                'id' => intval($row['id']),
                'sender_role' => $row['sender_role'],
                'message' => $row['message'],
                'created_at' => date('M d, y h:i A', strtotime($row['created_at'])),
                'is_seen' => isset($row['is_seen']) ? intval($row['is_seen']) : 0,
                'medicine_id' => $row['medicine_id'] ? intval($row['medicine_id']) : null,
                'medicine_name' => $row['medicine_name'],
                'medicine_category' => $row['medicine_category'],
                'medicine_price' => $row['medicine_price'] ? floatval($row['medicine_price']) : null,
                'medicine_image' => $row['medicine_image']
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'messages' => $messages_list]);
    exit;
}

// Handle client message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    $is_ajax = isset($_POST['ajax']) && intval($_POST['ajax']) === 1;
    
    if (empty($message)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Message text cannot be empty.']);
            exit;
        }
        $error = "Message text cannot be empty.";
    } else {
        $escaped_message = mysqli_real_escape_string($conn, $message);
        
        // Insert follow-up message under our single thread
        $insert_msg = "INSERT INTO inquiry_messages (inquiry_id, sender_id, sender_role, message) VALUES ($inquiry_id, $client_id, 'client', '$escaped_message')";
        if (mysqli_query($conn, $insert_msg)) {
            // Update thread status to 'Pending' so admin knows there's a response
            mysqli_query($conn, "UPDATE inquiries SET status = 'Pending', created_at = CURRENT_TIMESTAMP WHERE id = $inquiry_id");
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Message sent!']);
                exit;
            }
            header("Location: my_inquiries.php");
            exit;
        } else {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Failed to send message: ' . mysqli_error($conn)]);
                exit;
            }
            $error = "Failed to send message: " . mysqli_error($conn);
        }
    }
}

// Query all messages across this user's inquiry threads to provide a continuous conversation flow
$query = "SELECT im.*, m.name as medicine_name, m.category as medicine_category, m.price as medicine_price, m.image as medicine_image
          FROM inquiry_messages im
          LEFT JOIN medicines m ON im.medicine_id = m.id
          WHERE im.inquiry_id IN (SELECT id FROM inquiries WHERE user_id = $client_id)
          ORDER BY im.created_at ASC";

$msgs_res = mysqli_query($conn, $query);
$messages = [];
if ($msgs_res) {
    while ($row = mysqli_fetch_assoc($msgs_res)) {
        $messages[] = $row;
    }
}

// If no messages inside inquiry_messages yet, let's populate using inquiries text for fallback
if (empty($messages)) {
    $inq_q = mysqli_query($conn, "SELECT i.*, m.name as medicine_name, m.category as medicine_category, m.price as medicine_price, m.image as medicine_image 
                                  FROM inquiries i 
                                  JOIN medicines m ON i.medicine_id = m.id 
                                  WHERE i.user_id = $client_id LIMIT 1");
    if ($inq_q && mysqli_num_rows($inq_q) > 0) {
        $inq_row = mysqli_fetch_assoc($inq_q);
        if (!empty($inq_row['message'])) {
            $messages[] = [
                'sender_role' => 'client',
                'message' => $inq_row['message'],
                'created_at' => $inq_row['created_at'],
                'medicine_id' => $inq_row['medicine_id'],
                'medicine_name' => $inq_row['medicine_name'],
                'medicine_category' => $inq_row['medicine_category'],
                'medicine_price' => $inq_row['medicine_price'],
                'medicine_image' => $inq_row['medicine_image']
            ];
        }
        if (!empty($inq_row['response'])) {
            $messages[] = [
                'sender_role' => 'admin',
                'message' => $inq_row['response'],
                'created_at' => $inq_row['created_at']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Inquiries - PrimeCare</title>
    <link rel="stylesheet" href="../css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background-color: #f8fafc;
        }
        .container {
            flex: 1;
            padding: 2rem 1rem;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }
        
        /* Continuous Messenger Chatbox UI */
        .chat-thread-container {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 600px;
        }
        .chat-thread-header {
            background-color: var(--primary-dark);
            color: white;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            padding: 0.95rem 1.15rem;
            border-radius: 18px;
            font-size: 0.9rem;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-bottom: 0.25rem;
        }
        .chat-bubble.client {
            align-self: flex-end;
            background-color: var(--primary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .chat-bubble.admin {
            align-self: flex-start;
            background-color: white;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }
        .chat-meta {
            font-size: 0.725rem;
            margin-top: 0.25rem;
            display: block;
        }
        .chat-bubble.client .chat-meta {
            color: rgba(255, 255, 255, 0.8);
            text-align: right;
        }
        .chat-bubble.admin .chat-meta {
            color: #64748b;
        }
        .chat-thread-footer {
            background-color: #ffffff;
            border-top: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
        }
        .chat-input-form {
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
            border-color: var(--primary-color);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .chat-send-btn {
            background-color: var(--primary-color);
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
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }
        .chat-send-btn:hover {
            background-color: var(--primary-dark);
            transform: scale(1.05);
        }
        .chat-send-btn:active {
            transform: scale(0.95);
        }
        .chat-send-btn svg {
            margin-left: 2px;
        }
        .status-responded {
            background-color: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        /* Shopee-style product attachment card */
        .shopee-product-card {
            display: flex;
            align-items: center;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem;
            margin-bottom: 0.6rem;
            max-width: 100%;
            cursor: pointer;
            transition: background-color 0.2s ease;
            gap: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            text-align: left;
        }
        .shopee-product-card:hover {
            background-color: #f1f5f9;
        }
        .shopee-product-card img {
            width: 54px;
            height: 54px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
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
            font-size: 0.725rem;
            color: #64748b;
            background: #e2e8f0;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 0.15rem;
        }
        .shopee-product-price {
            font-size: 0.825rem;
            font-weight: 700;
            color: #2563eb;
        }
        .shopee-product-action {
            display: flex;
            align-items: center;
            padding-left: 0.5rem;
        }
        .shopee-buy-btn {
            font-size: 0.75rem;
            font-weight: 750;
            color: #2563eb;
            border: 1px solid #2563eb;
            background: white;
            padding: 0.3rem 0.75rem;
            border-radius: 14px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .shopee-product-card:hover .shopee-buy-btn {
            background: #2563eb;
            color: white;
        }

        /* Responsive overrides for Mobile view to prevent stretching and keep everything in viewport */
        @media (max-width: 768px) {
            .container {
                padding: 1rem 0.5rem !important;
            }
            .chat-thread-container {
                height: calc(100vh - 220px) !important;
                min-height: 380px !important;
                max-height: 520px !important;
                margin-bottom: 1rem !important;
            }
            .chat-thread-header {
                padding: 0.75rem 1rem !important;
            }
            .chat-thread-header strong {
                font-size: 0.95rem !important;
            }
            .chat-thread-body {
                padding: 0.75rem !important;
            }
            .chat-bubble {
                max-width: 85% !important;
                padding: 0.65rem 0.85rem !important;
                font-size: 0.85rem !important;
            }
            .chat-thread-footer {
                padding: 0.6rem 1rem !important;
            }
            .chat-textarea {
                padding: 0.5rem 1rem !important;
                font-size: 0.85rem !important;
            }
            .chat-send-btn {
                width: 38px !important;
                height: 38px !important;
            }
            .shopee-product-card {
                padding: 0.5rem !important;
                gap: 0.5rem !important;
            }
            .shopee-product-card img {
                width: 44px !important;
                height: 44px !important;
            }
            .shopee-product-name {
                font-size: 0.8rem !important;
            }
            .shopee-product-price {
                font-size: 0.775rem !important;
            }
            .shopee-buy-btn {
                padding: 0.2rem 0.5rem !important;
                font-size: 0.7rem !important;
            }
        }
    </style>
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
            <li><a href="home.php">Available Medicine</a></li>
            <li><a href="my_inquiries.php" class="nav-link-active-yellow">My Inquiries</a></li>
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

    <!-- Main Content Container -->
    <main class="container">
        
        <div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem">
            <a href="home.php" class="btn btn-outline btn-sm" style="font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; border-color: var(--border-color); color: var(--primary-dark); padding: 0.5rem 1rem;">
                &larr; Back to Dashboard
            </a>
        </div>

        <div style="margin-bottom: 2rem;">
            <h1 style="font-size:1.75rem; font-weight:800; color:var(--primary-dark)">My Inquiries Chatroom</h1>
            <p style="color:var(--text-muted); font-size:0.9rem">Exchange multiple messages, follow-up questions, and check staff responses in a single and continuous chatroom thread.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 8px; background-color: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c;">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>



        <div class="chat-thread-container">
            <div class="chat-thread-header">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background-color: #22c55e; display: inline-block;"></span>
                    <strong style="font-size:1.1rem; color:white">PrimeCare Customer Support</strong>
                </div>
            </div>
            
            <div class="chat-thread-body" id="client_chat_body">
                <?php if (empty($messages)): ?>
                    <div style="text-align:center; padding:4rem 2rem; color: #64748b;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 1rem; opacity: 0.5; color: var(--primary-color);">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <h3 style="margin-bottom:0.5rem; color: var(--primary-dark); font-weight: 700;">No messages yet.</h3>
                        <p style="font-size:0.875rem; max-width: 350px; margin: 0 auto;">Select any medicine compound from our catalog to submit an inquiry and start chatting!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $is_client = ($msg['sender_role'] === 'client'); 
                            $bubble_class = $is_client ? 'client' : 'admin';
                        ?>
                        <div class="chat-bubble-wrapper">
                            <div class="chat-bubble <?php echo $bubble_class; ?>">
                                
                                <!-- Render Shopee-style product card if medicine_id exists and is not empty -->
                                <?php if (!empty($msg['medicine_id'])): ?>
                                    <div class="shopee-product-card" onclick="window.location.href='medicine_details.php?id=<?php echo $msg['medicine_id']; ?>'">
                                        <img src="<?php echo !empty($msg['medicine_image']) ? '../uploads/medicines/' . $msg['medicine_image'] : 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=300&auto=format&fit=crop&q=60'; ?>" alt="" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=300&auto=format&fit=crop&q=60';">
                                        <div class="shopee-product-info">
                                            <h4 class="shopee-product-name"><?php echo htmlspecialchars($msg['medicine_name']); ?></h4>
                                            <span class="shopee-product-category"><?php echo htmlspecialchars(ucfirst(strtolower($msg['medicine_category']))); ?></span>
                                            <div class="shopee-product-price">₱<?php echo number_format($msg['medicine_price'], 2); ?> / Box</div>
                                        </div>
                                        <div class="shopee-product-action">
                                            <span class="shopee-buy-btn">Inquired Product</span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div style="word-wrap:break-word; white-space:pre-wrap; font-weight: 500;"><?php echo htmlspecialchars($msg['message']); ?></div>
                                <span class="chat-meta">
                                    <?php echo date('M d, y h:i A', strtotime($msg['created_at'])); ?>
                                    <?php if ($is_client): ?>
                                        &bull; <span style="font-weight: 700; color: #ffffff; opacity: 0.95;"><?php echo (intval($msg['is_seen'] ?? 0) === 1) ? 'Seen' : 'Delivered'; ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="chat-thread-footer">
                <form action="my_inquiries.php" method="POST" class="chat-input-form">
                    <input type="hidden" name="inquiry_id" value="<?php echo $inquiry_id; ?>">
                    <input type="hidden" name="send_message" value="1">
                    <textarea name="message" class="chat-textarea" rows="1" placeholder="Type your message here" required></textarea>
                    <button type="submit" class="chat-send-btn" title="Send message">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </main>

    <!-- Footer Area -->
    <footer class="footer">
        <p>&copy; 2026 PrimeCare Pharmaceutical Distributors. Managed System Portal.</p>
    </footer>

    <!-- Scripts Integration -->
    <script src="../js/script.js?v=2.1"></script>
    <script>
        // Auto-scroll chat body to bottom
        const chatBody = document.getElementById("client_chat_body");
        function scrollToBottom() {
            if (chatBody) {
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        }
        scrollToBottom();

        // Dynamic height adjustment for chat input textareas
        const textarea = document.querySelector(".chat-textarea");
        if (textarea) {
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
        }

        // AJAX Message Sending & Real-time polling
        const chatForm = document.querySelector(".chat-input-form");
        if (chatForm) {
            chatForm.addEventListener("submit", function(e) {
                e.preventDefault();
                
                const msgVal = textarea.value.trim();
                if (!msgVal) return;

                const submitBtn = chatForm.querySelector(".chat-send-btn");
                const originalBtnHTML = submitBtn.innerHTML;

                // Set Loading State (Spinner Feedback)
                submitBtn.disabled = true;
                submitBtn.style.opacity = "0.7";
                submitBtn.innerHTML = `
                    <svg class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="8"></circle>
                    </svg>
                `;

                const formData = new FormData(chatForm);
                formData.append("ajax", "1");

                fetch("my_inquiries.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("HTTP connection error");
                    }
                    return response.json();
                })
                .then(data => {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = "1";
                    submitBtn.innerHTML = originalBtnHTML;

                    if (data.status === "success") {
                        textarea.value = "";
                        textarea.style.height = "auto";
                        // Poll immediately to show new message
                        pollMessages();
                    } else {
                        if (window.primecare_toast) {
                            window.primecare_toast(data.message || "Unable to send message.", "danger");
                        } else {
                            alert(data.message || "Unable to send message.");
                        }
                    }
                })
                .catch(err => {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = "1";
                    submitBtn.innerHTML = originalBtnHTML;
                    console.error("AJAX Chat Error:", err);
                    if (window.primecare_toast) {
                        window.primecare_toast("Failed to send message. Please check connection.", "danger");
                    } else {
                        alert("Failed to send message. Please check connection.");
                    }
                });
            });

            // Enter key to submit
            textarea.addEventListener("keydown", function(e) {
                if (e.key === "Enter" && !e.shiftKey) {
                    e.preventDefault();
                    chatForm.dispatchEvent(new Event("submit"));
                }
            });
        }

        // Poll messages dynamically
        let lastMessageCount = 0;
        function pollMessages() {
            fetch("my_inquiries.php?action=get_messages")
            .then(res => res.json())
            .then(data => {
                if (data.status === "success" && data.messages) {
                    const messages = data.messages;
                    
                    // Render only if message count changed or initial load
                    if (messages.length !== lastMessageCount) {
                        lastMessageCount = messages.length;
                        
                        if (messages.length === 0) {
                            chatBody.innerHTML = `
                                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#94a3b8; text-align:center; padding:2rem">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:1rem; opacity:0.6">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                    <h3 style="font-size:1.15rem; font-weight:700; color:#475569; margin-bottom:0.25rem">No Messages Yet</h3>
                                    <p style="font-size:0.875rem; max-width:280px">Your inquiry is ready. Send a message above to start a conversation with our staff.</p>
                                </div>
                            `;
                            return;
                        }

                        let html = "";
                        messages.forEach(msg => {
                            const isClient = (msg.sender_role === "client");
                            const bubbleClass = isClient ? "client" : "admin";
                            
                            html += `<div class="chat-bubble-wrapper">`;
                            html += `  <div class="chat-bubble ${bubbleClass}">`;
                            
                            if (msg.medicine_id) {
                                const medImg = msg.medicine_image ? `../uploads/medicines/${msg.medicine_image}` : 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=300&auto=format&fit=crop&q=60';
                                html += `
                                    <div class="shopee-product-card" onclick="window.location.href='medicine_details.php?id=${msg.medicine_id}'">
                                        <img src="${medImg}" alt="" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=300&auto=format&fit=crop&q=60';">
                                        <div class="shopee-product-info">
                                            <h4 class="shopee-product-name">${escapeHTML(msg.medicine_name)}</h4>
                                            <span class="shopee-product-category">${escapeHTML(msg.medicine_category.charAt(0).toUpperCase() + msg.medicine_category.slice(1).toLowerCase())}</span>
                                            <div class="shopee-product-price">₱${Number(msg.medicine_price).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})} / Box</div>
                                        </div>
                                        <div class="shopee-product-action">
                                            <span class="shopee-buy-btn">Inquired Product</span>
                                        </div>
                                    </div>
                                `;
                            }

                            html += `    <div style="word-wrap:break-word; white-space:pre-wrap; font-weight: 500;">${escapeHTML(msg.message)}</div>`;
                            html += `    <span class="chat-meta">`;
                            html += `        ${msg.created_at}`;
                            if (isClient) {
                                const seenText = (msg.is_seen === 1) ? "Seen" : "Delivered";
                                html += `        &bull; <span style="font-weight: 700; color: #ffffff; opacity: 0.95;">${seenText}</span>`;
                            }
                            html += `    </span>`;
                            html += `  </div>`;
                            html += `</div>`;
                        });
                        
                        chatBody.innerHTML = html;
                        scrollToBottom();
                    }
                }
            })
            .catch(err => console.warn("Polling error (offline or network interrupted):", err));
        }

        // Helper to escape HTML to prevent XSS
        function escapeHTML(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;")
                      .replace(/</g, "&lt;")
                      .replace(/>/g, "&gt;")
                      .replace(/"/g, "&quot;")
                      .replace(/'/g, "&#039;");
        }

        // Set up safe periodic polling interval (every 4 seconds)
        setInterval(pollMessages, 4000);
    </script>
</body>
</html>
