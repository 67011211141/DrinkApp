<?php checkLogin(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drink Admin System</title>
    <style>
        * { box-sizing: border-box; font-family: sans-serif; }
        body { margin: 0; padding: 0; background-color: #f8f9fa; }
        nav { background: #343a40; color: #fff; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        nav a { color: #fff; text-decoration: none; margin-right: 15px; }
        .container { padding: 15px; max-width: 1100px; margin: 0 auto; }
        .card { background: #fff; padding: 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; min-width: 600px; }
        th, td { border: 1px solid #dee2e6; padding: 8px 12px; text-align: left; font-size: 14px; }
        th { background: #e9ecef; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #fff; display: inline-block; }
        .bg-pending { background: #ffc107; color: #000; }
        .bg-approved { background: #28a745; }
        .bg-rejected { background: #dc3545; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px; display: inline-block; }
        .btn-success { background: #28a745; color: #fff; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .form-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
        .form-row input, .form-row select, .form-row textarea { flex: 1; min-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
    <nav>
        <div>
            <strong style="margin-right: 20px;">Drink System Admin</strong>
            <a href="beverages.php">จัดการเครื่องดื่ม</a>
            <a href="orders.php">อนุมัติคำสั่งซื้อ</a>
            
        </div>
        <div>
            <span style="font-size: 14px;">คุณ: <?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <a href="logout.php" style="margin-left: 10px; color: #ff8888;">ออกจากระบบ</a>
        </div>
    </nav>
    <div class="container">