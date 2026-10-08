<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

// รับค่า JSON จาก Flutter
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || empty($data['phone_number']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้องหรือตะกร้าสินค้าว่างเปล่า']);
    exit;
}

$phone_number = trim($data['phone_number']);
$customer_name = trim($data['customer_name'] ?? '');
$note = trim($data['note'] ?? '');
$items = $data['items']; // Array ของรายการสินค้า

try {
    $pdo->beginTransaction();

    // 1. ตรวจสอบหรือเพิ่มข้อมูลลูกค้าใหม่
    $stmt_cust = $pdo->prepare("SELECT id FROM customers WHERE phone_number = ?");
    $stmt_cust->execute([$phone_number]);
    if (!$stmt_cust->fetch()) {
        $stmt_ins_cust = $pdo->prepare("INSERT INTO customers (phone_number, name) VALUES (?, ?)");
        $stmt_ins_cust->execute([$phone_number, $customer_name]);
    }

    // 2. คำนวณราคารวม
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += ($item['price'] * $item['quantity']);
    }

    // 3. สร้าง Order No. (เช่น ORD-20261001-XXXX)
    $order_no = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

    // 4. บันทึกลงตาราง orders
    $stmt_order = $pdo->prepare("INSERT INTO orders (order_no, customer_phone, total_amount, note, status) VALUES (?, ?, ?, ?, 'pending_payment')");
    $stmt_order->execute([$order_no, $phone_number, $total_amount, $note]);
    $order_id = $pdo->lastInsertId();

    // 5. บันทึกรายการเครื่องดื่มลงตาราง order_items
    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, beverage_id, beverage_name, type, sweetness, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $stmt_item->execute([
            $order_id,
            $item['beverage_id'] ?? null,
            $item['beverage_name'],
            $item['type'] ?? 'เย็น',
            $item['sweetness'] ?? '100%',
            $item['price'],
            $item['quantity'],
            $subtotal
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'สร้างคำสั่งซื้อสำเร็จ',
        'order_id' => (int)$order_id,
        'order_no' => $order_no,
        'total_amount' => (float)$total_amount
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>