<?php
require_once 'config.php';
include 'nav.php';

// 1. อนุมัติ / ปฏิเสธ ออเดอร์
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $pdo->prepare("UPDATE orders SET status = 'approved' WHERE id = ?")->execute([$order_id]);
        $pdo->prepare("UPDATE payments SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE order_id = ?")
            ->execute([$_SESSION['user_id'], $order_id]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE orders SET status = 'rejected' WHERE id = ?")->execute([$order_id]);
        $pdo->prepare("UPDATE payments SET status = 'rejected' WHERE order_id = ?")->execute([$order_id]);
    }
    
    // ส่งกลับหน้าเดิมพร้อม Parameter การค้นหาเดิม
    $query_string = $_SERVER['QUERY_STRING'];
    $clean_query = preg_replace('/&?action=[^&]*/', '', $query_string);
    $clean_query = preg_replace('/&?order_id=[^&]*/', '', $clean_query);
    header('Location: orders.php?' . $clean_query);
    exit;
}

// 2. รับค่าตัวกรองค้นหา
$filter_type = $_GET['filter_type'] ?? 'all';
$selected_date = $_GET['selected_date'] ?? date('Y-m-d');
$selected_month = $_GET['selected_month'] ?? date('Y-m');
$selected_year = $_GET['selected_year'] ?? date('Y');

$where_sql = "";
$params = [];
$filter_title = "ทั้งหมด";

if ($filter_type === 'day' && !empty($selected_date)) {
    $where_sql = " WHERE DATE(o.created_at) = :filter_val";
    $params[':filter_val'] = $selected_date;
    $filter_title = "ประจำวันที่ " . date('d/m/Y', strtotime($selected_date));
} elseif ($filter_type === 'month' && !empty($selected_month)) {
    $where_sql = " WHERE DATE_FORMAT(o.created_at, '%Y-%m') = :filter_val";
    $params[':filter_val'] = $selected_month;
    $filter_title = "ประจำเดือน " . date('m/Y', strtotime($selected_month . '-01'));
} elseif ($filter_type === 'year' && !empty($selected_year)) {
    $where_sql = " WHERE YEAR(o.created_at) = :filter_val";
    $params[':filter_val'] = $selected_year;
    $filter_title = "ประจำปี " . $selected_year;
}

// 3. ค้นหายอดขายรวมเฉพาะรายการที่ 'approved'
$sales_sql = "SELECT SUM(total_amount) as total_sales FROM orders o " . 
             ($where_sql ? $where_sql . " AND status = 'approved'" : " WHERE status = 'approved'");
$stmt_sales = $pdo->prepare($sales_sql);
$stmt_sales->execute($params);
$sales = $stmt_sales->fetch();

// 4. ดึงข้อมูลรายการสั่งซื้อตามตัวกรอง
$orders_sql = "SELECT o.*, p.slip_image_url, p.created_at as slip_time 
               FROM orders o 
               LEFT JOIN payments p ON o.id = p.order_id 
               {$where_sql} 
               ORDER BY o.id DESC";
$stmt_orders = $pdo->prepare($orders_sql);
$stmt_orders->execute($params);
$orders = $stmt_orders->fetchAll();
?>

<!-- ฟอร์มค้นหาและสรุปยอดขาย -->
<div class="card">
    <h3 style="margin-top: 0;">ค้นหายอดขายและคำสั่งซื้อ</h3>
    <form method="GET" action="orders.php">
        <div class="form-row" style="align-items: center;">
            <div>
                <label style="font-size: 13px; font-weight: bold;">เงื่อนไขการค้นหา:</label>
                <select name="filter_type" id="filter_type" onchange="toggleFilterInputs()" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>แสดงทั้งหมด</option>
                    <option value="day" <?= $filter_type === 'day' ? 'selected' : '' ?>>ค้นหาตามวัน</option>
                    <option value="month" <?= $filter_type === 'month' ? 'selected' : '' ?>>ค้นหาตามเดือน</option>
                    <option value="year" <?= $filter_type === 'year' ? 'selected' : '' ?>>ค้นหาตามปี</option>
                </select>
            </div>

            <div id="box_day" style="display: none;">
                <label style="font-size: 13px; font-weight: bold;">เลือกวันที่:</label>
                <input type="date" name="selected_date" value="<?= htmlspecialchars($selected_date) ?>">
            </div>

            <div id="box_month" style="display: none;">
                <label style="font-size: 13px; font-weight: bold;">เลือกเดือน/ปี:</label>
                <input type="month" name="selected_month" value="<?= htmlspecialchars($selected_month) ?>">
            </div>

            <div id="box_year" style="display: none;">
                <label style="font-size: 13px; font-weight: bold;">ระบุปี (ค.ศ.):</label>
                <input type="number" name="selected_year" min="2020" max="2099" value="<?= htmlspecialchars($selected_year) ?>">
            </div>

            <div style="margin-top: 18px;">
                <button type="submit" class="btn btn-primary">ค้นหา</button>
                <a href="orders.php" class="btn btn-secondary">รีเซ็ต</a>
            </div>
        </div>
    </form>
