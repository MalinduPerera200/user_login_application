<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$navbar_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Navbar Styles (same as before) */
    .main-navbar {
        background-color: #343a40;
        padding: 10px 30px;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-sizing: border-box;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .main-navbar .navbar-brand {
        color: #f8f9fa;
        font-size: 1.2em;
        font-weight: bold;
        text-decoration: none;
    }

    .main-navbar .navbar-brand span {
        font-weight: normal;
        font-size: 0.9em;
        opacity: 0.8;
    }

    .main-navbar .nav-links a {
        color: #adb5bd;
        text-decoration: none;
        padding: 10px 15px;
        border-radius: 5px;
        margin-left: 8px;
        transition: background-color 0.3s ease, color 0.3s ease;
        font-size: 0.95em;
    }

    .main-navbar .nav-links a.active,
    .main-navbar .nav-links a:hover {
        background-color: #495057;
        color: #ffffff;
    }

    .main-navbar .nav-links a.logout-link {
        background-color: #dc3545;
        color: #ffffff;
    }

    .main-navbar .nav-links a.logout-link:hover {
        background-color: #c82333;
    }

    @media (max-width: 768px) {
        .main-navbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .main-navbar .nav-links {
            margin-top: 10px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        .main-navbar .nav-links a {
            margin-left: 0;
            margin-bottom: 5px;
            text-align: center;
        }

        .main-navbar .navbar-brand {
            margin-bottom: 10px;
        }
    }
</style>

<nav class="main-navbar">
    <div class="navbar-brand">
        Your App Name
        <span>| Welcome, <?php echo htmlspecialchars($navbar_username); ?>!</span>
    </div>

    <div class="nav-links">
        <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Main Page</a>
        <a href="manage_employees.php" class="nav-link <?php echo ($current_page == 'manage_employees.php') ? 'active' : ''; ?>">Manage Employees</a>
        <a href="add_update_sale.php" class="nav-link <?php echo ($current_page == 'add_update_sale.php') ? 'active' : ''; ?>">Add/Update Sale</a>
        <a href="logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
    </div>
</nav>