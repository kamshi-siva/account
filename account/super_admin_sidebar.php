<?php

?>
<div class="sidebar" id="sidebar">
    <h4><i class="fa-solid fa-crown me-2"></i>Super Admin</h4>

    <a href="super_admin_panel.php" class="<?= basename($_SERVER['PHP_SELF'])=='super_admin_panel.php'?'active':'' ?>">
        <i class="fa-solid fa-gauge-high me-2"></i> Dashboard
    </a>

    <a href="#requests" class="<?= basename($_SERVER['PHP_SELF'])=='super_admin_panel.php'?'active':'' ?>">
        <i class="fa-solid fa-envelope-open-text me-2"></i> Pending Requests 
        <span class="badge badge-info float-end">
            <?php
            require_once "config.php";
            $pendingCount = $conn->query("SELECT COUNT(*) AS total FROM restaurant_requests WHERE status='Pending'")->fetch_assoc()['total'] ?? 0;
            echo $pendingCount;
            ?>
        </span>
    </a>

    <!-- Restaurant Dropdown -->
    <a href="javascript:void(0);" class="dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['add_restaurant.php','view_restaurants.php'])?'active':'' ?>" id="restaurantDropdown">
        <i class="fa-solid fa-utensils me-2"></i> Restaurants 
    </a>
    <div class="submenu" id="restaurantMenu" style="<?= in_array(basename($_SERVER['PHP_SELF']), ['add_restaurant.php','view_restaurants.php'])?'display:block;':'' ?>">
        <a href="add_restaurant.php"><i class="fa-solid fa-plus-circle me-2"></i> Add Restaurant</a>
        <a href="view_restaurants.php"><i class="fa-solid fa-list me-2"></i> View Restaurants</a>
    </div>

    <a href="super_admin_logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
</div>
