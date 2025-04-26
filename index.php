<?php
// Start the session at the very beginning of the script
session_start();

// Check if the user is logged in by checking the session variable
if (!isset($_SESSION['user_id'])) {
    // If user_id is not set in the session, the user is not logged in
    // Redirect them to the login page
    header("Location: login.php");
    exit(); // Stop script execution after redirect
}

// If the script reaches here, the user is logged in.
// Get the username from the session to display it
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; // Default to 'User' if username is not set for some reason

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        /* Simple styles for the dashboard */
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            /* Arrange items vertically */
            align-items: center;
            min-height: 100vh;
        }

        .navbar {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            /* Pushes items to ends */
            align-items: center;
            box-sizing: border-box;
        }

        .navbar span {
            font-size: 1.1em;
        }

        /* Updated Navbar Link Styles */
        .navbar .nav-links a {
            /* Target links within a container */
            color: #f4f4f4;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            margin-left: 10px;
            /* Space between links */
            transition: background-color 0.3s ease;
        }

        .navbar .nav-links a.nav-link {
            /* Style for regular nav links */
            background-color: #007bff;
            /* Blue for navigation */
        }

        .navbar .nav-links a.nav-link:hover {
            background-color: #0056b3;
        }

        .navbar .nav-links a.logout-link {
            /* Style for logout link */
            background-color: #dc3545;
            /* Red color for logout */
        }

        .navbar .nav-links a.logout-link:hover {
            background-color: #c82333;
            text-decoration: none;
        }

        .content {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            /* Make content area wider */
            max-width: 800px;
        }

        .content h1 {
            color: #333;
        }

        .content p {
            color: #555;
            font-size: 1.1em;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <span>Welcome, <?php echo htmlspecialchars($username); ?>!</span>
        <div class="nav-links">
            <a href="manage_employees.php" class="nav-link">Manage Employees</a>
            <a href="logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        </div>
    </div>

    <div class="content">
        <h1>Main Page</h1>
        <p>You have successfully logged in.</p>
        <p>This is your main content area.</p>
        <p>Use the 'Manage Employees' link in the navigation bar to manage employee data.</p>
    </div>

</body>

</html>