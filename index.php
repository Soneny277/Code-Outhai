<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$rooms = $db->query("SELECT * FROM room")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໂຮງແຮມອຸດົມຊັບ - ຈອງຫ້ອງພັກອອນລາຍ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans Lao', 'Phetsarath OT',sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: #fff !important;
        }
        .hero-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 60px 0;
            margin: 40px 0;
        }
        .room-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            transition: transform 0.3s ease;
            margin-bottom: 30px;
        }
        .room-card:hover {
            transform: translateY(-10px);
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
            transform: translateY(-2px);
        }
        .footer {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 40px 0;
            margin-top: 50px;
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
                        <a class="nav-link" href="#home">ໜ້າຫຼັກ</a>
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
                    <?php if (isset(
                        $_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">ເຂົ້າສູ່ລະບົບ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">ລົງທະບຽນ</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container text-center text-white">
            <h1 class="display-4 mb-4">ຍິນດີຕ້ອນຮັບສູ່ໂຮງແຮມອຸດົມຊັບ</h1>
            <p class="lead mb-4">ປະສົບການການພັກຜ່ອນທີ່ດີເລີດ ກັບບໍລິການທີ່ມາດຕະຖານ</p>
            <a href="#rooms" class="btn btn-primary btn-lg">
                <i class="fas fa-bed me-2"></i>
                ຈອງຫ້ອງພັກ
            </a>
        </div>
    </section>

    <!-- Rooms Section -->
    <section id="rooms" class="py-5">
        <div class="container">
            <h2 class="text-center text-white mb-5">ຫ້ອງພັກຂອງພວກເຮົາ</h2>
            <div class="row">
                <?php foreach ($rooms as $room): ?>
                <div class="col-md-3">
                    <div class="room-card p-4 text-center">
                        <?php
                        $type = trim($room['Room_type']);
                        $img_file = null;
                        if ($type === 'ຕຽງດຽວ') {
                            $img_file = 'room1.jpg';
                        } elseif ($type === 'ຕຽງຄູ່') {
                            $img_file = 'room2.jpg';
                        } elseif ($type === 'ຫ້ອງຄອບຄົວ') {
                            $img_file = 'room3.jpg';
                        } elseif ($type === 'ຫ້ອງ VIP') {
                            $img_file = 'room4.jpg';
                        }
                        $show_img = '';
                        if ($room['images']) {
                            $show_img = 'uploads/rooms/' . htmlspecialchars($room['images']);
                        } elseif ($img_file && file_exists($img_file)) {
                            $show_img = $img_file;
                        }
                        ?>
                        <?php if ($show_img): ?>
                            <img src="<?= $show_img ?>" alt="room image" style="aspect-ratio:4/3;width:100%;height:auto;object-fit:cover;border-radius:10px;margin-bottom:15px;">
                        <?php else: ?>
                            <img src="room1.jpg" alt="room image" style="aspect-ratio:4/3;width:100%;height:auto;object-fit:cover;border-radius:10px;margin-bottom:15px;">
                        <?php endif; ?>
                        <h4><?= htmlspecialchars($room['Room_type']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($room['Room_detail']) ?></p>
                        <h5 class="text-primary"><?= number_format($room['Price']) ?> ກີບ/ຄືນ</h5>
                        <a href="booking.php?room_type=<?= urlencode($room['Room_type']) ?>" class="btn btn-primary">ຈອງຫ້ອງ</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4">
                    <i class="fas fa-wifi fa-3x text-primary mb-3"></i>
                    <h4>WiFi ຟຣີ</h4>
                    <p>ການເຊື່ອມຕໍ່ອິນເຕີເນັດໄວທົ່ວທຸກຫ້ອງ</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-parking fa-3x text-success mb-3"></i>
                    <h4>ທີ່ຈອດລົດຟຣີ</h4>
                    <p>ທີ່ຈອດລົດທີ່ປອດໄພ ແລະ ສະດວກ</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-utensils fa-3x text-warning mb-3"></i>
                    <h4>ຮ້ານອາຫານ</h4>
                    <p>ອາຫານລາວທີ່ແຊບ ແລະ ສົດໃໝ່</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>ໂຮງແຮມອຸດົມຊັບ</h5>
                    <p>ບໍລິການທີ່ດີເລີດ ສຳລັບການພັກຜ່ອນຂອງທ່ານ</p>
                </div>
                <div class="col-md-4">
                    <h5>ຕິດຕໍ່</h5>
                    <p><i class="fas fa-phone me-2"></i> 020-12345678</p>
                    <p><i class="fas fa-envelope me-2"></i> info@hoteloudomsup.com</p>
                </div>
                <div class="col-md-4">
                    <h5>ຕິດຕາມພວກເຮົາ</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-2x"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-2x"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-2x"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 