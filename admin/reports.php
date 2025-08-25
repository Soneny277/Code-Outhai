<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Lao status badge function
function lao_status_badge($status) {
    switch ($status) {
        case 'completed': return '<span class="badge bg-success">ສຳເລັດ</span>';
        case 'pending': return '<span class="badge bg-warning text-dark">ລໍຖ້າ</span>';
        case 'cancelled': return '<span class="badge bg-danger">ຍົກເລີກແລ້ວ</span>';
        case 'checked_out': return '<span class="badge bg-secondary">ອອກແລ້ວ</span>';
        case 'confirmed': return '<span class="badge bg-success">ຢືນຢັນແລ້ວ</span>';
        default: return htmlspecialchars($status);
    }
}

// Revenue Report (use payment table)
$type = $_GET['revenue_type'] ?? 'all';
$filter_value = $_GET['revenue_value'] ?? '';

$where = "WHERE status = 'completed'";
$params = [];
if ($type === 'day' && $filter_value) {
    $where .= " AND DATE(payment_date) = ?";
    $params[] = $filter_value;
} elseif ($type === 'month' && $filter_value) {
    $where .= " AND DATE_FORMAT(payment_date, '%Y-%m') = ?";
    $params[] = $filter_value;
} elseif ($type === 'year' && $filter_value) {
    $where .= " AND YEAR(payment_date) = ?";
    $params[] = $filter_value;
}
$revenue_sql = "SELECT SUM(Payment) as total_revenue FROM payment $where";
$revenue_stmt = $db->prepare($revenue_sql);
$revenue_stmt->execute($params);
$revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC);
// Fetch payments for detail table
$detail_sql = "SELECT p.*, b.booking_id, b.booking_start, b.booking_end, b.date, b.Total_price, c.Name, c.Lastname
FROM payment p
JOIN bookings b ON p.Booking_id = b.booking_id
JOIN customers c ON b.Cus_id = c.Cus_id
WHERE p.status = 'completed'";

if ($type === 'day' && $filter_value) {
    $detail_sql .= " AND DATE(p.payment_date) = ?";
    $detail_params = [$filter_value];
} elseif ($type === 'month' && $filter_value) {
    $detail_sql .= " AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?";
    $detail_params = [$filter_value];
} elseif ($type === 'year' && $filter_value) {
    $detail_sql .= " AND YEAR(p.payment_date) = ?";
    $detail_params = [$filter_value];
} else {
    $detail_params = [];
}
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q) {
    $detail_sql .= " AND (c.Name LIKE ? OR c.Lastname LIKE ? OR DATE(p.payment_date) = ?)";
    $detail_params[] = "%$q%";
    $detail_params[] = "%$q%";
    $detail_params[] = $q;
}
$detail_sql .= " ORDER BY p.payment_date DESC";
$detail_stmt = $db->prepare($detail_sql);
$detail_stmt->execute($detail_params);
$revenue_bookings = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

// Room Report
$room_stmt = $db->prepare("SELECT Room_id, Room_type, Price FROM room");
$room_stmt->execute();
$rooms = $room_stmt->fetchAll(PDO::FETCH_ASSOC);

// Customer Report
$customer_stmt = $db->prepare("SELECT Cus_id, Name, Lastname, Email, Phone FROM customers");
$customer_stmt->execute();
$customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);

// Booking Report
$booking_stmt = $db->prepare("SELECT b.*, c.Name, c.Lastname FROM bookings b JOIN customers c ON b.Cus_id = c.Cus_id ORDER BY b.date DESC");
$booking_stmt->execute();
$bookings = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);

// Booking Status Report
$status_stmt = $db->prepare("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
$status_stmt->execute();
$status_counts = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

// Payment Report
$payment_stmt = $db->prepare("SELECT p.*, b.booking_id, c.Name, c.Lastname FROM payment p JOIN bookings b ON p.Booking_id = b.booking_id JOIN customers c ON b.Cus_id = c.Cus_id WHERE p.status = 'completed' ORDER BY p.payment_date DESC");
$payment_stmt->execute();
$payments = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);

