<?php
session_start();
include '../koneksi.php';

/* =========================
   CEK LOGIN PEMINJAM
========================= */
if (!isset($_SESSION['role'])) {
    header("Location: ../login.php?pesan=Silakan login terlebih dahulu!");
    exit;
}

$id_user = $_SESSION['id_user'] ?? $_SESSION['id'] ?? 0;
$nama_user = $_SESSION['nama'] ?? 'Peminjam';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman Sarpras - Peminjam</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #f4f7fa; --card-bg: #FFFFFF; --text-dark: #1e293b; --text-muted: #64748b;
            --border-color: #e2e8f0; --primary-blue: #0d253f; --primary-blue-dark: #071628; --primary-blue-light: #e0f2fe;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg-canvas); color: var(--text-dark); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; }
        
        .main-wrapper { max-width: 1000px; margin: 0 auto; min-height: 100vh; padding: 24px 24px 60px 24px; }
        
        .top-navbar-bar {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; background: #fff; padding: 14px 24px;
            border-radius: 14px; border: 1px solid var(--border-color); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .brand i { font-size: 22px; color: var(--primary-blue); }
        .brand-text { font-size: 14px; font-weight: 800; color: var(--text-dark); }
        
        .custom-card {
            background: var(--card-bg); border: 1px solid var(--border-color);
            border-radius: 16px; padding: 28px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        }
        .table { font-size: 13px; }
        .table th { background-color: var(--primary-blue) !important; color: #fff !important; font-weight: 600; padding: 12px; border: none; }
        .table td { padding: 12px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        
        .badge-status { padding: 6px 12px; font-weight: 700; border-radius: 50px; font-size: 11px; display: inline-block; }
        .logout-btn { color: #DC3545; text-decoration: none; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; transition: background 0.2s; }
        .logout-btn:hover { background: #FFF0F0; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <!-- TOP NAVBAR BAR -->
    <div class="top-navbar-bar">
        <a href="index.php" class="brand">
            <i class="bi bi-buildings-fill"></i>
            <span class="brand-text">SARPRAS <span class="text-info">SEKOLAH</span></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-plus-lg me-1"></i> Buat Booking Baru</a>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama_user); ?>
            </span>
            <a href="../logout.php" class="logout-btn" title="Keluar"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
        </div>
    </div>

    <div class="custom-card">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="font-size: 17px;"><i class="bi bi-clock-history me-2 text-primary"></i> Riwayat Pengajuan Peminjaman Sarpras</h4>
                <p class="text-muted small mb-0">Daftar status peminjaman barang atau ruangan yang telah Anda ajukan.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th width="40">No</th>
                        <th>Penanggung Jawab</th>
                        <th>Barang / Ruangan</th>
                        <th>Jadwal Peminjaman</th>
                        <th>Keperluan</th>
                        <th>Catatan Petugas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    // Ambil data peminjaman khusus user yang sedang login
                    $query_riwayat = mysqli_query($koneksi, "
                        SELECT * FROM peminjaman 
                        WHERE id_user = '$id_user' 
                        ORDER BY id DESC
                    ");
                    
                    if ($query_riwayat && mysqli_num_rows($query_riwayat) > 0) {
                        while($row = mysqli_fetch_assoc($query_riwayat)): 
                            $id_pinjam = $row['id'];
                            
                            // Ambil detail barang yang dipinjam pada transaksi ini
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
                    ?>
                    <tr>
                        <td class="text-muted fw-bold"><?= $no++; ?></td>
                        <td><strong><?= htmlspecialchars($row['nama_peminjam_detail']); ?></strong></td>
                        <td><?= !empty($list_barang) ? implode('', $list_barang) : '<span class="text-muted">Tidak ada item</span>'; ?></td>
                        <td>
                            <div class="fw-semibold small">
                                <?= date('d M Y', strtotime($row['tgl_pinjam'])); ?> s/d <?= date('d M Y', strtotime($row['tgl_kembali'])); ?>
                            </div>
                            <?php if(!empty($row['jam_pinjam']) && $row['jam_pinjam'] != '00:00:00'): ?>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($row['jam_pinjam'])); ?> - <?= date('H:i', strtotime($row['jam_kembali'])); ?> WIB</small>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-dark"><?= htmlspecialchars($row['keperluan']); ?></small></td>
                        <td>
                            <small class="text-muted">
                                <?= !empty($row['catatan_petugas']) ? '<i class="bi bi-chat-left-text me-1 text-primary"></i>' . htmlspecialchars($row['catatan_petugas']) : '-'; ?>
                            </small>
                        </td>
                        <td>
                            <?php if($row['status'] == 'Diterima'): ?>
                                <span class="badge-status bg-success bg-opacity-10 text-success border border-success border-opacity-25">Diterima</span>
                            <?php elseif($row['status'] == 'Dipinjam'): ?>
                                <span class="badge-status bg-info bg-opacity-10 text-info border border-info border-opacity-25">Dipinjam</span>
                            <?php elseif($row['status'] == 'Selesai'): ?>
                                <span class="badge-status bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Selesai</span>
                            <?php elseif($row['status'] == 'Ditolak'): ?>
                                <span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Ditolak</span>
                            <?php else: ?>
                                <span class="badge-status bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Menunggu Konfirmasi</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    } else {
                        echo '<tr><td colspan="7" class="text-center text-muted py-5">Belum ada riwayat pengajuan peminjaman.</td></tr>';
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