<?php
session_start();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ສະແກນ QR ການຈອງ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #23235b 0%, #6366f1 100%);
            min-height: 100vh;
            font-family: 'Noto Sans Lao', 'Phetsarath OT', sans-serif;
        }
        .sidebar {
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
        .sidebar .avatar {
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
        .sidebar .nav-link {
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
        .sidebar .nav-link i {
            color: #fff;
            font-size: 1.25rem;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.22);
            color: #fff;
            box-shadow: 0 0 12px 2px #818cf8, 0 2px 8px 0 rgba(99,102,241,0.13);
        }
        .sidebar .nav-link.active i, .sidebar .nav-link:hover i {
            color: #fff;
            text-shadow: 0 0 8px #a5b4fc;
        }
        .sidebar h4 {
            color: #fff;
            font-weight: 700;
            letter-spacing: 1px;
            text-align: center;
            margin-bottom: 18px;
        }
        .main-content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            background: transparent;
        }
        .qr-card {
            max-width: 440px;
            margin: 56px auto;
            background: rgba(255,255,255,0.18);
            border-radius: 28px;
            box-shadow: 0 8px 40px 0 rgba(49,46,129,0.18), 0 2px 12px 0 rgba(99,102,241,0.10);
            padding: 44px 32px 32px 32px;
            text-align: center;
            border: 2.5px solid rgba(129,140,248,0.18);
            backdrop-filter: blur(10px);
        }
        .qr-card h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 22px;
            letter-spacing: 1px;
            color: #23235b;
            text-shadow: 0 2px 8px #a5b4fc33;
        }
        .qr-icon {
            font-size: 4.2rem;
            color: #6366f1;
            margin-bottom: 22px;
            text-shadow: 0 2px 12px #a5b4fc55;
        }
        #reader {
            width: 100%;
            min-height: 260px;
            border-radius: 18px;
            border: 2.5px solid #a5b4fc;
            background: #f3f4f6cc;
            margin-bottom: 22px;
            box-shadow: 0 2px 12px 0 #a5b4fc33;
        }
        .or {
            color: #818cf8;
            font-weight: 600;
            margin: 22px 0 12px 0;
            letter-spacing: 1px;
        }
        .upload-area {
            border: 2.5px dashed #818cf8;
            border-radius: 16px;
            padding: 22px 10px;
            background: #f8fafcbb;
            margin-bottom: 12px;
            transition: border-color 0.2s, background 0.2s;
        }
        .upload-area.dragover {
            border-color: #6366f1;
            background: #eef2ffcc;
        }
        .btn-gradient {
            background: linear-gradient(90deg, #6366f1 0%, #818cf8 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1.18rem;
            font-weight: 700;
            padding: 14px 0;
            width: 100%;
            margin-top: 12px;
            box-shadow: 0 2px 12px 0 #818cf855, 0 2px 8px 0 rgba(99,102,241,0.13);
            transition: background 0.2s, box-shadow 0.2s;
            letter-spacing: 1px;
        }
        .btn-gradient:hover {
            background: linear-gradient(90deg, #818cf8 0%, #6366f1 100%);
            box-shadow: 0 0 16px 2px #a5b4fc, 0 2px 8px 0 rgba(99,102,241,0.13);
        }
        #result {
            margin-top: 22px;
        }
        @media (max-width: 900px) {
            .main-content { flex-direction: column; }
            .sidebar { min-height: unset; border-right: none; border-bottom: 1.5px solid #e0e7ff; border-radius: 0 0 32px 32px; }
        }
    </style>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body>
<div class="d-flex">
    <div class="sidebar">
        <div class="avatar mb-2"><i class="fa-solid fa-user-shield"></i></div>
        <h4 class="mb-4">Admin Panel</h4>
        <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php"><i class="fas fa-user-circle"></i> ຫນ້າຫຼັກ</a>
                    <a class="nav-link" href="bookings.php"><i class="fas fa-calendar-check"></i> ການຈອງ</a>
                    <a class="nav-link active" href="rooms.php"><i class="fas fa-bed"></i> ເພີ່ມຫ້ອງພັກ</a>
                    <a class="nav-link" href="customers.php"><i class="fas fa-user-plus"></i> ເພີ່ມລູກຄ້າ</a>
                    <a class="nav-link" href="add_booking.php"><i class="fas fa-calendar-plus"></i> ເພີ່ມການຈອງ</a>
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> ລາຍງານ</a>
                    <a class="nav-link" href="history.php"><i class="fas fa-history"></i> ຈັດເກັບຂໍ້ມູນ</a>
                    <a class="nav-link" href="management.php"><i class="fas fa-cogs"></i> ຈັດການຂໍ້ມູນ</a>
                    <a class="nav-link" href="scan_qr.php">
                            <i class="fas fa-qrcode me-1"></i>ສະແກຮນ
                        </a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> ຕັ້ງຄ່າ</a>
                    <hr class="my-3">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> ອອກຈາກລະບົບ</a>
        </nav>
    </div>
    <div class="main-content flex-grow-1">
        <div class="container-fluid">
            <button class="btn btn-outline-secondary mb-3" onclick="window.history.back();"><i class="fa fa-arrow-left me-1"></i> ກັບຄືນ</button>
            <div class="qr-card">
                <div class="qr-icon"><i class="fa-solid fa-qrcode"></i></div>
                <h2>ສະແກນ QR ການຈອງຂອງລູກຄ້າ</h2>
                <div id="reader"></div>
                <div class="or">- ຫຼື -</div>
                <div class="upload-area" id="upload-area">
                    <label for="qr-image" class="form-label mb-2">ອັບໂຫຼດ/ລາກຮູບ QR Code ມາວາງ</label>
                    <input type="file" class="form-control" id="qr-image" accept="image/*" capture="environment" style="display:none;">
                    <button class="btn btn-gradient mt-2" type="button" onclick="document.getElementById('qr-image').click()">ເລືອກຮູບ QR Code</button>
                </div>
                <div id="result" class="text-center"></div>
            </div>
        </div>
    </div>
</div>
<script>
function handleResult(decodedText) {
    document.getElementById('result').innerHTML = '<div class="alert alert-success">ພົບ QR: ' + decodedText + '</div>';
    let match = decodedText.match(/booking_id=(\d+)/);
    let booking_id = null;
    if (match) {
        booking_id = match[1];
    } else if (/^\d+$/.test(decodedText)) {
        booking_id = decodedText;
    }
    if (booking_id) {
        window.location.href = '../view_booking.php?booking_id=' + booking_id;
    } else if (decodedText.startsWith('http')) {
        window.location.href = decodedText;
    }
}
let html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", { fps: 10, qrbox: 220 }, false);
html5QrcodeScanner.render(handleResult);

// Drag and drop for upload area
const uploadArea = document.getElementById('upload-area');
const qrImageInput = document.getElementById('qr-image');
uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});
uploadArea.addEventListener('dragleave', function(e) {
    uploadArea.classList.remove('dragover');
});
uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) {
        qrImageInput.files = e.dataTransfer.files;
        scanImageFile(e.dataTransfer.files[0]);
    }
});
qrImageInput.addEventListener('change', function(e) {
    if (e.target.files.length === 0) return;
    scanImageFile(e.target.files[0]);
});
function scanImageFile(file) {
    const reader = new FileReader();
    reader.onload = function() {
        Html5Qrcode.getCameras().then(() => {
            const html5Qr = new Html5Qrcode("reader");
            html5Qr.scanFile(file, true)
                .then(handleResult)
                .catch(err => {
                    document.getElementById('result').innerHTML = '<div class="alert alert-danger">ບໍ່ພົບ QR code ໃນຮູບນີ້</div>';
                });
        });
    };
    reader.readAsDataURL(file);
}
</script>
</body>
</html> 