<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$order_id = $_POST['order_id'] ?? null;
$amount_transferred = $_POST['amount_transferred'] ?? 0;

if (!$order_id || !isset($_FILES['slip'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุ order_id และแนบไฟล์สลิป']);
    exit;
}

$upload_dir = '../slips/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    // เซฟไฟล์รูปภาพสลิป
    $ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
    $filename = 'slip_ord_' . $order_id . '_' . time() . '.' . $ext;
    $target_file = $upload_dir . $filename;
    $db_path = 'slips/' . $filename;

    if (move_uploaded_file($_FILES['slip']['tmp_name'], $target_file)) {
        $pdo->beginTransaction();

        // บันทึกหลักฐานการชำระเงิน
        $stmt_pay = $pdo->prepare("INSERT INTO payments (order_id, slip_image_url, amount_transferred, status) VALUES (?, ?, ?, 'pending')");
        $stmt_pay->execute([$order_id, $db_path, $amount_transferred]);

        // อัปเดตสถานะออเดอร์เป็น 'pending_approval' (รอตรวจสลิป)
        $stmt_ord = $pdo->prepare("UPDATE orders SET status = 'pending_approval' WHERE id = ?");
        $stmt_ord->execute([$order_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'อัปโหลดสลิปเรียบร้อยแล้ว รอการตรวจสอบจากแอดมิน'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์รูปภาพได้']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}