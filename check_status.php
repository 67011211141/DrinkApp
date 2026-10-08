<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$phone = $_GET['phone'] ?? '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุเบอร์โทรศัพท์']);
    exit;
}

try {
    // ดึงรายการออเดอร์ทั้งหมดของเบอร์โทรนี้
    $stmt = $pdo->prepare("SELECT o.*, p.slip_image_url 
                           FROM orders o 
                           LEFT JOIN payments p ON o.id = p.order_id 
                           WHERE o.customer_phone = ? 
                           ORDER BY o.id DESC");
    $stmt->execute([$phone]);
    $orders = $stmt->fetchAll();

    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])) . '/';

    // ดึงรายการแก้วในแต่ละออเดอร์
    foreach ($orders as &$order) {
        $stmt_items = $pdo->prepare("SELECT beverage_name, type, sweetness, price, quantity, subtotal FROM order_items WHERE order_id = ?");
        $stmt_items->execute([$order['id']]);
        $order['items'] = $stmt_items->fetchAll();

        if (!empty($order['slip_image_url'])) {
            $order['slip_image_url'] = $base_url . $order['slip_image_url'];
        }
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}