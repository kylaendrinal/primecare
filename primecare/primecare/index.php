<?php
/**
 * PrimeCare Pharmaceutical Distributors
 * Redesigned Premium About Us / Portal Gateway
 */
require_once __DIR__ . '/database/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrimeCare Pharmaceutical Distributors</title>
    <!-- Use core CSS stylesheet directly -->
    <link rel="stylesheet" href="css/style.css?v=2.5">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap">
</head>
<body class="pc-redesign-body">

    <!-- Glowing Pill Navigation Bar (Matches Attachment 1 Layout) -->
    <nav class="premium-capsule-nav">
        <div class="brand" style="cursor:pointer" onclick="window.location.href='index.php'">
            <div class="ppd-capsule-logo">
                <span>ppd</span>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="#about">About Us</a></li>
            <li><a href="#features">Services</a></li>
            <li><a href="#products">Products</a></li>
            <li><a href="#contact">Contacts</a></li>
        </ul>
        <div class="nav-actions-capsule">
            <a href="login.php" class="nav-login-link">Login</a>
            <a href="client/signup.php" class="nav-register-pill">Register</a>
        </div>
    </nav>

    <!-- Main Container -->
    <main style="max-width: 1200px; margin: 0 auto; width: 95%;">
        
        <!-- Hero Section with overlay team picture and bottom actions (Matches Attachment 1 Layout) -->
        <section class="premium-hero-card" id="home">
            <div class="hero-overlay-content">
                <h1 class="hero-headline">PRIMECARE PHARMACEUTICAL DISTRIBUTORS</h1>
            </div>
            
            <div class="hero-bottom-area">
                <!-- Left-aligned spacer to leave room for background group photo image and ground branding graphics -->
                <div style="flex: 1; min-width: 20px;"></div>
                
                <!-- Right-aligned content container housing the motto and straight "Shop Now ↗" button Pill -->
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 1.25rem; max-width: 580px; text-align: right;">
                    <p class="hero-motto" style="text-align: right;">
                        Delivering quality and affordable healthcare products since 2009, committed to reliable service and nationwide distribution
                    </p>
                    <div class="hero-cta" style="align-items: flex-end;">
                        <a href="#products" class="hero-shop-pill">
                            Shop Now ↗
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Us Section (Matches Attachment 2 Layout) -->
        <section id="about" class="about-section-view">
            <h2 class="about-title-large">About Us</h2>
            
            <div class="about-grid-card">
                <div class="about-description-box">
                    <p>
                        Founded in 2009, Primecare Pharmaceutical Distributor is your prime source for high-quality, affordable generic and branded medicines and medical supplies.
                    </p>
                    <p>
                        We are distributor of NELPA and PLCC products in the Philippines. Currently, serving different areas nationwide and shipping nationwide.
                    </p>
                </div>
                
                <!-- Medical team cover with custom user-provided image -->
                <div class="ppd-team-img-card">
                    <div style="background-color: #f1f5f9; border-radius: 20px; overflow: hidden; position: relative;">
                        <!-- Custom uploaded clinical support team or branded image -->
                        <img src="images/about-team.jpg" alt="About Primecare Support Team" style="width: 100%; height: auto; min-height: 280px; display: block; object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579684389782-64d84b5e901a?w=1600&auto=format&fit=crop&q=80';">
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Mission & Vision & 16th Anniversary (Matches Attachment 3 Layout) -->
        <section class="mv-section-row">
            <!-- Our Mission Card -->
            <div class="mv-card mv-card-left">
                <div class="mv-icon-container">
                    <!-- Configurable Mission Logo Image with Inline SVG Target/Bullseye Fallback -->
                    <img src="images/mission-icon.png" alt="Our Mission Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="fallback-icon" style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#001270" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="12" r="6"></circle>
                            <circle cx="12" cy="12" r="2"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <path d="M12 12l6-6M18 6h-4M18 6v4"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="mv-title">Our Mission</h3>
                <p class="mv-desc">
                    To provide every Filipino to high quality and reasonably priced medicines.
                </p>
            </div>

            <!-- Central 16th Anniversary Branding Column (Matches Reference Card Box Outline) -->
            <div class="mv-card-center-anniversary">
                <!-- User-provided graphic (which already contains text inside image, e.g., "16 TH ANNIVERSARY") -->
                <img src="images/anniversary-banner.png" alt="16th Anniversary Banner" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80';">
            </div>

            <!-- Our Vision Card -->
            <div class="mv-card mv-card-right">
                <div class="mv-icon-container">
                    <!-- Configurable Vision Logo Image with Inline SVG Eye Fallback -->
                    <img src="images/vision-icon.png" alt="Our Vision Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="fallback-icon" style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#001270" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </div>
                </div>
                <h3 class="mv-title">Our Vision</h3>
                <p class="mv-desc">
                    To reach every pharmacy, hospital, and distributor in the community by delivering exceptional customer and service, empowering both establishment and consumer while creating better opportunities for our partners
                </p>
            </div>
        </section>

    </main>

    <!-- Client Testimonials Sliding Panel (Matches Attachment 4 Layout) -->
    <section class="testimonials-panel">
        <h2>Client Testimonials</h2>
        
        <div class="testimonials-slider-row">
            <!-- Left Button -->
            <button class="carousel-arrow-btn" onclick="slidePrev()" aria-label="Previous testimonial">
                <span>&larr;</span>
            </button>
            
            <!-- Cards Stage Container (Allows overlapping 3D slider as seen in Attachment 4) -->
            <div class="testimonial-stage" id="testimonialStage">
                <!-- Slide 1: Ma'am Aisha Cardenas -->
                <div class="testi-slide" id="testi-slide-0">
                    <img src="images/testimonial-1.jpg" alt="Client Testimonial 1 - Aisha Cardenas" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1200&auto=format&fit=crop&q=80';">
                </div>
                <!-- Slide 2: Ma'am Ching De Los Reyes (Default Center) -->
                <div class="testi-slide" id="testi-slide-1">
                    <img src="images/testimonial-2.jpg" alt="Client Testimonial 2 - Ching De Los Reyes" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=1200&auto=format&fit=crop&q=80';">
                </div>
                <!-- Slide 3: Ma'am Liezl San Diego -->
                <div class="testi-slide" id="testi-slide-2">
                    <img src="images/testimonial-3.jpg" alt="Client Testimonial 3 - Liezl San Diego" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1580489944761-15a19d654956?w=1200&auto=format&fit=crop&q=80';">
                </div>
            </div>

            <!-- Right Button -->
            <button class="carousel-arrow-btn" onclick="slideNext()">
                <span>&rarr;</span>
            </button>
        </div>
    </section>

    <!-- Services Section with Orbit layout (Matches Attachment 5 Layout) -->
    <section id="features" class="services-orbit-view">
        <div style="text-align:center; margin-bottom:1rem">
            <h2 style="font-size:2.85rem; font-weight:800; color:#ffffff; margin-bottom:0.5rem">Our Services</h2>
        </div>
        
        <div class="services-outer-container">
            <!-- Center branding core -->
            <div class="services-center-core">
                <h3>Our<br>Services</h3>
            </div>
            
            <!-- Orbit Nodes with Image support and Beautiful Vector Fallbacks -->
            <!-- 1. Generic and Branded medicines -->
            <div class="service-orbit-node s-node-1">
                <div class="service-icon-bubble">
                    <img src="images/service-medicines.png" alt="Generic and Branded Medicines" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="service-fallback" style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <!-- Blue outline plus sign inside circular layout -->
                        <svg width="44" height="44" viewBox="0 0 44 44" fill="none" stroke="#0055ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="22" cy="22" r="18" fill="rgba(0, 85, 255, 0.05)"></circle>
                            <line x1="22" y1="14" x2="22" y2="30"></line>
                            <line x1="14" y1="22" x2="30" y2="22"></line>
                        </svg>
                    </div>
                </div>
                <h4 class="service-node-title">Generic and Branded Medicines</h4>
                <p class="service-node-desc">Quality medicines that work, affordable for consumers,<br>and profitable for businesses</p>
            </div>

            <!-- 2. Fast Delivery -->
            <div class="service-orbit-node s-node-2">
                <div class="service-icon-bubble">
                    <img src="images/service-delivery.png" alt="Fast Delivery" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="service-fallback" style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <!-- Delivery scooter / active delivery symbol -->
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#0055ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="6" cy="19" r="3" fill="rgba(0, 85, 255, 0.1)"></circle>
                            <circle cx="18" cy="19" r="3" fill="rgba(0, 85, 255, 0.1)"></circle>
                            <path d="M10 19h5M17 19h2c1.1 0 2-.9 2-2v-4l-3-4h-3M14 6h-4l-1 4H2v5a2 2 0 0 0 2 2h2"></path>
                            <path d="M12 2l-7 4"></path>
                        </svg>
                    </div>
                </div>
                <h4 class="service-node-title">Fast Delivery</h4>
                <p class="service-node-desc">Fast, reliable delivery straight to your door saving time, cost, and effort</p>
            </div>

            <!-- 3. Medical Supplies -->
            <div class="service-orbit-node s-node-3">
                <div class="service-icon-bubble">
                    <img src="images/service-supplies.png" alt="Medical Supplies" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="service-fallback" style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <!-- Elegant medical shield/cross or tools -->
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#0055ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="rgba(0, 85, 255, 0.05)"></path>
                            <line x1="12" y1="8" x2="12" y2="16"></line>
                            <line x1="9" y1="12" x2="15" y2="12"></line>
                        </svg>
                    </div>
                </div>
                <h4 class="service-node-title">Medical Supplies</h4>
                <p class="service-node-desc">Available on-hand and for pre-order</p>
            </div>

            <!-- 4. Nationwide Shipping -->
            <div class="service-orbit-node s-node-4">
                <div class="service-icon-bubble">
                    <img src="images/service-shipping.png" alt="Nationwide Shipping" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="service-fallback" style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <!-- Elegant Cargo Shipping vessel based on Image 2 logo design -->
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#0055ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 17l2 2h16l2-2" fill="rgba(0, 85, 255, 0.1)"></path>
                            <path d="M5 17V9h3v8M11 17V4h3v13M16 17v-5h3v5"></path>
                            <path d="M22 21H2"></path>
                        </svg>
                    </div>
                </div>
                <h4 class="service-node-title">Nationwide Shipping</h4>
                <p class="service-node-desc">Primecare ships anywhere in the Philippines</p>
            </div>

            <!-- 5. Customer Care -->
            <div class="service-orbit-node s-node-5">
                <div class="service-icon-bubble">
                    <img src="images/service-customer.png" alt="Customer Care" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="service-fallback" style="display: none; align-items: center; justify-content: center; width: 100%; height: 100%;">
                        <!-- Heart care / Hands logo as depicted in Image 2 -->
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#0055ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="rgba(0, 85, 255, 0.05)"></path>
                        </svg>
                    </div>
                </div>
                <h4 class="service-node-title">Customer Care</h4>
                <p class="service-node-desc">Primecare ensures exceptional customer care through dedicated Territory Managers</p>
            </div>
        </div>
    </section>

    <!-- Our Products Section (Dynamic Catalog, Matches Attachment 6 Layout) -->
    <section id="products" class="products-redesign-section">
        <div class="products-container-inner">
            <div style="text-align:center; margin-bottom:3.5rem">
                <h2 style="font-size:3rem; font-weight:800; color:#1a0b73">Our Products</h2>
            </div>

            <!-- Dynamic Search and Filter bar (Maintained 100% functionality) -->
            <div class="search-filter-bar" style="max-width: 900px; margin: 0 auto 3rem; display: flex; gap: 1rem; flex-wrap: wrap; background:#ffffff; border-radius:18px; padding:1rem; box-shadow:0 8px 20px rgba(0, 18, 112, 0.05)">
                <div class="form-group-flex" style="flex: 2; min-width: 250px; display: flex; gap: 0.5rem;">
                    <input type="text" id="productSearchInput" placeholder="Search medicine by name, category, or therapeutic keyword..." style="box-shadow: none; border:1px solid #cbd5e1; width:100%; border-radius:12px; padding:0.75rem 1rem; flex: 1;">
                    <button id="productSearchBtn" style="background: #1a0b73; color: white; border: none; border-radius: 12px; padding: 0.75rem 1.5rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; transition: background 0.2s ease; height: 100%; white-space: nowrap;" onmouseover="this.style.background='#2a1b83'" onmouseout="this.style.background='#1a0b73'">
                        🔍 Search
                    </button>
                </div>
                <div class="form-group-flex" style="flex: 1; min-width: 180px;">
                    <select id="productCategoryFilter" style="box-shadow: none; border:1px solid #cbd5e1; width:100%; border-radius:12px; padding:0.75rem">
                        <option value="">All Categories</option>
                        <option value="Tablet">Tablets</option>
                        <option value="Capsule">Capsules</option>
                        <option value="Suspension">Suspensions</option>
                        <option value="Ampule">Ampules</option>
                        <option value="Nebule">Nebules</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Medicines Database Grid -->
            <div class="product-catalog-grid" id="homepageMedicinesGrid">
                <?php 
                $result = mysqli_query($conn, "SELECT * FROM medicines");
                $medicines = [];
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $medicines[] = $row;
                    }
                }
                ?>
                <?php if (empty($medicines)): ?>
                    <!-- Empty State fallback -->
                    <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem; background-color: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); width:100%">
                        <h4 style="font-size: 1.15rem; font-weight: 700; color: #1e3a8a; margin-bottom: 0.25rem;">No Pharmacological Compounds Catalogued</h4>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">The inventory database is currently empty. Please import a CSV compound sheet in administrative portal to build catalogue records.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($medicines as $index => $med): ?>
                        <?php 
                            $isAvailable = ($med['stock'] > 0);
                            $statusClass = $isAvailable ? 'badge-available' : 'badge-outofstock';
                            $statusLabel = $isAvailable ? 'In Stock' : 'Out of Stock';
                            $stockLabel = $isAvailable ? htmlspecialchars($med['stock']) . ' units available' : 'no stock';
                            $imgUrl = getMedicineImageSrc($med['image'], 'uploads/medicines/');
                            $detailPage = "login.php";
                            if (isset($_SESSION['user_id'])) {
                                $detailPage = "client/medicine_details.php?id=" . urlencode($med['id']);
                            }
                            $isExtra = ($index >= 8);
                        ?>
                        <!-- Individual medicine card matching design exactly -->
                        <div class="ppd-product-card php-med-card <?php echo $isExtra ? 'extra-med' : ''; ?>" 
                             data-name="<?php echo htmlspecialchars(strtolower($med['name'])); ?>"
                             data-category="<?php echo htmlspecialchars($med['category']); ?>"
                             data-description="<?php echo htmlspecialchars(strtolower($med['description'] ?? '')); ?>"
                             <?php if ($isExtra): ?>style="display: none;"<?php endif; ?>
                             onclick="window.location.href='<?php echo $detailPage; ?>'">
                            
                            <div class="ppd-product-img-holder">
                                <!-- Medication visual image file link -->
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($med['name']); ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=300&auto=format&fit=crop&q=60';">
                            </div>

                            <div class="ppd-product-info">
                                <div>
                                    <div class="ppd-product-title-row">
                                        <h3><?php echo htmlspecialchars($med['name']); ?></h3>
                                        <?php 
                                            $catRaw = $med['category'] ?? '';
                                            $catCap = !empty($catRaw) ? ucfirst(strtolower($catRaw)) : '';
                                        ?>
                                        <span class="ppd-product-form-span"><?php echo htmlspecialchars($catCap); ?></span>
                                    </div>
                                    <p class="ppd-product-desc"><?php echo htmlspecialchars($med['description'] ?? 'Certified pharmaceutical-grade medical compounds registered for wholesale supply chain distribution.'); ?></p>
                                </div>
                                
                                <div class="ppd-product-action-row">
                                    <span class="stock-indicator" style="color: <?php echo $isAvailable ? '#16a34a' : '#ef4444'; ?>"><?php echo $stockLabel; ?></span>
                                    <span class="inquire-arrow">Inquire&rarr;</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (count($medicines) > 8): ?>
            <div style="text-align:center" id="exploreBtnContainer">
                <button class="explore-btn-capsule" id="exploreAllProductsBtn">
                    Explore All Products &rarr;
                </button>
            </div>
            <?php endif; ?>
        </div>
    </section>



    <!-- Redesigned Contact & Premium Footer (Matches Attachment 6 bottom layouts) -->
    <footer class="premium-ppd-footer" id="contact">
        <div class="footer-main-row">
            <div class="footer-company-info">
                <h2>PRIMECARE PHARMACEUTICAL DISTRIBUTORS</h2>
                <p>Committed to reliable service and nationwide distribution</p>
                
                <div class="footer-contact-details">
                    <div class="footer-contact-item">
                        <span class="icon">📍</span>
                        <div><strong>Manila Office:</strong><br>#1575 Mayhaligue St., Sta. Cruz, Manila</div>
                    </div>
                    <div class="footer-contact-item">
                        <span class="icon">📍</span>
                        <div><strong>Bulacan Warehouse Office:</strong><br>#2426 Halang, Parong-Parong, San Agustin, Hagonoy, Bulacan 3002</div>
                    </div>
                    <div class="footer-contact-item">
                        <span class="icon">✉️</span>
                        <div>primecarebulacan@gmail.com</div>
                    </div>
                    <div class="footer-contact-item">
                        <span class="icon">📞</span>
                        <div>0917-155-8618 / 0917-650-1299</div>
                    </div>
                </div>
            </div>
            
            <div class="footer-menu-links">
                <a href="#home">HOME</a>
                <a href="#about">ABOUT US</a>
                <a href="#features">SERVICES</a>
                <a href="#products">PRODUCTS</a>
            </div>
        </div>
        
        <hr class="footer-credits-divider">
        
        <div class="footer-bottom-flex">
            <div>&copy; 2026 PrimeCare Pharmaceutical Distributors. All rights reserved.</div>
            <div class="footer-bottom-links">
                <a href="javascript:void(0)" onclick="openDocsModal('terms')">Terms of Distribution Agreement</a>
                <span style="color: rgba(255, 255, 255, 0.2); margin: 0 8px;">|</span>
                <a href="javascript:void(0)" onclick="openDocsModal('guidelines')">Procurement & Shipping Guidelines</a>
            </div>
        </div>
    </footer>

    <!-- Carousel Javascript Slider Logic -->
    <script>
        let currentActiveIdx = 1; // Ching De Los Reyes is default active center slide
        
        function updateCarouselUI() {
            const slideCount = 3;
            for (let i = 0; i < slideCount; i++) {
                const card = document.getElementById("testi-slide-" + i);
                if (!card) continue;
                
                card.classList.remove("testi-slide-active", "testi-slide-left", "testi-slide-right");
                
                if (i === currentActiveIdx) {
                    card.classList.add("testi-slide-active");
                } else if (i === (currentActiveIdx + 1) % slideCount) {
                    card.classList.add("testi-slide-left");
                } else {
                    card.classList.add("testi-slide-right");
                }
            }
        }
        
        function slidePrev() {
            currentActiveIdx = (currentActiveIdx - 1 + 3) % 3;
            updateCarouselUI();
        }
        
        function slideNext() {
            currentActiveIdx = (currentActiveIdx + 1) % 3;
            updateCarouselUI();
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateCarouselUI();
        });
    </script>

    <!-- Instant Search & Category Filtering JS -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("productSearchInput");
            const categoryFilter = document.getElementById("productCategoryFilter");
            const medCards = document.querySelectorAll(".php-med-card");
            let isExpanded = false;

            function filterMedicines() {
                const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
                const category = categoryFilter ? categoryFilter.value.toLowerCase().trim() : "";

                medCards.forEach(card => {
                    const name = (card.getAttribute("data-name") || "").toLowerCase().trim();
                    const cat = (card.getAttribute("data-category") || "").toLowerCase().trim();
                    const desc = (card.getAttribute("data-description") || "").toLowerCase().trim();
                    const isExtra = card.classList.contains("extra-med");

                    const matchesSearch = query === "" || name.includes(query) || desc.includes(query) || cat.includes(query);
                    const matchesCategory = category === "" || cat === category || cat.includes(category) || category.includes(cat);

                    if (matchesSearch && matchesCategory) {
                        if (isExtra && !isExpanded) {
                            card.style.display = "none";
                        } else {
                            card.style.display = "flex";
                        }
                    } else {
                        card.style.display = "none";
                    }
                });
            }

            if (searchInput) {
                searchInput.addEventListener("input", filterMedicines);
                searchInput.addEventListener("keydown", function(e) {
                    if (e.key === "Enter") {
                        filterMedicines();
                    }
                });
            }
            const searchBtn = document.getElementById("productSearchBtn");
            if (searchBtn) {
                searchBtn.addEventListener("click", filterMedicines);
            }
            if (categoryFilter) categoryFilter.addEventListener("change", filterMedicines);

            const exploreBtn = document.getElementById("exploreAllProductsBtn");
            if (exploreBtn) {
                exploreBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    isExpanded = true;
                    filterMedicines();
                    exploreBtn.style.display = "none";
                });
            }

            // Dynamic Active Navigation Item styling (Scrollspy & Click detection)
            const navLinks = document.querySelectorAll(".nav-links a");
            const sections = document.querySelectorAll("section[id], footer[id]");

            function updateActiveNav() {
                let currentId = "";
                const scrollPos = window.scrollY + 180;

                sections.forEach(sec => {
                    const secTop = sec.offsetTop;
                    const secHeight = sec.offsetHeight;
                    if (scrollPos >= secTop && scrollPos < secTop + secHeight) {
                        currentId = sec.getAttribute("id");
                    }
                });

                if ((window.innerHeight + window.scrollY) >= document.documentElement.scrollHeight - 60) {
                    currentId = "contact";
                }

                navLinks.forEach(link => {
                    link.classList.remove("nav-link-active-yellow");
                    if (link.getAttribute("href") === `#${currentId}`) {
                        link.classList.add("nav-link-active-yellow");
                    }
                });
            }

            navLinks.forEach(link => {
                link.addEventListener("click", function() {
                    navLinks.forEach(l => l.classList.remove("nav-link-active-yellow"));
                    this.classList.add("nav-link-active-yellow");
                });
            });

            window.addEventListener("scroll", updateActiveNav, { passive: true });
            window.addEventListener("resize", updateActiveNav);
            updateActiveNav();
        });

        // Interactive Terms & Guidelines overlay modal controller
        function openDocsModal(documentType) {
            const modal = document.getElementById("docsModal");
            const title = document.getElementById("modalTitle");
            const subtitle = document.getElementById("modalSubtitle");
            const content = document.getElementById("modalTextContent");
            if (!modal || !title || !subtitle || !content) return;

            if (documentType === 'terms') {
                title.innerText = "Wholesale Terms & Distribution Agreements";
                subtitle.innerText = "Last updated: June 2026. Governing FDA regulatory standards.";
                content.innerHTML = `
                    <section>
                        <h3>1. Scope of Institutional Wholesale Delivery</h3>
                        <p>PrimeCare Pharmaceutical Distributors supplies licensed hospitals, healthcare centers, clinical institutions, and wholesale medicine distributors. No retail individual client accounts are permitted. By setting up a client account, you certify that your organization holds active medical dispatch or dispensing licenses in compliance with local regulations.</p>
                    </section>
                    <section>
                        <h3>2. Ordering Limits & Bulk Quantities</h3>
                        <p>Wholesale transaction minimum orders are measured in whole Box Units (Bxs). For security and distribution logistics, custom orders are verified by admin dispatchers before scheduling. Unverified listings, custom medical stocks, or bulk liquid medicines may require authorized secondary clearance papers.</p>
                    </section>
                    <section>
                        <h3>3. Temperature Control & Cold Storage Liabilities</h3>
                        <p>Certain items (insulin, biologicals, customized vaccines) are dispatched in thermal vault boxes. PrimeCare is fully responsible for cold chain logistics up to institutional reception. Upon receipt and successful digital signature, responsibility for proper clinical refrigeration limits transfers completely to the receiving clinical entity.</p>
                    </section>
                    <section>
                        <h3>4. Expiry Batch Replacements</h3>
                        <p>We guarantee all dispatched medicines hold a minimum of twelve (12) months validity upon delivery. Under custom distributor provisions, short-expiry products can be pre-authorized for returns or batch replacement swap requests up to 60 days strictly prior to the stamped expiry date.</p>
                    </section>
                    <section>
                        <h3>5. Privacy & Data Handling Notice</h3>
                        <p>Information collected inside our wholesale database system including institutional credentials, shipping coordinates, mobile numbers, and transactional ledger balances is stored encrypted in our server arrays. We never sell contact information to external medical research firms.</p>
                    </section>
                `;
            } else if (documentType === 'guidelines') {
                title.innerText = "Pharmacy Procurement Guidelines";
                subtitle.innerText = "Quality Assurance Protocols across WHO Good Distribution Practice (GDP).";
                content.innerHTML = `
                    <section>
                        <h3>1. Quality Inspection Stamps</h3>
                        <p>Every single batch cataloged into PrimeCare is inspected for brand packaging integrity, seal protection, correct pharmaceutical active ingredient percentage, and batch certificate numbers. All medicines are stored strictly at optimal warehousing environments (controlled RH below 60% and temperature ranges matching product requirements).</p>
                    </section>
                    <section>
                        <h3>2. Bulk Shipping Charge Policies</h3>
                        <p>To incentivize clinical bulk operations, PrimeCare enforces simple, transparent flat shipping parameters:</p>
                        <ul style="margin-left: 1.5rem; list-style-type: square; display: grid; gap: 0.5rem; font-weight: 500; margin-top: 0.5rem; margin-bottom: 1rem;">
                            <li>Fixed Cargo Handling Fee: <strong style="color: #001270">₱50</strong> flat rate for orders below ₱1,000.</li>
                            <li>Promotional Free Institutional Delivery: <strong style="color: #16a34a">₱0 (FREE)</strong> for orders total of ₱1,000 and higher.</li>
                        </ul>
                    </section>
                    <section>
                        <h3>3. Proper Disposal of Expired Medicines</h3>
                        <p>Receiving institutions are instructed to never discard unused chemical formulations into civic trash or water drains. Overaged lot stocks should either be surrendered to visiting PrimeCare logistics personnel during restocking routines, or processed through certified chemical/biological waste disposal services.</p>
                    </section>
                    <section>
                        <h3>4. Client License Requirements</h3>
                        <p>Corporate account holders are required to submit their annual municipal health licenses, BFAD/FDA clearances, or operating permits prior to authorized order release.</p>
                    </section>
                `;
            }

            modal.classList.add("active");
            document.body.style.overflow = "hidden";
        }

        function closeDocsModal() {
            const modal = document.getElementById("docsModal");
            if (modal) {
                modal.classList.remove("active");
                document.body.style.overflow = "";
            }
        }

        // Close modal on outer background click
        window.addEventListener('click', function(e) {
            const modal = document.getElementById("docsModal");
            if (e.target === modal) {
                closeDocsModal();
            }
        });
    </script>

    <!-- Document Modal Popup Overlay Markup -->
    <div class="docs-modal-overlay" id="docsModal">
        <div class="docs-modal-card">
            <button class="docs-modal-close-btn" onclick="closeDocsModal()">&times;</button>
            <h2 class="docs-modal-title" id="modalTitle">Document Title</h2>
            <div class="docs-modal-subtitle" id="modalSubtitle">Subheading info...</div>
            <div class="docs-modal-content" id="modalTextContent">
                <!-- Content injected dynamically -->
            </div>
        </div>
    </div>

    <script src="js/script.js?v=2.1"></script>
</body>
</html>
                        