<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas Sistem - Admin Sarpras</title>
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
        }

        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg-canvas); color: var(--text-dark); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; }

        /* SIDEBAR */
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

        /* MAIN CONTENT & NAVBAR */
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

        /* CSS KHUSUS CETAK LAPORAN (Menyembunyikan sidebar, navbar, dan tombol cetak saat diprint) */
        @media print {
            .sidebar, .top-navbar-bar, .page-header-desc, .btn-print-area { display: none !important; }
            .main-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .custom-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
            body { background: #fff !important; }
        }
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
            <a href="kategori.php"><i class="bi bi-tags-fill"></i><span>Kategori Sarpras</span></a>
            <a href="sarpras.php"><i class="bi bi-boxes"></i><span>Kelola Sarpras</span></a>
            <a href="user.php"><i class="bi bi-people-fill"></i><span>Kelola User</span></a>
            <a href="peminjaman.php"><i class="bi bi-file-earmark-text-fill"></i><span>Peminjaman</span></a>
            <a href="log.php" class="active"><i class="bi bi-journal-text"></i><span>Log Aktivitas</span></a>
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
            <div class="d-flex align-items-center gap-2">
                <button onclick="window.print()" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold btn-print-area shadow-sm">
                    <i class="bi bi-printer me-1"></i> Cetak Log
                </button>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['nama']); ?> (Admin)
                </span>
            </div>
        </header>

            <div class="custom-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 70px;">No</th>
                                <th style="width: 180px;">Waktu (Tanggal & Jam)</th>
                                <th style="width: 200px;">Nama Pengguna</th>
                                <th>Aktivitas yang Dilakukan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            $query = mysqli_query($koneksi, "SELECT * FROM log_aktivitas ORDER BY id DESC");
                            if ($query && mysqli_num_rows($query) > 0) {
                                while($row = mysqli_fetch_assoc($query)): 
                                    $aktivitas_teks = strtolower($row['aktivitas']);
                                    $is_login = strpos($aktivitas_teks, 'login') !== false;
                                    $is_logout = strpos($aktivitas_teks, 'logout') !== false;
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++; ?></td>
                                <td>
                                    <span class="fw-semibold text-dark"><i class="bi bi-clock me-1 text-muted"></i> <?= $row['waktu']; ?></span>
                                </td>
                                <td><strong><?= htmlspecialchars($row['username']); ?></strong></td>
                                <td>
                                    <?php if ($is_login): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-box-arrow-in-right me-1"></i> <?= htmlspecialchars($row['aktivitas']); ?></span>
                                    <?php elseif ($is_logout): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1"><i class="bi bi-box-arrow-right me-1"></i> <?= htmlspecialchars($row['aktivitas']); ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary"><?= htmlspecialchars($row['aktivitas']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                                echo '<tr><td colspan="4" class="text-center text-muted py-4">Belum ada catatan log aktivitas sistem.</td></tr>';
                            }
                            ?>
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