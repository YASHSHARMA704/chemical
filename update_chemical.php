<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cim_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $sno = $_POST['sno'];
    $chemical_name = $conn->real_escape_string($_POST['chemical_name']);
    $common_name = $conn->real_escape_string($_POST['common_name']);
    $category = $conn->real_escape_string($_POST['category']);
    $quantity = $conn->real_escape_string($_POST['quantity']);
    $unit = $conn->real_escape_string($_POST['unit']);
    $remark = $conn->real_escape_string($_POST['remark']);

    // Update query
    $query = "UPDATE chemicals SET chemical_name='$chemical_name', common_name='$common_name', category='$category', quantity='$quantity', unit='$unit', remark='$remark', is_updated=NOW() WHERE sno='$sno'";

    if ($conn->query($query) === TRUE) {
        // Entry updated successfully
        echo "<script>alert('Entry has been updated successfully.'); window.location.href = 'view_details.php';</script>";
        exit;
    } else {
        // Error occurred
        echo "Error: " . $query . "<br>" . $conn->error;
    }
}

$conn->close();
?>
