<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['booking_id'])) {
    echo '<h2>Booking ID not specified.</h2>';
    exit;
}
$booking_id = intval($_GET['booking_id']);

// Connect PDO
$db = (new Database())->getConnection();

// Fetch booking, customer, room, and payment info - simplified query
$sql = "SELECT DISTINCT b.booking_id, b.Cus_id, b.date, b.booking_start, b.booking_end, b.Total_price, b.status,
               c.Name, c.Lastname, c.Phone, c.Email, c.Identity_card_number,
               p.Payment AS payment_amount, p.status AS payment_status, p.Image_qr AS payment_slip
        FROM bookings b
        JOIN customers c ON b.Cus_id = c.Cus_id
        LEFT JOIN payment p ON b.booking_id = p.Booking_id
        WHERE b.booking_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$booking_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) {
    echo '<h2>Booking not found.</h2>';
    exit;
}
$row = $rows[0];

// Get room details separately
$room_sql = "SELECT r.Room_type, r.Price, COUNT(*) as qty 
             FROM booking_details bd 
             JOIN room r ON bd.Room_id = r.Room_id 
             WHERE bd.Booking_id = ? 
             GROUP BY r.Room_id, r.Room_type, r.Price";
$room_stmt = $db->prepare($room_sql);
$room_stmt->execute([$booking_id]);
$room_details = $room_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ລາຍລະອຽດການຈອງ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Noto Sans Lao', 'Phetsarath OT', sans-serif;
        }
        .booking-card {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 32px 24px 24px 24px;
        }
        .booking-card h2 {
            font-size: 1.6rem;
            font-weight: bold;
            margin-bottom: 18px;
            text-align: center;
        }
        .field-label {
            font-weight: bold;
            color: #222;
            min-width: 120px;
            display: inline-block;
        }
        .room-list {
            margin-top: 10px;
            margin-bottom: 18px;
        }
        .room-list li {
            margin-bottom: 4px;
        }
        .btn-confirm {
            width: 100%;
            border-radius: 8px;
            font-size: 1.1rem;
            padding: 10px 0;
        }
        @media (max-width: 600px) {
            .booking-card { padding: 18px 4px; }
        }
    </style>
</head>
<body>
    
<div class="booking-card">
    <h2>ລາຍລະອຽດການຈອງ</h2>
    <div class="mb-2"><span class="field-label">ຊື່ລູກຄ້າ:</span> <?= htmlspecialchars($row['Name'] . ' ' . $row['Lastname']) ?></div>
    <div class="mb-2"><span class="field-label">ເບີໂທ:</span> <?= htmlspecialchars($row['Phone']) ?></div>
    <div class="mb-2"><span class="field-label">ອີເມວ:</span> <?= htmlspecialchars($row['Email']) ?></div>
    <div class="mb-2"><span class="field-label">ເລກບັດປະຈຳຕົວ:</span> <?= isset($row['Identity_card_number']) ? htmlspecialchars($row['Identity_card_number']) : '-' ?></div>
    <div class="mb-2"><span class="field-label">ວັນທີຈອງ:</span> <?= htmlspecialchars($row['date']) ?></div>
    <div class="mb-2"><span class="field-label">ວັນເລີ່ມ:</span> <?= htmlspecialchars($row['booking_start']) ?></div>
    <div class="mb-2"><span class="field-label">ວັນອອກ:</span> <?= htmlspecialchars($row['booking_end']) ?></div>
    <div class="mb-2"><span class="field-label">ຈຳນວນຄືນ:</span> 
        <?php
        $start_date = new DateTime($row['booking_start']);
        $end_date = new DateTime($row['booking_end']);
        $nights = $end_date->diff($start_date)->days;
        echo $nights . ' ຄືນ';
        ?>
    </div>
    <div class="mb-2"><span class="field-label">ລາຄາລວມ:</span> <?= number_format($row['Total_price']) ?> ກີບ</div>
    <div class="mb-2"><span class="field-label">ສະຖານະ:</span> 
        <?php
        // Debug removed for clean display
        
        // Use the status from bookings table
        $status = $row['status'];
        
        if (empty($status)) {
            $status = 'checked_out'; // Default status if empty
        }
        
        if ($status == 'confirmed') {
            echo '<span style="color: #28a745; font-weight: bold;">ຢືນຢັນແລ້ວ</span>';
        } elseif ($status == 'pending') {
            echo '<span style="color: #ffc107; font-weight: bold;">ລໍຖ້າ</span>';
        } elseif ($status == 'checked_out') {
            echo '<span style="color: #17a2b8; font-weight: bold;">ອອກແລ້ວ</span>';
        } elseif ($status == 'cancelled') {
            echo '<span style="color: #dc3545; font-weight: bold;">ຍົກເລີກແລ້ວ</span>';
        } else {
            echo '<span style="color: #6c757d;">' . htmlspecialchars($status) . '</span>';
        }
        ?>
    </div>
    <div class="mb-2"><span class="field-label">ລາຍລະອຽດຫ້ອງ:</span></div>
    <ul class="room-list">
        <?php foreach ($room_details as $rd): ?>
            <li><?= htmlspecialchars($rd['Room_type']) ?> <?= number_format($rd['Price']) ?> ກີບ/ຄືນ <?= $rd['qty'] > 1 ? 'x ' . $rd['qty'] : '' ?></li>
        <?php endforeach; ?>
    </ul>
    <button class="btn btn-secondary btn-confirm" onclick="window.history.back(); return false;">ກັບຄືນ</button>
    
</div>
</body>
</html>
