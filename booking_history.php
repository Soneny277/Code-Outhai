<?php
session_start();
require_once 'config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$customer_id = $_SESSION['user_id'];

// Fetch booking history for this customer
$query = "SELECT b.* FROM bookings b WHERE b.Cus_id = :customer_id ORDER BY b.date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ປະຫວັດການຈອງຂອງຂ້ອຍ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
           font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-history me-2"></i>ປະຫວັດການຈອງຂອງທ່ານ</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ຫ້ອງພັກ</th>
                    <th>ຈຳນວນ</th>
                    <th>ລາຄາຫ້ອງ/ຄືນ</th>
                    <th>ວັນທີຈອງ</th>
                    <th>ວັນທີເລີ່ມ</th>
                    <th>ວັນທີອອກ</th>
                    <th>ລາຄາລວມ</th>
                    <th>ສະຖານະ</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($bookings) > 0): ?>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>
                            <?php
                            // Fetch room types and quantities for this booking
                            $room_stmt = $db->prepare('SELECT r.Room_type, bd.Room_price, COUNT(*) as qty FROM booking_details bd JOIN room r ON bd.Room_id = r.Room_id WHERE bd.Booking_id = ? GROUP BY r.Room_id, bd.Room_price');
                            $room_stmt->execute([$booking['booking_id']]);
                            $room_types = $room_stmt->fetchAll(PDO::FETCH_ASSOC);
                            $room_strs = [];
                            $room_price_strs = [];
                            foreach ($room_types as $rt) {
                                $room_strs[] = htmlspecialchars($rt['Room_type']) . ' x ' . $rt['qty'];
                                $room_price_strs[] = htmlspecialchars($rt['Room_type']) . ': ' . number_format($rt['Room_price']) . ' ກີບ/ຄືນ';
                            }
                            echo implode(', ', $room_strs);
                            ?>
                        </td>
                        <td><?php echo array_sum(array_column($room_types, 'qty')); ?></td>
                        <td><?php echo implode('<br>', $room_price_strs); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($booking['date'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($booking['booking_start'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($booking['booking_end'])); ?></td>
                        <td><?php echo number_format($booking['Total_price']); ?> ກີບ</td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $booking['status'] == 'confirmed' ? 'success' : 
                                    ($booking['status'] == 'pending' ? 'warning' : 'danger'); 
                            ?>">
                                <?php 
                                echo $booking['status'] == 'confirmed' ? 'ຢືນຢັນແລ້ວ' : 
                                    ($booking['status'] == 'pending' ? 'ລໍຖ້າ' : 'ຍົກເລີກ'); 
                                ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">ທ່ານຍັງບໍ່ມີປະຫວັດການຈອງ</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="index.php" class="btn btn-secondary mt-3">ກັບໄປໜ້າຫຼັກ</a>
</div>
</body>
</html> 