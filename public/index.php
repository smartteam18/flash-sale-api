<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// header basic buat JSON + CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../src/Database.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(str_replace('/index.php', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');

// ambil input JSON kalau POST
$input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) ?? [] : [];

// helper buat response JSON
function response($status, $data)
{
    http_response_code($status);
    echo json_encode($data);
    exit();
}

try {
    $db = Database::getInstance();
    
    // ambil semua produk
    if ($method === 'GET' && $path === 'api/products') {
        $stmt = $db->query('SELECT * FROM products');
        response(200, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // ambil detail produk by id
    if ($method === 'GET' && preg_match('#^api/products/(\d+)$#', $path, $m)) {
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$m[1]]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        $product ? response(200, $product) : response(404, ['error' => 'Product not found']);
    }
    
    // bikin order baru
    if ($method === 'POST' && $path === 'api/orders') {
        $productId = $input['product_id'] ?? null;
        $qty = (int) ($input['quantity'] ?? 1);
        
        if (!$productId) response(400, ['error' => 'Product ID required']);
        if ($qty <= 0) response(400, ['error' => 'Quantity must be > 0']);
        
        // mulai transaksi biar stok aman
        $db->beginTransaction();
        
        try {
            // ambil produk + stok (SQLite ga ada FOR UPDATE)
            $stmt = $db->prepare('SELECT id, stock, flash_price, price FROM products WHERE id = ?');
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $db->rollBack();
                response(404, ['error' => 'Product not found']);
            }
            
            if ($product['stock'] < $qty) {
                $db->rollBack();
                response(409, [
                    'error' => 'Insufficient stock',
                    'available' => $product['stock']
                ]);
            }
            
            // update stok secara atomic
            $stmt = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
            $stmt->execute([$qty, $productId, $qty]);
            
            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                response(409, ['error' => 'Stock depleted by another customer']);
            }
            
            // bikin order
            $price = $product['flash_price'] ?? $product['price'];
            $orderNumber = 'ORD-' . uniqid(); // TODO: bikin generator order number lebih proper
            $total = $price * $qty;
            
            $stmt = $db->prepare('INSERT INTO orders (order_number, total) VALUES (?, ?)');
            $stmt->execute([$orderNumber, $total]);
            $orderId = $db->lastInsertId();
            
            $stmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $productId, $qty, $price]);
            
            $db->commit();
            
            response(201, [
                'message' => 'Order created',
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $total
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    // ambil detail order by id
    if ($method === 'GET' && preg_match('#^api/orders/(\d+)$#', $path, $m)) {
        $stmt = $db->prepare('
            SELECT o.*, oi.product_id, oi.quantity, oi.price 
            FROM orders o 
            LEFT JOIN order_items oi ON oi.order_id = o.id 
            WHERE o.id = ?
        ');
        $stmt->execute([$m[1]]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$rows) response(404, ['error' => 'Order not found']);
        
        $order = [
            'id' => $rows[0]['id'],
            'order_number' => $rows[0]['order_number'],
            'total' => $rows[0]['total'],
            'status' => $rows[0]['status'],
            'items' => array_map(fn($r) => [
                'product_id' => $r['product_id'],
                'quantity' => $r['quantity'],
                'price' => $r['price']
            ], $rows)
        ];
        response(200, $order);
    }
    
    // info API
    if ($method === 'GET' && $path === '') {
        response(200, [
            'api' => 'Flash Sale API',
            'endpoints' => [
                'GET /api/products',
                'GET /api/products/{id}',
                'POST /api/orders',
                'GET /api/orders/{id}'
            ]
        ]);
    }
    
    // fallback kalau endpoint ga ketemu
    response(404, ['error' => 'Endpoint not found']);
    
} catch (PDOException $e) {
    // TODO: log error ke file biar gampang trace
    response(500, ['error' => 'Database error', 'message' => $e->getMessage()]);
} catch (Exception $e) {
    response(500, ['error' => $e->getMessage()]);
}
