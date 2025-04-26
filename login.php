<?php
// Start the session at the very beginning
session_start();

// Include the database connection file
require_once 'db_connect.php';

// Initialize variables
$errors = [];
$login_identifier = ''; // To keep username/email in the form field on error

// --- Redirect if already logged in ---
// If user is already logged in, redirect them away from login page
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); // Redirect to dashboard or home page
    exit(); // Stop script execution after redirect
}


// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get Data from Form
    $login_identifier = trim($_POST['login_identifier']); // Can be username or email
    $password = $_POST['password'];

    // Basic Validation
    if (empty($login_identifier)) {
        $errors[] = "Username or Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // If basic validation passes, proceed to check database
    if (empty($errors)) {
        // Prepare SQL statement to find user by username OR email
        // Using prepared statements to prevent SQL injection
        $sql = "SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind the login identifier to the placeholder
            $stmt->bind_param("ss", $login_identifier, $login_identifier);

            // Execute the statement
            $stmt->execute();

            // Store the result so we can check number of rows and get data
            $stmt->store_result();

            // Check if a user was found (should be 1 if found)
            if ($stmt->num_rows == 1) {
                // Bind the result variables
                $stmt->bind_result($user_id, $username_db, $password_hash_db);

                // Fetch the result
                if ($stmt->fetch()) {
                    // Verify the password
                    if (password_verify($password, $password_hash_db)) {
                        // Password is correct!

                        // Regenerate session ID for security (prevents session fixation)
                        session_regenerate_id(true);

                        // Store user data in session variables
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username_db;
                        // You can store other user info here if needed

                        // Redirect to the protected dashboard page
                        header("Location: index.php");
                        exit(); // Important to stop script execution after redirect

                    } else {
                        // Password is not valid
                        $errors[] = "Incorrect username/email or password.";
                    }
                } else {
                    $errors[] = "Error fetching user data."; // Should not happen often if num_rows is 1
                }
            } else {
                // No user found with that username or email
                $errors[] = "Incorrect username/email or password.";
            }

            // Close statement
            $stmt->close();
        } else {
            $errors[] = "Database error: Could not prepare statement. " . $conn->error; // Show error in development
            // Production: $errors[] = "An error occurred. Please try again later.";
        }
    }

    // Close connection (optional)
    // $conn->close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Reusing similar styles from register.php for consistency */
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding-top: 20px;
            padding-bottom: 20px;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #aaa;
        }

        .btn {
            /* Using a blue color for login */
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
        }

        .register-link a {
            color: #5cb85c;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* No success message needed on login form itself, usually redirect */
        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Login</h2>

        <?php
        // Display Error Messages if they exist
        if (!empty($errors)) {
            echo '<div class="message error-message">';
            echo '<ul class="error-list">';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <form action="login.php" method="POST" novalidate>
            <div class="form-group">
                <label for="login_identifier">Username or Email:</label>
                <input type="text" id="login_identifier" name="login_identifier" value="<?php echo htmlspecialchars($login_identifier); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>

</body>

</html> 