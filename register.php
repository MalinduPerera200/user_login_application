<?php
// Start the session to store messages if needed (e.g., after redirect)
// Although we display messages directly here for now, it's good practice.
session_start();

// Include the database connection file
require_once 'db_connect.php'; // Use require_once to ensure it's included only once and halt if not found

// Initialize variables for messages and errors
$errors = [];
$success_message = '';
$username = ''; // Keep username value for the form field if there's an error
$email = '';    // Keep email value for the form field if there's an error

// Check if the form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Get Data from Form ---
    // Use trim() to remove leading/trailing whitespace
    // Use htmlspecialchars() to prevent XSS attacks when redisplaying data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Don't trim password initially
    $confirm_password = $_POST['confirm_password'];

    // --- Basic Validation ---
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if (empty($confirm_password)) {
        $errors[] = "Confirm Password is required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    // You might want to add password strength validation here (e.g., minimum length)
    if (strlen($password) < 6) { // Example: Minimum 6 characters
        $errors[] = "Password must be at least 6 characters long.";
    }


    // --- Check if username or email already exists (only if basic validation passed) ---
    if (empty($errors)) {
        // Check for existing email
        $sql_check_email = "SELECT id FROM users WHERE email = ?";
        if ($stmt_check_email = $conn->prepare($sql_check_email)) {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result(); // Store result to check number of rows
            if ($stmt_check_email->num_rows > 0) {
                $errors[] = "Email address already registered.";
            }
            $stmt_check_email->close();
        } else {
            $errors[] = "Database error (email check): " . $conn->error; // Show specific error during development
        }

        // Check for existing username (only if email check passed or no error occurred)
        if (empty($errors)) { // Proceed only if no previous errors
            $sql_check_username = "SELECT id FROM users WHERE username = ?";
            if ($stmt_check_username = $conn->prepare($sql_check_username)) {
                $stmt_check_username->bind_param("s", $username);
                $stmt_check_username->execute();
                $stmt_check_username->store_result();
                if ($stmt_check_username->num_rows > 0) {
                    $errors[] = "Username already taken.";
                }
                $stmt_check_username->close();
            } else {
                $errors[] = "Database error (username check): " . $conn->error;
            }
        }
    }

    // --- If No Errors, Proceed with Registration ---
    if (empty($errors)) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Use default strong hashing algorithm

        // Prepare SQL INSERT statement to prevent SQL injection
        $sql_insert = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";

        if ($stmt_insert = $conn->prepare($sql_insert)) {
            // Bind variables to the prepared statement as parameters
            // 'sss' means three string parameters
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password);

            // Execute the prepared statement
            if ($stmt_insert->execute()) {
                $success_message = "Registration successful! You can now <a href='login.php'>login</a>.";
                // Clear form fields on success
                $username = '';
                $email = '';
            } else {
                // Provide a generic error message in production
                $errors[] = "Registration failed. Please try again later.";
                // Log detailed error: error_log("Registration failed: " . $stmt_insert->error);
            }

            // Close statement
            $stmt_insert->close();
        } else {
            // Provide a generic error message in production
            $errors[] = "Database error. Could not prepare statement.";
            // Log detailed error: error_log("Prepare statement failed: " . $conn->error);
        }
    }

    // Close database connection (optional here, as PHP usually closes it at script end)
    // $conn->close(); // Uncomment if you want explicit closing
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        /* (CSS styles remain the same as before) */
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding-top: 20px;
            /* Add padding for messages */
            padding-bottom: 20px;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            /* Max width */
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
            /* Include padding and border in element's total width and height */
        }

        .form-group input:focus {
            outline: none;
            border-color: #aaa;
        }

        .btn {
            background-color: #5cb85c;
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
            background-color: #4cae4c;
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
        }

        .login-link a {
            color: #007bff;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Styles for messages */
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

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Register</h2>

        <?php
        // Display Success Message if it exists
        if (!empty($success_message)) {
            // Use html_entity_decode if message contains HTML like the link
            echo '<div class="message success-message">' . $success_message . '</div>';
        }

        // Display Error Messages if they exist
        if (!empty($errors)) {
            echo '<div class="message error-message">';
            echo '<ul class="error-list">';
            foreach ($errors as $error) {
                // Use htmlspecialchars to prevent XSS if errors might contain user input
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <?php if (empty($success_message)): ?>
            <form action="register.php" method="POST" novalidate>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Register</button>
            </form>
        <?php endif; ?>

        <div class="login-link">
            <?php if (empty($success_message)): // Show only if registration form is visible 
            ?>
                Already have an account? <a href="login.php">Login here</a>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>