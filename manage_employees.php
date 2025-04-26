<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Get the ID of the currently logged-in user
$current_user_id = $_SESSION['user_id'];

// Initialize variables
$employees = [];
$errors = [];
$success_message = '';
$edit_mode = false;
$edit_employee_data = null;

// --- Check for Edit Request (GET) ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT)) {
    $employee_id_to_edit = $_GET['edit'];
    // Removed job_title from SELECT
    $sql_edit = "SELECT id, name, username, phone, location FROM employees WHERE id = ?";
    if ($stmt_edit = $conn->prepare($sql_edit)) {
        $stmt_edit->bind_param("i", $employee_id_to_edit);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows === 1) {
            $edit_employee_data = $result_edit->fetch_assoc();
            $edit_mode = true;
        } else {
            $errors[] = "Employee not found for editing.";
        }
        $stmt_edit->close();
    } else {
        $errors[] = "Database error preparing edit statement: " . $conn->error;
    }
}


// --- Handle Add Employee Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    // Get data from form - removed job_title
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($username)) {
        $errors[] = "Username is required.";
    }

    if (empty($errors)) {
        // Removed job_title from INSERT and VALUES placeholders
        $sql_insert = "INSERT INTO employees (name, username, phone, location, registered_by_user_id) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql_insert)) {
            // Updated bind_param types ('ssssi' - 4 strings, 1 integer)
            $stmt->bind_param("ssssi", $name, $username, $phone, $location, $current_user_id);
            if ($stmt->execute()) {
                header("Location: manage_employees.php?success=1");
                exit();
            } else {
                if ($stmt->errno == 1062 && strpos($stmt->error, 'username_unique') !== false) {
                    $errors[] = "Error: Employee username already exists.";
                } else {
                    $errors[] = "Error adding employee: " . $stmt->error;
                }
            }
            $stmt->close();
        } else {
            $errors[] = "Database error preparing insert statement: " . $conn->error;
        }
    }
}

// --- Handle Update Employee Form Submission ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_employee'])) {
    // Get data from form - removed job_title
    $employee_id = trim($_POST['employee_id']);
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);

    // Validation
    if (empty($employee_id) || !filter_var($employee_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Invalid Employee ID.";
    }
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($username)) {
        $errors[] = "Username is required.";
    }

    if (empty($errors)) {
        // Removed job_title from UPDATE statement
        $sql_update = "UPDATE employees SET name = ?, username = ?, phone = ?, location = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql_update)) {
            // Updated bind_param types ('ssssi' - 4 strings, 1 integer for ID)
            $stmt->bind_param("ssssi", $name, $username, $phone, $location, $employee_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    header("Location: manage_employees.php?success=4");
                    exit();
                } else {
                    header("Location: manage_employees.php?success=5"); // No changes detected
                    exit();
                }
            } else {
                if ($stmt->errno == 1062 && strpos($stmt->error, 'username_unique') !== false) {
                    $errors[] = "Error: Employee username already exists for another employee.";
                } else {
                    $errors[] = "Error updating employee: " . $stmt->error;
                }
            }
            $stmt->close();
        } else {
            $errors[] = "Database error preparing update statement: " . $conn->error;
        }
    }
    // If update fails, stay in edit mode
    if (!empty($errors)) {
        $edit_mode = true;
        // Repopulate $edit_employee_data with submitted (but failed) data
        $edit_employee_data = [
            'id' => $employee_id,
            'name' => $name,
            'username' => $username,
            'phone' => $phone,
            'location' => $location // Removed job_title
        ];
    }
}


// --- Handle Deactivate/Activate Actions (GET requests) ---
elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
    $action_success = false;
    // Deactivate
    if (isset($_GET['deactivate']) && filter_var($_GET['deactivate'], FILTER_VALIDATE_INT)) {
        $employee_id_to_deactivate = $_GET['deactivate'];
        $sql_deactivate = "UPDATE employees SET status = 'inactive' WHERE id = ?";
        if ($stmt = $conn->prepare($sql_deactivate)) {
            $stmt->bind_param("i", $employee_id_to_deactivate);
            if ($stmt->execute()) {
                $action_success = true;
            } else {
                $errors[] = "Error deactivating employee: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "DB error (deactivate): " . $conn->error;
        }
        if ($action_success) {
            header("Location: manage_employees.php?success=2");
            exit();
        }
    }
    // Activate
    elseif (isset($_GET['activate']) && filter_var($_GET['activate'], FILTER_VALIDATE_INT)) {
        $employee_id_to_activate = $_GET['activate'];
        $sql_activate = "UPDATE employees SET status = 'active' WHERE id = ?";
        if ($stmt = $conn->prepare($sql_activate)) {
            $stmt->bind_param("i", $employee_id_to_activate);
            if ($stmt->execute()) {
                $action_success = true;
            } else {
                $errors[] = "Error activating employee: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "DB error (activate): " . $conn->error;
        }
        if ($action_success) {
            header("Location: manage_employees.php?success=3");
            exit();
        }
    }
}

// --- Display Success Messages ---
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case '1':
            $success_message = "Employee added successfully!";
            break;
        case '2':
            $success_message = "Employee deactivated successfully.";
            break;
        case '3':
            $success_message = "Employee activated successfully.";
            break;
        case '4':
            $success_message = "Employee updated successfully!";
            break;
        case '5':
            $success_message = "No changes detected for the employee.";
            break;
    }
}


