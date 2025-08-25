<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch all customers and rooms
$all_customers = $db->query('SELECT Cus_id, Name, Lastname FROM customers ORDER BY Name')->fetchAll(PDO::FETCH_ASSOC);
$all_rooms = $db->query('SELECT Room_id, Room_type FROM room ORDER BY Room_type')->fetchAll(PDO::FETCH_ASSOC);

$booking_id = null;
$booking_info = null;
$payment_success = false;
$error = '';

// Fetch all rooms with details and available count for display
$room_list = $db->query('SELECT * FROM room')->fetchAll(PDO::FETCH_ASSOC);
foreach ($room_list as $i => &$room) {
    $q_stmt = $db->prepare('SELECT quantity FROM room WHERE Room_id = ?');
    $q_stmt->execute([$room['Room_id']]);
    $total = (int)$q_stmt->fetchColumn();
    $booked_stmt = $db->prepare('
        SELECT COUNT(*) FROM booking_details bd
        JOIN bookings b ON bd.Booking_id = b.booking_id
        WHERE bd.Room_id = ? AND b.status IN ("pending", "confirmed")
          AND (b.booking_start < ? AND b.booking_end > ?)
    ');
    $today = date('Y-m-d');
    $booked_stmt->execute([$room['Room_id'], $today, $today]);
    $booked = (int)$booked_stmt->fetchColumn();
    $room['available'] = $total - $booked;
}
unset($room);

// 1. Handle booking form
if (isset($_POST['add_booking'])) {
    $Cus_id = intval($_POST['Cus_id']);
    $room_ids = $_POST['room_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $checkin = $_POST['booking_start'];
    $checkout = $_POST['booking_end'];
    $nights = (strtotime($checkout) - strtotime($checkin)) / 86400;
    if ($nights < 1) $nights = 1;
    $total_price = 0;
    $room_prices = [];
    // Calculate total price
    foreach ($room_ids as $i => $room_id) {
        $qty = max(1, (int)($quantities[$i] ?? 1));
        $room_stmt = $db->prepare('SELECT Price FROM room WHERE Room_id = ?');
        $room_stmt->execute([$room_id]);
        $room_price = $room_stmt->fetch(PDO::FETCH_ASSOC)['Price'];
        $room_prices[$room_id] = $room_price;
        $total_price += $room_price * $nights * $qty;
    }
    // Insert booking
    $stmt = $db->prepare('INSERT INTO bookings (Cus_id, booking_start, booking_end, Total_price, date, status) VALUES (?, ?, ?, ?, NOW(), "pending")');
    $stmt->execute([$Cus_id, $checkin, $checkout, $total_price]);
    $booking_id = $db->lastInsertId();
    // Insert booking_details
    foreach ($room_ids as $i => $room_id) {
        $qty = max(1, (int)($quantities[$i] ?? 1));
        for ($q = 0; $q < $qty; $q++) {
            $stmt2 = $db->prepare('INSERT INTO booking_details (Booking_id, Room_id, Room_price) VALUES (?, ?, ?)');
            $stmt2->execute([$booking_id, $room_id, $room_prices[$room_id]]);
        }
    }
    header('Location: payment.php?booking_id=' . $booking_id);
    exit();
}

// 2. Handle payment form
if (isset($_POST['add_payment']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
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

// Fetch latest 10 bookings for table display
$recent_bookings = $db->query("SELECT b.*, c.Name, c.Lastname, c.Email FROM bookings b JOIN customers c ON b.Cus_id = c.Cus_id ORDER BY b.date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເພີ່ມການຈອງແລະຊຳລະເງິນ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./admin-style.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; }
        .main-content { padding: 30px; }
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
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="main-content flex-grow-1">
                <div class="card">
                    <div class="card-body">
                        <h2 class="mb-4"><i class="fas fa-calendar-plus text-primary me-2"></i> ເພີ່ມການຈອງແລະຊຳລະເງິນ</h2>
                        <div class="row justify-content-center">
                            <div class="col-md-10 col-lg-8">
                                <div class="payment-form">
                                    <div class="text-center mb-4">
                                        <h2><i class="fas fa-bed text-primary me-2"></i>ເພີ່ມການຈອງຫ້ອງພັກ</h2>
                                        <p class="text-muted">ເລືອກຫ້ອງພັກ ແລະ ວັນທີທີ່ຕ້ອງການ</p>
                                    </div>
                                    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                                    <form method="POST" id="bookingForm">
                                        <div class="mb-3">
                                            <label class="form-label">ລູກຄ້າ</label>
                                            <select name="Cus_id" class="form-select" required>
                                                <option value="">-- ເລືອກລູກຄ້າ --</option>
                                                <?php foreach (
                                                    $all_customers as $cus): ?>
                                                    <option value="<?= $cus['Cus_id'] ?>"><?= htmlspecialchars($cus['Name'] . ' ' . $cus['Lastname']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <h4><i class="fas fa-list text-primary me-2"></i>ເລືອກຫ້ອງພັກ</h4>
                                            <div class="row">
                                                <?php foreach ($room_list as $i => $room): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="room-card">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input room-checkbox" type="checkbox" name="room_id[]" value="<?= $room['Room_id'] ?>" id="roomCheck<?= $i ?>">
                                                            <label class="form-check-label" for="roomCheck<?= $i ?>">
                                                                <i class="fas fa-bed fa-lg text-primary me-2"></i>
                                                                <strong><?= htmlspecialchars($room['Room_type']) ?></strong>
                                                            </label>
                                                        </div>
                                                        <p class="text-muted mb-1"><?= htmlspecialchars($room['Description'] ?? '-') ?></p>
                                                        <h5 class="text-primary mb-2"><?= number_format($room['Price']) ?> ກີບ/ຄືນ</h5>
                                                        <div class="input-group input-group-sm mb-2">
                                                            <span class="input-group-text">ຈຳນວນ</span>
                                                            <input type="number" class="form-control room-qty" name="quantity[]" min="1" value="1" disabled>
                                                            <span class="input-group-text bg-info text-white">ວ່າງ <?= $room['available'] ?> ຫ້ອງ</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="booking_start" class="form-label">ວັນທີເລີ່ມຈອງ</label>
                                                <input type="date" class="form-control" id="booking_start" name="booking_start" required min="<?= date('Y-m-d') ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="booking_end" class="form-label">ວັນທີອອກ</label>
                                                <input type="date" class="form-control" id="booking_end" name="booking_end" required min="<?= date('Y-m-d') ?>">
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h5><i class="fas fa-calculator text-primary me-2"></i>ສະຫຼຸບລາຄາ</h5>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p><strong>ຫ້ອງພັກ:</strong> <span id="selected_room_type">-</span></p>
                                                            <p><strong>ລາຄາຕໍ່ຄືນ:</strong> <span id="selected_room_price_display">-</span> ກີບ</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>ຈຳນວນວັນ:</strong> <span id="total_days">-</span> ວັນ</p>
                                                            <p><strong>ລາຄາລວມ:</strong> <span id="total_price" class="text-primary fw-bold">-</span> ກີບ</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" name="add_booking">
                                                <i class="fas fa-check me-2"></i>ຢືນຢັນການຈອງ
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($booking_id): ?>
<div class="booking-summary my-4" id="booking-confirmation">
    <h4><i class="fas fa-receipt text-primary me-2"></i>ໃບຢືນຢັນການຈອງ</h4>
    <div class="row">
        <div class="col-md-6">
            <p><strong>ລະຫັດການຈອງ:</strong> <?php echo $booking_id; ?></p>
            <?php if ($booking_info): ?>
            <p><strong>ຊື່ລູກຄ້າ:</strong> <?php echo $booking_info['Name'] . ' ' . $booking_info['Lastname']; ?></p>
            <p><strong>Email:</strong> <?php echo $booking_info['Email']; ?></p>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-center">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=https://57602800e48e.ngrok-free.app/oudomsupsoneny/view_booking.php?booking_id=<?php echo $booking_id; ?>" alt="QR Code" style="border:1px solid #eee; border-radius:8px; background:#fff; padding:4px;">
            <div><small class="text-muted">QR ສຳລັບຢືນຢັນການຈອງ</small></div>
        </div>
    </div>
    <div class="text-end mt-2">
        <button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="fas fa-print me-2"></i>ພິມ/ດາວໂຫຼດໃບຢືນຢັນ</button>
    </div>
</div>
<?php endif; ?>
                <?php if ($recent_bookings): ?>
                <div class="mt-5">
                    <h4 class="mb-3"><i class="fas fa-list text-primary me-2"></i>ການຈອງຫຼ້າສຸດ</h4>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover table-striped align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>ລູກຄ້າ</th>
                                    <th>ອີເມວ</th>
                                    <th>ຫ້ອງພັກ</th>
                                    <th>ລາຄາຫ້ອງ</th>
                                    <th>ຈຳນວນ</th>
                                    <th>ວັນທີຈອງ</th>
                                    <th>ວັນທີເລີ່ມ</th>
                                    <th>ວັນທີອອກ</th>
                                    <th>ລາຄາລວມ</th>
                                    <th>ສະຖານະ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                <?php
                                $details_query = "SELECT r.Room_type, bd.Room_price, COUNT(*) as qty FROM booking_details bd JOIN room r ON bd.Room_id = r.Room_id WHERE bd.Booking_id = ? GROUP BY r.Room_type, bd.Room_price";
                                $details_stmt = $db->prepare($details_query);
                                $details_stmt->execute([$booking['booking_id']]);
                                $room_types = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
                                $room_strs = [];
                                $price_strs = [];
                                $qty_strs = [];
                                foreach ($room_types as $rt) {
                                    $room_strs[] = $rt['Room_type'];
                                    $price_strs[] = number_format($rt['Room_price']) . ' ກີບ/ຄືນ';
                                    $qty_strs[] = $rt['qty'] . ' ຫ້ອງ';
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['Name'] . ' ' . $booking['Lastname']) ?></td>
                                    <td><?= htmlspecialchars($booking['Email']) ?></td>
                                    <td><?= implode('<br>', $room_strs) ?></td>
                                    <td><?= implode('<br>', $price_strs) ?></td>
                                    <td><?= implode('<br>', $qty_strs) ?></td>
                                    <td><?= date('d/m/Y', strtotime($booking['date'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($booking['booking_start'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($booking['booking_end'])) ?></td>
                                    <td><?= number_format($booking['Total_price']) ?> ກີບ</td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] == 'confirmed' ? 'success' : 
                                                ($booking['status'] == 'pending' ? 'warning' : ($booking['status'] == 'checked_out' ? 'secondary' : 'danger'));
                                        ?>">
                                            <?php 
                                            echo $booking['status'] == 'confirmed' ? 'ຢືນຢັນແລ້ວ' : 
                                                ($booking['status'] == 'pending' ? 'ລໍຖ້າ' : ($booking['status'] == 'checked_out' ? 'Check-out ແລ້ວ' : 'ຍົກເລີກ'));
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Collect room data for JS
    var roomData = {};
    <?php foreach ($room_list as $room): ?>
        roomData[<?= $room['Room_id'] ?>] = {
            name: <?= json_encode($room['Room_type']) ?>,
            price: <?= (int)$room['Price'] ?>
        };
    <?php endforeach; ?>
    function calculateSummary() {
        var checkedRooms = document.querySelectorAll('.room-checkbox:checked');
        var roomNames = [];
        var roomPricePerNight = [];
        var roomPriceTotal = 0;
        checkedRooms.forEach(function(cb) {
            var roomId = cb.value;
            var qtyInput = cb.closest('.room-card').querySelector('.room-qty');
            var qty = parseInt(qtyInput.value) || 1;
            roomNames.push(roomData[roomId].name + (qty > 1 ? ' x' + qty : ''));
            roomPricePerNight.push(
                roomData[roomId].name + ' : ' +
                roomData[roomId].price.toLocaleString() + ' ກີບ/ຄືນ x ' + qty +
                ' = ' + (roomData[roomId].price * qty).toLocaleString() + ' ກີບ/ຄືນ'
            );
            roomPriceTotal += roomData[roomId].price * qty;
        });
        var start = document.getElementById('booking_start').value;
        var end = document.getElementById('booking_end').value;
        var days = 0;
        if (start && end) {
            var d1 = new Date(start);
            var d2 = new Date(end);
            days = (d2 - d1) / (1000*60*60*24);
            if (days < 1) days = 0;
        }
        document.getElementById('selected_room_type').textContent = roomNames.length ? roomNames.join(', ') : '-';
        document.getElementById('selected_room_price_display').textContent = roomPricePerNight.length ? roomPricePerNight.join(', ') : '-';
        document.getElementById('total_days').textContent = days > 0 ? days : '-';
        var total = (days > 0) ? (roomPriceTotal * days) : 0;
        document.getElementById('total_price').textContent = total > 0 ? total.toLocaleString() : '-';
    }
    // Enable quantity only if room is checked
    document.querySelectorAll('.room-checkbox').forEach(function(checkbox, idx) {
        checkbox.addEventListener('change', function() {
            var qtyInput = this.closest('.room-card').querySelector('.room-qty');
            qtyInput.disabled = !this.checked;
            if (!this.checked) qtyInput.value = 1;
            checkFormValidity();
            calculateSummary();
        });
    });
    document.querySelectorAll('.room-qty').forEach(function(input) {
        input.addEventListener('input', calculateSummary);
    });
    document.getElementById('booking_start').addEventListener('change', function() {
        checkFormValidity();
        calculateSummary();
    });
    document.getElementById('booking_end').addEventListener('change', function() {
        checkFormValidity();
        calculateSummary();
    });
    function checkFormValidity() {
        var checked = document.querySelectorAll('.room-checkbox:checked').length;
        var start = document.getElementById('booking_start').value;
        var end = document.getElementById('booking_end').value;
        var btn = document.getElementById('submitBtn');
        btn.disabled = !(checked && start && end);
    }
    // Initial check and summary
    checkFormValidity();
    calculateSummary();
    // Prevent submit if no room selected
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        var checked = document.querySelectorAll('.room-checkbox:checked').length;
        if (!checked) {
            alert('ກະລຸນາເລືອກຫ້ອງພັກຢ່າງນ້ອຍ 1 ຫ້ອງ');
            e.preventDefault();
        }
    });
    </script>
</body>
</html> 