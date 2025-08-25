<?php
// Sidebar for admin pages (glassmorphism, gradient, avatar, glow)
?>
<style>
.admin-sidebar {
    min-height: 100vh;
    background: linear-gradient(135deg, rgba(49,46,129,0.85) 0%, rgba(99,102,241,0.85) 100%);
    border-right: none;
    padding: 36px 18px 18px 18px;
    color: #fff;
    box-shadow: 8px 0 32px 0 rgba(99,102,241,0.13);
    border-top-right-radius: 36px;
    border-bottom-right-radius: 36px;
    backdrop-filter: blur(8px);
    position: relative;
}
.admin-sidebar .avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #818cf8 0%, #a5b4fc 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px auto;
    box-shadow: 0 2px 12px 0 rgba(99,102,241,0.18);
    font-size: 2.2rem;
    color: #fff;
}
.admin-sidebar .nav-link {
    white-space: nowrap;
    color: #fff;
    font-weight: 500;
    margin-bottom: 12px;
    border-radius: 12px;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    padding: 14px 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 1.13rem;
    letter-spacing: 0.5px;
    box-shadow: none;
}
.admin-sidebar .nav-link i {
    color: #fff;
    font-size: 1.25rem;
}
.admin-sidebar .nav-link.active, .admin-sidebar .nav-link:hover {
    background: rgba(255,255,255,0.22);
    color: #fff;
    box-shadow: 0 0 12px 2px #818cf8, 0 2px 8px 0 rgba(99,102,241,0.13);
}
.admin-sidebar .nav-link.active i, .admin-sidebar .nav-link:hover i {
    color: #fff;
    text-shadow: 0 0 8px #a5b4fc;
}
.admin-sidebar h4 {
    white-space: nowrap;
    color: #fff;
    font-weight: 700;
    letter-spacing: 1px;
    text-align: center;
    margin-bottom: 18px;
}
@media (max-width: 900px) {
    .admin-sidebar { min-height: unset; border-right: none; border-bottom: 1.5px solid #e0e7ff; border-radius: 0 0 32px 32px; }
}
</style>
<div class="admin-sidebar">
    <div class="avatar mb-2"><i class="fa-solid fa-user-shield"></i></div>
    <h4 class="mb-4">Admin Panel</h4>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php"><i class="fa fa-home me-2"></i>ໜ້າຫຼັກ</a>
        <a class="nav-link" href="bookings.php"><i class="fa fa-calendar-check me-2"></i>ຈັດການການຈອງ</a>
        <a class="nav-link" href="rooms.php"><i class="fa fa-bed me-2"></i>ຈັດການຫ້ອງພັກ</a>
        <a class="nav-link" href="customers.php"><i class="fa fa-users me-2"></i>ຈັດການລູກຄ້າ</a>
        <a class="nav-link" href="add_booking.php"><i class="fas fa-calendar-plus"></i> ເພີ່ມການຈອງ</a>
        
        <a class="nav-link" href="reports.php"><i class="fa fa-chart-bar me-2"></i>ລາຍງານ</a>
        <a class="nav-link" href="management.php"><i class="fas fa-cogs me-2"></i>ຈັດການຂໍ້ມູນ</a>
        <a class="nav-link" href="history.php"><i class="fas fa-history me-2"></i>ຈັດເກັບຂໍ້ມູນ</a>
        <a class="nav-link" href="scan_qr.php"><i class="fa-solid fa-qrcode me-2"></i>ສະແກນ QR</a>
       
        <a class="nav-link" href="logout.php"><i class="fa fa-sign-out-alt me-2"></i>ອອກຈາກລະບົບ</a>
    </nav>
</div>

<!-- Booking Notification System -->
<script src="js/notifications.js"></script> 