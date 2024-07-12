<?php
// server.php
ini_set("soap.wsdl_cache_enabled", "0");

class RestaurantService {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "dbrestaurant");
        if ($this->conn->connect_error) {
            error_log("Connection failed: " . $this->conn->connect_error);
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        } else {
            error_log("Database connected successfully");
        }
    }

    public function getMenu() {
        $result = $this->conn->query("SELECT name, description, price, image_data FROM menu");
        if ($result === false) {
            error_log("Error retrieving menu items: " . $this->conn->error);
            return [];
        }

        $menu = [];
        while ($row = $result->fetch_assoc()) {
            // Convert image data to base64 encoding
            $image_data = base64_encode($row['image_data']);
            // Construct menu item object with image data
            $menu[] = (object) [
                'name' => $row['name'], 
                'description' => $row['description'],
                'price' => $row['price'],
                'image_data' => $image_data
            ];
        }

        error_log("Retrieved menu items: " . json_encode($menu));

        return $menu;
    }

    public function getDrinks() {
        $result = $this->conn->query("SELECT name, description, price, image_data FROM menu_drinks");
        if ($result === false) {
            error_log("Error retrieving drinks items: " . $this->conn->error);
            return [];
        }

        $drinks = [];
        while ($row = $result->fetch_assoc()) {
            // Convert image data to base64 encoding
            $image_data = base64_encode($row['image_data']);
            // Construct drinks item object with image data
            $drinks[] = (object) [
                'name' => $row['name'], 
                'description' => $row['description'],
                'price' => $row['price'],
                'image_data' => $image_data
            ];
        }

        error_log("Retrieved drinks items: " . json_encode($drinks));

        return $drinks;
    }

    public function placeOrder($params) {
        $customer_name = $params->customer_name;
        $items = $params->items;
        $total = $params->total;

        // Log the received order details
        error_log("Received order: customer_name=$customer_name, items=$items, total=$total");

        $stmt = $this->conn->prepare("INSERT INTO orders (customer_name, items, total) VALUES (?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return "Failed to place order";
        }

        $stmt->bind_param("ssd", $customer_name, $items, $total);

        if ($stmt->execute()) {
            error_log("Order placed successfully");
            return (object) ['return' => 'Order placed successfully'];
        } else {
            error_log("Failed to place order: " . $stmt->error);
            return (object) ['return' => 'Failed to place order'];
        }        
    }

    public function getOrders() {
        $result = $this->conn->query("SELECT * FROM orders");
        if ($result === false) {
            error_log("Error retrieving orders: " . $this->conn->error);
            return [];
        }

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        error_log("Retrieved orders: " . json_encode($orders));

        return $orders;
    }

    public function getOrdersByStatus($status) {
        // Convert status to string if it's an object of type stdClass
        if (is_object($status) && property_exists($status, 'status')) {
            $status = $status->status;
        }
    
        // Sanitize the status parameter to prevent SQL injection
        $status = $this->conn->real_escape_string($status);
    
        error_log("getOrdersByStatus called with status: $status");
    
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE status = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return [];
        }
    
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result === false) {
            error_log("Error retrieving orders by status: " . $this->conn->error);
            return [];
        }
    
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    
        error_log("Retrieved orders by status $status: " . json_encode($orders));
    
        return $orders;
    }
    
    public function updateOrderStatus($params) {
        error_log("updateOrderStatus called with params: " . json_encode($params));
        
        $orderId = intval($params->orderId);
        $newStatus = $this->conn->real_escape_string($params->newStatus);
        
        error_log("Updating order ID: $orderId to status: $newStatus");
    
        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return "Failed to update order status";
        }
    
        $stmt->bind_param("si", $newStatus, $orderId);
    
        if ($stmt->execute()) {
            error_log("Order status updated successfully");
            return "Order status updated successfully";
        } else {
            error_log("Failed to update order status: " . $stmt->error);
            return "Failed to update order status";
        }
    }
    
    // Add this method to the RestaurantService class
    public function cancelOrder($params) {
        error_log("cancelOrder called with params: " . json_encode($params));
    
        $orderId = intval($params->orderId);
    
        error_log("Deleting order ID: $orderId");
    
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE order_id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return "Failed to cancel order";
        }
    
        $stmt->bind_param("i", $orderId);
    
        if ($stmt->execute()) {
            error_log("Order cancelled successfully");
            return (object) ['return' => 'Order cancelled successfully'];
        } else {
            error_log("Failed to cancel order: " . $stmt->error);
            return (object) ['return' => 'Failed to cancel order'];
        }
    }
    public function calculateMonthlySales($params) {
        $month = $params->month;
        $year = $params->year;

        $stmt = $this->conn->prepare("SELECT SUM(total) AS monthly_sales FROM orders WHERE MONTH(sales_date) = ? AND YEAR(sales_date) = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return "Failed to calculate monthly sales";
        }

        $stmt->bind_param("ii", $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (object) ['monthly_sales' => $row['monthly_sales']];
    }

    public function insertMenuItem($params) {
        $name = $params->name;
        $description = $params->description;
        $price = $params->price;
        $image_data = base64_decode($params->image_data);
    
        $stmt = $this->conn->prepare("INSERT INTO menu (name, description, price, image_data) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return (object) ['return' => 'Failed to insert menu item']; // Ensure response is an object
        }
    
        $stmt->bind_param("ssds", $name, $description, $price, $image_data);
    
        if ($stmt->execute()) {
            return (object) ['return' => 'Menu item inserted successfully']; // Ensure response is an object
        } else {
            error_log("Failed to insert menu item: " . $stmt->error);
            return (object) ['return' => 'Failed to insert menu item']; // Ensure response is an object
        }
    }

    public function deleteMenuItem($params) {
        $name = $params->name;
    
        $stmt = $this->conn->prepare("DELETE FROM menu WHERE name = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return (object) ['return' => 'Failed to delete menu item'];
        }
    
        $stmt->bind_param("s", $name);
    
        if ($stmt->execute()) {
            return (object) ['return' => 'Menu item deleted successfully'];
        } else {
            error_log("Failed to delete menu item: " . $stmt->error);
            return (object) ['return' => 'Failed to delete menu item'];
        }
    }
    
    
}

try {
    $server = new SoapServer("http://localhost/restaurant/restaurant.wsdl");
    $server->setClass("RestaurantService");
    $server->handle();
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "Exception: " . $e->getMessage();
}
?>