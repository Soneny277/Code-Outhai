<?php
session_start();
require_once 'config/database.php';

// ກວດສອບການເຂົ້າສູ່ລະບົບ
if (!isset($_SESSION['user_id'])) {
    echo '<!DOCTYPE html><html lang="lo"><head><meta charset="UTF-8"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><title>ກະລຸນາລົງທະບຽນ</title></head><body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center;">';
    echo '<div class="card shadow-lg p-4 text-center" style="max-width: 420px; border-radius: 22px;">';
    echo '<div class="mb-3"><span style="font-size:3rem;color:#ff9800;"><i class="fas fa-exclamation-triangle"></i></span></div>';
    echo '<div style="font-size:1.35rem;font-weight:700;" class="mb-2">ທ່ານຍັງບໍ່ໄດ້ລົງທະບຽນ ຫຼື ລ໋ອກອິນ</div>';
    echo '<div class="mb-4 text-muted">ກະລຸນາລົງທະບຽນ ຫຼື ລ໋ອກອິນ ກ່ອນຈະຈອງຫ້ອງພັກ</div>';
    echo '<a href="login.php" class="btn btn-warning btn-lg w-100" style="font-size:1.15rem;">ໄປຫນ້າລົງທະບຽນ/ລ໋ອກອິນ</a>';
    echo '</div>';
    echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>';
    echo '</body></html>';
    exit();
}

$database = new Database();
$db = $database->getConnection();

// ດຶງຂໍ້ມູນຫ້ອງພັກ
$room_query = "SELECT * FROM room ORDER BY Price ASC";
$room_stmt = $db->prepare($room_query);
$room_stmt->execute();
$rooms = $room_stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter out duplicate rooms by Room_type, Price, and Room_detail
$unique_rooms = [];
foreach ($rooms as $room) {
    $key = $room['Room_type'] . '|' . $room['Price'] . '|' . $room['Room_detail'];
    if (!isset($unique_rooms[$key])) {
        $unique_rooms[$key] = $room;
    }
}
$rooms = array_values($unique_rooms);

