<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle delete booking
if (isset($_GET['delete_booking'])) {
    $id = intval($_GET['delete_booking']);
    
    // Get booking details to update room availability
    $booking_details = $db->prepare("SELECT Room_id FROM booking_details WHERE Booking_id = ?");
    $booking_details->execute([$id]);
    $rooms = $booking_details->fetchAll(PDO::FETCH_ASSOC);
    
    // Update room availability (increase available rooms)
    foreach ($rooms as $room) {
        $db->prepare("UPDATE room SET quantity = quantity + 1 WHERE Room_id = ?")->execute([$room['Room_id']]);
    }
    
    $db->prepare("DELETE FROM payment WHERE Booking_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM booking_details WHERE Booking_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM bookings WHERE booking_id = ?")->execute([$id]);
    header("Location: management.php#bookings");
    exit();
}
// Handle delete payment
if (isset($_GET['delete_payment'])) {
    $id = intval($_GET['delete_payment']);
    $db->prepare("DELETE FROM payment WHERE No = ?")->execute([$id]);
    header("Location: management.php#payments");
    exit();
}
// Handle delete booking detail
if (isset($_GET['delete_detail'])) {
    $id = intval($_GET['delete_detail']);
    $db->prepare("DELETE FROM booking_details WHERE No = ?")->execute([$id]);
    header("Location: management.php#details");
    exit();
}
// Handle edit booking detail
if (isset($_POST['edit_detail_id'])) {
    $id = intval($_POST['edit_detail_id']);
    $room_price = $_POST['edit_room_price'];
    $db->prepare("UPDATE booking_details SET Room_price = ? WHERE No = ?")->execute([$room_price, $id]);
    header("Location: management.php#details");
    exit();
}
// Handle edit booking
if (isset($_POST['edit_booking_id'])) {
    $id = intval($_POST['edit_booking_id']);
    $start = $_POST['edit_booking_start'];
    $end = $_POST['edit_booking_end'];
    $status = $_POST['edit_booking_status'];
    
    // Get current booking status
    $current_status_query = $db->prepare("SELECT status FROM bookings WHERE booking_id = ?");
    $current_status_query->execute([$id]);
    $current_status = $current_status_query->fetchColumn();
    
    // If status is being changed to cancelled or checked_out, update room availability
    if (($status === 'cancelled' || $status === 'checked_out') && $current_status !== $status) {
        // Get booking details to update room availability
        $booking_details = $db->prepare("SELECT Room_id FROM booking_details WHERE Booking_id = ?");
        $booking_details->execute([$id]);
        $rooms = $booking_details->fetchAll(PDO::FETCH_ASSOC);
        
        // Update room availability (increase available rooms)
        foreach ($rooms as $room) {
            $db->prepare("UPDATE room SET Available_rooms = Available_rooms + 1 WHERE Room_id = ?")->execute([$room['Room_id']]);
        }
    }
    
    $db->prepare("UPDATE bookings SET booking_start = ?, booking_end = ?, status = ? WHERE booking_id = ?")->execute([$start, $end, $status, $id]);
    header("Location: management.php#bookings");
    exit();
}
// Handle checkout booking
if (isset($_GET['checkout_booking'])) {
    $id = intval($_GET['checkout_booking']);
    
    // Get booking details to update room availability
    $booking_details = $db->prepare("SELECT Room_id FROM booking_details WHERE Booking_id = ?");
    $booking_details->execute([$id]);
    $rooms = $booking_details->fetchAll(PDO::FETCH_ASSOC);
    
    // Update room availability (increase available rooms)
    foreach ($rooms as $room) {
        $db->prepare("UPDATE room SET quantity = quantity + 1 WHERE Room_id = ?")->execute([$room['Room_id']]);
    }
    
    $db->prepare("UPDATE bookings SET status = 'checked_out' WHERE booking_id = ?")->execute([$id]);
    header("Location: management.php#bookings");
    exit();
}
$sql = "SELECT b.*, p.Image_qr 
        FROM bookings b
        LEFT JOIN payment p ON b.booking_id = p.Booking_id
        ORDER BY p.payment_date DESC";
