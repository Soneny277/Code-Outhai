<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Ensure $rooms is always defined before HTML
$stmt = $db->prepare('SELECT * FROM room');
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add room
if (isset($_POST['add_room'])) {
    $room_type = $_POST['room_type'];
    $price = $_POST['price'];
    $room_detail = $_POST['room_detail'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/rooms/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = 'room_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $image;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
    }
    $stmt = $db->prepare("INSERT INTO room (Room_type, Price, Room_detail, images, quantity) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$room_type, $price, $room_detail, $image, $quantity]);
    header("Location: rooms.php");
    exit();
}

// Handle edit price
if (isset($_POST['edit_room_id'], $_POST['edit_price'])) {
    $edit_room_id = $_POST['edit_room_id'];
    $edit_price = $_POST['edit_price'];
    $stmt = $db->prepare("UPDATE room SET Price = ? WHERE Room_id = ?");
    $stmt->execute([$edit_price, $edit_room_id]);
    header("Location: rooms.php");
    exit();
}

// Handle edit room (all fields)
if (isset($_POST['edit_full_room_id'])) {
    $edit_room_id = $_POST['edit_full_room_id'];
    $edit_room_type = $_POST['edit_room_type'];
    $edit_price = $_POST['edit_full_price'];
    $edit_room_detail = $_POST['edit_room_detail'];
    $edit_quantity = isset($_POST['edit_quantity']) ? intval($_POST['edit_quantity']) : 1;
    $image = null;
    $old_image = $_POST['old_image'] ?? null;
    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == 0) {
        $upload_dir = '../uploads/rooms/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
        $image = 'room_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $image;
        move_uploaded_file($_FILES['edit_image']['tmp_name'], $upload_path);
        // Optionally delete old image file
        if ($old_image && file_exists($upload_dir . $old_image)) {
            @unlink($upload_dir . $old_image);
        }
    } else {
        $image = $old_image;
    }
    $stmt = $db->prepare("UPDATE room SET Room_type=?, Price=?, Room_detail=?, images=?, quantity=? WHERE Room_id=?");
    $stmt->execute([$edit_room_type, $edit_price, $edit_room_detail, $image, $edit_quantity, $edit_room_id]);
    header("Location: rooms.php?edit_success=1");
    exit();
}

// Handle delete room
if (isset($_GET['delete_room_id'])) {
    $delete_room_id = intval($_GET['delete_room_id']);
    $stmt = $db->prepare('DELETE FROM room WHERE Room_id = ?');
    $stmt->execute([$delete_room_id]);
    header('Location: rooms.php?delete_success=1');
    exit();
}

function returnRoomQuantity($db, $booking_id) {
    // ລາຍການຫ້ອງທີ່ຈອງ
    $stmt = $db->prepare("SELECT Room_id, COUNT(*) as qty FROM booking_details WHERE Booking_id = ? GROUP BY Room_id");
    $stmt->execute([$booking_id]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rooms as $room) {
        // ຫ້ອງວ່າງກັບຄືນ
        $update = $db->prepare("UPDATE room SET quantity = quantity + ? WHERE Room_id = ?");
        $update->execute([$room['qty'], $room['Room_id']]);
    }
}

?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການຫ້ອງພັກ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./admin-style.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; }
        .main-content { padding: 30px; }
        .room-img { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="main-content flex-grow-1">
            <h2 class="mb-4"><i class="fas fa-bed text-primary me-2"></i>ຈັດການຫ້ອງພັກ</h2>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">ເພີ່ມຫ້ອງພັກໃໝ່</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">ປະເພດຫ້ອງ</label>
                                <input type="text" name="room_type" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ລາຄາຕໍ່ຄືນ</label>
                                <input type="number" name="price" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ຈຳນວນຫ້ອງວ່າງ</label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ຮູບຫ້ອງ</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">ລາຍລະອຽດຫ້ອງ</label>
                                <textarea name="room_detail" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="add_room" class="btn btn-primary"><i class="fas fa-plus me-2"></i>ເພີ່ມຫ້ອງ</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">ລາຍການຫ້ອງພັກທັງໝົດ</h5>
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>ID</th><th>ປະເພດຫ້ອງ</th><th>ລາຄາຕໍ່ຄືນ</th><th>ຈຳນວນຫ້ອງວ່າງ</th><th>ຮູບ</th><th>ລາຍລະອຽດ</th><th></th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo $room['Room_id']; ?></td>
                                <td><?php echo $room['Room_type']; ?></td>
                                <td><?php echo number_format($room['Price']); ?> ກີບ</td>
                                <td><?php echo $room['quantity']; ?></td>
                                <td>
                                    <?php if ($room['images']): ?>
                                        <img src="../uploads/rooms/<?php echo $room['images']; ?>" class="room-img">
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $room['Room_detail']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editRoomModal<?php echo $room['Room_id']; ?>"><i class="fas fa-edit"></i> ແກ້ໄຂ</button>
                                </td>
                                <td>
                                    <a href="?delete_room_id=<?php echo $room['Room_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ຢືນຢັນການລົບ?');"><i class="fas fa-trash"></i> ລົບ</a>
                                </td>
                            </tr>
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editRoomModal<?php echo $room['Room_id']; ?>" tabindex="-1" aria-labelledby="editRoomLabel<?php echo $room['Room_id']; ?>" aria-hidden="true">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <form method="POST" enctype="multipart/form-data">
                                    <div class="modal-header">
                                      <h5 class="modal-title" id="editRoomLabel<?php echo $room['Room_id']; ?>">ແກ້ໄຂຫ້ອງ</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                      <input type="hidden" name="edit_full_room_id" value="<?php echo $room['Room_id']; ?>">
                                      <input type="hidden" name="old_image" value="<?php echo $room['images']; ?>">
                                      <div class="mb-3">
                                        <label class="form-label">ປະເພດຫ້ອງ</label>
                                        <input type="text" name="edit_room_type" class="form-control" value="<?php echo htmlspecialchars($room['Room_type']); ?>" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">ລາຄາຕໍ່ຄືນ</label>
                                        <input type="number" name="edit_full_price" class="form-control" value="<?php echo $room['Price']; ?>" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">ລາຍລະອຽດຫ້ອງ</label>
                                        <textarea name="edit_room_detail" class="form-control" rows="2"><?php echo htmlspecialchars($room['Room_detail']); ?></textarea>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">ຈຳນວນຫ້ອງວ່າງ</label>
                                        <input type="number" name="edit_quantity" class="form-control" value="<?php echo $room['quantity']; ?>" min="1" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">ຮູບຫ້ອງ</label><br>
                                        <?php if ($room['images']): ?>
                                            <img src="../uploads/rooms/<?php echo $room['images']; ?>" class="room-img mb-2"><br>
                                        <?php endif; ?>
                                        <input type="file" name="edit_image" class="form-control" accept="image/*">
                                        <small class="text-muted">ຫາກບໍ່ເລືອກຮູບໃໝ່ ຈະໃຊ້ຮູບເກົ່າ</small>
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ປິດ</button>
                                      <button type="submit" class="btn btn-primary">ບັນທຶກ</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
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