// ກວດສອບການຈອງ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_ids = $_POST['room_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $booking_start = $_POST['booking_start'];
    $booking_end = $_POST['booking_end'];
    $customer_id = $_SESSION['user_id'];

    // ຄຳນວນຈຳນວນວັນ
    $start_date = new DateTime($booking_start);
    $end_date = new DateTime($booking_end);
    $interval = $start_date->diff($end_date);
    $days = $interval->days;
    if ($days < 1) $days = 1;

    $total_price = 0;
    $room_prices = [];
    $error = '';
    $db->beginTransaction();
    try {
        // 1. ກວດຈຳນວນຫ້ອງວ່າງ
        foreach ($room_ids as $i => $room_id) {
            $qty = max(1, (int)($quantities[$i] ?? 1));
            $stmt = $db->prepare('SELECT quantity, Price FROM room WHERE Room_id = ?');
            $stmt->execute([$room_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$room || $room['quantity'] < $qty) {
                throw new Exception('ຫ້ອງບາງປະເພດບໍ່ພຽງພໍ ຫຼື ບໍ່ມີຢູ່');
            }
            $room_prices[$room_id] = $room['Price'];
            $total_price += $room['Price'] * $qty * $days;
        }
        // 2. ເພີ່ມການຈອງ
        $booking_query = "INSERT INTO bookings (booking_start, booking_end, Total_price, Cus_id) VALUES (?, ?, ?, ?)";
        $booking_stmt = $db->prepare($booking_query);
        $booking_stmt->execute([$booking_start, $booking_end, $total_price, $customer_id]);
        $booking_id = $db->lastInsertId();
        // 3. ເພີ່ມລາຍລະອຽດການຈອງ ແລະ update quantity
        foreach ($room_ids as $i => $room_id) {
            $qty = max(1, (int)($quantities[$i] ?? 1));
            for ($q = 0; $q < $qty; $q++) {
                $detail_query = "INSERT INTO booking_details (Booking_id, Room_id, Room_price) VALUES (?, ?, ?)";
                $detail_stmt = $db->prepare($detail_query);
                $detail_stmt->execute([$booking_id, $room_id, $room_prices[$room_id]]);
            }
            // update quantity
            $db->prepare('UPDATE room SET quantity = quantity - ? WHERE Room_id = ?')->execute([$qty, $room_id]);
        }
        $db->commit();
        $_SESSION['success'] = "ຈອງຫ້ອງພັກສຳເລັດແລ້ວ! ກະລຸນາຊຳລະເງິນ";
        header("Location: payment.php?booking_id=" . $booking_id);
        exit();
    } catch (Exception $e) {
        $db->rollback();
        $error = "ມີຂໍ້ຜິດພາດໃນການຈອງ: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈອງຫ້ອງພັກ - ໂຮງແຮມອຸດົມຊັບ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; ;
        }
        .navbar-brand {
            font-weight: bold;
            color: #fff !important;
        }
        .booking-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .room-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .room-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
        }
        .room-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="booking-form">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-bed text-primary me-2"></i>ຈອງຫ້ອງພັກ</h2>
                        <p class="text-muted">ເລືອກຫ້ອງພັກ ແລະ ວັນທີທີ່ທ່ານຕ້ອງການ</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" id="bookingForm">
                        <div class="mb-4">
                            <h4><i class="fas fa-list text-primary me-2"></i>ເລືອກຫ້ອງພັກ</h4>
                            <table class="table table-bordered" id="roomTable">
                                <thead>
                                    <tr>
                                        <th>ປະເພດຫ້ອງ</th>
                                        <th>ລາຄາຕໍ່ຄືນ</th>
                                        <th>ຈຳນວນ</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select name="room_id[]" class="form-select room-select" required onchange="updatePrice(this)">
                                                <option value="">-- ເລືອກຫ້ອງ --</option>
                                                <?php foreach ($rooms as $room): ?>
                                                    <option value="<?php echo $room['Room_id']; ?>" data-price="<?php echo $room['Price']; ?>" data-type="<?php echo htmlspecialchars($room['Room_type']); ?>" data-available="<?php echo $room['quantity']; ?>">
                                                        <?php echo $room['Room_type']; ?> (<?php echo number_format($room['Price']); ?> ກີບ, ຫ້ອງວ່າງ <?php echo $room['quantity']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="room-price">-</td>
                                        <td><input type="number" name="quantity[]" class="form-control qty-input" min="1" value="1" required><span class="room-available text-info ms-2"></span></td>
                                        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-success btn-sm" onclick="addRow()"><i class="fas fa-plus"></i> ເພີ່ມຫ້ອງ</button>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="booking_start" class="form-label">ວັນທີເລີ່ມຈອງ</label>
                                <!-- ສ່ວນ input ວັນທີເລີ່ມ -->
                                <input type="date" id="booking_start" name="booking_start" class="form-control" required>
                                <script>
                                    // ກຳນົດ min ແລະ max ຂອງວັນທີເລີ່ມ
                                    const startInput = document.getElementById('booking_start');
                                    const today = new Date();
                                    const yyyy = today.getFullYear();
                                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                                    const dd = String(today.getDate()).padStart(2, '0');
                                    const minDate = `${yyyy}-${mm}-${dd}`;
                                    // ຄຳນວນ max ຄື 7 ວັນຈາກປັດຈຸບັນ
                                    const maxDateObj = new Date(today.getTime() + 6 * 24 * 60 * 60 * 1000);
                                    const maxyyyy = maxDateObj.getFullYear();
                                    const maxmm = String(maxDateObj.getMonth() + 1).padStart(2, '0');
                                    const maxdd = String(maxDateObj.getDate()).padStart(2, '0');
                                    const maxDate = `${maxyyyy}-${maxmm}-${maxdd}`;
                                    startInput.setAttribute('min', minDate);
                                    startInput.setAttribute('max', maxDate);
                                </script>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="booking_end" class="form-label">ວັນທີອອກ</label>
                                <input type="date" class="form-control" id="booking_end" name="booking_end" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5><i class="fas fa-calculator text-primary me-2"></i>ສະຫຼຸບລາຄາ</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>ລາຍການຫ້ອງ:</strong> <span id="selected_rooms">-</span></p>
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
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-check me-2"></i>ຢືນຢັນການຈອງ
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>ກັບໄປ Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addRow() {
            const table = document.getElementById('roomTable').getElementsByTagName('tbody')[0];
            const newRow = table.rows[0].cloneNode(true);
            newRow.querySelector('.room-select').selectedIndex = 0;
            newRow.querySelector('.room-price').textContent = '-';
            newRow.querySelector('.qty-input').value = 1;
            table.appendChild(newRow);
            updateSummary();
        }
        function removeRow(btn) {
            const table = document.getElementById('roomTable').getElementsByTagName('tbody')[0];
            if (table.rows.length > 1) {
                btn.closest('tr').remove();
                updateSummary();
            }
        }
        function updatePrice(select) {
            const price = select.options[select.selectedIndex].getAttribute('data-price');
            const available = select.options[select.selectedIndex].getAttribute('data-available');
            select.closest('tr').querySelector('.room-price').textContent = price ? parseInt(price).toLocaleString() : '-';
            // set max
            const qtyInput = select.closest('tr').querySelector('.qty-input');
            qtyInput.max = available || 1;
            // show available
            let availableSpan = select.closest('tr').querySelector('.room-available');
            if (availableSpan) {
                availableSpan.textContent = available ? `ຫ້ອງວ່າງ: ${available}` : '';
            }
            updateSummary();
        }
        function updateSummary() {
            let total = 0;
            let roomList = [];
            const startDate = document.getElementById('booking_start').value;
            const endDate = document.getElementById('booking_end').value;
            let days = 1;
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                days = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if (days < 1) days = 1;
            }
            document.getElementById('total_days').textContent = days;
            document.querySelectorAll('#roomTable tbody tr').forEach(row => {
                const select = row.querySelector('.room-select');
                const qty = parseInt(row.querySelector('.qty-input').value) || 1;
                const price = select.options[select.selectedIndex].getAttribute('data-price');
                const type = select.options[select.selectedIndex].getAttribute('data-type');
                if (select.value && price) {
                    total += parseInt(price) * qty * days;
                    roomList.push(type + ' x' + qty);
                }
            });
            document.getElementById('total_price').textContent = total > 0 ? total.toLocaleString() : '-';
            document.getElementById('selected_rooms').textContent = roomList.length ? roomList.join(', ') : '-';
        }
        document.getElementById('booking_start').addEventListener('change', updateSummary);
        document.getElementById('booking_end').addEventListener('change', updateSummary);
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('room-select') || e.target.classList.contains('qty-input')) {
                updateSummary();
            }
        });
        // ເພີ່ມການອັບເດດລາຄາເມື່ອເລືອກຫ້ອງ
        document.querySelectorAll('.room-select').forEach(select => {
            select.addEventListener('change', function() { updatePrice(this); });
        });
        // ເລີ່ມຕົ້ນ
        updateSummary();
    </script>
</body>
</html>