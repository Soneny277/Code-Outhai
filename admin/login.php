<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM Admin WHERE Email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $admin['Password'])) {
            $_SESSION['admin_id'] = $admin['Id'];
            $_SESSION['admin_name'] = $admin['Name'] . ' ' . $admin['Lastname'];
            $_SESSION['admin_email'] = $admin['Email'];
            $_SESSION['admin_role'] = $admin['Role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ";
        }
    } else {
        $error = "ບໍ່ພົບອີເມວນີ້ໃນລະບົບ";
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ໂຮງແຮມອຸດົມຊັບ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif;
        }
        .login-form {
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
            <div class="col-md-5">
                <div class="login-form">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-user-shield text-primary me-2"></i>Admin Login</h2>
                        <p class="text-muted">ເຂົ້າສູ່ລະບົບຈັດການ</p>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">ອີເມວ</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">ລະຫັດຜ່ານ</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>ເຂົ້າສູ່ລະບົບ
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <a href="../index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>ກັບໄປໜ້າຫຼັກ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 