<?php
// Start the session to access session variables
session_start();

// --- Destroy the Session ---

// 1. Unset all of the session variables.
$_SESSION = array(); // Set $_SESSION to an empty array

// 2. If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Set expiry time in the past
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Finally, destroy the session.
session_destroy();

// --- Redirect to Login Page ---
// Redirect the user back to the login page after destroying the session
header("Location: login.php");
exit(); // Ensure no further code is executed after redirect
