<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: ../login.php"); exit; }

// Ambil parameter filter dari input form
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_bulan  = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$filter_tahun  = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$keyword       = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';

// Susun kondisi query dinamis berdasarkan filter
$where_arr = ["1=1"];
if ($filter_status !== '') {
    if ($filter_status === 'Belum Kembali') {
        $where_arr[] = "(peminjaman.status = 'Dipinjam' OR peminjaman.status = 'Diterima' OR peminjaman.status = 'Disetujui')";
    } else {
        $where_arr[] = "peminjaman.status = '$filter_status'";
    }
}
if (!empty($filter_bulan)) {
    $where_arr[] = "MONTH(peminjaman.tgl_pinjam) = '$filter_bulan'";
}
if (!empty($filter_tahun)) {
    $where_arr[] = "YEAR(peminjaman.tgl_pinjam) = '$filter_tahun'";
}
if (!empty($keyword)) {
    $where_arr[] = "(users.nama LIKE '%$keyword%' OR peminjaman.nama_peminjam_detail LIKE '%$keyword%' OR peminjaman.keperluan LIKE '%$keyword%')";
}
$where_sql = implode(" AND ", $where_arr);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Log Kontrol Peminjaman & Pengembalian - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        body { margin: 0; background: var(--bg-canvas); color: var(--text-dark); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; }

        /* SIDEBAR ADMIN */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 240px;
            background: var(--sidebar-bg); border-right: 1px solid var(--border-color);
            z-index: 1050; display: flex; flex-direction: column; padding: 24px 16px;
            transition: all 0.3s ease;
        }
        .sidebar.active { left: -240px; }

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
            margin-left: 240px; min-height: 100vh; padding: 32px;
            transition: margin-left 0.3s ease;
        }
        .main-wrapper.active { margin-left: 0; }

        .top-navbar-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; background: #fff; padding: 14px 24px;
            border-radius: 14px; border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02); gap: 15px;
        }
        .toggle-btn {
            background: #f1f5f9; border: 1px solid var(--border-color); color: var(--primary-blue);
            padding: 8px 12px; border-radius: 10px; font-size: 16px; cursor: pointer;
        }

        .custom-card {
            background: var(--card-bg); border: 1px solid var(--border-color);
            border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .table { font-size: 13px; margin-bottom: 0; }
        .table th { background-color: var(--primary-blue) !important; color: #fff !important; font-weight: 600; padding: 14px 12px; border: none; letter-spacing: 0.3px; }
        .table td { padding: 14px 12px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        
        .badge-status { padding: 5px 12px; font-weight: 700; border-radius: 50px; font-size: 11px; }
        .filter-box { background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }

        /* KHUSUS CETAK (PRINT) : Menyembunyikan sidebar, navbar, judul, dan filter */
        @media print {
            .sidebar, 
            .top-navbar-bar, 
            .filter-box, 
            .page-header-title {
                display: none !important;
            }
            .main-wrapper {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .custom-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            body {
                background: #ffffff !important;
            }
        }

        @media (max-width: 768px) {
            .sidebar { left: -240px; }
            .sidebar.active { left: 0; }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR KIRI -->
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
            <a href="log.php" class="active"><i class="bi bi-journal-text"></i><span>Log Peminjaman</span></a>
            <a href="log_sistem.php"><i class="bi bi-clock-history"></i><span>Log Aktivitas Sistem</span></a>
        </div>
        <div class="p-2 border-top">
            <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i><span>Logout Sistem</span></a>
        </div>
    </aside>

    <!-- KONTEN UTAMA -->
    <div class="main-wrapper" id="mainWrapper">
        <header class="top-navbar-bar">
            <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="d-flex align-items-center gap-2">
                <button onclick="window.print()" class="btn btn-info btn-sm rounded-pill px-3 fw-bold text-dark shadow-sm">
                    <i class="bi bi-printer me-1"></i> Cetak Laporan
                </button>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?>
                </span>
            </div>
        </header>

            <!-- FORM FILTER & PENCARIAN -->
            <div class="filter-box">
                <form method="GET" action="" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1 text-uppercase">Cari Peminjam / Keperluan</label>
                        <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($keyword); ?>" placeholder="Ketik nama...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1 text-uppercase">Status Barang</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- Semua Status --</option>
                            <option value="Selesai" <?= ($filter_status == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                            <option value="Belum Kembali" <?= ($filter_status == 'Belum Kembali') ? 'selected' : ''; ?>>Belum Kembali (Aktif)</option>
                            <option value="Ditolak" <?= ($filter_status == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1 text-uppercase">Bulan</label>
                        <select name="bulan" class="form-select form-select-sm">
                            <option value="">-- Semua Bulan --</option>
                            <?php 
                            $array_bulan = [
                                1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 
                                5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 
                                9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'
                            ];
                            foreach($array_bulan as $num => $nama_bln) {
                                $sel = ($filter_bulan == $num) ? 'selected' : '';
                                echo "<option value='$num' $sel>$nama_bln</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1 text-uppercase">Tahun</label>
                        <input type="number" name="tahun" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_tahun); ?>" placeholder="Tahun">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2 pt-3">
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-3 py-2 rounded-pill flex-grow-1"><i class="bi bi-filter me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
            
            <div class="custom-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="rounded-start">No</th>
                                <th>Akun Pemohon</th>
                                <th>Penanggung Jawab</th>
                                <th>Barang / Ruangan</th>
                                <th>Durasi & Tgl Pinjam</th>
                                <th>Waktu Kembali Asli</th>
                                <th>Petugas / Admin</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            $query = mysqli_query($koneksi, "
                                SELECT peminjaman.*, users.nama AS nama_user 
                                FROM peminjaman 
                                LEFT JOIN users ON peminjaman.id_user = users.id 
                                WHERE $where_sql 
                                ORDER BY peminjaman.id DESC
                            "); 
                            
                            if ($query && mysqli_num_rows($query) > 0) {
                                while($row = mysqli_fetch_assoc($query)): 
                                    $id_pinjam = $row['id'];
                                    
                                    // Ambil detail rincian barang
                                    $q_detail = mysqli_query($koneksi, "
                                        SELECT detail_peminjaman.*, sarpras.nama_sarpras 
                                        FROM detail_peminjaman 
                                        JOIN sarpras ON detail_peminjaman.id_sarpras = sarpras.id 
                                        WHERE detail_peminjaman.id_peminjaman = '$id_pinjam'
                                    ");
                                    $list_barang = [];
                                    while($d = mysqli_fetch_assoc($q_detail)) {
                                        $list_barang[] = '<span class="badge bg-light text-dark border me-1 mb-1">' . htmlspecialchars($d['nama_sarpras']) . ' (' . $d['jumlah'] . ' Unit)</span>';
                                    }

                                    // Menentukan nama petugas/admin yang menangani
                                    $petugas_nama = !empty($row['catatan_petugas']) ? htmlspecialchars($row['catatan_petugas']) : '<span class="text-muted fst-italic">Administrator</span>';
                            ?>
                            <tr>
                                <td class="text-muted fw-semibold"><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($row['nama_user'] ?? 'User Dihapus'); ?></strong></td>
                                <td><?= htmlspecialchars($row['nama_peminjam_detail']); ?></td>
                                <td><?= !empty($list_barang) ? implode('', $list_barang) : '<span class="text-muted">Tidak ada item</span>'; ?></td>
                                <td>
                                    <span class="badge bg-secondary text-white px-2 py-1 mb-1" style="font-size: 10px;"><?= htmlspecialchars($row['jenis_durasi']); ?></span><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['tgl_pinjam']); ?> s/d <?= htmlspecialchars($row['tgl_kembali']); ?></small>
                                </td>
                                <td>
                                    <small class="fw-semibold text-dark">
                                        <?= !empty($row['tgl_dikembalikan_asli']) ? htmlspecialchars($row['tgl_dikembalikan_asli']) : '<span class="text-danger fw-semibold">Belum Kembali</span>'; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-dark border border-info border-opacity-25 px-2 py-1 fw-semibold">
                                        <i class="bi bi-person-badge me-1 text-primary"></i> <?= $petugas_nama; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['status'] == 'Selesai' || $row['status'] == 'Dikembalikan'): ?>
                                        <span class="badge-status bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Selesai</span>
                                    <?php elseif($row['status'] == 'Dipinjam' || $row['status'] == 'Diterima' || $row['status'] == 'Disetujui'): ?>
                                        <span class="badge-status bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Sedang Dipinjam</span>
                                    <?php elseif($row['status'] == 'Ditolak'): ?>
                                        <span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge-status bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                                echo '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data log peminjaman yang cocok dengan filter.</td></tr>';
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
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mainWrapper').classList.toggle('active');
        });
    </script>
</body>
</html>