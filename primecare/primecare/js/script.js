/**
 * PrimeCare Pharmaceutical Distributors
 * General Frontend Interactions Helper
 */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Dynamic Client Side Medicine Filtering & Searching
    const searchInput = document.getElementById("searchMed");
    const categorySelect = document.getElementById("filterCategory");

    function filterMedicines() {
        const liveSearchInput = document.getElementById("searchMed");
        const liveCategorySelect = document.getElementById("filterCategory");
        if (!liveSearchInput && !liveCategorySelect) return;

        const searchText = liveSearchInput ? liveSearchInput.value.toLowerCase().trim() : "";
        const selectedCategory = liveCategorySelect ? liveCategorySelect.value.toLowerCase().trim() : "all";
        
        const cards = document.querySelectorAll(".med-card, .ppd-product-card");

        cards.forEach(card => {
            const titleEl = card.querySelector(".med-title");
            const categoryEl = card.querySelector(".med-category-badge, .ppd-product-form-span");
            const descEl = card.querySelector(".ppd-product-desc");

            // Extract values from DOM text
            const medName = titleEl ? titleEl.textContent.toLowerCase().trim() : "";
            const medCategory = categoryEl ? categoryEl.textContent.toLowerCase().trim() : "";
            const medDesc = descEl ? descEl.textContent.toLowerCase().trim() : "";

            // Extract values from card data attributes (added in HTML for robust matching of both name and brand)
            const nameAttr = card.getAttribute("data-name") ? card.getAttribute("data-name").toLowerCase().trim() : "";
            const brandAttr = card.getAttribute("data-brand") ? card.getAttribute("data-brand").toLowerCase().trim() : "";
            const categoryAttr = card.getAttribute("data-category") ? card.getAttribute("data-category").toLowerCase().trim() : "";
            const descAttr = card.getAttribute("data-description") ? card.getAttribute("data-description").toLowerCase().trim() : "";

            // Matches search text if found in name, brand, category, description, or data attributes
            const matchSearch = searchText === "" || 
                                medName.includes(searchText) || 
                                medCategory.includes(searchText) || 
                                medDesc.includes(searchText) ||
                                nameAttr.includes(searchText) ||
                                brandAttr.includes(searchText) ||
                                categoryAttr.includes(searchText) ||
                                descAttr.includes(searchText);

            // Matches category if "all" or matching categories (either via text or attribute)
            const matchCategory = selectedCategory === "all" || 
                                  medCategory === selectedCategory || 
                                  categoryAttr === selectedCategory ||
                                  medCategory.includes(selectedCategory) ||
                                  categoryAttr.includes(selectedCategory) ||
                                  selectedCategory.includes(medCategory) ||
                                  selectedCategory.includes(categoryAttr);

            if (matchSearch && matchCategory) {
                card.style.display = "flex";
            } else {
                card.style.display = "none";
            }
        });
    }

    if (searchInput) {
        // Run on typing, changes, and keyup for instant responsiveness
        searchInput.addEventListener("input", filterMedicines);
        searchInput.addEventListener("keyup", filterMedicines);
        searchInput.addEventListener("change", filterMedicines);
        searchInput.addEventListener("keydown", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                filterMedicines();
            }
        });
    }
    const medSearchBtn = document.getElementById("medSearchBtn");
    if (medSearchBtn) {
        medSearchBtn.addEventListener("click", function(e) {
            e.preventDefault();
            filterMedicines();
        });
    }
    if (categorySelect) {
        categorySelect.addEventListener("change", filterMedicines);
    }

    // Run once initially to sync in case the inputs are pre-filled on load (e.g. back navigation or browser autofill)
    filterMedicines();

    // 2. Real-time Search for Admin Tables
    const adminSearchInput = document.getElementById("adminTableSearch");
    if (adminSearchInput) {
        adminSearchInput.addEventListener("input", function () {
            const searchText = this.value.toLowerCase().trim();
            const tableRows = document.querySelectorAll("tbody tr");

            tableRows.forEach(row => {
                const textContent = row.textContent.toLowerCase();
                if (textContent.includes(searchText)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    }

    // 3. Confirm Delete Prompts
    const deleteBtnList = document.querySelectorAll(".confirm-delete");
    deleteBtnList.forEach(btn => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            const href = this.getAttribute("href");
            const form = this.closest("form");
            window.primecare_confirm("Are you absolutely sure you want to delete this record? This action cannot be undone.", function () {
                if (href) {
                    window.location.href = href;
                } else if (form) {
                    form.submit();
                }
            });
        });
    });

    // 4. Client Side File Size / Typelist Validation for CSV Uploads
    const csvFileInput = document.getElementById("csvFile");
    if (csvFileInput) {
        csvFileInput.addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                const extension = file.name.split('.').pop().toLowerCase();
                if (extension !== 'csv') {
                    window.primecare_toast("Please select a valid CSV file (.csv)", "danger");
                    this.value = "";
                }
            }
        });
    }
});

