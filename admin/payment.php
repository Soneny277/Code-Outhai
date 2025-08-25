<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$booking_info = null;
$payment_success = false;

if ($booking_id) {
    $stmt = $db->prepare('SELECT b.*, c.Name, c.Lastname FROM bookings b JOIN customers c ON b.Cus_id = c.Cus_id WHERE b.booking_id = ?');
    $stmt->execute([$booking_id]);
    $booking_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['add_payment']) && $booking_id) {
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];
    $slip = null;
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] == 0) {
        $upload_dir = '../uploads/payment_slips/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
        $slip = 'slip_' . $booking_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $slip;
        move_uploaded_file($_FILES['slip']['tmp_name'], $upload_path);
    }
    // Table: payment (No, Booking_id, Payment, Image_qr, payment_date, status)
    $stmt = $db->prepare('INSERT INTO payment (Booking_id, Payment, Image_qr, payment_date, status) VALUES (?, ?, ?, NOW(), "completed")');
    $stmt->execute([$booking_id, $amount, $slip]);
    $payment_success = true;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຊຳລະເງິນການຈອງ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif;
        }
        .payment-card {
            background: rgba(255,255,255,0.97);
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.10);
            padding: 40px 32px;
        }
        .summary-box {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 24px 20px;
            margin-bottom: 32px;
        }
        .btn-gradient {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            border-radius: 2rem;
            font-size: 1.2rem;
            padding: 14px 0;
        }
        .btn-gradient:hover {
            background: linear-gradient(45deg, #764ba2, #667eea);
            color: #fff;
        }
        .form-label {
            font-weight: 500;
        }
        .form-control-lg, .form-select-lg {
            font-size: 1.1rem;
            border-radius: 1rem;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="main-content flex-grow-1">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="payment-card">
                        <div class="mb-4 text-center">
                            <h2><i class="fas fa-credit-card text-primary me-2"></i>ຊຳລະເງິນການຈອງ</h2>
                        </div>
                        <?php if ($booking_info): ?>
                        <div class="summary-box mb-4">
                            <h5><i class="fas fa-receipt text-primary me-2"></i>ສະຫຼຸບການຈອງ</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ລູກຄ້າ:</strong> <?= htmlspecialchars($booking_info['Name'] . ' ' . $booking_info['Lastname']) ?></p>
                                    <p><strong>ວັນເຂົ້າ:</strong> <?= htmlspecialchars($booking_info['booking_start']) ?></p>
                                    <p><strong>ວັນອອກ:</strong> <?= htmlspecialchars($booking_info['booking_end']) ?></p>
                                </div>
                                <div class="col-md-6 d-flex align-items-center justify-content-md-end">
                                    <h4 class="text-primary mb-0"><strong>ລາຄາລວມ: <?= number_format($booking_info['Total_price']) ?> ກີບ</strong></h4>
                                </div>
                            </div>
                        </div>
                        <?php if (!$payment_success): ?>
                        <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end" id="paymentForm">
                            <div class="col-md-4">
                                <label class="form-label">ຈຳນວນເງິນ</label>
                                <input type="number" name="amount" class="form-control form-control-lg" value="<?= $booking_info['Total_price'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ວິທີຊຳລະ</label>
                                <select name="method" class="form-select form-select-lg" id="methodSelect" required>
                                    <option value="cash">ເງິນສົດ</option>
                                    <option value="transfer">ໂອນ</option>
                                    <option value="qr">QR</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ອັບໂຫຼດສະລິບ (ບັງຄັບສຳລັບເງິນສົດ)</label>
                                <input type="file" name="slip" class="form-control form-control-lg" id="slipInput" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ຜູ້ຮັບເງິນ (Staff)</label>
                                <input type="text" name="receiver" class="form-control form-control-lg" placeholder="ຊື່ພະນັກງານ" required>
                            </div>
                            <div class="col-12 mt-4 d-grid">
                                <button type="submit" name="add_payment" class="btn btn-gradient btn-lg">
                                    <i class="fas fa-check-circle me-2"></i>ບັນທຶກການຊຳລະ
                                </button>
                                <a href="bookings.php" class="btn btn-secondary btn-lg mt-2">ກັບຄືນ</a>
                            </div>
                        </form>
                        <script>
                        document.getElementById('paymentForm').addEventListener('submit', function(e) {
                            var method = document.getElementById('methodSelect').value;
                            var slip = document.getElementById('slipInput').value;
                            if (method === 'cash' && !slip) {
                                alert('ກະລຸນາອັບໂຫຼດຮູບ slip ສຳລັບການຮັບເງິນສົດ!');
                                e.preventDefault();
                            }
                        });
                        document.getElementById('methodSelect').addEventListener('change', function() {
                            var slipInput = document.getElementById('slipInput');
                            if (this.value === 'cash') {
                                slipInput.required = true;
                            } else {
                                slipInput.required = false;
                            }
                        });
                        </script>
                        <?php else: ?>
                        <div class="alert alert-success mt-4">ບັນທຶກການຊຳລະເງິນສຳເລັດ!</div>
                        <a href="bookings.php" class="btn btn-primary">ກັບຄືນຫນ້າຈັດການການຈອງ</a>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="alert alert-danger">ບໍ່ພົບການຈອງນີ້</div>
                        <a href="bookings.php" class="btn btn-secondary">ກັບຄືນ</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 