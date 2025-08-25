<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle delete customer                                                                                                                                                                                
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $db->prepare('DELETE FROM customers WHERE Cus_id = ?');
    $stmt->execute([$delete_id]);
    header('Location: customers.php');
    exit();
}

// Handle add customer
if (isset($_POST['add_customer'])) {
    $name = trim($_POST['name']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $identity_card = trim($_POST['identity_card_number']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO customers (Name, Lastname, Email, Phone, identity_card_number, Password) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $lastname, $email, $phone, $identity_card, $password]);
    header('Location: customers.php?add_success=1');
    exit();
}

// Search
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM customers';
$params = [];
if ($q) {
    $sql .= ' WHERE Name LIKE ? OR Lastname LIKE ? OR Email LIKE ? OR Phone LIKE ?';
    $params = ["%$q%", "%$q%", "%$q%", "%$q%"];
}
$sql .= ' ORDER BY Cus_id DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການລູກຄ້າ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; }
        .main-content { padding: 30px; }
        .sidebar {
            background: #667eea;
            color: #fff;
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link.active {
            background: #667eea;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    <div class="main-content flex-grow-1">
            <h2 class="mb-4 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users text-primary me-2"></i>ຈັດການລູກຄ້າ</span>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="fas fa-user-plus me-1"></i> ເພີ່ມລູກຄ້າ
                </button>
            </h2>
            <?php if (isset($_GET['add_success'])): ?>
                <div class="alert alert-success">ເພີ່ມລູກຄ້າສຳເລັດ!</div>
            <?php endif; ?>
            <form method="get" class="mb-3 d-flex">
                <input type="text" name="q" class="form-control me-2" placeholder="ຄົ້ນຫາຊື່, ອີເມວ, ເບີໂທ..." value="<?= htmlspecialchars($q) ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> ຄົ້ນຫາ</button>
            </form>
            <!-- Add Customer Modal -->
            <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST">
                    <div class="modal-header">
                      <h5 class="modal-title" id="addCustomerModalLabel"><i class="fas fa-user-plus me-1"></i> ເພີ່ມລູກຄ້າ</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="ຊື່" required></div>
                      <div class="mb-2"><input type="text" name="lastname" class="form-control" placeholder="ນາມສະກຸນ" required></div>
                      <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                      <div class="mb-2"><input type="text" name="phone" class="form-control" placeholder="ເບີໂທ" required></div>
                      <div class="mb-2"><input type="text" name="identity_card_number" class="form-control" placeholder="ເລກບັດປະຈຳຕົວ" required></div>
                      <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ປິດ</button>
                      <button type="submit" name="add_customer" class="btn btn-success"><i class="fas fa-user-plus me-1"></i> ເພີ່ມລູກຄ້າ</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>ຊື່</th>
                                <th>ນາມສະກຸນ</th>
                                <th>Email</th>
                                <th>ເບີໂທ</th>
                                <th>ເລກບັດປະຈຳຕົວ</th>
                                <th>ຈັດການ</th>
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
                                <td><?= htmlspecialchars($cus['Identity_card_number'] ?? '') ?></td>
                                <td>
                                    <a href="?delete_id=<?= $cus['Cus_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ຢືນຢັນການລົບ?');"><i class="fas fa-trash"></i> ລົບ</a>
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