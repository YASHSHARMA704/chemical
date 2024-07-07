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

// Fetch user's details from session
$username = $_SESSION['username'];
$desig_id = $_SESSION['desig_id'];
$group_id = $_SESSION['group_id'];

// Pagination
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search ? "AND chemical_name LIKE '%$search%'" : '';

// Query to fetch chemicals for the current page with search filter
$query = "SELECT * FROM chemicals WHERE username = '$username' AND desig_id = '$desig_id' AND group_id = '$group_id' $search_condition LIMIT $start, $limit";
$result = $conn->query($query);

// Check for SQL errors
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Total number of entries with search filter applied
$total_query = "SELECT COUNT(*) AS total FROM chemicals WHERE username = '$username' AND desig_id = '$desig_id' AND group_id = '$group_id' $search_condition";
$total_result = $conn->query($total_query);

// Check for SQL errors
if (!$total_result) {
    die("Total query failed: " . $conn->error);
}

$total_entries = $total_result->fetch_assoc()['total'];

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
    <title>View Details</title>
    <style>
       body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e0f7fa;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            width: 78%;
            max-width: 1200px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .button-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            margin-top: 50px;
            gap: 10px;
        }

        .button-container button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            background-image: linear-gradient(to right, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .button-container .print {
            background-image: linear-gradient(to right, #ff0844 0%, #ffb199 100%);
        }

        .button-container button:hover {
            filter: brightness(110%);
        }

        .button-container .add-chemical:hover {
            background-image: linear-gradient(to right, #00c6ff 0%, #0072ff 100%);
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
            font-size: 27px;
            color: #00796b;
            font-family: 'Georgia', serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            text-align: center;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        table th {
            background-color: #00796b;
            color: #fff;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #e0f7fa;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 10px 0;
        }

        .pagination a {
            padding: 6px 7px;
            text-decoration: none;
            border: 1px solid #00796b;
            border-radius: 4px;
            color: #00796b;
        }

        .pagination a.active {
            background-color: #00796b;
            color: #fff;
        }

        .pagination a:hover {
            background-color: #039be5;
            color: #fff;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin-top: 25px;
            margin-left:auto;
            margin-right:auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%; /* Adjust the width of the modal here */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .modal-content h2 {
            margin-top: 0;
            color: #00796b;
        }

        .modal-content label {
            font-weight: bold;
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content select,
        .modal-content textarea {
            width: 100%;
            padding: 8px;
            margin: 6px 0;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .modal-content textarea {
            height: 100px;
            resize: vertical;
        }

        .modal-content button {
            background-color: #00796b;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin-top: 10px;
            cursor: pointer;
            border-radius: 4px;
        }

        .modal-content button:hover {
            background-color: #005a4a;
        }
    </style>

    <script>
        // Function to open edit modal
        function openEditModal(sno, chemical_name, common_name, category, quantity, unit, remark) {
            // Populate modal fields
            document.getElementById('edit_sno').value = sno;
            document.getElementById('edit_chemical_name').value = chemical_name;
            document.getElementById('edit_common_name').value = common_name;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_unit').value = unit;
            document.getElementById('edit_remark').value = remark;

            // Show the modal
            document.getElementById('editModal').style.display = 'block';
        }

        // Function to close modals
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Function to open delete modal
        function openDeleteModal(sno) {
            // Set the delete button's data-sno attribute
            document.getElementById('confirmDeleteButton').setAttribute('data-sno', sno);

            // Show the delete modal
            document.getElementById('deleteModal').style.display = 'block';
        }

        // Function to handle delete confirmation
        function confirmDelete() {
            var sno = document.getElementById('confirmDeleteButton').getAttribute('data-sno');
            alert('Entry has been deleted successfully.');
            // Redirect to delete script with sno as a query parameter
            window.location.href = 'delete_chemical.php?sno=' + sno;
        }
    </script>
</head>
<body>
    <div class="container">
        <?php include 'navbar.php'; ?>

        <div class="button-container">
            <button class="add-chemical" onclick="window.location.href='dashboard.php'">Back</button>
            <button class="print" onclick="window.print()">Print</button>
        </div>

        <div class="search-container">
            <h2>View Details</h2>
            <form method="get" action="">
                <input type="text" name="search" placeholder="Search chemical" value="<?php echo $search; ?>">
                <button class="add-chemical" type="submit">Search</button>
            </form>
        </div>

        <table>
            <tr>
                <th>S. No.</th>
                <th>Chemical Name</th>
                <th>Common Name</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Remark</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['sno']; ?></td>
                    <td><?php echo $row['chemical_name']; ?></td>
                    <td><?php echo $row['common_name']; ?></td>
                    <td><?php echo $row['category']; ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $row['unit']; ?></td>
                    <td><?php echo $row['remark']; ?></td>
                    <td>
                        <button style="background-color: #00796b; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;" onclick="openEditModal('<?php echo $row['sno']; ?>', '<?php echo $row['chemical_name']; ?>', '<?php echo $row['common_name']; ?>', '<?php echo $row['category']; ?>', '<?php echo $row['quantity']; ?>', '<?php echo $row['unit']; ?>', '<?php echo $row['remark']; ?>')">Edit</button>
                        <button style="background-color: #ff0844; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;" onclick="openDeleteModal('<?php echo $row['sno']; ?>')">Delete</button>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <div class="pagination">
            <?php if ($page > 1) { ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>">&laquo; Previous</a>
            <?php } ?>

            <?php for ($i = $pagination_start; $i <= $pagination_end; $i++) { ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php } ?>

            <?php if ($page < $total_pages) { ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>">Next &raquo;</a>
            <?php } ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit Chemical</h2>
            <form method="post" action="update_chemical.php">
                <input type="hidden" id="edit_sno" name="sno">
                <label for="edit_chemical_name">Chemical Name:</label>
                <input type="text" id="edit_chemical_name" name="chemical_name" required>
                <label for="edit_common_name">Common Name:</label>
                <input type="text" id="edit_common_name" name="common_name">
                <label for="edit_category">Category:</label>
                <select id="edit_category" name="category" required>
                    <option value="concentrated">Concentrated</option>
                    <option value="dilute">Dilute</option>
                    <option value="solvent">Solvent</option>
                </select>
                <label for="edit_quantity">Quantity:</label>
                <input type="number" id="edit_quantity" name="quantity" step="0.01" required>
                <label for="edit_unit">Unit:</label>
                <select id="edit_unit" name="unit" required>
                    <option value="kg">kg</option>
                    <option value="gm">gm</option>
                    <option value="L">L</option>
                    <option value="ml">ml</option>
                </select>
                <label for="edit_remark">Remark:</label>
                <textarea id="edit_remark" name="remark"></textarea>
                <button type="submit">Update</button>
                <button type="button" onclick="closeModal('editModal')">Back</button>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this chemical entry?</p>
            <button id="confirmDeleteButton" style="background-color: #ff0844; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;" onclick="confirmDelete()">Delete</button>
            <button type="button" style="background-color: #00796b; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
