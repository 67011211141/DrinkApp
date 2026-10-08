<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

try {
    // ดึงข้อมูลหมวดหมู่ทั้งหมด
    $stmt_cat = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC");
    $categories = $stmt_cat->fetchAll();

    // ดึงรายการเครื่องดื่มที่พร้อมขาย (is_available = 1)
    $stmt_bev = $pdo->query("SELECT b.id, b.category_id, b.name, b.description, b.price, b.image_url 
                             FROM beverages b 
                             WHERE b.is_available = 1 
                             ORDER BY b.id DESC");
    $beverages = $stmt_bev->fetchAll();

    // จัด Format path รูปภาพให้สมบูรณ์สำหรับ Mobile
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])) . '/';
    
    foreach ($beverages as &$b) {
        if (!empty($b['image_url']) && file_exists('../' . $b['image_url'])) {
            $b['image_url'] = $base_url . $b['image_url'];
        } else {
            $b['image_url'] = $base_url . 'beverages_images/no_image.png';
        }
    }

    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'beverages' => $beverages
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}