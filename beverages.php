<?php
require_once 'config.php';
include 'nav.php';

// กำหนดโฟลเดอร์เก็บรูปภาพ
$upload_dir = 'beverages_images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ฟังก์ชันสำหรับย่อ/ขยายรูปภาพให้ได้ขนาด 120x150 พิกเซล
function resizeAndSaveImage($tmpName, $destination, $targetWidth = 120, $targetHeight = 150) {
    // ตรวจสอบว่าเปิดใช้งาน GD Library หรือยัง หากยังไม่เปิด ให้ย้ายไฟล์ตรงๆ
    if (!extension_loaded('gd') || !function_exists('imagecreatefrompng')) {
        return move_uploaded_file($tmpName, $destination);
    }

    list($origWidth, $origHeight, $imageType) = getimagesize($tmpName);

    switch ($imageType) {
        case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($tmpName); break;
        case IMAGETYPE_PNG:  $source = imagecreatefrompng($tmpName); break;
        case IMAGETYPE_WEBP: $source = imagecreatefromwebp($tmpName); break;
        default: return move_uploaded_file($tmpName, $destination);
    }

    $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

    if ($imageType === IMAGETYPE_PNG) {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
    }

    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);

    $result = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG: $result = imagejpeg($canvas, $destination, 90); break;
        case IMAGETYPE_PNG:  $result = imagepng($canvas, $destination, 8); break;
        case IMAGETYPE_WEBP: $result = imagewebp($canvas, $destination, 90); break;
    }

    imagedestroy($source);
    imagedestroy($canvas);

    return $result;
}

// 1. เพิ่มเครื่องดื่มใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'drink_' . time() . '_' . rand(100, 999) . '.' . $ext;
        $target_file = $upload_dir . $filename;

        // ปรับขนาดเป็น 120x150 แล้วบันทึก
        if (resizeAndSaveImage($_FILES['image']['tmp_name'], $target_file, 120, 150)) {
            $image_url = $target_file;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO beverages (category_id, name, description, price, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$category_id, $name, $description, $price, $image_url]);
    header('Location: beverages.php');
    exit;
}

// 2. แก้ไขเครื่องดื่ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $old_image = $_POST['old_image'];

    $image_url = $old_image;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'drink_' . time() . '_' . rand(100, 999) . '.' . $ext;
        $target_file = $upload_dir . $filename;

        // ปรับขนาดเป็น 120x150 แล้วบันทึก
        if (resizeAndSaveImage($_FILES['image']['tmp_name'], $target_file, 120, 150)) {
            $image_url = $target_file;
            
            // ลบรูปภาพเก่าออก
            if (!empty($old_image) && file_exists($old_image)) {
                unlink($old_image);
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE beverages SET category_id = ?, name = ?, description = ?, price = ?, image_url = ? WHERE id = ?");
    $stmt->execute([$category_id, $name, $description, $price, $image_url, $id]);
    header('Location: beverages.php');
    exit;
}

// 3. สลับสถานะสินค้า
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE beverages SET is_available = NOT is_available WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: beverages.php');
    exit;
}

// 4. ลบเครื่องดื่ม
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT image_url FROM beverages WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item && !empty($item['image_url']) && file_exists($item['image_url'])) {
        unlink($item['image_url']);
    }

    $stmt = $pdo->prepare("DELETE FROM beverages WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: beverages.php');
    exit;
}

// ดึงข้อมูลกรณีต้องการกด "แก้ไข"
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM beverages WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$beverages = $pdo->query("SELECT b.*, c.name as category_name FROM beverages b JOIN categories c ON b.category_id = c.id ORDER BY b.id DESC")->fetchAll();
?>

<!-- ฟอร์มเพิ่ม / แก้ไข ข้อมูล -->
<div class="card">
    <h3><?= $edit_item ? 'แก้ไขเครื่องดื่ม (ID: ' . $edit_item['id'] . ')' : 'เพิ่มเครื่องดื่มใหม่' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?php if ($edit_item): ?>
            <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
            <input type="hidden" name="old_image" value="<?= $edit_item['image_url'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <input type="text" name="name" placeholder="ชื่อเครื่องดื่ม" value="<?= $edit_item ? htmlspecialchars($edit_item['name']) : '' ?>" required>
            
            <select name="category_id" required>
                <option value="">-- เลือกประเภท --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($edit_item && $edit_item['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= $cat['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="number" step="0.01" name="price" placeholder="ราคา (บาท)" value="<?= $edit_item ? $edit_item['price'] : '' ?>" required>
        </div>

        <div class="form-row">
            <textarea name="description" placeholder="รายละเอียดเครื่องดื่ม"><?= $edit_item ? htmlspecialchars($edit_item['description']) : '' ?></textarea>
            
            <div>
                <label style="font-size: 12px; display: block; margin-bottom: 4px;">
                    <?= $edit_item ? 'เปลี่ยนรูปภาพ (ระบบจะย่อขนาดเป็น 120x150 px):' : 'อัปโหลดรูปภาพ (ระบบจะย่อขนาดเป็น 120x150 px):' ?>
                </label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <button type="submit" name="<?= $edit_item ? 'action_edit' : 'action_add' ?>" class="btn btn-primary">
            <?= $edit_item ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล' ?>
        </button>

        <?php if ($edit_item): ?>
            <a href="beverages.php" class="btn btn-secondary">ยกเลิก</a>
        <?php endif; ?>
    </form>
</div>

<!-- ตารางแสดงรายการเครื่องดื่มทั้งหมด -->
<div class="card">
    <h3>รายการเครื่องดื่มทั้งหมด</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>รูปภาพ (120x150)</th>
                    <th>ชื่อรายการ</th>
                    <th>หมวดหมู่</th>
                    <th>ราคา</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($beverages as $b): ?>
                <?php 
                    $img_src = (!empty($b['image_url']) && file_exists($b['image_url'])) 
                        ? $b['image_url'] 
                        : 'beverages_images/no_image.png';
                ?>
                <tr>
                    <td>
                        <img src="<?= $img_src ?>" width="60" height="75" style="object-fit:cover; border-radius:4px; border:1px solid #ddd;">
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($b['name']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($b['description']) ?></small>
                    </td>
                    <td><?= $b['category_name'] ?></td>
                    <td><?= number_format($b['price'], 2) ?></td>
                    <td>
                        <?php if ($b['is_available']): ?>
                            <span class="badge bg-approved">พร้อมขาย</span>
                        <?php else: ?>
                            <span class="badge bg-rejected">สินค้าหมด</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit=<?= $b['id'] ?>" class="btn btn-primary">แก้ไข</a>
                        <a href="?toggle=<?= $b['id'] ?>" class="btn btn-secondary">สลับสถานะ</a>
                        <a href="?delete=<?= $b['id'] ?>" class="btn btn-danger" onclick="return confirm('ยืนยันการลบเครื่องดื่มรายการนี้?')">ลบ</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div></body></html>