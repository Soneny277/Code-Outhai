<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    
    $query = "UPDATE customers SET Name = ?, Lastname = ?, Email = ? WHERE Cus_id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$name, $lastname, $email, $user_id])) {
        $_SESSION['user_name'] = $name . ' ' . $lastname;
        $_SESSION['user_email'] = $email;
        $success = "ອັບເດດຂໍ້ມູນສຳເລັດ!";
    } else {
        $error = "ບໍ່ສາມາດອັບເດດຂໍ້ມູນ";
    }
}

// Fetch user info
$query = "SELECT * FROM customers WHERE Cus_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໂປຣໄຟລ໌ຂອງຂ້ອຍ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif; background-color: #f8f9fa; }
        .profile-card { background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="profile-card">
                <h2 class="mb-4 text-center"><i class="fas fa-user text-primary me-2"></i>ໂປຣໄຟລ໌ຂອງຂ້ອຍ</h2>
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">ຊື່</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['Name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ນາມສະກຸນ</label>
                        <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($user['Lastname']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ອີເມວ</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['Email']) ?>" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>ບັນທຶກການປ່ຽນແປງ</button>
                    </div>
                </form>
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-home me-2"></i>ກັບໄປໜ້າຫຼັກ</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 