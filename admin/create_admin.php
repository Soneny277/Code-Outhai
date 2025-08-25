<?php
require_once '../config/database.php';


$database = new Database();
$db = $database->getConnection();

// ລະຫັດຜ່ານທີ່ຕ້ອງການ
$password = 'password';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ລຶບ admin ເກົ່າ (ຖ້າມີ)
$delete_query = "DELETE FROM Admin WHERE Email = 'admin@hoteloudomsup.com'";
$delete_stmt = $db->prepare($delete_query);
$delete_stmt->execute();

// ເພີ່ມ admin ໃໝ່
$insert_query = "INSERT INTO Admin (Name, Lastname, Email, Password, Role) VALUES (?, ?, ?, ?, ?)";
$insert_stmt = $db->prepare($insert_query);

if ($insert_stmt->execute(['ອຸດົມ', 'ຊັບ', 'admin@hoteloudomsup.com', $hashed_password, 'superadmin'])) {
    echo "✅ ສ້າງ Admin ສຳເລັດແລ້ວ!<br>";
    echo "📧 Email: admin@hoteloudomsup.com<br>";
    echo "🔑 Password: password<br>";
    echo "<br>ຕອນນີ້ທ່ານສາມາດເຂົ້າສູ່ລະບົບ Admin ໄດ້ແລ້ວ!";
} else {
    echo "❌ ມີຂໍ້ຜິດພາດໃນການສ້າງ Admin";
}
?> 