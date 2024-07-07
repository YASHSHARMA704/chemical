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

// Retrieve username, desig_id, and group name from session
$username = $_SESSION['username'];
$desig_id = $_SESSION['desig_id'];
$group_id = $_SESSION['group_id'];

$error = ""; // Initialize the error variable
$success = false; // Initialize the success variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $casNumber = $_POST['casNumber'];
    $chemicalName = $_POST['chemicalName'];
    $commonName = $_POST['commonName'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $remarks = $_POST['remarks'];

    // Check if CAS No. already exists
    $checkStmt = $conn->prepare("SELECT * FROM chemicals WHERE cas_no = ?");
    $checkStmt->bind_param("s", $casNumber);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // CAS No. already exists, show alert and do not insert
        echo "<script>alert('Chemical with this CAS No. already exists');</script>";
    } else {
        // CAS No. does not exist, proceed with insertion
        $insertStmt = $conn->prepare("INSERT INTO chemicals (username, desig_id, group_id, cas_no, chemical_name, common_name, category, quantity, unit, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("siissssdss", $username, $desig_id, $group_id, $casNumber, $chemicalName, $commonName, $category, $quantity, $unit, $remarks);

        if ($insertStmt->execute()) {
            $success = true; // Set success flag
        } else {
            $error = "Error: " . $insertStmt->error;
        }

        $insertStmt->close();
    }

    $checkStmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Chemical</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e0f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin-top: 45px;
            margin-left: auto;
            margin-right:auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
            color: #555;
        }
        input[type="text"], input[type="number"], select {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
        }
        .btn {
            padding: 10px;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            width: 48%;
            text-align: center;
        }
        .btn.add {
            background: #28a745;
        }
        .btn.add:hover {
            background: #218838;
        }
        .btn.back {
            background: #007bff;
        }
        .btn.back:hover {
            background: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
    <script>
        function showAlert(message) {
            alert(message);
        }
    </script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>Add New Chemical Details</h1>
        <?php if ($success) : ?>
            <script>
                showAlert("Chemical Added Successfully");
            </script>
        <?php elseif (!empty($error)) : ?>
            <div class="error">
                <?php echo "Error adding chemical: " . $error; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="my_form.php">
            <label for="Username">Username</label>
            <input type="text" id="Username" name="username" value="<?php echo $username; ?>" readonly required>

            <label for="desig_id">Designation ID</label>
            <input type="text" id="desig_id" name="desig_id" value="<?php echo $desig_id; ?>" readonly required>
            
            <label for="group_id">Group Name</label>
            <input type="text" id="group_id" name="group_id" value="<?php echo $group_id; ?>" readonly required>
            
            <label for="casNumber">CAS No.</label>
            <input type="text" id="casNumber" name="casNumber" required>
            
            <label for="chemicalName">Chemical Name</label>
            <input type="text" id="chemicalName" name="chemicalName" required>
            
            <label for="commonName">Common Name</label>
            <input type="text" id="commonName" name="commonName">
            
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Select Category</option>
                <option value="concentrated">Concentrated</option>
                <option value="dilute">Dilute</option>
                <option value="solvent">Solvent</option>
            </select>
            
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" step="0.01" min="1" required>
            
            <label for="unit">Unit</label>
            <select id="unit" name="unit" required>
                <option value="">Select Unit</option>
                <option value="L">L</option>
                <option value="ml">ml</option>
                <option value="kg">kg</option>
                <option value="gm">gm</option>
            </select>
            
            <label for="remarks">Remarks</label>
            <input type="text" id="remarks" name="remarks">
            
            <div class="button-container">
                <input type="submit" class="btn add" value="Add Chemical">
                <button type="button" class="btn back" onclick="window.location.href='dashboard.php';">BACK</button>
            </div>
        </form>
    </div>
</body>
</html>
