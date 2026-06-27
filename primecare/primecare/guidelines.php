<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Pharmacy Procurement Guidelines
 */
require_once __DIR__ . '/database/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurement Guidelines - PrimeCare</title>
    <link rel="stylesheet" href="css/style.css?v=2.1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
</head>
<body>
    <nav class="navbar">
        <div class="brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-color)"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            <span>PrimeCare Quality Standards</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Catalog Home</a></li>
            <li><a href="client/login.php">Login Portal</a></li>
        </ul>
    </nav>

    <main class="container" style="max-width: 800px; padding: 2rem 1rem">
        <div style="margin-bottom: 2rem">
            <h1 style="font-size:2.5rem; color:var(--primary-dark); font-weight:800; margin-bottom:0.5rem">Pharmacy Procurement Guidelines</h1>
            <p style="color:var(--text-muted); font-size:0.95rem">Quality Assurance Protocols across WHO Good Distribution Practice (GDP) guidelines.</p>
        </div>

        <div style="background:#fff; border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; display:grid; gap:1.5rem; line-height:1.6; color:var(--text-color)">
            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">1. Quality Inspection Stams</h3>
                <p>Every single batch cataloged into PrimeCare is inspected for brand packaging integrity, seal protection, correct pharmaceutical active ingredient percentage, and batch certificate numbers. All medicines are stored strictly at optimal warehousing environments (controlled RH below 60% and temperature ranges matching product requirements).</p>
            </section>

            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">2. Bulk Shipping Charge Policies</h3>
                <p>To incentivize clinical bulk operations, PrimeCare enforces simple, transparent flat shipping parameters:</p>
                <ul style="margin-left: 1.5rem; list-style-type: square; display:grid; gap:0.4rem; font-weight:500; margin-top:0.5rem">
                    <li>Fixed Cargo Handling Fee: <strong style="color:var(--primary-color)">₱50</strong> flat rate for orders below ₱1,000.</li>
                    <li>Promotional Free Institutional Delivery: <strong style="color:var(--success-color)">₱0 (FREE)</strong> for orders total of ₱1,000 and higher.</li>
                </ul>
            </section>

            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">3. Proper Disposal of Expired Medicines</h3>
                <p>Receiving institutions are instructed to never discard unused chemical formulations into civic trash or water drains. Overaged lot stocks should either be surrendered to visiting PrimeCare logistics personnel during restocking routines, or processed through certified chemical/biological waste disposal services.</p>
            </section>

            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">4. Client License Requirements</h3>
                <p>Corporate account holders are required to submit their annual municipal health licenses, BFAD/FDA clearances, or clinical operating permits to PrimeCare account dispatch representatives prior to authorized order release.</p>
            </section>
        </div>

        <div style="margin-top:2rem; text-align:center">
            <a href="index.php" class="btn btn-primary" style="padding:0.75rem 2rem">Accept & Return to Catalog &raquo;</a>
        </div>
    </main>

    <footer class="footer" style="margin-top: 4rem">
        <p>&copy; 2026 PrimeCare Pharmaceutical Distributors. All Rights Reserved.</p>
    </footer>
</body>
</html>
