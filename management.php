<?php
// management.php
try {
    $client = new SoapClient("http://localhost/restaurant/restaurant.wsdl");
} catch (SoapFault $e) {
    echo "SOAP Client Error: " . $e->getMessage();
    exit;
}

function fetchOrdersByStatus($client, $status) {
    try {
        $response = $client->getOrdersByStatus(['status' => $status]);
        if (is_object($response) && isset($response->return)) {
            return is_array($response->return) ? $response->return : [$response->return];
        } else {
            echo "<pre>Unexpected getOrdersByStatus response structure: " . print_r($response, true) . "</pre>";
            error_log("Unexpected getOrdersByStatus response structure: " . print_r($response, true));
            return [];
        }
    } catch (SoapFault $e) {
        echo "<pre>SOAP Error (getOrdersByStatus): " . $e->getMessage() . "</pre>";
        error_log("SOAP Error (getOrdersByStatus): " . $e->getMessage());
        return [];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['action'];
    
    if ($action == 'complete') {
        // Existing completion logic
        $order_id = $_POST['order_id'];
        error_log("Attempting to update order ID: $order_id");

        $params = [
            'orderId' => $order_id,
            'newStatus' => 'Completed'
        ];
        try {
            $response = $client->updateOrderStatus((object)$params);
            echo "<script>alert('Order ID $order_id has been marked as Completed.')</script>";
        } catch (SoapFault $e) {
            echo "<script>alert('Failed to update order status: " . $e->getMessage() . "')</script>";
            error_log("Failed to update order status: " . $e->getMessage());
        }
    } elseif ($action == 'cancel') {
        // New cancellation logic
        $params = ['orderId' => $order_id];
        try {
            $response = $client->cancelOrder((object)$params);
            echo "<script>alert('Order ID $order_id has been cancelled.')</script>";
        } catch (SoapFault $e) {
            echo "<script>alert('Failed to cancel order: " . $e->getMessage() . "')</script>";
            error_log("Failed to cancel order: " . $e->getMessage());
        }
    }
}


$orders_pending = fetchOrdersByStatus($client, 'Pending');
$orders_completed = fetchOrdersByStatus($client, 'Completed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        .section {
            margin-bottom: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .date-column {
            width: 150px;
        }
        .no-orders {
            text-align: center;
            font-style: italic;
            color: #888;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
        }
        .complete-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 15px;
        }
        .complete-button:hover {
            background-color: #45a049;
        }
        .cancel-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 15px;
        }
        .cancel-button:hover {
            background-color: #e53935;
        }
        form {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Order Management</h1>
        <hr>
        <div class="section">
            <h2>Pending Orders</h2>
            <?php if (!empty($orders_pending)) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Orders</th>
                            <th>Total (RM)</th>
                            <th>Status</th>
                            <th class="date-column">Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_pending as $order) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order->order_id); ?></td>
                                <td><?php echo htmlspecialchars($order->customer_name); ?></td>
                                <td><?php echo htmlspecialchars($order->items); ?></td>
                                <td><?php echo htmlspecialchars($order->total); ?></td>
                                <td><?php echo htmlspecialchars($order->status); ?></td>
                                <td class="date-column"><?php echo htmlspecialchars($order->sales_date); ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="order_id" value="<?php echo $order->order_id; ?>">
                                        <div class="button-container">
                                            <button type="submit" name="action" value="complete" class="complete-button">Mark as Completed</button>
                                            <button type="submit" name="action" value="cancel" class="cancel-button">Cancel Order</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="no-orders">No pending orders found.</p>
            <?php endif; ?>
        </div>
        <div class="section">
            <hr>
            <h2>Completed Orders</h2>
            <?php if (!empty($orders_completed)) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="date-column">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_completed as $order) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order->order_id); ?></td>
                                <td><?php echo htmlspecialchars($order->customer_name); ?></td>
                                <td><?php echo htmlspecialchars($order->items); ?></td>
                                <td><?php echo htmlspecialchars($order->total); ?></td>
                                <td><?php echo htmlspecialchars($order->status); ?></td>
                                <td class="date-column"><?php echo htmlspecialchars($order->sales_date); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="no-orders">No completed orders found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
