<?php
session_start();
include '../koneksi.php';

/* =========================
   CEK LOGIN (PETUGAS / ADMIN)
========================= */
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'petugas' && $_SESSION['role'] != 'admin')) {
    header("Location: ../login.php?pesan=Akses ditolak!");
    exit;
}

// Ambil filter tanggal dari input form (default bulan berjalan)
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-t');

// Query untuk mengambil riwayat peminjaman / keluar masuk barang berdasarkan rentang tanggal
$query_laporan = mysqli_query($koneksi, "
    SELECT peminjaman.*, users.nama AS nama_user 
    FROM peminjaman 
    LEFT JOIN users ON peminjaman.id_user = users.id 
    WHERE (peminjaman.tgl_pinjam BETWEEN '$tgl_mulai' AND '$tgl_selesai')
    ORDER BY peminjaman.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keluar Masuk Barang - Petugas & Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #f4f7fa; --card-bg: #FFFFFF; --text-dark: #1e293b; --text-muted: #64748b;
            --border-color: #e2e8f0; --sidebar-bg: #FFFFFF; --primary-blue: #0d253f; --primary-blue-dark: #071628; --primary-blue-light: #e0f2fe;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg-canvas); color: var(--text-dark); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; }
        
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 240px; background: var(--sidebar-bg); border-right: 1px solid var(--border-color);
            z-index: 1050; display: flex; flex-direction: column; padding: 24px 16px; transition: all 0.3s ease;
        }
        .sidebar.collapsed { left: -240px; }
        .brand { display: flex; align-items: center; gap: 10px; padding: 0 8px 20px 8px; border-bottom: 1px solid var(--border-color); margin-bottom: 12px; }
        .brand i { font-size: 22px; color: var(--primary-blue); }
        .brand-text { font-size: 13px; font-weight: 800; color: var(--text-dark); }
        .menu { flex: 1; overflow-y: auto; }
        .menu a {
            display: flex; align-items: center; gap: 12px; color: var(--text-muted); text-decoration: none; padding: 10px 14px;
            border-radius: 10px; margin-bottom: 4px; font-size: 13px; font-weight: 600; transition: all 0.2s;
        }
        .menu a:hover { background: var(--primary-blue-light); color: var(--primary-blue-dark); }
        .menu a.active { background: var(--primary-blue); color: #FFFFFF; }
        .logout-link { color: #DC3545; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px 14px; font-weight: 600; font-size: 13px; border-radius: 10px; }
        .logout-link:hover { background: #FFF0F0; }
        
        .main-wrapper { margin-left: 240px; min-height: 100vh; padding: 24px 32px 100px 32px; transition: margin-left 0.3s ease; }
        .main-wrapper.expanded { margin-left: 0; }
        
        .top-navbar-bar {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; background: #fff; padding: 14px 24px;
            border-radius: 14px; border: 1px solid var(--border-color);
        }
        .toggle-btn { background: #f1f5f9; border: 1px solid var(--border-color); color: var(--primary-blue); padding: 8px 12px; border-radius: 10px; font-size: 16px; cursor: pointer; }
        
        .custom-card {
            background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }
        .table { font-size: 13px; }
        .table th { background-color: var(--primary-blue) !important; color: #fff !important; font-weight: 600; padding: 12px; border: none; }
        .table td { padding: 12px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        
        .filter-box { background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        
        /* KOP SURAT / JUDUL SAAT DICETAK */
        .print-header { display: none; }

        @media print {
            .sidebar, .top-navbar-bar, .filter-box, .btn-print-area, .page-desc { display: none !important; }
            .main-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .custom-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <i class="bi bi-buildings-fill text-primary"></i>
        <span class="brand-text">SARPRAS <span class="text-info"><?= strtoupper($_SESSION['role']); ?></span></span>
    </div>
    <div class="menu">
        <a href="index.php"><i class="bi bi-journal-plus"></i><span>Dashboard Booking</span></a>
        <a href="peminjaman.php"><i class="bi bi-file-earmark-text-fill"></i><span>Peminjaman</span></a>
        <a href="laporan.php" class="active"><i class="bi bi-file-earmark-bar-graph-fill"></i><span>Laporan Barang</span></a>
        <a href="akun.php"><i class="bi bi-people-fill"></i><span>Cek Akun</span></a>
    </div>
    <div class="p-2 border-top">
        <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
    </div>
</aside>

<div class="main-wrapper" id="mainWrapper">
    <header class="top-navbar-bar">
        <button class="toggle-btn" id="sidebarToggle" title="Buka/Tutup Menu"><i class="bi bi-list"></i></button>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold btn-print-area shadow-sm">
                <i class="bi bi-printer me-1"></i> Cetak Laporan
            </button>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'Petugas'); ?> (<?= ucfirst($_SESSION['role']); ?>)
            </span>
        </div>
    </header>

    <div class="container-fluid px-0">
        <!-- Header Khusus Print -->
        <div class="print-header">
            <h3>LAPORAN KELUAR MASUK BARANG SARPRAS</h3>
            <p class="mb-0">Periode Tanggal: <strong><?= date('d-m-Y', strtotime($tgl_mulai)); ?></strong> s/d <strong><?= date('d-m-Y', strtotime($tgl_selesai)); ?></strong></p>
            <hr>
        </div>

        <div class="mb-4">
            <h2 class="fw-bold text-dark mb-1" style="font-size: 18px;">Laporan Keluar Masuk Barang</h2>
            <p class="text-muted small mb-0 page-desc">Pantau rekam jejak aktivitas sirkulasi barang keluar dan masuk berdasarkan rentang tanggal.</p>
        </div>

        <!-- FORM FILTER BERDASARKAN TANGGAL -->
        <div class="filter-box">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= $tgl_mulai; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= $tgl_selesai; ?>" required>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3 py-2 rounded-pill flex-grow-1"><i class="bi bi-filter me-1"></i> Terapkan Filter</button>
                    <a href="laporan.php" class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-pill" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </form>
        </div>

        <!-- TABEL LAPORAN -->
        <div class="custom-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Tanggal Pinjam</th>
                            <th>Pemohon</th>
                            <th>Penanggung Jawab</th>
                            <th>Barang / Unit Keluar</th>
                            <th>Tgl Kembali Asli</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($query_laporan && mysqli_num_rows($query_laporan) > 0) {
                            while($row = mysqli_fetch_assoc($query_laporan)):
                                $id_pinjam = $row['id'];
                                
                                // Ambil detail barang yang dikeluarkan
                                $q_detail = mysqli_query($koneksi, "
                                    SELECT detail_peminjaman.*, sarpras.nama_sarpras 
                                    FROM detail_peminjaman 
                                    JOIN sarpras ON detail_peminjaman.id_sarpras = sarpras.id 
                                    WHERE detail_peminjaman.id_peminjaman = '$id_pinjam'
                                ");
                                $list_barang = [];
                                while($d = mysqli_fetch_assoc($q_detail)) {
                                    $list_barang[] = htmlspecialchars($d['nama_sarpras']) . ' (' . $d['jumlah'] . ' Unit)';
                                }
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['tgl_pinjam']); ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_user'] ?? 'User Dihapus'); ?></strong></td>
                            <td><?= htmlspecialchars($row['nama_peminjam_detail']); ?></td>
                            <td><?= !empty($list_barang) ? implode(', ', $list_barang) : '-'; ?></td>
                            <td>
                                <?= !empty($row['tgl_dikembalikan_asli']) ? htmlspecialchars($row['tgl_dikembalikan_asli']) : '<span class="text-danger fw-semibold">Belum Kembali</span>'; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary px-2 py-1"><?= htmlspecialchars($row['status']); ?></span>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo '<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data laporan pada rentang tanggal tersebut.</td></tr>';
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
document.getElementById('sidebarToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainWrapper').classList.toggle('expanded');
});
</script>
</body>
</html>