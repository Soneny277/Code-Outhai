<?php
session_start();
require_once 'config/database.php';

// ກວດສອບການເຂົ້າສູ່ລະບົບ
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// ດຶງຂໍ້ມູນການຈອງ
$booking_query = "SELECT b.*, r.Room_type, r.Price, c.Name, c.Lastname, c.Email, c.Phone 
                  FROM bookings b 
                  JOIN booking_details bd ON b.booking_id = bd.Booking_id 
                  JOIN room r ON bd.Room_id = r.Room_id 
                  JOIN customers c ON b.Cus_id = c.Cus_id 
                  WHERE b.booking_id = ? AND b.Cus_id = ?";
$booking_stmt = $db->prepare($booking_query);
$booking_stmt->execute([$booking_id, $user_id]);

if ($booking_stmt->rowCount() == 0) {
    header("Location: dashboard.php");
    exit();
}

$booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

// ກວດສອບການຊຳລະ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_amount = $_POST['payment_amount'];
    $payment_method = $_POST['payment_method'];
    
    // ອັບໂຫຼດຮູບ QR Code (ຖ້າມີ)
    $image_qr = null;
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] == 0) {
        $upload_dir = 'uploads/qr_codes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
        $image_qr = 'qr_' . $booking_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $image_qr;
        
        if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $upload_path)) {
            // ຮູບຖືກອັບໂຫຼດສຳເລັດ
        } else {
            $error = "ບໍ່ສາມາດອັບໂຫຼດຮູບໄດ້";
        }
    }
    
    if (!isset($error)) {
        try {
            $db->beginTransaction();
            
            // ເພີ່ມຂໍ້ມູນການຊຳລະ
            $payment_query = "INSERT INTO payment (Booking_id, Payment, Image_qr) VALUES (?, ?, ?)";
            $payment_stmt = $db->prepare($payment_query);
            $payment_stmt->execute([$booking_id, $payment_amount, $image_qr]);

            // ອັບເດດສະຖານະການຊຳລະໃຫ້ completed
            $update_payment = "UPDATE payment SET status = 'completed' WHERE Booking_id = ? ORDER BY No DESC LIMIT 1";
            $update_payment_stmt = $db->prepare($update_payment);
            $update_payment_stmt->execute([$booking_id]);

            // ອັບເດດສະຖານະການຈອງ
            $update_booking = "UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?";
            $update_stmt = $db->prepare($update_booking);
            $update_stmt->execute([$booking_id]);
            
            $db->commit();
            
            $_SESSION['success'] = "ຊຳລະເງິນສຳເລັດແລ້ວ! ການຈອງຂອງທ່ານໄດ້ຮັບການຢືນຢັນແລ້ວ";
            header("Location: dashboard.php");
            exit();
            
        } catch (Exception $e) {
            $db->rollback();
            $error = "ມີຂໍ້ຜິດພາດໃນການຊຳລະ: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຊຳລະເງິນ - ໂຮງແຮມອຸດົມຊັບ</title>
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
        .payment-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .booking-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
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
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .payment-method.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="payment-form">
                    <form method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-credit-card text-primary me-2"></i>ຊຳລະເງິນ</h2>
                        <p class="text-muted">ຊຳລະເງິນເພື່ອຢືນຢັນການຈອງຫ້ອງພັກ</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- ສະຫຼຸບການຈອງ -->
                    <div class="booking-summary">
                        <h4><i class="fas fa-receipt text-primary me-2"></i>ສະຫຼຸບການຈອງ</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ຫ້ອງພັກ:</strong> <?php echo $booking['Room_type']; ?></p>
                                <p><strong>ວັນທີເລີ່ມ:</strong> <?php echo date('d/m/Y', strtotime($booking['booking_start'])); ?></p>
                                <p><strong>ວັນທີອອກ:</strong> <?php echo date('d/m/Y', strtotime($booking['booking_end'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>ລາຄາຕໍ່ຄືນ:</strong> <?php echo number_format($booking['Price']); ?> ກີບ</p>
                                <p><strong>ຈຳນວນວັນ:</strong> 
                                    <?php 
                                    $start = new DateTime($booking['booking_start']);
                                    $end = new DateTime($booking['booking_end']);
                                    $days = $start->diff($end)->days;
                                    echo $days . ' ວັນ';
                                    ?>
                                </p>
                                <h5 class="text-primary"><strong>ລາຄາລວມ: <?php echo number_format($booking['Total_price']); ?> ກີບ</strong></h5>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code for payment -->
                    <div class="mb-4">
                        <h4><i class="fas fa-money-bill-wave text-primary me-2"></i>ເລືອກວິທີການຊຳລະ</h4>
                        <div class="payment-method" onclick="selectPaymentMethod('qr_code')">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <input type="radio" name="payment_method" value="qr_code" id="qr_code" required checked>
                                </div>
                                <div class="col-md-2">
                                    <i class="fas fa-qrcode fa-2x text-success"></i>
                                </div>
                                <div class="col-md-9 d-flex align-items-center justify-content-between">
                                    <div>
                                        <div style="font-weight:bold; font-size:1.5rem;">ສະແກນ QR Code</div>
                                        <div class="text-muted" style="font-size:1.2rem;">ສະແກນ QR Code ເພື່ອຊຳລະ</div>
                                    </div>
                                    <img src="uploads/qr_codes/qr.png" alt="QR Code ສຳລັບຊຳລະເງິນ" style="max-width:120px; width:100%; border:2px solid #e0e0e0; border-radius:8px; padding:4px; box-shadow:0 2px 8px rgba(0,0,0,0.07); background:#fff;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">ຈຳນວນເງິນທີ່ຊຳລະ</label>
                        <div class="input-group">
                            <span class="input-group-text">ກີບ</span>
                            <input type="number" class="form-control" id="payment_amount" name="payment_amount" value="<?php echo $booking['Total_price']; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3" id="qr_upload_section">
                        <label for="qr_image" class="form-label">ອັບໂຫຼດຮູບ QR Code (ຈຳເປັນ)</label>
                        <input type="file" class="form-control" id="qr_image" name="qr_image" accept="image/*" required>
                        <small class="text-muted">ອັບໂຫຼດຮູບການຊຳລະຜ່ານ QR Code ຈຳເປັນ</small>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check me-2"></i>ຢືນຢັນການຊຳລະ
                        </button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>ກັບໄປຫນ້າ Dashboard
                        </a>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPaymentMethod(method) {
            document.querySelectorAll('.payment-method').forEach(pm => {
                pm.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            if (method === 'qr_code') {
                document.getElementById('qr_upload_section').style.display = 'block';
            } else {
                document.getElementById('qr_upload_section').style.display = 'none';
            }
        }
        // Prevent submit if no slip image
        document.querySelector('form').addEventListener('submit', function(e) {
            var fileInput = document.getElementById('qr_image');
            if (!fileInput.value) {
                alert('ກະລຸນາອັບໂຫຼດຮູບ Slip ການໂອນເງິນກ່ອນ!');
                fileInput.focus();
                e.preventDefault();
            }
        });
    </script>
</body>
</html> 