<?php
session_start(); // Start session to store notification message

$wsdl = "http://localhost/restaurant/restaurant.wsdl";

$responseMessage = "";
$menuItems = []; // Initialize an array to store menu items

try {
    $client = new SoapClient($wsdl);

    // Fetch menu items using getMenu operation
    $response = $client->getMenu();
    if (isset($response->return)) {
        $menuItems = $response->return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if (isset($_POST['action']) && $_POST['action'] === 'insertMenuItem') {
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
        
                if ($_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['image_file']['tmp_name'];
                    $image_data = file_get_contents($tmp_name);
                    $image_data_base64 = base64_encode($image_data);
        
                    try {
                        $response = $client->insertMenuItem([
                            'name' => $name,
                            'description' => $description,
                            'price' => $price,
                            'image_data' => $image_data_base64
                        ]);
        
                        if (isset($response->return) && $response->return === 'Menu item inserted successfully') {
                            $responseMessage = "Insert Menu Item Response: " . $response->return;
                        } else {
                            $responseMessage = "Insert Menu Item failed."; // Handle failure case
                        }
                    } catch (SoapFault $e) {
                        $responseMessage = "SOAP Fault: " . $e->getMessage();
                    }
                } else {
                    $responseMessage = "Failed to upload image file."; // Handle upload error
                }
            } elseif ($_POST['action'] === 'calculateMonthlySales') {
                $response = $client->calculateMonthlySales([
                    'month' => intval($_POST['month']),
                    'year' => intval($_POST['year'])
                ]);

                // Check if response is valid and has monthly_sales
                if (isset($response->monthly_sales)) {
                    $monthlySales = number_format(floatval($response->monthly_sales), 2);
                    $responseMessage = "Monthly Sales: RM" . $monthlySales;
                } else {
                    $responseMessage = "Unable to fetch monthly sales.";
                }
            } elseif ($_POST['action'] === 'deleteMenuItem' && isset($_POST['name'])) {
                try {
                    $response = $client->deleteMenuItem(['name' => $_POST['name']]);
                    if (isset($response->return) && $response->return === 'Menu item deleted successfully') {
                        $_SESSION['deleteSuccess'] = true; // Set session variable for successful delete
                        $responseMessage = "Delete Menu Item Response: " . $response->return;
                    } else {
                        $responseMessage = "Delete Menu Item failed: " . (isset($response->return) ? $response->return : 'Unknown error'); // Handle failure case
                    }
                } catch (SoapFault $e) {
                    $responseMessage = "SOAP Fault: " . $e->getMessage();
                }
            }
            // Handle other actions as needed
        }
    }
} catch (SoapFault $e) {
    $responseMessage = "SOAP Fault: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Restaurant Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }
        header {
            background: #343a40;
            color: #ffffff;
            padding-top: 30px;
            min-height: 70px;
            border-bottom: #007bff 3px solid;
        }
        header a {
            color: #ffffff;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 16px;
        }
        #showcase {
            min-height: 400px;
            background: url('restaurant.jpg') no-repeat 0 -400px;
            text-align: center;
            color: #ffffff;
        }
        #showcase h1 {
            margin-top: 100px;
            font-size: 55px;
            margin-bottom: 10px;
        }
        .form-container {
            padding: 30px;
            background: #ffffff;
            box-shadow: 0px 0px 10px 0px #ccc;
            margin-top: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            margin-bottom: 5px;
            display: block;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group button {
            background: #007bff;
            color: #ffffff;
            border: 0;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-group button:hover {
            background: #0056b3;
        }
        .response-message {
            margin-top: 20px;
            padding: 10px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
        table td button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
        }
        table td button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <h1>Admin Page</h1>
    </div>
</header>
<div class="container">
    <div class="form-container">
        <h2>Insert Menu Item</h2>
        <form method="POST" action="admin.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="insertMenuItem">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea name="description" id="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="text" name="price" id="price" required>
            </div>
            <div class="form-group">
                <label for="image_file">Image File:</label>
                <input type="file" name="image_file" id="image_file" required accept="image/*">
            </div>
            <div class="form-group">
                <button type="submit">Insert Menu Item</button>
            </div>
        </form>
    </div>
    <div class="form-container">
        <h2>Calculate Monthly Sales</h2>
        <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="calculateMonthlySales">
            <div class="form-group">
                <label for="month">Month:</label>
                <input type="number" name="month" id="month" required>
            </div>
            <div class="form-group">
                <label for="year">Year:</label>
                <input type="number" name="year" id="year" required>
            </div>
            <div class="form-group">
                <button type="submit">Calculate Sales</button>
            </div>
            <?php if (!empty($responseMessage)): ?>
                <div class="response-message">
                    <?php echo $responseMessage; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
    <div class="form-container">
        <h2>Delete Menu Item</h2>
        <?php if (!empty($menuItems)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menuItems as $menuItem): ?>
                        <tr>
                            <td><?php echo $menuItem->name; ?></td>
                            <td><?php echo $menuItem->description; ?></td>
                            <td><?php echo $menuItem->price; ?></td>
                            <td>
                                <form method="POST" action="admin.php">
                                    <input type="hidden" name="action" value="deleteMenuItem">
                                    <input type="hidden" name="name" value="<?php echo $menuItem->name; ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No menu items available.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>