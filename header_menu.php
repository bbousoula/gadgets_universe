<div class="nav-header">
    <!-- <div class="header-left"></div> -->
    <div class="header-center">
        <img src="images/logo_horizontal.png" alt="Gadgets Universe Logo" class="site-logo">
    </div>
    <div class="header-right">
        <div class="user-menu" id="user-menu">
            <button type="button" class="user-icon-btn" onclick="toggleUserMenu(event)" aria-haspopup="true"
                aria-expanded="false" aria-label="User menu">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24"
                    aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span class="cart-badge"><?php echo array_sum($_SESSION['cart']); ?></span>
            </button>

            <div class="user-dropdown" id="user-dropdown">
                <div class="user-greeting">
                    Hello,
                    <strong><?php echo htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['username']); ?></strong>
                </div>
                <a href="cart.php" class="user-menu-item">
                    <span class="menu-icon">🛒</span>
                    <span>My Cart</span>
                    <span class="menu-count"><?php echo array_sum($_SESSION['cart']); ?></span>
                </a>
                <a href="orders.php" class="user-menu-item">
                    <span class="menu-icon">📦</span>
                    <span>My Orders</span>
                </a>
                <div class="menu-divider"></div>
                <a href="logout.php" class="user-menu-item user-menu-logout">
                    <span class="menu-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</div>