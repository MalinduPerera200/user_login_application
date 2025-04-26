<?php
// Start the session at the very beginning of the script
session_start(); // Make sure session is started BEFORE including navbar

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// No need to get username here anymore, navbar handles it

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        /* Remove the old .navbar styles from here */
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            /* Remove default body margin */
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        /* Keep .content styles */
        .content {
            text-align: center;
            margin-top: 50px;
            /* Add space below navbar */
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
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

    <?php
    // Include the new navigation bar
    require_once 'navbar.php';
    ?>

    <div class="content">
        <h1>Main Page</h1>
        <p>You have successfully logged in.</p>
        <p>This is your main content area.</p>
        <p>Use the links in the navigation bar to move around.</p>
    </div>

</body>

</html>