$stmt = $db->query($sql);
$booking_slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle edit payment (optional, not implemented here)

// Fetch data
$bookings = $db->query("SELECT b.*, c.Cus_id FROM bookings b LEFT JOIN customers c ON b.Cus_id = c.Cus_id ORDER BY b.date DESC")->fetchAll(PDO::FETCH_ASSOC);
$payments = $db->query("SELECT * FROM payment ORDER BY payment_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$details = $db->query("SELECT * FROM booking_details ORDER BY No DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການຂໍ້ມູນ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./admin-style.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; }
        .main-content { padding: 30px; }
        .nav-tabs .nav-link.active { background: #667eea; color: #fff; }
        .nav-tabs .nav-link { color: #667eea; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="main-content flex-grow-1">
        <a href="dashboard.php" class="btn btn-outline-primary mb-3"><i class="fas fa-arrow-left me-2"></i>ກັບໄປ Dashboard</a>
        <h2 class="mb-4"><i class="fas fa-cogs text-primary me-2"></i>ຈັດການຂໍ້ມູນ</h2>
        <ul class="nav nav-tabs mb-4" id="manageTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab">ການຈອງ</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">ການຊຳລະເງິນ</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">ລາຍລະອຽດການຈອງ</button>
            </li>
        </ul>
        <div class="tab-content" id="manageTabsContent">
            <!-- Bookings Tab -->
            <div class="tab-pane fade show active" id="bookings" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ການຈອງ</h5>
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Booking_id</th><th>ໄອດີລູກຄ້າ</th><th>ວັນທີຈອງ</th><th>ເລີ່ມ</th><th>ອອກ</th><th>ລາຄາລວມ</th><th>ສະຖານະ</th><th>ຮູບSlip</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <form method="POST">
                                        <td><?php echo $b['booking_id']; ?><input type="hidden" name="edit_booking_id" value="<?php echo $b['booking_id']; ?>"></td>
                                        <td><?php echo $b['Cus_id']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($b['date'])); ?></td>
                                        <td><input type="date" name="edit_booking_start" value="<?php echo $b['booking_start']; ?>" class="form-control form-control-sm"></td>
                                        <td><input type="date" name="edit_booking_end" value="<?php echo $b['booking_end']; ?>" class="form-control form-control-sm"></td>
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
                                        <td>
                                             <?php
                // Fetch payment slip for this booking
                $stmt = $db->prepare("SELECT Image_qr FROM payment WHERE Booking_id = ? ORDER BY payment_date DESC LIMIT 1");
                $stmt->execute([$b['booking_id']]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Debug: Show what we found
                // echo "Debug: Booking ID = " . $b['booking_id'] . "<br>";
                // if ($payment) {
                //     echo "Debug: Image_qr = " . $payment['Image_qr'] . "<br>";
                // } else {
                //     echo "Debug: No payment found<br>";
                // }
                
                if ($payment && !empty($payment['Image_qr'])) {
                    // Fix potential file extension issue
                    $imageFileName = htmlspecialchars($payment['Image_qr']);
                    
                    // Check if file extension is incomplete (missing 'g' in .png)
                    if (substr($imageFileName, -3) === '.pn') {
                        $imageFileName .= 'g'; // Add missing 'g' to make it .png
                    }
                    
                    // Use correct path separator for Windows
                    $baseDir = dirname(__DIR__); // Go up one level from admin folder
                    $absolutePath = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qr_codes' . DIRECTORY_SEPARATOR . $imageFileName;
                    $relativePath = '../uploads/qr_codes/' . $imageFileName;
                    
                    // Remove debug after fixing
                    // echo "Debug: Base dir = " . $baseDir . "<br>";
                    // echo "Debug: Absolute path = " . $absolutePath . "<br>";
                    // echo "Debug: File exists = " . (file_exists($absolutePath) ? 'YES' : 'NO') . "<br>";
                    
                    // Check if file exists using absolute path
                    if (file_exists($absolutePath)) {
                        echo '<img src="' . $relativePath . '" alt="slip" style="width:50px;height:50px;object-fit:cover;border-radius:4px;border:1px solid #ddd;cursor:pointer;" onclick="showImageModal(this.src)">';
                    } else {
                        echo '<span class="text-danger">ໄຟລ໌ບໍ່ພົບ: ' . basename($imageFileName) . '</span>';
                    }
                } else {
                    echo '<div style="height:50px;display:flex;align-items:center;justify-content:center;"><span class="text-muted">-</span></div>';
                }
                ?>
                                        </td>
                                        <td>
                                            <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                                            <a href="?delete_booking=<?php echo $b['booking_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ຢືນຢັນລຶບ?');"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Payments Tab -->
            <div class="tab-pane fade" id="payments" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ການຊຳລະເງິນ</h5>
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>NO</th><th>Booking ID</th><th>Amount</th><th>Date</th><th>ສະຖານະ</th><th>Slip</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?php echo $p['No']; ?></td>
                                    <td><?php echo $p['Booking_id']; ?></td>
                                    <td><?php echo number_format($p['Payment']); ?> ກີບ</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($p['payment_date'])); ?></td>
                                    <td>
                                        <?php
                                        // Get booking status for this payment
                                        $booking_status_query = $db->prepare("SELECT status FROM bookings WHERE booking_id = ?");
                                        $booking_status_query->execute([$p['Booking_id']]);
                                        $booking_status = $booking_status_query->fetchColumn();
                                        
                                        if ($booking_status === 'cancelled') {
                                            echo 'ຍົກເລີກແລ້ວ';
                                        } elseif ($booking_status === 'checked_out') {
                                            echo 'ອອກແລ້ວ';
                                        } elseif ($booking_status === 'confirmed') {
                                            echo 'ຢືນຢັນແລ້ວ';
                                        } elseif ($booking_status === 'pending') {
                                            echo 'ລໍຖ້າ';
                                        } elseif (empty($booking_status)) {
                                            echo 'ອອກແລ້ວ';
                                        } else {
                                            echo htmlspecialchars($booking_status);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['Image_qr'])): ?>
                                            <?php
                                            $imageFileName = htmlspecialchars($p['Image_qr']);
                                            // Fix incomplete file extensions
                                            if (substr($imageFileName, -3) === '.pn') {
                                                $imageFileName .= 'g';
                                            }
                                            $baseDir = dirname(__DIR__);
                                            $absolutePath = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qr_codes' . DIRECTORY_SEPARATOR . $imageFileName;
                                            $relativePath = '../uploads/qr_codes/' . $imageFileName;
                                            if (file_exists($absolutePath)) {
                                                echo '<img src="' . $relativePath . '" alt="Slip" style="width:50px;height:50px;object-fit:cover;border-radius:4px;border:1px solid #ddd;cursor:pointer;" onclick="showImageModal(this.src)">';
                                            } else {
                                                echo '<div style="height:50px;display:flex;align-items:center;justify-content:center;"><span class="text-muted">File not found</span></div>';
                                            }
                                            ?>
                                        <?php else: ?>
                                            <div style="height:50px;display:flex;align-items:center;justify-content:center;"><span class="text-muted">-</span></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?delete_payment=<?php echo $p['No']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ຢືນຢັນລຶບ?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Booking Details Tab -->
            <div class="tab-pane fade" id="details" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">ລາຍລະອຽດການຈອງ</h5>
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>NO</th><th>Booking ID</th><th>Room ID</th><th>Room Price</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($details as $d): ?>
                                <tr>
                                    <form method="POST">
                                        <td><?php echo $d['No']; ?><input type="hidden" name="edit_detail_id" value="<?php echo $d['No']; ?>"></td>
                                        <td><?php echo $d['Booking_id']; ?></td>
                                        <td><?php echo $d['Room_id']; ?></td>
                                        <td><input type="number" name="edit_room_price" value="<?php echo $d['Room_price']; ?>" class="form-control form-control-sm"></td>
                                        <td>
                                            <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                                            <a href="?delete_detail=<?php echo $d['No']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('ຢືນຢັນລຶບ?');"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </form>
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

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">ຮູບ Payment Slip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Payment Slip" style="max-width:100%;max-height:400px;height:auto;border-radius:8px;object-fit:contain;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ປິດ</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    var modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}
</script>
</body>
</html>