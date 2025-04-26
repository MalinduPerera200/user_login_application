<?php
// Start session and check login
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include DB connection
require_once 'db_connect.php';

// Get current user ID
$current_user_id = $_SESSION['user_id'];

// Initialize variables
$employees_list = [];
$errors = [];
$success_message = '';
$selected_employee = '';
$sale_amount = '';
$sale_date = date('Y-m-d'); // Default to today's date

// --- Fetch active employees for the dropdown ---
$sql_fetch_emp = "SELECT id, name FROM employees WHERE status = 'active' ORDER BY name ASC";
$result_emp = $conn->query($sql_fetch_emp);
if ($result_emp && $result_emp->num_rows > 0) {
    while ($row = $result_emp->fetch_assoc()) {
        $employees_list[] = $row;
    }
} elseif (!$result_emp) {
    $errors[] = "Error fetching employees: " . $conn->error;
}

// --- Handle Form Submission (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_sale'])) {
    // Get data from form
    $employee_id = trim($_POST['employee_id']);
    $sale_amount = trim($_POST['sale_amount']);
    $sale_date = trim($_POST['sale_date']);
    // Optional: $description = trim($_POST['description']);

    // --- Validation ---
    if (empty($employee_id) || !filter_var($employee_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Please select a valid employee.";
    }
    if (empty($sale_amount) || !is_numeric($sale_amount) || $sale_amount < 0) {
        $errors[] = "Please enter a valid positive sale amount.";
    }
    if (empty($sale_date)) {
        $errors[] = "Please select a sale date.";
    } else {
        // Validate date format (Y-m-d)
        $d = DateTime::createFromFormat('Y-m-d', $sale_date);
        if (!$d || $d->format('Y-m-d') !== $sale_date) {
            $errors[] = "Invalid date format. Please use YYYY-MM-DD.";
        }
    }

    // Keep selected values on error
    $selected_employee = $employee_id;
    // $sale_amount is already set

    // --- If no validation errors, proceed with Add/Update ---
    if (empty($errors)) {
        // Check if a sale record already exists for this employee on this date
        $sql_check = "SELECT id FROM sales WHERE employee_id = ? AND sale_date = ?";
        $existing_sale_id = null;

        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("is", $employee_id, $sale_date);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $existing_sale_id = $result_check->fetch_assoc()['id'];
            }
            $stmt_check->close();
        } else {
            $errors[] = "Database error checking existing sale: " . $conn->error;
        }

        // Proceed only if check was successful (no DB error)
        if (empty($errors)) {
            if ($existing_sale_id !== null) {
                // --- UPDATE existing record ---
                $sql_update = "UPDATE sales SET sale_amount = ?, recorded_by_user_id = ?, updated_at = NOW() WHERE id = ?";
                if ($stmt_update = $conn->prepare($sql_update)) {
                    // Bind parameters: amount (decimal/double 'd'), user_id (int 'i'), sale_id (int 'i')
                    $stmt_update->bind_param("dii", $sale_amount, $current_user_id, $existing_sale_id);
                    if ($stmt_update->execute()) {
                        $success_message = "Sale updated successfully for the selected date.";
                        // Clear form fields after successful update
                        $selected_employee = '';
                        $sale_amount = '';
                        // $sale_date = date('Y-m-d'); // Optionally reset date
                    } else {
                        $errors[] = "Error updating sale: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                    $errors[] = "Database error preparing update statement: " . $conn->error;
                }
            } else {
                // --- INSERT new record ---
                // Optional: Add description field if needed
                $sql_insert = "INSERT INTO sales (employee_id, sale_amount, sale_date, recorded_by_user_id) VALUES (?, ?, ?, ?)";
                if ($stmt_insert = $conn->prepare($sql_insert)) {
                    // Bind parameters: emp_id (int 'i'), amount (decimal/double 'd'), date (string 's'), user_id (int 'i')
                    $stmt_insert->bind_param("idsi", $employee_id, $sale_amount, $sale_date, $current_user_id);
                    if ($stmt_insert->execute()) {
                        $success_message = "Sale added successfully!";
                        // Clear form fields after successful insert
                        $selected_employee = '';
                        $sale_amount = '';
                        // $sale_date = date('Y-m-d'); // Optionally reset date
                    } else {
                        // The unique constraint should prevent duplicates, but handle other errors
                        $errors[] = "Error adding sale: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                } else {
                    $errors[] = "Database error preparing insert statement: " . $conn->error;
                }
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add/Update Sale</title>
    <style>
        /* Include styles from navbar.php implicitly */
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px;
            max-width: 600px;
            /* Smaller container for this form */
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .description {
            text-align: center;
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 0.9em;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
            /* Increased spacing */
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            /* Darker label color */
            font-weight: bold;
            font-size: 0.95em;
        }

        .form-group select,
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group input[type="text"]

        /* Added for consistency */
            {
            width: 100%;
            padding: 12px;
            /* Increased padding */
            border: 1px solid #ced4da;
            /* Standard border color */
            border-radius: 5px;
            /* Slightly more rounded */
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-group input[type="number"] {
            /* Specific style for number input if needed */
            text-align: right;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #80bdff;
            /* Bootstrap focus color */
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        /* Input group for amount with '$' sign */
        .input-group {
            position: relative;
            display: flex;
            align-items: stretch;
            /* Make items same height */
            width: 100%;
        }

        .input-group-prepend {
            margin-right: -1px;
            /* Overlap border */
        }

        .input-group-text {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 0;
            font-size: 1em;
            font-weight: 400;
            line-height: 1.5;
            color: #495057;
            text-align: center;
            white-space: nowrap;
            background-color: #e9ecef;
            /* Light grey background */
            border: 1px solid #ced4da;
            border-radius: 5px 0 0 5px;
            /* Rounded left corners */
        }

        .input-group input[type="number"] {
            border-radius: 0 5px 5px 0;
            /* Rounded right corners */
            position: relative;
            flex: 1 1 auto;
            /* Allow input to grow */
            width: 1%;
            /* Prevent shrinking */
            min-width: 0;
            /* Override default min-width */
        }


        .btn-submit {
            background-color: #28a745;
            /* Green color */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1em;
            /* Slightly larger font */
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #218838;
            /* Darker green */
        }

        /* Messages */
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-size: 0.95em;
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

    <?php require_once 'navbar.php'; // Include the common navbar 
    ?>

    <div class="container">
        <h1>Add / Update Sale</h1>
        <p class="description">If a sale exists for the selected seller and date, the amount will be updated.</p>

        <?php
        // Display Messages
        if (!empty($success_message)) {
            echo '<div class="message success-message">' . htmlspecialchars($success_message) . '</div>';
        }
        if (!empty($errors)) {
            echo '<div class="message error-message"><ul class="error-list">';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }
        ?>

        <form action="add_update_sale.php" method="POST">
            <div class="form-group">
                <label for="employee_id">Seller</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">Select Seller...</option>
                    <?php foreach ($employees_list as $emp): ?>
                        <option value="<?php echo htmlspecialchars($emp['id']); ?>" <?php echo ($selected_employee == $emp['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sale_amount">Amount</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">$</span>
                    </div>
                    <input type="number" id="sale_amount" name="sale_amount" step="0.01" min="0" placeholder="0.00"
                        value="<?php echo htmlspecialchars($sale_amount); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="sale_date">Sale Date</label>
                <input type="date" id="sale_date" name="sale_date"
                    value="<?php echo htmlspecialchars($sale_date); ?>" required>
            </div>

            <button type="submit" name="submit_sale" class="btn-submit">Add / Update Sale</button>
        </form>
    </div>

</body>

</html>