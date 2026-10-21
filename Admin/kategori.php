<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit; }

if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori) VALUES ('$nama')");
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Menambah kategori: $nama')");
    header("Location: kategori.php"); exit;
}
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM kategori WHERE id = '$id'");
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Menghapus kategori ID: $id')");
    header("Location: kategori.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Sarpras - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-canvas: #f4f7fa;
            --card-bg: #FFFFFF;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --sidebar-bg: #FFFFFF;
            --primary-blue: #0d253f;
            --primary-blue-dark: #071628;
            --primary-blue-light: #e0f2fe;
            --accent-color: #0284c7;
        }

        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg-canvas); color: var(--text-dark); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; }

        /* SIDEBAR BUKA-TUTUP */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 240px;
            background: var(--sidebar-bg); border-right: 1px solid var(--border-color);
            z-index: 1050; display: flex; flex-direction: column; padding: 24px 16px;
            transition: all 0.3s ease;
        }
        .sidebar.collapsed { left: -240px; }

        .brand { display: flex; align-items: center; gap: 10px; padding: 0 8px 20px 8px; border-bottom: 1px solid var(--border-color); margin-bottom: 12px; }
        .brand i { font-size: 22px; color: var(--primary-blue); }
        .brand-text { font-size: 13px; font-weight: 800; color: var(--text-dark); }
        
        .menu { flex: 1; overflow-y: auto; }
        .menu a {
            display: flex; align-items: center; gap: 12px; color: var(--text-muted);
            text-decoration: none; padding: 10px 14px; border-radius: 10px; margin-bottom: 4px;
            font-size: 13px; font-weight: 600; transition: all 0.2s;
        }
        .menu a:hover { background: var(--primary-blue-light); color: var(--primary-blue-dark); }
        .menu a.active { background: var(--primary-blue); color: #FFFFFF; }
        
        .logout-link { color: #DC3545; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px 14px; font-weight: 600; font-size: 13px; border-radius: 10px; }
        .logout-link:hover { background: #FFF0F0; }

        /* MAIN CONTENT & NAVBAR TOGGLE */
        .main-wrapper {
            margin-left: 240px; min-height: 100vh; padding: 24px 32px;
            transition: margin-left 0.3s ease;
        }
        .main-wrapper.expanded { margin-left: 0; }

        .top-navbar-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; background: #fff; padding: 14px 24px;
            border-radius: 14px; border: 1px solid var(--border-color);
        }
        .toggle-btn {
            background: #f1f5f9; border: 1px solid var(--border-color); color: var(--primary-blue);
            padding: 8px 12px; border-radius: 10px; font-size: 16px; cursor: pointer;
        }

        .custom-card {
            background: var(--card-bg); border: 1px solid var(--border-color);
            border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        .table { font-size: 13px; }
        .table th { background-color: var(--primary-blue) !important; color: #fff !important; font-weight: 600; padding: 12px; border: none; }
        .table td { padding: 12px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        
        .form-control { font-size: 13px; padding: 10px 14px; border-radius: 10px; border-color: var(--border-color); }
        .form-control:focus { border-color: var(--primary-blue); box-shadow: none; }
    </style>
</head>
<body>

    <!-- SIDEBAR ADMIN -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-shield-lock-fill text-primary"></i>
            <span class="brand-text">SARPRAS <span class="text-info">ADMIN</span></span>
        </div>
        <div class="menu">
            <a href="index.php"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard & Booking</span></a>
            <a href="kategori.php" class="active"><i class="bi bi-tags-fill"></i><span>Kategori Sarpras</span></a>
            <a href="sarpras.php"><i class="bi bi-boxes"></i><span>Kelola Sarpras</span></a>
            <a href="user.php"><i class="bi bi-people-fill"></i><span>Kelola User</span></a>
            <a href="peminjaman.php"><i class="bi bi-file-earmark-text-fill"></i><span>Peminjaman</span></a>
            <a href="log.php"><i class="bi bi-journal-text"></i><span>Log Peminjaman</span></a>
        </div>
        <div class="p-2 border-top">
            <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i><span>Logout Sistem</span></a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper" id="mainWrapper">
        <header class="top-navbar-bar">
            <button class="toggle-btn" id="sidebarToggle" title="Buka/Tutup Menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                    <i class="bi bi-person-circle me-1"></i> <?= $_SESSION['nama']; ?> (Admin)
                </span>
            </div>
        </header>
            <div class="custom-card mb-4">
                <form action="" method="POST" class="row g-3 align-items-center">
                    <div class="col-md-9">
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Masukkan Nama Kategori Baru (Cth: Elektronik, Ruangan)..." required autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="tambah" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm" style="background: var(--primary-blue); border: none;">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                        </button>
                    </div>
                </form>
            </div>

            <div class="custom-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 80px;">No</th>
                                <th>Nama Kategori</th>
                                <th class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; $q=mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id DESC"); while($k=mysqli_fetch_assoc($q)): ?>
                            <tr>
                                <td class="text-muted"><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($k['nama_kategori']); ?></strong></td>
                                <td class="text-end">
                                    <a href="kategori.php?hapus=<?= $k['id']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold" onclick="return confirm('Hapus kategori ini?')">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('mainWrapper');
        const toggleBtn = document.getElementById('sidebarToggle');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainWrapper.classList.toggle('expanded');
        });
    </script>
</body>
</html>