</div>

<!-- การ์ดแสดงสรุปยอดขาย -->
<div class="card" style="background-color: #e3f2fd; border-left: 5px solid #0d6efd;">
    <h3 style="margin: 0; font-size: 18px;">
        ยอดขายรวม (อนุมัติแล้ว) <small style="color: #555; font-weight: normal;"><?= $filter_title ?></small>: 
        <span style="color: #0d6efd; font-size: 22px; font-weight: bold;"><?= number_format($sales['total_sales'] ?? 0, 2) ?> บาท</span>
    </h3>
</div>

<!-- ตารางแสดงรายการคำสั่งซื้อ -->
<div class="card">
    <h3>รายการคำสั่งซื้อ (<?= count($orders) ?> รายการ)</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>เลขที่ออเดอร์</th>
                    <th>เบอร์โทรลูกค้า</th>
                    <th>ยอดรวม</th>
                    <th>หลักฐานสลิป</th>
                    <th>สถานะออเดอร์</th>
                    <th>วันเวลาที่สั่ง</th>
                    <th>การดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($o['order_no']) ?></strong></td>
                        <td><?= htmlspecialchars($o['customer_phone']) ?></td>
                        <td><strong><?= number_format($o['total_amount'], 2) ?></strong></td>
                        <td>
                            <?php if (!empty($o['slip_image_url']) && file_exists($o['slip_image_url'])): ?>
                                <a href="<?= $o['slip_image_url'] ?>" target="_blank">
                                    <img src="<?= $o['slip_image_url'] ?>" width="60" height="60" style="object-fit:cover; border-radius:4px; border:1px solid #ccc;">
                                </a>
                            <?php else: ?>
                                <small style="color: #888;">ยังไม่อัปโหลด</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $status_map = [
                                    'pending_payment'  => ['รอโอนเงิน', 'bg-pending'],
                                    'pending_approval' => ['รอตรวจสอบสลิป', 'bg-pending'],
                                    'approved'         => ['อนุมัติแล้ว', 'bg-approved'],
                                    'completed'        => ['เสร็จสิ้น', 'bg-approved'],
                                    'rejected'         => ['ยกเลิก/สลิปไม่ผ่าน', 'bg-rejected']
                                ];
                                $st = $status_map[$o['status']] ?? ['-', ''];
                            ?>
                            <span class="badge <?= $st[1] ?>"><?= $st[0] ?></span>
                        </td>
                        <td><small><?= $o['created_at'] ?></small></td>
                        <td>
                            <?php if ($o['status'] === 'pending_approval' || $o['status'] === 'pending_payment'): ?>
                                <a href="?action=approve&order_id=<?= $o['id'] ?>&<?= http_build_query($_GET) ?>" class="btn btn-success" onclick="return confirm('ยืนยันอนุมัติสลิปนี้?')">อนุมัติ</a>
                                <a href="?action=reject&order_id=<?= $o['id'] ?>&<?= http_build_query($_GET) ?>" class="btn btn-danger" onclick="return confirm('ปฏิเสธสลิปนี้?')">ปฏิเสธ</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #888; padding: 20px;">ไม่พบข้อมูลรายการสั่งซื้อตามเงื่อนไขที่เลือก</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleFilterInputs() {
    const filterType = document.getElementById('filter_type').value;
    document.getElementById('box_day').style.display = (filterType === 'day') ? 'block' : 'none';
    document.getElementById('box_month').style.display = (filterType === 'month') ? 'block' : 'none';
    document.getElementById('box_year').style.display = (filterType === 'year') ? 'block' : 'none';
}

// เรียกทำงานทันทีเมื่อโหลดหน้าเว็บเพื่อตั้งค่าช่องอินพุตตามค่าที่เลือกอยู่
toggleFilterInputs();
</script>

</div></body></html>