// --- Fetch employee list (Always fetch AFTER potential updates/adds/status changes) ---
$employees = [];
// Removed e.job_title from SELECT
$sql_fetch = "SELECT e.id, e.name, e.username, e.phone, e.location, e.status, e.created_at, u.username as registered_by
              FROM employees e
              LEFT JOIN users u ON e.registered_by_user_id = u.id
              ORDER BY e.name ASC";
$result = $conn->query($sql_fetch);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
} elseif (!$result && empty($errors)) { // Avoid overwriting previous errors if fetching fails
    $errors[] = "Error fetching employees: " . $conn->error;
}


$conn->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Employee' : 'Employee Management'; ?></title>
    <style>
        /* CSS Styles remain the same */
        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
        }

        .navbar span {
            font-size: 1.1em;
        }

        .navbar .nav-links a {
            color: #f4f4f4;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            margin-left: 10px;
            transition: background-color 0.3s ease;
        }

        .navbar .nav-links a.nav-link {
            background-color: #007bff;
        }

        .navbar .nav-links a.nav-link:hover {
            background-color: #0056b3;
        }

        .navbar .nav-links a.logout-link {
            background-color: #dc3545;
        }

        .navbar .nav-links a.logout-link:hover {
            background-color: #c82333;
        }

        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-container {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="tel"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }

        .btn {
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-add {
            background-color: #28a745;
        }

        .btn-add:hover {
            background-color: #218838;
        }

        .btn-update {
            background-color: #007bff;
        }

        .btn-update:hover {
            background-color: #0056b3;
        }

        .btn-cancel {
            background-color: #6c757d;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
            line-height: normal;
            vertical-align: middle;
        }

        .btn-cancel:hover {
            background-color: #5a6268;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
            padding: 5px 8px;
            font-size: 13px;
            margin-right: 5px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-deactivate {
            background-color: #dc3545;
            color: white;
            padding: 5px 8px;
            font-size: 13px;
            margin-right: 5px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
        }

        .btn-deactivate:hover {
            background-color: #c82333;
        }

        .btn-activate {
            background-color: #17a2b8;
            color: white;
            padding: 5px 8px;
            font-size: 13px;
            margin-right: 5px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
        }

        .btn-activate:hover {
            background-color: #138496;
        }

        .form-actions {
            text-align: right;
            margin-top: 15px;
        }

        .employee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .employee-table th,
        .employee-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }

        .employee-table th {
            background-color: #e9ecef;
            color: #495057;
        }

        .employee-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .employee-table tr:hover {
            background-color: #dee2e6;
        }

        .employee-table td .actions {
            white-space: nowrap;
        }

        .status-active {
            color: green;
            font-weight: bold;
        }

        .status-inactive {
            color: red;
            font-weight: bold;
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

    <div class="navbar">
        <span>Welcome, <?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : 'User'); ?>!</span>
        <div class="nav-links">
            <a href="index.php" class="nav-link">Main Page</a>
            <a href="logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>Employee Management</h1>

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

        <div class="form-container" id="employee-form-container">
            <h2><?php echo $edit_mode ? 'Edit Employee' : 'Add New Employee'; ?></h2>
            <form action="manage_employees.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_employee_data['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($edit_employee_data['username'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($edit_employee_data['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($edit_employee_data['location'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-actions">
                    <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($edit_employee_data['id'] ?? ''); ?>">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_employee" class="btn btn-update">Update Employee</button>
                        <a href="manage_employees.php" class="btn btn-cancel">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_employee" class="btn btn-add">Add Employee</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <h2>Employee List</h2>
        <div style="overflow-x:auto;">
            <table class="employee-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Registered Date</th>
                        <th>Registered By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr <?php echo ($edit_mode && isset($edit_employee_data['id']) && $edit_employee_data['id'] == $emp['id']) ? 'style="background-color: #cfe2ff;"' : ''; // Highlight row being edited 
                                ?>>
                                <td><?php echo htmlspecialchars($emp['id']); ?></td>
                                <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['username']); ?></td>
                                <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                                <td><?php echo htmlspecialchars($emp['location']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($emp['created_at']))); ?></td>
                                <td><?php echo htmlspecialchars($emp['registered_by'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-<?php echo htmlspecialchars($emp['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($emp['status'])); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="manage_employees.php?edit=<?php echo $emp['id']; ?>#employee-form-container" class="btn btn-edit">Edit</a>
                                    <?php if ($emp['status'] == 'active'): ?>
                                        <a href="manage_employees.php?deactivate=<?php echo $emp['id']; ?>" class="btn btn-deactivate" onclick="return confirm('Are you sure you want to deactivate this employee?');">Deactivate</a>
                                    <?php else: ?>
                                        <a href="manage_employees.php?activate=<?php echo $emp['id']; ?>" class="btn btn-activate" onclick="return confirm('Are you sure you want to activate this employee?');">Activate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center;">No employees found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>