<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch admins
$admins = $db->query("SELECT * FROM Admin ORDER BY Id DESC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch customers
$customers = $db->query("SELECT * FROM customers ORDER BY Cus_id DESC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch rooms
$rooms = $db->query("SELECT * FROM room ORDER BY Room_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ປະຫວັດແລະຈັດເກັບຂໍ້ມູນ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./admin-style.css" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; background: #f8f9fa; }
        .history-card { background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 30px; }
        h3 { color:rgb(134, 121, 147); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <?php include 'sidebar.php'; ?>
            </div>
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h2 class="mb-4 text-center"><i class="fas fa-database text-primary me-2"></i>ປະຫວັດແລະຈັດເກັບຂໍ້ມູນ</h2>
                <div class="history-card mb-5">
                    <h3><i class="fas fa-user-shield me-2"></i>ຂໍ້ມູນແອັດມິນ</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>ຊື່</th>
                                    <th>ນາມສະກຸນ</th>
                                    <th>ອີເມວ</th>
                                    <th>ສິດທິ</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?= $admin['Id'] ?></td>
                                    <td><?= htmlspecialchars($admin['Name']) ?></td>
                                    <td><?= htmlspecialchars($admin['Lastname']) ?></td>
                                    <td><?= htmlspecialchars($admin['Email']) ?></td>
                                    <td><?= htmlspecialchars($admin['Role']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="history-card mb-5">
                    <h3><i class="fas fa-users me-2"></i>ຂໍ້ມູນລູກຄ້າ</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>ຊື່</th>
                                    <th>ນາມສະກຸນ</th>
                                    <th>ອີເມວ</th>
                                    <th>ເບີໂທ</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($customers as $cus): ?>
                                <tr>
                                    <td><?= $cus['Cus_id'] ?></td>
                                    <td><?= htmlspecialchars($cus['Name']) ?></td>
                                    <td><?= htmlspecialchars($cus['Lastname']) ?></td>
                                    <td><?= htmlspecialchars($cus['Email']) ?></td>
                                    <td><?= htmlspecialchars($cus['Phone']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="history-card">
                    <h3><i class="fas fa-bed me-2"></i>ຂໍ້ມູນຫ້ອງພັກ</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>ປະເພດຫ້ອງ</th>
                                    <th>ລາຄາ</th>
                                    <th>ລາຍລະອຽດ</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?= $room['Room_id'] ?></td>
                                    <td><?= htmlspecialchars($room['Room_type']) ?></td>
                                    <td><?= number_format($room['Price']) ?> ກີບ</td>
                                    <td><?= htmlspecialchars($room['Room_detail']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 