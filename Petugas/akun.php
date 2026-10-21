<?php
session_start();
include '../koneksi.php';

// Pastikan hanya petugas yang dapat mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php?pesan=Akses ditolak!");
    exit;
}

// Menangani pencarian
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$where_clause = "";
if (!empty($search)) {
    $where_clause = "WHERE nama LIKE '%$search%' OR username LIKE '%$search%' OR role LIKE '%$search%'";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Daftar Akun - Petugas Sarpras</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        }
        .brand { display: flex; align-items: center; gap: 10px; padding: 0 8px 20px 8px; border-bottom: 1px solid var(--border-color); margin-bottom: 12px; }
        .brand i { font-size: 22px; color: var(--primary-blue); }
        .brand-text { font-size: 13px; font-weight: 800; color: var(--text-dark); }
        .menu { flex: 1; }
        .menu a {
            display: flex; align-items: center; gap: 12px; color: var(--text-muted);
            text-decoration: none; padding: 10px 14px; border-radius: 10px; margin-bottom: 4px;
            font-size: 13px; font-weight: 600; transition: all 0.2s;
        }
        .menu a:hover { background: var(--primary-blue-light); color: var(--primary-blue-dark); }
        .menu a.active { background: var(--primary-blue); color: #FFFFFF; }
        .logout-link { color: #DC3545; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px 14px; font-weight: 600; font-size: 13px; border-radius: 10px; }
        .logout-link:hover { background: #FFF0F0; }

        /* MAIN CONTENT */
        .main-wrapper { margin-left: 240px; min-height: 100vh; padding: 32px; }

        .top-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; background: #fff; padding: 18px 24px;
            border-radius: 14px; border: 1px solid var(--border-color);
        }

        .custom-card {
            background: var(--card-bg); border: 1px solid var(--border-color);
            border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }

        .table { font-size: 13px; }
        .table th { background-color: var(--primary-blue) !important; color: #fff !important; font-weight: 600; padding: 12px; border: none; }
        .table td { padding: 12px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        
        .badge-role { padding: 6px 12px; font-weight: 600; border-radius: 50px; font-size: 11px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">
        <i class="bi bi-buildings-fill"></i>
        <span class="brand-text">SARPRAS SEKOLAH</span>
    </div>
    <div class="menu">
        <a href="index.php"><i class="bi bi-journal-plus"></i><span>Peminjaman / Booking</span></a>
        <a href="peminjaman.php"><i class="bi bi-file-earmark-text-fill"></i><span>Riwayat</span></a>
                <a href="laporan.php"><i class="bi bi-file-earmark-bar-graph-fill"></i><span>Laporan Barang</span></a>

        <a href="akun.php" class="active"><i class="bi bi-people-fill"></i><span>Cek Akun</span></a>
    </div>
    <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-left"></i><span>Keluar</span></a>
</aside>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
    
    <div class="top-header">
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size: 16px;">Daftar Akun Pengguna</h4>
            <p class="text-muted small mb-0">Menampilkan daftar akun yang terdaftar di dalam sistem.</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold"><i class="bi bi-printer me-1"></i> Cetak</button>
        </div>
    </div>

    <div class="custom-card">
        <!-- Form Pencarian -->
        <form action="" method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, username, atau role..." value="<?= htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit" style="background: var(--primary-blue); border: none;"><i class="bi bi-search"></i> Cari</button>
                    <?php if (!empty($search)): ?>
                        <a href="akun.php" class="btn btn-outline-secondary" title="Reset Pencarian"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Role / Hak Akses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    $query = mysqli_query($koneksi, "SELECT * FROM users $where_clause ORDER BY id DESC");
                    
                    if ($query && mysqli_num_rows($query) > 0) {
                        while($row = mysqli_fetch_assoc($query)): 
                    ?>
                    <tr>
                        <td class="text-muted"><?= $no++; ?></td>
                        <td><strong><?= htmlspecialchars($row['nama']); ?></strong></td>
                        <td><?= htmlspecialchars($row['username']); ?></td>
                        <td>
                            <?php if(strtolower($row['role']) == 'admin'): ?>
                                <span class="badge-role bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Admin</span>
                            <?php elseif(strtolower($row['role']) == 'petugas'): ?>
                                <span class="badge-role bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Petugas</span>
                            <?php else: ?>
                                <span class="badge-role bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><?= htmlspecialchars(ucfirst($row['role'])); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    } else {
                        echo '<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data akun yang ditemukan.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>