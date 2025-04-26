<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$employees = [];
$errors = [];
$success_message = '';

// --- Fetch existing employees ---
$sql_fetch = "SELECT id, first_name, last_name, email, phone, job_title, hire_date, status FROM employees ORDER BY first_name ASC";
$result = $conn->query($sql_fetch);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
} elseif (!$result) {
    $errors[] = "Error fetching employees: " . $conn->error; // Show error during development
}

// --- Handle Add Employee Form Submission (Basic Structure) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    // Get data from form (add validation later)
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $job_title = trim($_POST['job_title']);
    $hire_date = $_POST['hire_date']; // Needs validation

    // Basic validation example (expand this)
    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First name and Last name are required.";
    }
    // Add more validation (email format, phone format, date format etc.)

    // If no errors, insert into database (add prepared statements)
    if (empty($errors)) {
        $sql_insert = "INSERT INTO employees (first_name, last_name, email, phone, job_title, hire_date) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql_insert)) {
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone, $job_title, $hire_date);
            if ($stmt->execute()) {
                $success_message = "Employee added successfully!";
                // Refresh the page or list to show the new employee
                header("Location: manage_employees.php"); // Simple refresh
                exit();
            } else {
                $errors[] = "Error adding employee: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database error preparing statement: " . $conn->error;
        }
    }
}

// TODO: Add logic for Update and Deactivate actions

$conn->close(); // Close connection at the end

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <style>
        /* Reusing and adapting styles */
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

        .navbar a {
            color: #f4f4f4;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            margin-left: 10px;
        }

        .navbar a.nav-link {
            background-color: #007bff;
        }

        /* Blue for navigation */
        .navbar a.nav-link:hover {
            background-color: #0056b3;
        }

        .navbar a.logout-link {
            background-color: #dc3545;
        }

        /* Red for logout */
        .navbar a.logout-link:hover {
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

        /* Form Styles */
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
        .form-group input[type="email"],
        .form-group input[type="tel"],
        /* Use tel for phone */
        .form-group input[type="date"] {
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
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background-color: #218838;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-deactivate {
            background-color: #dc3545;
            color: white;
        }

        .btn-deactivate:hover {
            background-color: #c82333;
        }

        .btn-activate {
            background-color: #17a2b8;
            color: white;
        }

        .btn-activate:hover {
            background-color: #138496;
        }

        .form-actions {
            text-align: right;
            margin-top: 15px;
        }

        /* Align button to right */

        /* Table Styles */
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

        .employee-table td .actions a,
        .employee-table td .actions button {
            display: inline-block;
            padding: 5px 8px;
            margin-right: 5px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }

        /* Status Styles */
        .status-active {
            color: green;
            font-weight: bold;
        }

        .status-inactive {
            color: red;
            font-weight: bold;
        }

        /* Messages */
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
        <div>
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

        <div class="form-container">
            <h2>Add New Employee</h2>
            <form action="manage_employees.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="job_title">Job Title:</label>
                        <input type="text" id="job_title" name="job_title">
                    </div>
                    <div class="form-group">
                        <label for="hire_date">Hire Date:</label>
                        <input type="date" id="hire_date" name="hire_date">
                    </div>
                </div>
                <div class="form-actions">
                    <input type="hidden" name="employee_id" value="">
                    <button type="submit" name="add_employee" class="btn">Add Employee</button>
                </div>
            </form>
        </div>

        <h2>Employee List</h2>
        <div style="overflow-x:auto;">
            <table class="employee-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Job Title</th>
                        <th>Hire Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['id']); ?></td>
                                <td><?php echo htmlspecialchars($emp['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                                <td><?php echo htmlspecialchars($emp['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($emp['hire_date']); ?></td>
                                <td>
                                    <span class="status-<?php echo htmlspecialchars($emp['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($emp['status'])); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="manage_employees.php?edit=<?php echo $emp['id']; ?>" class="btn btn-edit">Edit</a>

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