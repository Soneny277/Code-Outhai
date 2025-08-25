<?php
session_start();
require_once '../config/database.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle status update actions
if (isset($_GET['action'], $_GET['id'])) {
    $booking_id = intval($_GET['id']);
    if ($_GET['action'] === 'confirm') {
        $stmt = $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
    } elseif ($_GET['action'] === 'cancel') {
        $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
    } elseif ($_GET['action'] === 'checkout') {
        // 1. ດຶງລາຍລະອຽດຫ້ອງທີ່ຈອງ
        $details_stmt = $db->prepare("SELECT Room_id, COUNT(*) as qty FROM booking_details WHERE Booking_id = ? GROUP BY Room_id");
        $details_stmt->execute([$booking_id]);
        $room_details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. ເພີ່ມຈຳນວນຫ້ອງກັບຄືນ
        foreach ($room_details as $rd) {
            $update_stmt = $db->prepare("UPDATE room SET quantity = quantity + ? WHERE Room_id = ?");
            $update_stmt->execute([$rd['qty'], $rd['Room_id']]);
        }

        // 3. ອັບເດດສະຖານະ
        $stmt = $db->prepare("UPDATE bookings SET status = 'checked_out' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
    }
    header("Location: bookings.php");
    exit();
}

// Handle add booking by admin
if (isset($_POST['add_booking'])) {
    $Cus_id = intval($_POST['Cus_id']);
    $room_id = intval($_POST['room_id']);
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $qty = intval($_POST['qty']);
    // Get room price
    $room_stmt = $db->prepare('SELECT Price FROM room WHERE Room_id = ?');
    $room_stmt->execute([$room_id]);
    $room = $room_stmt->fetch(PDO::FETCH_ASSOC);
    $price = $room ? $room['Price'] : 0;
    $nights = (strtotime($checkout) - strtotime($checkin)) / 86400;
    if ($nights < 1) $nights = 1;
    $total_price = $price * $qty * $nights;
    // Insert booking
    $stmt = $db->prepare('INSERT INTO bookings (Cus_id, booking_start, booking_end, Total_price, date, status) VALUES (?, ?, ?, ?, NOW(), "pending")');
    $stmt->execute([$Cus_id, $checkin, $checkout, $total_price]);
    $booking_id = $db->lastInsertId();
    // Insert booking_details
    for ($i = 0; $i < $qty; $i++) {
        $stmt2 = $db->prepare('INSERT INTO booking_details (Booking_id, Room_id, Room_price) VALUES (?, ?, ?)');
        $stmt2->execute([$booking_id, $room_id, $price]);
    }
    // ຫລັງຈາກ insert booking_details
    $db->prepare('UPDATE room SET quantity = quantity - ? WHERE Room_id = ?')->execute([$qty, $room_id]);
    header('Location: bookings.php?add_success=1');
    exit();
}

// Add search form
if (!isset($_GET['q'])) $_GET['q'] = '';

// Fetch all bookings with customer and room info (search)
$q = trim($_GET['q']);
$booking_query = "SELECT b.*, c.Name, c.Lastname, c.Email FROM bookings b JOIN customers c ON b.Cus_id = c.Cus_id";
$params = [];
if ($q) {
    $booking_query .= " WHERE c.Name LIKE ? OR c.Lastname LIKE ? OR b.booking_id = ?";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = $q;
}
$booking_query .= " ORDER BY b.date DESC";
$booking_stmt = $db->prepare($booking_query);
$booking_stmt->execute($params);
$bookings = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group bookings by customer (Name + Email)
$grouped_bookings = [];
foreach ($bookings as $booking) {
    $key = $booking['Name'] . '|' . $booking['Lastname'] . '|' . $booking['Email'];
    if (!isset($grouped_bookings[$key])) {
        $grouped_bookings[$key] = [
            'Name' => $booking['Name'],
            'Lastname' => $booking['Lastname'],
            'Email' => $booking['Email'],
            'rooms' => [],
            'prices' => [],
            'qtys' => [],
            'totals' => [],
            'dates' => [],
            'starts' => [],
            'ends' => [],
            'statuses' => [],
            'booking_ids' => [],
        ];
    }
    // Get room details for this booking
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
    $grouped_bookings[$key]['rooms'][] = implode('<br>', $room_strs);
    $grouped_bookings[$key]['prices'][] = implode('<br>', $price_strs);
    $grouped_bookings[$key]['qtys'][] = implode('<br>', $qty_strs);
    $grouped_bookings[$key]['totals'][] = number_format($booking['Total_price']) . ' ກີບ';
    $grouped_bookings[$key]['dates'][] = date('d/m/Y', strtotime($booking['date']));
    $grouped_bookings[$key]['starts'][] = date('d/m/Y', strtotime($booking['booking_start']));
    $grouped_bookings[$key]['ends'][] = date('d/m/Y', strtotime($booking['booking_end']));
    $grouped_bookings[$key]['statuses'][] = $booking['status'];
    $grouped_bookings[$key]['booking_ids'][] = $booking['booking_id'];
}

// Fetch all customers for dropdown
$customer_stmt = $db->prepare("SELECT Cus_id, Name, Lastname FROM customers");
$customer_stmt->execute();
$all_customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all rooms for dropdown
$room_stmt = $db->prepare("SELECT Room_id, Room_type FROM room");
$room_stmt->execute();
$all_rooms = $room_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<head>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./admin-style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #7b8cff 0%, #8f5fe8 100%);
            min-height: 100vh;
            font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; 
        }
        .card-common {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 32px 24px;
            margin-top: 32px;
        }
        .table th, .table td {
            vertical-align: middle;
            white-space: nowrap;
        }
        .divider {
            border-top: 2px solid #eee;
            margin: 32px 0 24px 0;
        }
        /* Custom status and action styles for table */
        .table-status {
            display: inline-block;
            font-size: 0.95em;
            padding: 2px 10px;
            border-radius: 6px;
            margin-bottom: 2px;
            margin-right: 2px;
            font-weight: 500;
        }
        .status-confirmed { background: #43d15c; color: #fff; }
        .status-pending { background: #ffd600; color: #222; }
        .status-cancelled { background: #f44336; color: #fff; }
        .status-checkedout { background: #888; color: #fff; }
        .table-action {
            display: inline-block;
            font-size: 0.95em;
            padding: 2px 10px;
            border-radius: 6px;
            margin-bottom: 2px;
            margin-right: 2px;
            font-weight: 500;
            text-decoration: none;
        }
        .action-confirm { background: #43d15c; color: #fff; }
        .action-cancel { background: #f44336; color: #fff; }
        .action-checkout { background: #00d4d4; color: #fff; }
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            min-width: 1400px;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="main-content flex-grow-1">
        <div class="card-common">
                    <h2 class="mb-4"><i class="fas fa-calendar-check text-primary me-2"></i> ຈັດການການຈອງ</h2>
                    <div class="divider"></div>
                    <form method="get" class="mb-4 d-flex align-items-center gap-2 flex-wrap">
                        <input type="text" name="q" class="form-control" style="max-width:350px; border-radius:12px;" placeholder="ຄົ້ນຫາຊື່ລູກຄ້າ, booking id..." value="<?= htmlspecialchars($_GET['q']) ?>">
                        <button type="submit" class="btn btn-primary" style="border-radius:12px;"><i class="fas fa-search"></i> ຄົ້ນຫາ</button>
                    </form>
                    <div class="table-responsive mb-4" style="overflow-x:auto;">
                        <table class="table table-bordered table-hover align-middle mb-0" style="min-width: 1400px;">
                            <thead class="table-primary">
                                <tr>
                                    <th>ລຳດັບ</th>
                                    <th>Booking ID</th>
                                    <th>ID ລູກຄ້າ</th>
                                    <th>ລູກຄ້າ</th>
                                    <th>ອີເມວ</th>
                                    <th>ປະເພດ</th>
                                    <th>ລາຄາຫ້ອງ</th>
                                    <th>ຈຳນວນ</th>
                                    <th>ວັນທີຈອງ</th>
                                    <th>ວັນທີເລີ່ມ</th>
                                    <th>ວັນທີອອກ</th>
                                    <th>ຈຳນວນຄືນ</th>
                                    <th>ລວມ</th>
                                    <th>ສະຖານະ</th>
                                    <th>ຈັດການ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; foreach ($bookings as $booking): ?>
                                    <?php
                                    $details_query = "SELECT r.Room_type, bd.Room_price, COUNT(*) as qty FROM booking_details bd JOIN room r ON bd.Room_id = r.Room_id WHERE bd.Booking_id = ? GROUP BY r.Room_type, bd.Room_price";
                                    $details_stmt = $db->prepare($details_query);
                                    $details_stmt->execute([$booking['booking_id']]);
                                    $room_types = $details_stmt->fetchAll(PDO::FETCH_ASSOC);

                                    $room_str = [];
                                    $price_str = [];
                                    $qty_str = [];
                                    foreach ($room_types as $rt) {
                                        $room_str[] = $rt['Room_type'];
                                        $price_str[] = number_format($rt['Room_price']);
                                        $qty_str[] = $rt['qty'];
                                    }
                                    $status = $booking['status'];
                                    ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= $booking['booking_id'] ?></td>
                                        <td><?= $booking['Cus_id'] ?></td>
                                        <td><?= htmlspecialchars($booking['Name'] . ' ' . $booking['Lastname']) ?></td>
                                        <td><?= htmlspecialchars($booking['Email']) ?></td>
                                        <td><?= implode('<br>', $room_str) ?></td>
                                        <td><?= implode('<br>', $price_str) ?></td>
                                        <td><?= implode('<br>', $qty_str) ?></td>
                                        <td><?= date('d/m/Y', strtotime($booking['date'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($booking['booking_start'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($booking['booking_end'])) ?></td>
                                        <td>
                                            <?php
                                            $start_date = new DateTime($booking['booking_start']);
                                            $end_date = new DateTime($booking['booking_end']);
                                            $nights = $end_date->diff($start_date)->days;
                                            echo $nights . ' ຄືນ';
                                            ?>
                                        </td>
                                        <td><?= number_format($booking['Total_price']) ?></td>
                                        <td>
                                            <?php
                                            if ($status == 'confirmed') {
                                                echo '<span class="table-status status-confirmed">ຢືນຢັນແລ້ວ</span>';
                                            } elseif ($status == 'pending') {
                                                echo '<span class="table-status status-pending">ລໍຖ້າ</span>';
                                            } elseif ($status == 'checked_out') {
                                                echo '<span class="table-status status-checkedout">ອອກແລ້ວ</span>';
                                            } elseif ($status == 'cancelled') {
                                                echo '<span class="table-status status-cancelled">ຍົກເລີກແລ້ວ</span>';
                                            } elseif (empty($status)) {
                                                echo '<span class="table-status status-pending">ອອກແລ້ວ</span>';
                                            } else {
                                                echo '<span class="table-status status-cancelled">'.htmlspecialchars($status).'</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($status == 'pending'): ?>
                                                <a href="?action=confirm&id=<?= $booking['booking_id'] ?>" class="table-action action-confirm">ຢືນຢັນ</a>
                                                <a href="?action=cancel&id=<?= $booking['booking_id'] ?>" class="table-action action-cancel">ຍົກເລີກ</a>
                                            <?php elseif ($status == 'confirmed'): ?>
                                                <a href="?action=checkout&id=<?= $booking['booking_id'] ?>" class="table-action action-checkout">CheckOut</a>
                                            <?php else: ?>
                                                
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 