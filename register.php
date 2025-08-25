<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = $_POST['name'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $identity_card = $_POST['identity_card'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // ກວດສອບວ່າ email ມີຢູ່ແລ້ວບໍ່
    $check_query = "SELECT * FROM customers WHERE Email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$email]);
    
    if ($check_stmt->rowCount() > 0) {
        $error = "Email ນີ້ມີຢູ່ແລ້ວ ກະລຸນາໃຊ້ email ອື່ນ";
    } else {
        $query = "INSERT INTO customers (Name, Lastname, Email, Phone, Identity_card_number, password) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$name, $lastname, $email, $phone, $identity_card, $password])) {
            $_SESSION['success'] = "ລົງທະບຽນສຳເລັດແລ້ວ! ກະລຸນາເຂົ້າສູ່ລະບົບ";
            header("Location: login.php");
            exit();
        } else {
            $error = "ມີຂໍ້ຜິດພາດໃນການລົງທະບຽນ";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລົງທະບຽນ - ໂຮງແຮມອຸດົມຊັບ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif;
        }
        .register-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
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
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="register-form">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-user-plus text-primary me-2"></i>ລົງທະບຽນ</h2>
                        <p class="text-muted">ສ້າງບັນຊີໃໝ່ສຳລັບຈອງຫ້ອງພັກ</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">ຊື່</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lastname" class="form-label">ນາມສະກຸນ</label>
                                <input type="text" class="form-control" id="lastname" name="lastname" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">ອີເມວ</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">ເບີໂທລະສັບ</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>

                        <div class="mb-3">
                            <label for="identity_card" class="form-label">ເລກບັດປະຈຳຕົວ</label>
                            <input type="text" class="form-control" id="identity_card" name="identity_card" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">ລະຫັດຜ່ານ</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">ຢືນຢັນລະຫັດຜ່ານ</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>ລົງທະບຽນ
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p>ມີບັນຊີແລ້ວ? <a href="login.php" class="text-primary">ເຂົ້າສູ່ລະບົບ</a></p>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>ກັບໄປໜ້າຫຼັກ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ກວດສອບລະຫັດຜ່ານ
        document.getElementById('confirm_password').addEventListener('input', function() {
            var password = document.getElementById('password').value;
            var confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('ລະຫັດຜ່ານບໍ່ກົງກັນ');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html> 