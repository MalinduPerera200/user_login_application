<?php
// Start session (optional, but good practice if you need user context later)
session_start();
// Could add login check here too if this endpoint needs protection
/*
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    echo '<p class="error-message">Access denied.</p>';
    exit();
}
*/

// Include DB connection
require_once 'db_connect.php';

// --- Get and Validate Employee ID ---
$employee_id = null;
if (isset($_GET['employee_id']) && filter_var($_GET['employee_id'], FILTER_VALIDATE_INT)) {
    $employee_id = $_GET['employee_id'];
} else {
    // No valid employee ID provided
    http_response_code(400); // Bad Request
    echo '<p class="error-message">Invalid request: Employee ID missing or invalid.</p>';
    exit();
}

// --- Fetch Sales Data ---
$sales_history = [];
// Fetch recent 10 sales, ordered by date descending
$sql = "SELECT sale_date, sale_amount FROM sales WHERE employee_id = ? ORDER BY sale_date DESC LIMIT 10";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sales_history[] = $row;
        }
    }
    $stmt->close();
} else {
    // Database error
    // In production, log this error instead of echoing details
    echo '<p class="error-message">Error fetching sales data: ' . htmlspecialchars($conn->error) . '</p>';
    $conn->close();
    exit();
}

$conn->close();

// --- Generate HTML Output ---
if (!empty($sales_history)) {
    echo '<table>';
    echo '<thead><tr><th>Sale Date</th><th>Amount</th></tr></thead>';
    echo '<tbody>';
    foreach ($sales_history as $sale) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars(date('M d, Y', strtotime($sale['sale_date']))) . '</td>'; // Format date nicely
        echo '<td>$' . htmlspecialchars(number_format($sale['sale_amount'], 2)) . '</td>'; // Format amount
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    // No sales found for this employee
    echo '<p class="no-sales">No recent sales found for this employee.</p>';
}