// ============================================
// PRIMCARE CUSTOM POPUP & TOAST NOTIFICATIONS
// ============================================
(function() {
    const customStyles = `
        .pc-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
            pointer-events: none;
        }
        .pc-toast {
            background: #ffffff;
            color: #1e293b;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #3b82f6;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: pcSlideIn 0.3s ease forwards;
            pointer-events: auto;
            opacity: 0.95;
        }
        .pc-toast-success { border-left-color: #10b981; }
        .pc-toast-danger { border-left-color: #ef4444; }
        .pc-toast-warning { border-left-color: #f59e0b; }
        
        @keyframes pcSlideIn {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes pcFadeOut {
            to { transform: translateY(-20px); opacity: 0; }
        }
        
        /* Custom Modal Confirm */
        .pc-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            font-family: 'Inter', sans-serif;
            animation: pcFadeIn 0.2s ease;
        }
        .pc-modal {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 440px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: pcScaleIn 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .pc-modal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .pc-modal-body {
            font-size: 0.95rem;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .pc-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .pc-btn {
            padding: 8px 18px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .pc-btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }
        .pc-btn-cancel:hover { background: #e2e8f0; }
        .pc-btn-confirm {
            background: #ef4444;
            color: #ffffff;
        }
        .pc-btn-confirm:hover { background: #dc2626; }
        
        @keyframes pcFadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pcScaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    `;

    // Inject Styles
    const styleEl = document.createElement("style");
    styleEl.textContent = customStyles;
    document.head.appendChild(styleEl);

    // Create Container on Dom load
    document.addEventListener("DOMContentLoaded", function() {
        let container = document.querySelector(".pc-toast-container");
        if (!container) {
            container = document.createElement("div");
            container.className = "pc-toast-container";
            document.body.appendChild(container);
        }
    });

    // Global Functions exposure
    window.primecare_toast = function(message, type = 'success') {
        let container = document.querySelector(".pc-toast-container");
        if (!container) {
            container = document.createElement("div");
            container.className = "pc-toast-container";
            document.body.appendChild(container);
        }
        const toast = document.createElement("div");
        toast.className = `pc-toast pc-toast-${type}`;
        
        let icon = "💡";
        if (type === 'success') icon = "✅";
        if (type === 'danger') icon = "❌";
        if (type === 'warning') icon = "⚠️";
        
        toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = "pcFadeOut 0.3s ease forwards";
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    window.primecare_confirm = function(message, onConfirm) {
        const backdrop = document.createElement("div");
        backdrop.className = "pc-modal-backdrop";
        
        backdrop.innerHTML = `
            <div class="pc-modal">
                <div class="pc-modal-title">Confirm Action</div>
                <div class="pc-modal-body">${message}</div>
                <div class="pc-modal-actions">
                    <button type="button" class="pc-btn pc-btn-cancel" id="pcConfirmCancel">Cancel</button>
                    <button type="button" class="pc-btn pc-btn-confirm" id="pcConfirmOk">Confirm</button>
                </div>
            </div>
        `;
        document.body.appendChild(backdrop);
        
        document.getElementById("pcConfirmCancel").onclick = function() {
            backdrop.remove();
        };
        document.getElementById("pcConfirmOk").onclick = function() {
            backdrop.remove();
            onConfirm();
        };
    };

    // Define fly to cart animation
    if (!window.primecare_flyToCart) {
        window.primecare_flyToCart = function(startEl) {
            const cartEl = document.querySelector('.premium-capsule-nav a[href*="cart.php"]') || document.querySelector('a[href*="cart.php"]');
            if (!startEl || !cartEl) return;

            // Resolve Event to Element if needed
            let el = startEl;
            if (startEl && startEl.currentTarget) {
                el = startEl.currentTarget;
            } else if (startEl && startEl.target) {
                el = startEl.target;
            }

            if (!el || typeof el.getBoundingClientRect !== 'function') return;

            const startRect = el.getBoundingClientRect();
            const cartRect = cartEl.getBoundingClientRect();

            // Create floating flyer container
            const flyer = document.createElement("div");
            flyer.innerHTML = "💊";
            flyer.style.cssText = `
                position: fixed;
                left: ${startRect.left + startRect.width / 2 - 15}px;
                top: ${startRect.top + startRect.height / 2 - 15}px;
                width: 30px;
                height: 30px;
                font-size: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 100000;
                pointer-events: none;
                transition: transform 0.6s cubic-bezier(0.19, 1, 0.22, 1), left 0.6s cubic-bezier(0.19, 1, 0.22, 1), top 0.6s cubic-bezier(0.19, 1, 0.22, 1), opacity 0.6s ease;
            `;
            document.body.appendChild(flyer);

            // Force reflow
            flyer.offsetWidth;

            // Animate
            flyer.style.left = `${cartRect.left + cartRect.width / 2 - 15}px`;
            flyer.style.top = `${cartRect.top + cartRect.height / 2 - 15}px`;
            flyer.style.transform = "scale(0.3) rotate(360deg)";
            flyer.style.opacity = "0.7";

            setTimeout(() => {
                flyer.remove();
                // Pulse the Cart Link / Badge!
                cartEl.style.transition = "transform 0.15s ease-in-out";
                cartEl.style.transform = "scale(1.25)";
                setTimeout(() => {
                    cartEl.style.transform = "scale(1)";
                }, 150);
            }, 600);
        };
    }

    // Intercept all traditional "Add to Cart" form submissions to use background AJAX and animation
    function initCartInterception() {
        const cartForms = document.querySelectorAll('form[action*="cart.php"]');
        cartForms.forEach(form => {
            if (form.dataset.ajaxIntercepted === "true") return;

            const actionInput = form.querySelector('[name="action"]');
            const hasAddAction = (actionInput && actionInput.value === 'add') || 
                                 (form.action && form.action.indexOf('action=add') !== -1) ||
                                 (form.getAttribute('action') && form.getAttribute('action').indexOf('action=add') !== -1);

            if (hasAddAction) {
                form.dataset.ajaxIntercepted = "true";
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const idInput = form.querySelector('[name="id"]');
                    const qtyInput = form.querySelector('[name="qty"]');
                    
                    let medId = idInput ? idInput.value : '';
                    if (!medId) {
                        const urlParams = new URLSearchParams(window.location.search);
                        medId = urlParams.get('id') || '';
                    }
                    
                    const qty = qtyInput ? qtyInput.value : 1;
                    const submitBtn = form.querySelector('[type="submit"]') || form.querySelector('button');
                    
                    if (!medId) {
                        form.submit();
                        return;
                    }

                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('loading-state');
                    }

                    fetch(`cart.php?action=add&id=${medId}&qty=${qty}&ajax=1`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response not OK');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.classList.remove('loading-state');
                            }

                            if (data.success) {
                                // Update Navbar Badge
                                const cartLinks = document.querySelectorAll('a[href*="cart.php"]');
                                cartLinks.forEach(cartLink => {
                                    const span = cartLink.querySelector('span');
                                    if (span) {
                                        span.textContent = `(${data.cart_count})`;
                                    }
                                });

                                // Trigger Flying animation
                                if (window.primecare_flyToCart) {
                                    window.primecare_flyToCart(submitBtn || form);
                                }

                                window.primecare_toast(data.message || "Added to cart successfully!", "success");
                            } else {
                                window.primecare_toast(data.message || "Unable to add item to cart.", "danger");
                            }
                        })
                        .catch(err => {
                            console.error("AJAX Cart Error (Falling back to prototype state):", err);
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.classList.remove('loading-state');
                            }
                            // Fallback to client-side localStorage prototype database so there are no broken page redirects
                            if (window.db && typeof window.db.addToCart === 'function') {
                                const isAdded = window.db.addToCart(medId, qty);
                                if (isAdded) {
                                    if (window.primecare_updateCountBadge) {
                                        window.primecare_updateCountBadge();
                                    }
                                    if (window.primecare_flyToCart) {
                                        window.primecare_flyToCart(submitBtn || form);
                                    }
                                    window.primecare_toast("Medicine successfully added to your Cart! Click 'Cart' to checkout.", "success");
                                } else {
                                    window.primecare_toast("Unable to add item to cart. Stock is insufficient or item is invalid.", "danger");
                                }
                            } else {
                                window.primecare_toast("Added to cart (simulation mode).", "success");
                            }
                        });
                });
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initCartInterception);
    } else {
        initCartInterception();
    }
    window.addEventListener("load", initCartInterception);
})();

// Dynamic Hamburger Button Integration
(function() {
    function setupHamburger() {
        const nav = document.querySelector('.premium-capsule-nav');
        if (nav && !nav.querySelector('.nav-hamburger-btn')) {
            const hamburger = document.createElement('button');
            hamburger.className = 'nav-hamburger-btn';
            hamburger.innerHTML = '<span></span><span></span><span></span>';
            hamburger.setAttribute('aria-label', 'Toggle navigation');
            nav.appendChild(hamburger);
            
            hamburger.addEventListener('click', function(e) {
                e.stopPropagation();
                nav.classList.toggle('nav-open');
                document.body.classList.toggle('nav-menu-open');
            });

            // Close menu if clicking outside of it
            document.addEventListener('click', function(e) {
                if (nav.classList.contains('nav-open') && !nav.contains(e.target)) {
                    nav.classList.remove('nav-open');
                    document.body.classList.remove('nav-menu-open');
                }
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setupHamburger);
    } else {
        setupHamburger();
    }
    window.addEventListener("load", setupHamburger);
})();
