<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Terms of Service & General Conditions
 */
require_once __DIR__ . '/database/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Wholesale Agreements - PrimeCare</title>
    <link rel="stylesheet" href="css/style.css?v=2.1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
</head>
<body>
    <nav class="navbar">
        <div class="brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-color)"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            <span>PrimeCare Logistics</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Catalog Home</a></li>
            <li><a href="client/login.php">Login Portal</a></li>
        </ul>
    </nav>

    <main class="container" style="max-width: 800px; padding: 2rem 1rem">
        <div style="margin-bottom: 2rem">
            <h1 style="font-size:2.5rem; color:var(--primary-dark); font-weight:800; margin-bottom:0.5rem">Wholesale Terms & Distribution Agreements</h1>
            <p style="color:var(--text-muted); font-size:0.95rem">Last updated: June 2026. Governing Food & Drug Administration (FDA) regulatory distribution compliance standards.</p>
        </div>

        <div style="background:#fff; border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:2rem; display:grid; gap:1.5rem; line-height:1.6; color:var(--text-color)">
            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">1. Scope of Institutional Wholesale Delivery</h3>
                <p>PrimeCare Pharmaceutical Distributors supplies licensed hospitals, healthcare centers, clinical institutions, and wholesale medicine distributors. No retail individual client accounts are permitted. By setting up a client account, you certify that your organization holds active medical dispatch or dispensing licenses in compliance with local regulations.</p>
            </section>

            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">2. Ordering Limits & Bulk Quantities</h3>
                <p>Wholesale transaction minimum orders are measured in whole Box Units (Bxs). For security and distribution logistics, custom orders are verified by admin dispatchers before scheduling. Unverified listings, custom medical stocks, or bulk liquid medicines may require authorized secondary clearance papers.</p>
            </section>

            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">3. Temperature Control & Cold Storage Liabilities</h3>
                <p>Certain items (insulin, biologicals, customized vaccines) are dispatched in thermal vault boxes. PrimeCare is fully responsible for cold chain logistics up to institutional reception. Upon receipt and successful digital signature, responsibility for proper clinical refrigeration limits transfers completely to the receiving clinical entity.</p>
            </section>

            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">4. Expiry Batch Replacements</h3>
                <p>We guarantee all dispatched medicines hold a minimum of twelve (12) months validity upon delivery. Under custom distributor provisions, short-expiry products can be pre-authorized for returns or batch replacement swap requests up to 60 days strictly prior to the stamped expiry date.</p>
            </section>

            <section>
                <h3 style="font-size:1.2rem; color:var(--primary-color); font-weight:700; margin-bottom:0.5rem">5. Privacy & Data Handling Notice</h3>
                <p>Information collected inside our wholesale database system including institutional credentials, shipping coordinates, mobile numbers, and transactional ledger balances is stored encrypted in our server arrays. We never sell contact information to external medical research firms.</p>
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
