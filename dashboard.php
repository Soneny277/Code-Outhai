<?php
session_start();
require_once 'config/database.php';

// ກວດສອບການເຂົ້າສູ່ລະບົບ
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// ດຶງຂໍ້ມູນການຈອງຂອງລູກຄ້າ
$user_id = $_SESSION['user_id'];
$booking_query = "SELECT * FROM bookings WHERE Cus_id = ? ORDER BY date DESC";
$booking_stmt = $db->prepare($booking_query);
$booking_stmt->execute([$user_id]);
$bookings = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ໂຮງແຮມອຸດົມຊັບ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
             font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif;
        }
        .navbar-brand {
            font-weight: bold;
            color: #fff !important;
        }
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
        .booking-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }
        .status-pending { border-left-color: #ffc107; }
        .status-confirmed { border-left-color: #28a745; }
        .status-cancelled { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hotel me-2"></i>
                ໂຮງແຮມອຸດົມຊັບ
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">ໜ້າຫຼັກ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="booking.php">ຈອງຫ້ອງພັກ</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="booking_history.php">ປະຫວັດການຈອງ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i><?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">ໂປຣໄຟລ໌</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="dashboard-card">
                    <div class="row">
                        <div class="col-md-8">
                            <h2><i class="fas fa-tachometer-alt text-primary me-2"></i>Dashboard</h2>
                            <p class="text-muted">ຍິນດີຕ້ອນຮັບ, <?php echo $_SESSION['user_name']; ?>!</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="booking.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>ຈອງຫ້ອງພັກໃໝ່
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="dashboard-card">
                    <h3><i class="fas fa-calendar-check text-primary me-2"></i>ການຈອງຫ້ອງພັກຂອງທ່ານ</h3>
                    
                    <?php if (empty($bookings)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">ທ່ານຍັງບໍ່ໄດ້ຈອງຫ້ອງພັກເທື່ອໃດ</h5>
                            <a href="booking.php" class="btn btn-primary">
                                <i class="fas fa-bed me-2"></i>ຈອງຫ້ອງພັກຕອນນີ້
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            // ດຶງລາຍລະອຽດຫ້ອງຂອງແຕ່ລະ booking
                            $details_query = "SELECT bd.*, r.Room_type, r.Price FROM booking_details bd JOIN room r ON bd.Room_id = r.Room_id WHERE bd.Booking_id = ?";
                            $details_stmt = $db->prepare($details_query);
                            $details_stmt->execute([$booking['booking_id']]);
                            $room_details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <div class="booking-card status-<?php echo $booking['status']; ?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <?php
                                        // ສະແດງປະເພດຫ້ອງພ້ອມລາຄາ (ບໍ່ສະແດງຈຳນວນ)
                                        $details_stmt = $db->prepare("SELECT r.Room_type, bd.Room_price, COUNT(*) as qty FROM booking_details bd JOIN room r ON bd.Room_id = r.Room_id WHERE bd.Booking_id = ? GROUP BY r.Room_type, bd.Room_price");
                                        $details_stmt->execute([$booking['booking_id']]);
                                        $room_types = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($room_types as $rt) {
                                            echo "<h5>{$rt['Room_type']}</h5>";
                                            echo "<p class='text-muted'>ລາຄາ: " . number_format($rt['Room_price']) . " ກີບ</p>";
                                        }
                                        ?>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>ວັນທີຈອງ:</strong><br><?php echo date('d/m/Y', strtotime($booking['date'])); ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>ວັນທີເລີ່ມ:</strong><br><?php echo date('d/m/Y', strtotime($booking['booking_start'])); ?></p>
                                        <p><strong>ວັນທີອອກ:</strong><br><?php echo date('d/m/Y', strtotime($booking['booking_end'])); ?></p>
                                        <p><strong>ຈຳນວນຄືນ:</strong><br>
                                            <?php
                                            $start_date = new DateTime($booking['booking_start']);
                                            $end_date = new DateTime($booking['booking_end']);
                                            $nights = $end_date->diff($start_date)->days;
                                            echo $nights . ' ຄືນ';
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <p><strong>ລາຄາລວມ:</strong><br><?php echo number_format($booking['Total_price']); ?> ກີບ</p>
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] == 'confirmed' ? 'success' : 
                                                ($booking['status'] == 'pending' ? 'warning' : 
                                                    ($booking['status'] == 'checked_out' ? 'primary' : 
                                                        ($booking['status'] == 'cancelled' ? 'danger' : 'secondary'))); 
                                        ?>">
                                            <?php 
                                            echo $booking['status'] == 'confirmed' ? 'ຢືນຢັນແລ້ວ' : 
                                                ($booking['status'] == 'pending' ? 'ລໍຖ້າ' : 
                                                    ($booking['status'] == 'checked_out' ? 'ອອກແລ້ວ' : 
                                                        ($booking['status'] == 'cancelled' ? 'ຍົກເລີກແລ້ວ' : 'ອອກແລ້ວ'))); 
                                            ?>
                                        </span>
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-success btn-sm mt-2">
                                                <i class="fas fa-money-bill-wave me-1"></i> ຊຳລະເງິນ
                                            </a>
                                        <?php endif; ?>
                                        <div class="mt-3">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo urlencode('https://57602800e48e.ngrok-free.app/oudomsupsoneny/view_booking.php?booking_id=' . $booking['booking_id']); ?>" alt="Booking QR Code" style="border:1px solid #eee; border-radius:8px; background:#fff; padding:4px;">
                                            <div><small class="text-muted">QR ລາຍລະອຽດການຈອງ</small></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- ສະແດງການຈອງແຕ່ລະລາຍການ -->
                                <div class="row mt-3">
                                    <div class="col-md-9">
                                        <?php
                                        // ສະແດງສະຫຼຸບຈຳນວນຫ້ອງຕໍ່ປະເພດ
                                        foreach ($room_types as $rt) {
                                            echo "{$rt['Room_type']} ຈຳນວນ {$rt['qty']} ຫ້ອງ ລາຄາ " . number_format($rt['Room_price']) . " ກີບ/ຄືນ<br>";
                                        }
                                        ?>
                                    </div>
                                  
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <i class="fas fa-bed fa-3x text-primary mb-3"></i>
                    <h4>ຈອງຫ້ອງພັກ</h4>
                    <p>ຈອງຫ້ອງພັກໃໝ່ທີ່ທ່ານຕ້ອງການ</p>
                    <a href="booking.php" class="btn btn-primary">ຈອງຕອນນີ້</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <i class="fas fa-user fa-3x text-success mb-3"></i>
                    <h4>ໂປຣໄຟລ໌</h4>
                    <p>ແກ້ໄຂຂໍ້ມູນສ່ວນຕົວຂອງທ່ານ</p>
                    <a href="profile.php" class="btn btn-primary">ເບິ່ງໂປຣໄຟລ໌</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <i class="fas fa-headset fa-3x text-warning mb-3"></i>
                    <h4>ຕິດຕໍ່</h4>
                    <p>ຕິດຕໍ່ພວກເຮົາສຳລັບຄຳຊອບແຊວ</p>
                    <a href="contact.php" class="btn btn-primary">ຕິດຕໍ່</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>