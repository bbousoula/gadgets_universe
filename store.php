<?php
session_start();
require_once 'config/db.php';
require_once 'image_upload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("SELECT quantity FROM gadgets WHERE id = :id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product && $product['quantity'] > 0) {
        if (isset($_SESSION['cart'][$product_id])) { $_SESSION['cart'][$product_id]++; }
        else { $_SESSION['cart'][$product_id] = 1; }
    }
    header("Location: store.php" . (isset($_GET['cat']) ? "?cat=" . urlencode($_GET['cat']) : ""));
    exit();
}

$selected_category = isset($_GET['cat']) ? trim($_GET['cat']) : 'All';

try {
    if ($selected_category !== 'All') {
        $stmt = $conn->prepare("SELECT * FROM gadgets WHERE category = :cat ORDER BY name ASC");
        $stmt->execute([':cat' => $selected_category]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM gadgets ORDER BY name ASC");
        $stmt->execute();
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cat_stmt = $conn->prepare("SELECT DISTINCT category FROM gadgets ORDER BY category ASC");
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    die("Error loading configuration: " . $e->getMessage());
}

function product_images(string $raw): array {
    $list = parse_stored_images($raw);
    return $list ?: ['images/placeholder.png'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gadgets Store - Catalog View</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ==================== Page container (Fixed to Screen) ==================== */
        body > .crud-container {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
            margin: 0 auto;
            height: 100vh;
            overflow: hidden; 
            display: flex;
            flex-direction: column;
            background: transparent !important; /* Forces background to show */
        }

        /* ==================== Sticky Header ==================== */
        .nav-header {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            padding: 15px 10px;
            gap: 15px;
            width: 100%;
            z-index: 999;
            position: sticky;
            top: 0;
            
            /* Light frosted glass to blend with new background */
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .header-left, .header-right {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .header-left  { justify-content: flex-start; }
        .header-right { justify-content: flex-end; }
        .header-center {
            flex: 0 0 auto;
            display: flex;
            /* justify-content: center; */
            align-items: center;
        }
        .site-logo {
            /* height: 75px; */
            width: auto;
            object-fit: contain;
            background: white;
            padding: 4px 10px;
            border-radius: 6px;
            box-sizing: content-box;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* ==================== Category filters ==================== */
        .filter-strip {
            flex-shrink: 0;
            display: flex;
            gap: 10px;
            margin: 20px 0 15px 0;
            flex-wrap: wrap;
            justify-content: center;
            width: 100%;

            margin: 0 auto;
            margin-top: 20px;
            margin-bottom: 20px;

            @media (max-width: 640px) {
                gap: 5px 34px;
            }
        }
        .filter-badge {
            padding: 8px 14px;
            background: #fff;
            border-radius: 20px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.2s;
            white-space: nowrap;
            border: 2px solid transparent; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .filter-badge:hover { background: #f0f0f0; }
        .filter-badge.active { background: #3498db; color: white; border-color: #3498db; }

        /* ==================== Product grid (Scrollable Area) ==================== */
        .product-grid-wrapper {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 10px 0 40px 0;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(6, 210px);
            gap: 18px 14px;
            justify-content: center; 
            width: 100%;
            align-content: start;
        }

        @media (max-width: 1400px) { .product-grid { grid-template-columns: repeat(5, 210px); } }
        @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(4, 210px); } }
        @media (max-width: 1000px) { .product-grid { grid-template-columns: repeat(3, 210px); } }
        @media (max-width: 780px)  { .product-grid { grid-template-columns: repeat(2, 210px); } }
        @media (max-width: 560px)  { .product-grid { grid-template-columns: 210px; } }

        /* ==================== Product card ==================== */
        .product-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            height: 100%;
            box-sizing: border-box;
            width: 210px;
        }
        .product-card-body {
            padding: 10px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }
        .product-price { font-size: 1.05rem; color: #2ecc71; font-weight: bold; margin: 4px 0; }

        .product-image-© {
            position: relative;
            width: 50%;
            aspect-ratio: 1 / 1;
            margin: 12px auto 8px auto;
            background-color: #f8f8f8;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            box-sizing: border-box;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .product-image-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
            cursor: zoom-in;
        }
        .product-image-container:hover img { transform: scale(1.08); }

        .img-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 22px;
            height: 22px;
            border: 1px solid #ccc;
            border-radius: 50%;
            background: rgba(255,255,255,0.9);
            color: #333;
            font-size: 12px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            z-index: 3;
            transition: background 0.15s, transform 0.15s;
        }
        .img-nav:hover { background: #fff; transform: translateY(-50%) scale(1.1); }
        .img-nav.prev { left: 3px; }
        .img-nav.next { right: 3px; }

        .img-counter {
            position: absolute;
            bottom: 4px;
            right: 6px;
            background: rgba(0,0,0,0.55);
            color: #fff;
            font-size: 0.62rem;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 10px;
            z-index: 3;
            pointer-events: none;
        }

        .product-card h3  { font-size: 0.92rem; line-height: 1.2; margin-bottom: 3px; color: #2c3e50; }
        .product-card .meta { font-size: 0.68rem; }
        .product-card .desc {
            font-size: 0.76rem; line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-card .stock { font-size: 0.72rem; }
        .product-card button { font-size: 0.82rem; padding: 7px 8px; }

        /* ==================== User menu ==================== */
        .user-menu { position: relative; display: inline-block; }
        .user-icon-btn {
            position: relative;
            background: #fff;
            border: 2px solid transparent;
            border-radius: 50%;
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #2c3e50;
            transition: background 0.15s, border-color 0.15s, transform 0.15s;
            padding: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .user-icon-btn:hover, .user-icon-btn:focus-visible {
            border-color: #3498db;
            transform: scale(1.05);
            outline: none;
        }
        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #e74c3c;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 800;
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            border: 2px solid #fff;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        .user-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 230px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
            padding: 8px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: opacity 0.18s, transform 0.18s, visibility 0.18s;
            z-index: 1000;
            text-align: left;
        }
        .user-dropdown.open { opacity: 1; visibility: visible; transform: translateY(0); }
        .user-greeting {
            padding: 12px 16px 10px;
            font-size: 0.85rem;
            color: #7f8c8d;
            border-bottom: 1px solid #eee;
            margin-bottom: 4px;
        }
        .user-greeting strong {
            color: #2c3e50;
            display: block;
            font-size: 0.95rem;
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: #2c3e50;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: background 0.12s;
        }
        .user-menu-item:hover { background: #f4f6f8; }
        .menu-icon { font-size: 1rem; line-height: 1; }
        .menu-count {
            margin-left: auto;
            background: #2ecc71;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }
        .menu-divider { height: 1px; background: #eee; margin: 6px 0; }
        .user-menu-logout { color: #e74c3c; }
        .user-menu-logout:hover { background: #fdf2f1; }

        /* ==================== Lightbox ==================== */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .lightbox.active { display: flex; }
        .lightbox img {
            max-width: 90vw;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 6px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            background: #fff;
        }
        .lb-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.95);
            border: none;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            color: #333;
            line-height: 1;
            padding: 0;
            transition: background 0.15s;
        }
        .lb-close:hover { background: #fff; }
        .lb-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255,255,255,0.9);
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            z-index: 10;
            transition: background 0.15s, transform 0.15s;
        }
        .lb-nav:hover { background: #fff; transform: translateY(-50%) scale(1.1); }
        .lb-prev { left: 20px; }
        .lb-next { right: 20px; }
        .lb-nav[hidden] { display: none; }
        .lb-counter {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            background: rgba(0,0,0,0.6);
            padding: 4px 14px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* ==================== Back to Top Button ==================== */
        #back-to-top {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s, transform 0.2s, background 0.2s;
            z-index: 999;
            transform: translateY(20px);
        }
        #back-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        #back-to-top:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
        }

        /* ==================== Responsive: mobile/tablet ==================== */
        @media (max-width: 900px) {
            .nav-header {
                /* flex-direction: column;
                align-items: stretch;
                gap: 10px; */
                text-align: center;
                padding: 10px;
            }
            /* .header-left, .header-right {
                flex: 0 0 auto;
                justify-content: center;
                align-items: center;
                flex-wrap: wrap;
            } */
            .header-center { order: -1; }
            /* .site-logo { height: 62px; } */

            .product-image-container { width: 40%; }
            .img-nav { width: 42px; height: 42px; font-size: 18px; background: rgba(255,255,255,0.95); }
            .img-nav.prev { left: 6px; }
            .img-nav.next { right: 6px; }
            .img-counter { font-size: 0.72rem; padding: 2px 8px; bottom: 6px; right: 8px; }

            .product-card h3    { font-size: 1.05rem; }
            .product-card .meta { font-size: 0.8rem; }
            .product-card .desc { font-size: 0.88rem; }
            .product-card .stock{ font-size: 0.82rem; }
            .product-card button{ font-size: 0.95rem; padding: 10px; }
        }

        @media (max-width: 640px) {
            /* body > .crud-container { padding: 0 10px; } */
            .product-grid { grid-template-columns: 1fr; justify-content: stretch; }
            .product-card { width: 100%; }
            .product-image-container { width: 100%; }
            .img-nav { width: 44px; height: 44px; font-size: 20px; border-width: 2px; }
            .img-nav.prev { left: 4px; }
            .img-nav.next { right: 4px; }
            .lb-nav { width: 48px; height: 48px; font-size: 18px; }
            .lb-prev { left: 8px; }
            .lb-next { right: 8px; }
            .lb-close { top: 10px; right: 10px; width: 40px; height: 40px; font-size: 22px; }
            #back-to-top { bottom: 20px; right: 20px; width: 44px; height: 44px; font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="crud-container">
        <!-- Sticky Header -->
        <?php include 'header_menu.php'; ?>

        <div class="filter-strip">
            <a href="store.php?cat=All" class="filter-badge <?php echo $selected_category === 'All' ? 'active' : ''; ?>">All Products</a>
            <?php foreach ($categories as $cat): ?>
                <a href="store.php?cat=<?php echo urlencode($cat); ?>" class="filter-badge <?php echo $selected_category === $cat ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Scrollable Product Grid Wrapper -->
        <div class="product-grid-wrapper" id="product-scroll-area">
            <?php if (count($products) > 0): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product):
                        $images = product_images($product['image_url'] ?? '');
                        $img_count = count($images);
                        $pid = (int)$product['id'];
                    ?>
                        <div class="product-card">
                            <div class="product-image-container">
                                <?php if ($img_count > 1): ?>
                                    <button type="button" class="img-nav prev"
                                            onclick="changeImage(<?php echo $pid; ?>, -1, event)"
                                            aria-label="Previous image">&#10094;</button>
                                <?php endif; ?>

                                <img id="img-<?php echo $pid; ?>"
                                     src="<?php echo htmlspecialchars($images[0]); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onclick="openLightbox(<?php echo $pid; ?>)">

                                <?php if ($img_count > 1): ?>
                                    <button type="button" class="img-nav next"
                                            onclick="changeImage(<?php echo $pid; ?>, 1, event)"
                                            aria-label="Next image">&#10095;</button>
                                    <span class="img-counter" id="counter-<?php echo $pid; ?>">1 / <?php echo $img_count; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-card-body">
                                <div>
                                    <h3 style="color:#2c3e50;"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="meta" style="color:#7f8c8d; font-weight:bold; text-transform:uppercase; margin-bottom:6px;">
                                        <?php echo htmlspecialchars($product['brand']); ?> • <?php echo htmlspecialchars($product['category']); ?>
                                    </p>
                                    <p class="desc" style="color:#555; margin-bottom:8px;">
                                        <?php echo htmlspecialchars($product['description']); ?>
                                    </p>
                                </div>
                                <div>
                                    <div class="product-price">Rs <?php echo number_format($product['selling_price'], 2); ?></div>
                                    <div class="stock" style="margin-bottom: 8px; font-weight: 600; color: <?php echo $product['quantity'] > 0 ? '#7f8c8d' : '#e74c3c'; ?>;">
                                        <?php echo $product['quantity'] > 0 ? "In Stock: " . $product['quantity'] : "Out of Stock"; ?>
                                    </div>
                                    <form action="store.php<?php echo $selected_category !== 'All' ? '?cat='.urlencode($selected_category) : ''; ?>" method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                        <button type="submit" name="add_to_cart" class="btn" <?php echo $product['quantity'] <= 0 ? 'disabled style="background:#bdc3c7; cursor:not-allowed;"' : ''; ?>>
                                            <?php echo $product['quantity'] > 0 ? 'Add to Cart' : 'Sold Out'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center; color:#7f8c8d; padding: 40px 0;">No items found matching this category.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lightbox -->
    <div class="lightbox" id="lightbox" onclick="closeLightboxOnBackdrop(event)">
        <button type="button" class="lb-close" onclick="closeLightbox()" aria-label="Close">&times;</button>
        <button type="button" class="lb-nav lb-prev" onclick="lightboxNav(-1, event)" aria-label="Previous image">&#10094;</button>
        <img id="lightbox-img" src="" alt="">
        <button type="button" class="lb-nav lb-next" onclick="lightboxNav(1, event)" aria-label="Next image">&#10095;</button>
        <span class="lb-counter" id="lightbox-counter"></span>
    </div>

    <!-- Back to Top Button -->
    <button id="back-to-top" aria-label="Back to top" title="Back to top">
        ↑
    </button>

    <script>
        const galleries = {
            <?php foreach ($products as $p):
                $imgs = product_images($p['image_url'] ?? '');
            ?>
            <?php echo (int)$p['id']; ?>: { list: <?php echo json_encode($imgs); ?>, current: 0 },
            <?php endforeach; ?>
        };

        function changeImage(productId, direction, evt) {
            if (evt) { evt.preventDefault(); evt.stopPropagation(); }
            const g = galleries[productId];
            if (!g || g.list.length < 2) return;
            g.current = (g.current + direction + g.list.length) % g.list.length;
            document.getElementById('img-' + productId).src = g.list[g.current];
            const counter = document.getElementById('counter-' + productId);
            if (counter) counter.textContent = (g.current + 1) + ' / ' + g.list.length;
        }

        let lightboxPid = null;
        function openLightbox(pid) {
            const g = galleries[pid];
            if (!g) return;
            lightboxPid = pid;
            updateLightbox();
            document.getElementById('lightbox').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function updateLightbox() {
            const g = galleries[lightboxPid];
            if (!g) return;
            document.getElementById('lightbox-img').src = g.list[g.current];
            const counterEl = document.getElementById('lightbox-counter');
            const showNav = g.list.length > 1;
            counterEl.textContent = (g.current + 1) + ' / ' + g.list.length;
            counterEl.style.display = showNav ? '' : 'none';
            document.querySelector('.lb-prev').hidden = !showNav;
            document.querySelector('.lb-next').hidden = !showNav;
        }
        function lightboxNav(direction, evt) {
            if (evt) evt.stopPropagation();
            const g = galleries[lightboxPid];
            if (!g || g.list.length < 2) return;
            g.current = (g.current + direction + g.list.length) % g.list.length;
            const cardImg = document.getElementById('img-' + lightboxPid);
            if (cardImg) cardImg.src = g.list[g.current];
            const cardCounter = document.getElementById('counter-' + lightboxPid);
            if (cardCounter) cardCounter.textContent = (g.current + 1) + ' / ' + g.list.length;
            updateLightbox();
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.body.style.overflow = '';
            lightboxPid = null;
        }
        function closeLightboxOnBackdrop(evt) {
            if (evt.target.id === 'lightbox') closeLightbox();
        }
        document.addEventListener('keydown', function (e) {
            if (document.getElementById('lightbox').classList.contains('active')) {
                if (e.key === 'Escape') closeLightbox();
                else if (e.key === 'ArrowLeft') lightboxNav(-1);
                else if (e.key === 'ArrowRight') lightboxNav(1);
            }
        });

        function toggleUserMenu(evt) {
            if (evt) evt.stopPropagation();
            const dropdown = document.getElementById('user-dropdown');
            const button = evt.currentTarget;
            const isOpen = dropdown.classList.toggle('open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
        document.addEventListener('click', function (evt) {
            const menu = document.getElementById('user-menu');
            if (menu && !menu.contains(evt.target)) {
                document.getElementById('user-dropdown')?.classList.remove('open');
                document.querySelector('.user-icon-btn')?.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', function (evt) {
            if (evt.key === 'Escape') {
                document.getElementById('user-dropdown')?.classList.remove('open');
                document.querySelector('.user-icon-btn')?.setAttribute('aria-expanded', 'false');
            }
        });

        // ==================== Back to Top Logic ====================
        const backToTopBtn = document.getElementById('back-to-top');
        const productScrollArea = document.getElementById('product-scroll-area');

        productScrollArea.addEventListener('scroll', () => {
            if (productScrollArea.scrollTop > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            productScrollArea.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>