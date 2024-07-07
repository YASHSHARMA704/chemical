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

// Fetch user's name for welcome message
$username = $_SESSION['username'];

// Pagination
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = $search ? "WHERE chemical_name LIKE '%$search%'" : '';

// Query to fetch chemicals for the current page with search filter
$query = "SELECT * FROM chemicals $search_condition LIMIT $start, $limit";
$result = $conn->query($query);

// Total number of entries with search filter applied
$total_query = "SELECT COUNT(*) FROM chemicals $search_condition";
$total_result = $conn->query($total_query);
$total_entries = $total_result->fetch_row()[0];

// Calculate total pages
$total_pages = ceil($total_entries / $limit);

// Calculate pagination range
$pagination_range = 5; // Number of pages to show in the pagination bar
$pagination_start = max(1, $page - floor($pagination_range / 2));
$pagination_end = min($total_pages, $pagination_start + $pagination_range - 1);
$pagination_start = max(1, $pagination_end - $pagination_range + 1);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* General styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background-color: #e0f7fa;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            /* Removed min-height to allow content to expand */
            /* margin:auto; */

        }

        .welcome-message {
            font-size: 1.4em;
            color: #555555;
            text-align: left; /* Aligned to the left */
            margin-bottom: 10px; /* Added margin bottom for spacing */
        }

        .button-container {
            display: flex;
            justify-content: center;
            align-items: center; /* Vertically center items */
            margin-bottom: 10px; /* Reduced margin-bottom */
            gap: 10px;
        }

        .button-container button {
            padding: 10px 20px; /* Handling the size of buttons */
            border: none;
            border-radius: 6px;
            font-size: 1.1em; /* Increased font size */
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease; /* Added transition */
        }

        .button-container .add-chemical {
            background-color: #388e3c; /* Changed background color */
            color: #fff;
        }

        .button-container .print {
            background-color: #0288d1; /* Changed background color */
            color: #fff;
        }

        .button-container .add-chemical:hover {
            background-color: #4caf50;
        }

        .button-container .print:hover {
            background-color: #039be5;
        }

        .search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .search-container input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            width: 250px;
        }

        .search-container h2 {
            margin: 0;
            font-size: 27px; /* Increased size */
            color: #00796b; /* Changed color */
            font-family: 'Georgia', serif; /* Changed font style */
        }

        table {
            width: 100%; /* Table takes full width of container */
            border-collapse: collapse;
            margin-top: 10px; /* Added margin top */
            text-align: center;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 6px; /* Increased padding for better spacing */
        }

        table th {
            background-color: #00796b; /* Changed background color */
            color: #fff;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #e0f7fa; /* Changed background color */
        }

        .logout-container {
            text-align: center;
        }

        .logout-container button {
            padding: 8px 15px; /* Increased size of the button */
            cursor: pointer;
            background-color: #d32f2f; /* Changed background color */
            color: #fff;
            transition: background-color 0.3s ease, color 0.3s ease; /* Added transition */
            border: none;
            border-radius: 6px;
        }

        .logout-container button:hover {
            background-color: #f44336; /* Light red on hover */
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 10px 0; /* Adjusted margin */
        }

        .pagination a {
            padding: 6px 7px;
            text-decoration: none;
            border: 1px solid #00796b; /* Added border */
            border-radius: 4px; /* Added border radius */
            color: #00796b; /* Changed color */
        }

        .pagination a.active {
            background-color: #00796b;
            color: #fff;
        }

        .pagination a:hover {
            background-color: #039be5;
            color: #fff;
        }
        
    </style>
    <script>
        function searchChemicals() {
            let input, filter, table, tr, td, i, j, txtValue;
            input = document.getElementById("search");
            filter = input.value.toUpperCase();
            table = document.getElementById("chemicalsTable");
            tr = table.getElementsByTagName("tr");

            // Loop through all table rows, starting from index 1 (to skip header row)
            for (i = 1; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td");
                let found = false;
                // Loop through all table columns in the current row
                for (j = 0; j < td.length; j++) {
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        // Check if the current cell contains the search filter
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break; // Stop checking further cells in this row
                        }
                    }
                }
                // Show or hide the row based on search results
                tr[i].style.display = found ? "" : "none";
            }
        }
    </script>

</head>

<body>

    <div class="container">
        <?php include 'navbar.php'; ?>
        <div class="welcome-message">Welcome, <?php echo $username; ?>!</div>

        <div class="button-container">
            <button class="view-chemical bn3637" onclick="window.location.href='view_details.php'">View Details</button>
            <button class="add-chemical bn3637" onclick="window.location.href='my_form.php'">Add New</button>
            <button class="print bn3637" onclick="window.print()">Print</button>
            <div class="logout-container">
                <button onclick="window.location.href='logout.php'" class="bn3637">Logout</button>
            </div>
        </div>

        <div class="grouping">
            <div class="search-container">
                <h2>Available Chemicals</h2>
                <input type="text" id="search" name="search" placeholder="Search..." onkeyup="searchChemicals()">
            </div>

            <div style="overflow-x:auto;">
                <table id="chemicalsTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>User Name</th>
                            <th>Designation Id</th>
                            <th>Group Id</th>
                            <th>CAS No.</th>
                            <th>Chemical Name</th>
                            <th>Common Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $serial_number = ($page - 1) * $limit + 1; // Initialize serial number for current page

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $serial_number++ . "</td>"; // Increment and display serial number
                                echo "<td>" . $row['username'] . "</td>";
                                echo "<td>" . $row['desig_id'] . "</td>";
                                echo "<td>" . $row['group_id'] . "</td>";
                                echo "<td>" . $row['cas_no'] . "</td>";
                                echo "<td>" . $row['chemical_name'] . "</td>";
                                echo "<td>" . $row['common_name'] . "</td>";
                                echo "<td>" . $row['category'] . "</td>";
                                echo "<td>" . $row['quantity'] . "</td>";
                                echo "<td>" . $row['unit'] . "</td>";
                                echo "<td>" . $row['remark'] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8'>No chemicals found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">
            <?php if ($page > 1) : ?>
                <a href="?page=1<?php echo $search ? '&search='.$search : ''; ?>">&laquo;</a>
                <a href="?page=<?php echo ($page - 1); ?><?php echo $search ? '&search='.$search : ''; ?>">&lt;</a>
            <?php endif; ?>
            <?php
            for ($i = $pagination_start; $i <= $pagination_end; $i++) {
                echo "<a href='?page=$i" . ($search ? "&search=$search" : '') . "' class='" . ($page == $i ? "active" : "") . "'>$i</a>";
            }
            ?>
            <?php if ($page < $total_pages) : ?>
                <a href="?page=<?php echo ($page + 1); ?><?php echo $search ? '&search='.$search : ''; ?>">&gt;</a>
                <a href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search='.$search : ''; ?>">&raquo;</a>
            <?php endif; ?>
        </div>

    </div>

</body>

</html>

<?php
$conn->close();
?>
