<?php
session_start();
include 'koneksi.php';

$pesan = "";
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $q = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username' AND password = '$password'");
    if (mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);
        if($user['status_akun'] == 'Dibekukan') {
            $pesan = "Akun Anda sedang dibekukan karena sanksi keterlambatan!";
        } else {
            $_SESSION['id']   = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            // Redirect sesuai hak akses level
            if ($user['role'] == 'admin') {
                header("Location: admin/index.php");
            } elseif ($user['role'] == 'petugas') {
                header("Location: petugas/index.php");
            } else {
                header("Location: peminjam/index.php");
            }
            exit;
        }
    } else {
        $pesan = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sarpras Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            margin: 0;
            /* Wallpaper sarana prasarana sekolah/kampus dengan gradasi biru elegan */
            background: linear-gradient(135deg, rgba(13, 37, 63, 0.85), rgba(26, 82, 118, 0.9)), 
                        url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
            background-size: cover;
        }
        @keyframes pulseLogo {
            0% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.12); opacity: 1; text-shadow: 0 0 15px rgba(54, 162, 235, 0.8); }
            100% { transform: scale(1); opacity: 0.9; }
        }
        .animated-logo {
            display: inline-block;
            animation: pulseLogo 2s infinite ease-in-out;
        }
        .login-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(56, 189, 248, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            color: #fff;
        }
        .form-control {
            background-color: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            color: #fff;
        }
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.12);
            border-color: #38bdf8;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
        }
        .input-group-text {
            background-color: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            color: #94a3b8;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">

    <div class="card login-card p-5 rounded-4" style="width: 420px;">
        <div class="text-center mb-4">
            <div class="mb-2">
                <i class="bi bi-buildings text-info fs-1 animated-logo"></i>
            </div>
            <h3 class="fw-extrabold text-uppercase tracking-wide mb-1 text-white">SARPRAS <span class="text-info">SEKOLAH</span></h3>
            <p class="text-light small opacity-75">Sistem Informasi Peminjaman Alat & Ruangan</p>
        </div>

        <?php if(!empty($pesan)): ?>
            <div class="alert alert-danger py-2 small rounded-3 text-center bg-danger bg-opacity-75 text-white border-0 mb-3"><?= $pesan; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-light">Username / ID Kelas</label>
                <div class="input-group">
                    <span class="input-group-text border-end-0 rounded-start-3"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control border-start-0 rounded-end-3" placeholder="Masukkan username" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-light">Password</label>
                <div class="input-group">
                    <span class="input-group-text border-end-0 rounded-start-3"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 rounded-end-3" placeholder="Masukkan password" required>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-info w-100 rounded-pill py-2 fw-bold text-dark shadow-sm">Masuk Sistem</button>
        </form>

        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none small text-info"><i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda</a>
        </div>
    </div>

</body>
</html>