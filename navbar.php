<?php
// Session එක පටන් අරන් නැත්නම් පටන් ගන්නවා (මේ file එක include කරන තැනත් session_start() තියෙන්න ඕන)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Login වෙලා ඉන්න user ගේ username එක ගන්නවා
$navbar_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

// Current page එකේ filename එක ගන්නවා (active link එක highlight කරන්න)
$current_page = basename($_SERVER['PHP_SELF']); // e.g., "index.php", "manage_employees.php"

?>
<style>
    /* Navbar Styles */
    .main-navbar {
        background-color: #343a40;
        /* Dark grey background */
        padding: 10px 30px;
        /* More padding */
        width: 100%;
        display: flex;
        justify-content: space-between;
        /* Space between logo/welcome and links */
        align-items: center;
        box-sizing: border-box;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        /* Subtle shadow */
    }

    .main-navbar .navbar-brand {
        color: #f8f9fa;
        /* Light color for brand/welcome */
        font-size: 1.2em;
        font-weight: bold;
        text-decoration: none;
        /* Remove underline if it's a link */
    }

    .main-navbar .navbar-brand span {
        font-weight: normal;
        font-size: 0.9em;
        opacity: 0.8;
    }


    .main-navbar .nav-links a {
        color: #adb5bd;
        /* Lighter grey for links */
        text-decoration: none;
        padding: 10px 15px;
        /* Padding around links */
        border-radius: 5px;
        margin-left: 8px;
        transition: background-color 0.3s ease, color 0.3s ease;
        font-size: 0.95em;
    }

    /* Active link style */
    .main-navbar .nav-links a.active,
    .main-navbar .nav-links a:hover {
        background-color: #495057;
        /* Slightly darker grey on hover/active */
        color: #ffffff;
        /* White text on hover/active */
    }

    .main-navbar .nav-links a.logout-link {
        background-color: #dc3545;
        /* Red for logout */
        color: #ffffff;
    }

    .main-navbar .nav-links a.logout-link:hover {
        background-color: #c82333;
        /* Darker red on hover */
    }

    /* Responsive considerations (optional basic example) */
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
            /* Make links full width */
        }

        .main-navbar .nav-links a {
            margin-left: 0;
            margin-bottom: 5px;
            /* Space between vertical links */
            text-align: center;
        }

        .main-navbar .navbar-brand {
            margin-bottom: 10px;
            /* Space below brand on mobile */
        }
    }
</style>

<nav class="main-navbar">
    <div class="navbar-brand">
        Your App Name <span>| Welcome, <?php echo htmlspecialchars($navbar_username); ?>!</span>
    </div>

    <div class="nav-links">
        <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Main Page</a>
        <a href="manage_employees.php" class="nav-link <?php echo ($current_page == 'manage_employees.php') ? 'active' : ''; ?>">Manage Employees</a>
        <a href="logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
    </div>
</nav>