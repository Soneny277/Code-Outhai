<?php
session_start();
require_once '../config/database.php';

// ກວດສອບການເຂົ້າສູ່ລະບົບ Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// ດຶງສະຖິຕິຕ່າງໆ
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM customers) as total_customers,
    (SELECT COUNT(*) FROM bookings) as total_bookings,
    (SELECT COUNT(*) FROM bookings WHERE status = 'pending') as pending_bookings,
    (SELECT COUNT(*) FROM bookings WHERE status = 'confirmed') as confirmed_bookings,
    (SELECT SUM(Total_price) FROM bookings WHERE status = 'confirmed') as total_revenue";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// ດຶງການຈອງລ່າສຸດ
$recent_bookings_query = "SELECT b.*, c.Name, c.Lastname, c.Email, c.Cus_id
                         FROM bookings b 
                         JOIN customers c ON b.Cus_id = c.Cus_id 
                         ORDER BY b.date DESC LIMIT 10";
$recent_stmt = $db->prepare($recent_bookings_query);
$recent_stmt->execute();
$recent_bookings = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
// ດຶງລາຍລະອຽດຫ້ອງທີ່ຈອງສຳລັບແຕ່ລະ booking
$booking_room_map = [];
foreach ($recent_bookings as $booking) {
    $details_stmt = $db->prepare("SELECT r.Room_type, COUNT(*) as qty FROM booking_details bd JOIN room r ON bd.Room_id = r.Room_id WHERE bd.Booking_id = ? GROUP BY r.Room_type");
    $details_stmt->execute([$booking['booking_id']]);
    $room_types = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
    $room_strs = [];
    foreach ($room_types as $rt) {
        $room_strs[] = $rt['Room_type'] . ' x' . $rt['qty'];
    }
    $booking_room_map[$booking['booking_id']] = implode(', ', $room_strs);
}

// History tables moved to management.php
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ໂຮງແຮມອຸດົມຊັບ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./admin-style.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif;
        }
        .main-content {
            padding: 20px;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #764ba2, #667eea);
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="main-content flex-grow-1">
        <!-- Main Content (moved from col-md-9 col-lg-10 main-content) -->
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tachometer-alt text-primary me-2"></i>Dashboard</h2>
                <div class="text-end">
                    <p class="mb-0">ຍິນດີຕ້ອນຮັບ, <?php echo $_SESSION['admin_name']; ?>!</p>
                    <small class="text-muted"><?php echo $_SESSION['admin_role']; ?></small>
                </div>
            </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h3><?php echo number_format($stats['total_customers']); ?></h3>
                            <p class="text-muted mb-0">ລູກຄ້າທັງໝົດ</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                            <h3><?php echo number_format($stats['total_bookings']); ?></h3>
                            <p class="text-muted mb-0">ການຈອງທັງໝົດ</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                            <h3><?php echo number_format($stats['pending_bookings']); ?></h3>
                            <p class="text-muted mb-0">ລໍຖ້າຢືນຢັນ</p>
                        </div>
                    </div>
                   
                </div>

                <!-- Recent Bookings -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4><i class="fas fa-calendar-alt text-primary me-2"></i>ການຈອງລ່າສຸດ</h4>
                                <a href="bookings.php" class="btn btn-primary btn-sm">ເບິ່ງທັງໝົດ</a>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ໄອດີລູກຄ້າ</th>
                                            <th>ລູກຄ້າ</th>
                                            <th>ຫ້ອງພັກ</th>
                                            <th>ວັນທີຈອງ</th>
                                            <th>ວັນທີເລີ່ມ</th>
                                            <th>ວັນທີອອກ</th>
                                            <th>ລາຄາ</th>
                                            <th>ສະຖານະ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary"><?php echo $booking['Cus_id']; ?></span></td>
                                                <td>
                                                    <strong><?php echo $booking['Name'] . ' ' . $booking['Lastname']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $booking['Email']; ?></small>
                                                </td>
                                                <td><?php echo $booking_room_map[$booking['booking_id']] ?? '-'; ?></td>
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="stats-card">
                            <h4><i class="fas fa-plus-circle text-primary me-2"></i>ການດຳເນີນງານໄວ</h4>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <a href="management.php?action=add" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-cogs me-2"></i>ຈັດການຂໍ້ມູນ
                                    </a>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <a href="rooms.php?action=add" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-bed me-2"></i>ເພີ່ມຫ້ອງພັກ
                                    </a>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <a href="bookings.php" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-calendar-check me-2"></i>ລົບ ແລະ ຢືນຢັນການຈອງ
                                    </a>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <a href="reports.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-chart-bar me-2"></i>ລາຍງານ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stats-card">
                            <h4><i class="fas fa-chart-line text-success me-2"></i>ສະຖິຕິສັ້ນ</h4>
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <h5 class="text-primary"><?php echo number_format($stats['confirmed_bookings']); ?></h5>
                                    <p class="text-muted">ການຈອງທີ່ຢືນຢັນແລ້ວ</p>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-warning"><?php echo number_format($stats['pending_bookings']); ?></h5>
                                    <p class="text-muted">ການຈອງທີ່ລໍຖ້າ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add after main dashboard content -->
                <!-- History tables moved to management.php -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 