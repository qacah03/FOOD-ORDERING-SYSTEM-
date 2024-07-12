<?php
// Fetch menu items from the SOAP service
// client.php
try {
    // Creating a new SOAP client instance.
    $client = new SoapClient("http://localhost/restaurant/restaurant.wsdl");
    $response = $client->getMenu();
    $drinksResponse = $client->getDrinks();
    
    // Check if the response is an object and has the 'return' property for both menus
    if (is_object($response) && isset($response->return) && is_array($response->return)) {
        $menu = $response->return;
    } else {
        $menu = [];
        echo "<pre>Unexpected response structure: " . print_r($response, true) . "</pre>";
        error_log("Unexpected response structure: " . print_r($response, true));
    }

    if (is_object($drinksResponse) && isset($drinksResponse->return) && is_array($drinksResponse->return)) {
        $menu_drinks = $drinksResponse->return;
    } else {
        $menu_drinks = [];
        echo "<pre>Unexpected drinks response structure: " . print_r($drinksResponse, true) . "</pre>";
        error_log("Unexpected drinks response structure: " . print_r($drinksResponse, true));
    }

} catch (SoapFault $e) {
    echo "<pre>SOAP Error: " . $e->getMessage() . "</pre>";
    error_log("SOAP Error: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['customer_name'], $_POST['food_item_ids'], $_POST['food_quantities'], $_POST['drink_item_ids'], $_POST['drink_quantities'])) {
    $customer_name = $_POST['customer_name'];
    $food_item_ids = $_POST['food_item_ids'];
    $food_quantities = $_POST['food_quantities'];
    $drink_item_ids = $_POST['drink_item_ids'];
    $drink_quantities = $_POST['drink_quantities'];

    // Prepare order details
    $items = [];
    $total = 0;

    // Process food items
    foreach ($food_item_ids as $index => $item_id) {
        $item_name = $menu[$item_id]->name;
        $item_price = $menu[$item_id]->price;
        $item_quantity = $food_quantities[$index];
        if ($item_quantity > 0) { // Only include items with quantity greater than 0
            $items[] = "$item_name (Quantity: $item_quantity)";
            $total += $item_price * $item_quantity;
        }
    }

    // Process drink items
    foreach ($drink_item_ids as $index => $item_id) {
        $item_name = $menu_drinks[$item_id]->name;
        $item_price = $menu_drinks[$item_id]->price;
        $item_quantity = $drink_quantities[$index];
        if ($item_quantity > 0) { // Only include items with quantity greater than 0
            $items[] = "$item_name (Quantity: $item_quantity)";
            $total += $item_price * $item_quantity;
        }
    }

    // Check if there are any items to place an order for
    if (empty($items)) {
        echo "<script>alert('No items selected for order.');</script>";
        // You might want to add additional handling here, like redirecting back to the form
    } else {
        // Prepare parameters for SOAP request
        $params = [
            'customer_name' => $customer_name,
            'items' => implode(", ", $items),
            'total' => $total
        ];

        try {
            $client = new SoapClient("http://localhost/restaurant/restaurant.wsdl");
            $response = $client->placeOrder((object)$params);
            if (is_object($response) && isset($response->return)) {
                echo "<script>alert('" . htmlspecialchars($response->return, ENT_QUOTES, 'UTF-8') . "')</script>";
            } else {
                echo "<pre>Unexpected response structure: " . print_r($response, true) . "</pre>";
            }
        } catch (SoapFault $e) {
            echo "<script>alert('SOAP Error: " . $e->getMessage() . "')</script>";
        }
        
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PASTA SANTAI - Menu</title>
    <!-- Include Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            background-image: url('https://images.unsplash.com/photo-1526906883421-7633deb8e8d3');
            background-size: cover;
            background-position: center;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        h1, h2 {
            text-align: center;
            color: #333;
            margin-top: 0;
        }
        .restaurant-name {
            margin-bottom: 20px;
            font-size: 36px;
            font-weight: bold;
            color: #333;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            font-family: 'Pacifico', cursive;
            background: linear-gradient(45deg, #ff6ec4, #7873f5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .welcome-message {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: normal;
            color: #555;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            font-family: 'Roboto', sans-serif;
        }
        form {
            margin-top: 20px;
        }
        input[type="text"], input[type="number"], button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .menu-image {
            width: 120px; /* Adjusted width for larger images */
            height: auto; /* Maintain aspect ratio */
        }
        #cart-container {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 300px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        #cart-container h2 {
            margin-top: 0;
        }
        #cart {
            margin-top: 20px;
        }
        #cart div {
            margin-bottom: 5px;
        }
        #cart hr {
            margin-top: 10px;
            margin-bottom: 10px;
            border: none;
            border-top: 1px solid #ddd;
        }
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        button[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="restaurant-name">HANIQACAH CAFE</h1>
        <hr>
        <div class="welcome-message">WELCOME</div>
        <form id="orderForm" method="post" onsubmit="return validateOrder()">
            <input type="text" name="customer_name" placeholder="Your Name" required><br>
            <hr>
            <h2>Foods</h2>
            <table>
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Description</th>
                        <th>Price (RM)</th>
                        <th>Quantity</th>
                        <th>Image</th>
                    </tr>
                </thead>
                <tbody id="menu">
                    <?php foreach ($menu as $index => $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo isset($item->description) ? htmlspecialchars($item->description, ENT_QUOTES, 'UTF-8') : ''; ?></td>
                            <td><?php echo htmlspecialchars($item->price, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><input type="number" name="food_quantities[]" value="0" min="0" onchange="updateCart(<?php echo $index; ?>, 'food')"></td>
                            <input type="hidden" name="food_item_ids[]" value="<?php echo $index; ?>">
                            <td><img src="data:image/jpeg;base64,<?php echo $item->image_data; ?>" alt="<?php echo htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8'); ?>" class="menu-image"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <h2>Drinks</h2>
            <table>
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Description</th>
                        <th>Price (RM)</th>
                        <th>Quantity</th>
                        <th>Image</th>
                    </tr>
                </thead>
                <tbody id="menu_drinks">
                    <?php foreach ($menu_drinks as $index => $drink): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($drink->name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo isset($drink->description) ? htmlspecialchars($drink->description, ENT_QUOTES, 'UTF-8') : ''; ?></td>
                            <td><?php echo htmlspecialchars($drink->price, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><input type="number" name="drink_quantities[]" value="0" min="0" onchange="updateCart(<?php echo $index; ?>, 'drink')"></td>
                            <input type="hidden" name="drink_item_ids[]" value="<?php echo $index; ?>">
                            <td><img src="data:image/jpeg;base64,<?php echo $drink->image_data; ?>" alt="<?php echo htmlspecialchars($drink->name, ENT_QUOTES, 'UTF-8'); ?>" class="menu-image"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>

    <div id="cart-container">
        <h2>Your Cart</h2>
        <div id="cart"></div>
        <button type="submit" form="orderForm">Submit Order</button>
    </div>

    <script>
        var cart = {};
        var menu = <?php echo json_encode($menu); ?>;
        var menu_drinks = <?php echo json_encode($menu_drinks); ?>;

        window.onload = function() {
            renderCart();
        };

        function updateCart(index, type) {
            var quantity;
            var itemId;
            if (type === 'food') {
                quantity = parseInt(document.querySelectorAll('input[name="food_quantities[]"]')[index].value);
                itemId = 'food_' + document.querySelectorAll('input[name="food_item_ids[]"]')[index].value;
                console.log('Food Item ID:', itemId);
            } else if (type === 'drink') {
                quantity = parseInt(document.querySelectorAll('input[name="drink_quantities[]"]')[index].value);
                itemId = 'drink_' + document.querySelectorAll('input[name="drink_item_ids[]"]')[index].value;
                console.log('Drink Item ID:', itemId);
            }

            if (quantity > 0) {
                cart[itemId] = quantity;
            } else {
                delete cart[itemId];
            }
            renderCart();
        }

        function renderCart() {
            var cartDiv = document.getElementById('cart');
            cartDiv.innerHTML = '';
            var totalPrice = 0;
            for (var itemId in cart) {
                var item;
                var itemQuantity = cart[itemId];
                if (itemId.startsWith('food')) {
                    var foodIndex = itemId.split('_')[1];
                    item = menu[foodIndex];
                } else if (itemId.startsWith('drink')) {
                    var drinkIndex = itemId.split('_')[1];
                    item = menu_drinks[drinkIndex];
                }
                if (item) {
                    var itemName = item.name;
                    var itemPrice = parseFloat(item.price);
                    var itemTotal = itemPrice * itemQuantity;
                    totalPrice += itemTotal;
                    cartDiv.innerHTML += '<div>' +
                        itemName + ' - Quantity: ' + itemQuantity +
                        ' - Total: RM' + itemTotal.toFixed(2) +
                        '</div>';
                }
            }
            cartDiv.innerHTML += '<hr>Total Price: RM' + totalPrice.toFixed(2);
        }

        function validateOrder() {
            if (Object.keys(cart).length === 0) {
                alert('No items selected for order.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