$label = 'ລາຍຮັບລວມທີ່ຊຳລະແລ້ວ:';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລາຍງານ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./admin-style.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; }
        .main-content { padding: 30px; }
        .nav-tabs .nav-link.active { background: #667eea; color: #fff; }
        .nav-tabs .nav-link { color: #667eea; }
        @media print {
            .sidebar, .nav, .nav-tabs, .btn, .card-title, .form-label, form, .card .card-body > .row, .card .card-body > .col-auto, .alert, .modal, .modal-backdrop {
                display: none !important;
            }
            .main-content, .tab-pane.active, .card, .card-body, table {
                display: block !important;
                width: 100% !important;
                background: #fff !important;
                box-shadow: none !important;
            }
            table {
                page-break-inside: auto;
            }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="main-content flex-grow-1">
        <h2 class="mb-4"><i class="fas fa-chart-bar text-primary me-2"></i>ລາຍງານ</h2>
        <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="revenue-tab" data-bs-toggle="tab" data-bs-target="#revenue" type="button" role="tab">ລາຍງານລາຍຮັບ</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="room-tab" data-bs-toggle="tab" data-bs-target="#room" type="button" role="tab">ລາຍງານຫ້ອງ</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer" type="button" role="tab">ລາຍງານລູກຄ້າ</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="booking-tab" data-bs-toggle="tab" data-bs-target="#booking" type="button" role="tab">ລາຍງານການຈອງ</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab">ລາຍງານສະຖານະການຈອງ</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">ລາຍງານການຊຳລະເງິນ</button>
            </li>
        </ul>
        <div class="tab-content" id="reportTabsContent">
            <!-- Revenue Report -->
            <div class="tab-pane fade show active" id="revenue" role="tabpanel">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-2 align-items-end mb-3" method="get">
                            <input type="hidden" name="reportTabs" value="revenue">
                            <div class="col-auto">
                                <label class="form-label mb-0">ຄົ້ນຫາ</label>
                                <input type="text" class="form-control" name="q" placeholder="ຊື່/ນາມສະກຸນ/ວັນທີ" value="<?= htmlspecialchars($q) ?>">
                            </div>
                            <div class="col-auto">
                                <label class="form-label mb-0">ປະເພດການລາຍງານ</label>
                                <select class="form-select" name="revenue_type" id="revenue_type" onchange="updateRevenueInput()">
                                    <option value="all"<?= $type==='all'?' selected':''; ?>>ທັງໝົດ</option>
                                    <option value="day"<?= $type==='day'?' selected':''; ?>>ລາຍວັນ</option>
                                    <option value="month"<?= $type==='month'?' selected':''; ?>>ລາຍເດືອນ</option>
                                    <option value="year"<?= $type==='year'?' selected':''; ?>>ລາຍປີ</option>
                                </select>
                            </div>
                            <div class="col-auto" id="revenue_value_col">
                                <!-- JS will render input here -->
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> ຄົ້ນຫາ</button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-success" onclick="window.location.href='?reportTabs=revenue&revenue_type=day'">ສະຫຼຸບລາຍວັນ</button>
                              
                                <button type="button" class="btn btn-outline-warning" onclick="window.location.href='?reportTabs=revenue&revenue_type=month'">ສະຫຼຸບລາຍເດືອນ</button>
                            </div>
                        </form>
                        <h5 class="card-title"><?= $label ?></h5>
                        <h3 class="text-success"><?php echo number_format($revenue['total_revenue'] ?? 0); ?> ກີບ</h3>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ລາຍລະອຽດການຈອງທີ່ຢືນຢັນ</h5>
                        <div class="table-responsive">
                            <?php if (!empty($revenue_bookings)) { ?>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th class="fw-bold">Booking_id</th>
                                        <th class="fw-bold">ຊື່ລູກຄ້າ</th>
                                        <th class="fw-bold">ຫ້ອງ</th>
                                        <th class="fw-bold">ລາຄາຫ້ອງ</th>
                                        <th class="fw-bold">ຈຳນວນຫ້ອງ</th>
                                        <th class="fw-bold">ຈຳນວນຄືນ</th>
                                        <th class="fw-bold">ວັນທີຈອງ</th>
                                        <th class="fw-bold">ວັນທີເລີ່ມ</th>
                                        <th class="fw-bold">ວັນທີອອກ</th>
                                        <th class="fw-bold">ລາຄາລວມ</th>
                                        <th class="fw-bold">ສະຖານະ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($revenue_bookings as $b): ?>
                                    <?php
                                    $details_query = "SELECT r.Room_type, bd.Room_price, COUNT(*) as qty FROM booking_details bd JOIN room r ON bd.Room_id = r.Room_id WHERE bd.Booking_id = ? GROUP BY r.Room_type, bd.Room_price";
                                    $details_stmt = $db->prepare($details_query);
                                    $details_stmt->execute([$b['booking_id']]);
                                    $room_types = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    $room_strs = [];
                                    $price_strs = [];
                                    $qty_strs = [];
                                    $nights_strs = [];
                                    $start = new DateTime($b['booking_start']);
                                    $end = new DateTime($b['booking_end']);
                                    $nights = $start->diff($end)->days;
                                    foreach ($room_types as $rt) {
                                        $room_strs[] = $rt['Room_type'];
                                        $price_strs[] = number_format($rt['Room_price']) . ' ກີບ/ຄືນ';
                                        $qty_strs[] = $rt['qty'] . ' ຫ້ອງ';
                                        $nights_strs[] = $nights . ' ຄືນ';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $b['booking_id']; ?></td>
                                        <td><?php echo $b['Name'] . ' ' . $b['Lastname']; ?></td>
                                        <td><?php echo implode('<br>', $room_strs); ?></td>
                                        <td><?php echo implode('<br>', $price_strs); ?></td>
                                        <td><?php echo implode('<br>', $qty_strs); ?></td>
                                        <td><?php echo implode('<br>', $nights_strs); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($b['date'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($b['booking_start'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($b['booking_end'])); ?></td>
                                        <td><?php echo number_format($b['Total_price']); ?> ກີບ</td>
                                        <td><?= lao_status_badge($b['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php } else { ?>
                                <div class="alert alert-info text-center">ບໍ່ມີການຈອງໃນວັນນີ້</div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <script>
                    function updateRevenueInput() {
                        var type = document.getElementById('revenue_type').value;
                        var col = document.getElementById('revenue_value_col');
                        col.innerHTML = '';
                        if (type === 'day') {
                            col.innerHTML = '<label class="form-label mb-0">ວັນທີ</label><input type="date" class="form-control" name="revenue_value" value="<?= htmlspecialchars($filter_value) ?>">';
                        } else if (type === 'month') {
                            col.innerHTML = '<label class="form-label mb-0">ເດືອນ</label><input type="month" class="form-control" name="revenue_value" value="<?= htmlspecialchars($filter_value) ?>">';
                        } else if (type === 'year') {
                            col.innerHTML = '<label class="form-label mb-0">ປີ</label><input type="number" min="2000" max="2100" class="form-control" name="revenue_value" placeholder="2025" value="<?= htmlspecialchars($filter_value) ?>">';
                        }
                    }
                    document.addEventListener('DOMContentLoaded', updateRevenueInput);
                </script>
            </div>
            <!-- Room Report -->
            <div class="tab-pane fade" id="room" role="tabpanel">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ລາຄາຫ້ອງພັກ</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ປະເພດຫ້ອງ</th>
                                    <th>ລາຄາຕໍ່ຄືນ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?php echo $room['Room_type']; ?></td>
                                    <td><?php echo number_format($room['Price']); ?> ກີບ/ຄືນ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Customer Report -->
            <div class="tab-pane fade" id="customer" role="tabpanel">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ລາຍຊື່ລູກຄ້າ</h5>
                        <p class="fw-bold mb-2">ຈຳນວນລູກຄ້າ: <?php echo count($customers); ?></p>
                        <table class="table table-bordered">
                            <thead><tr><th>ໄອດີລູກຄ້າ</th><th>ຊື່</th><th>ນາມສະກຸນ</th><th>ອີເມວ</th><th>ເບີໂທ</th></tr></thead>
                            <tbody>
                            <?php foreach ($customers as $cus): ?>
                                <tr>
                                    <td><?php echo $cus['Cus_id']; ?></td>
                                    <td><?php echo $cus['Name']; ?></td>
                                    <td><?php echo $cus['Lastname']; ?></td>
                                    <td><?php echo $cus['Email']; ?></td>
                                    <td><?php echo $cus['Phone']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Booking Report -->
            <div class="tab-pane fade" id="booking" role="tabpanel">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ລາຍການການຈອງ</h5>
                        <p class="fw-bold mb-2">ຈຳນວນການຈອງ: <?php echo count($bookings); ?></p>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="fw-bold">Booking_id</th>
                                    <th class="fw-bold">ຊື່ລູກຄ້າ</th>
                                    <th class="fw-bold">ຫ້ອງ</th>
                                    <th class="fw-bold">ລາຄາຫ້ອງ</th>
                                    <th class="fw-bold">ຄືນ</th>
                                    <th class="fw-bold">ວັນທີຈອງ</th>
                                    <th class="fw-bold">ວັນທີເລີ່ມ</th>
                                    <th class="fw-bold">ວັນທີອອກ</th>
                                    <th class="fw-bold">ລາຄາລວມ</th>
                                    <th class="fw-bold">ສະຖານະ</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <?php
                                $details_stmt = $db->prepare('SELECT r.Room_type, bd.Room_price, COUNT(*) as qty FROM booking_details bd JOIN room r ON bd.Room_id = r.Room_id WHERE bd.Booking_id = ? GROUP BY r.Room_type, bd.Room_price');
                                $details_stmt->execute([$b['booking_id']]);
                                $room_types = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
                                $start = new DateTime($b['booking_start']);
                                $end = new DateTime($b['booking_end']);
                                $nights = $start->diff($end)->days;
                                $nights_strs = [];
                                foreach ($room_types as $rt) {
                                    $nights_strs[] = $nights . ' ຄືນ';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $b['booking_id']; ?></td>
                                    <td><?php echo $b['Name'] . ' ' . $b['Lastname']; ?></td>
                                    <td>
                                        <?php
                                        foreach ($room_types as $rt) {
                                            echo $rt['Room_type'] . ' ' . $rt['qty'] . ' ຫ້ອງ<br>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        foreach ($room_types as $rt) {
                                            echo number_format($rt['Room_price']) . ' ກີບ/ຄືນ<br>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo implode('<br>', $nights_strs); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($b['date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($b['booking_start'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($b['booking_end'])); ?></td>
                                    <td><?php echo number_format($b['Total_price']); ?> ກີບ</td>
                                  <td>
        <?php
        if ($b['status'] === 'cancelled') {
            echo 'ຍົກເລີກແລ້ວ';
        } elseif ($b['status'] === 'checked_out') {
            echo 'ອອກແລ້ວ';
        } elseif ($b['status'] === 'confirmed') {
            echo 'ຢືນຢັນແລ້ວ';
        } elseif ($b['status'] === 'pending') {
            echo 'ລໍຖ້າ';
        } elseif (empty($b['status'])) {
            echo 'ອອກແລ້ວ';
        } else {
            echo htmlspecialchars($b['status']);
        }
        ?>
    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Booking Status Report -->
            <div class="tab-pane fade" id="status" role="tabpanel">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ສະຖານະການຈອງ</h5>
                        <table class="table table-bordered">
                            <thead><tr><th>ສະຖານະ</th><th>ຈຳນວນ</th></tr></thead>
                            <tbody>
                            <?php foreach (
    $status_counts as $s): ?>
<tr>
    <td>
        <?php
        if ($s['status'] === 'cancelled') {
            echo 'ຍົກເລີກແລ້ວ';
        } elseif ($s['status'] === 'checked_out') {
            echo 'ອອກແລ້ວ';
        } elseif ($s['status'] === 'confirmed') {
            echo 'ຢືນຢັນແລ້ວ';
        } elseif ($s['status'] === 'pending') {
            echo 'ລໍຖ້າ';
        } elseif (empty($s['status'])) {
            echo 'ອອກແລ້ວ';
        } else {
            echo htmlspecialchars($s['status']);
        }
        ?>
    </td>
    <td><?php echo $s['count']; ?></td>
</tr>
<?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Payment Report -->
            <div class="tab-pane fade" id="payment" role="tabpanel">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ລາຍການຊຳລະເງິນ</h5>
                        <table class="table table-bordered">
                            <thead><tr><th>NO</th><th>ຊື່ລູກຄ້າ</th><th>ການຈອງ</th><th>ຈຳນວນ</th><th>ວັນທີຊຳລະ</th><th>ສະຖານະ</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?php echo $p['No']; ?></td>
                                    <td><?php echo $p['Name'] . ' ' . $p['Lastname']; ?></td>
                                    <td><?php echo $p['booking_id']; ?></td>
                                    <td><?php echo number_format($p['Payment']); ?> ກີບ</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($p['payment_date'])); ?></td>
                                    <td><?= lao_status_badge($p['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>