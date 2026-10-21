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

// ==========================================
// PROSES SIMPAN DATA KETIKA FORM DISUBMIT
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proses_booking'])) {
    $nama_peminjam_detail = mysqli_real_escape_string($koneksi, $_POST['nama_peminjam_detail']);
    $jenis_durasi = mysqli_real_escape_string($koneksi, $_POST['jenis_durasi']);
    $tgl_pinjam = mysqli_real_escape_string($koneksi, $_POST['tgl_pinjam']);
    $tgl_kembali = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
    
    // Jam 24 jam fleksibel (Jika kosong di-set NULL)
    $jam_pinjam = !empty($_POST['jam_pinjam']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['jam_pinjam']) . "'" : "NULL";
    $jam_kembali = !empty($_POST['jam_kembali']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['jam_kembali']) . "'" : "NULL";
    
    $keperluan = mysqli_real_escape_string($koneksi, $_POST['keperluan']);
    $status = 'Menunggu'; // Status awal konfirmasi petugas

    // Simpan ke tabel utama peminjaman
    $query_pinjam = mysqli_query($koneksi, "INSERT INTO peminjaman (id_user, nama_peminjam_detail, jenis_durasi, tgl_pinjam, jam_pinjam, tgl_kembali, jam_kembali, keperluan, status) 
                    VALUES ('$id_user', '$nama_peminjam_detail', '$jenis_durasi', '$tgl_pinjam', $jam_pinjam, '$tgl_kembali', $jam_kembali, '$keperluan', '$status')");
    
    if ($query_pinjam) {
        $id_peminjaman_baru = mysqli_insert_id($koneksi);
        
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $it) {
                $id_sarpras = intval($it['id']);
                $jumlah = intval($it['jumlah']);
                if ($jumlah > 0) {
                    // Masukkan ke detail peminjaman
                    mysqli_query($koneksi, "INSERT INTO detail_peminjaman (id_peminjaman, id_sarpras, jumlah) VALUES ('$id_peminjaman_baru', '$id_sarpras', '$jumlah')");
                    
                    // Kurangi stok sarpras secara otomatis
                    mysqli_query($koneksi, "UPDATE sarpras SET stok = stok - $jumlah WHERE id = '$id_sarpras'");
                }
            }
        }
        
        echo "<script>alert('Berhasil mengajukan peminjaman! Silakan tunggu konfirmasi petugas.'); window.location='riwayat.php';</script>";
        exit;
    } else {
        $pesan_gagal = "Gagal memproses peminjaman. Silakan coba lagi.";
    }
}

// Ambil item yang dikirim dari katalog via POST
$items_dipilih = $_POST['items'] ?? [];
if (empty($items_dipilih)) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Jadwal & Konfirmasi Booking - Peminjam</title>
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
        
        .main-wrapper { max-width: 900px; margin: 0 auto; min-height: 100vh; padding: 24px 24px 60px 24px; }
        
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
        .form-label { font-weight: 600; color: var(--text-dark); margin-bottom: 6px; font-size: 12.5px; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid var(--border-color); padding: 10px 14px; font-size: 13.5px; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(13, 37, 63, 0.1); }
        
        .summary-box { background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 24px; }
        .item-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
        .item-row:last-child { border-bottom: none; }
        .item-img { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color); }
        
        .btn-submit {
            background: var(--primary-blue); color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: 700;
            font-size: 14px; width: 100%; transition: background 0.2s; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: var(--primary-blue-dark); }
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
            <a href="riwayat.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-clock-history me-1"></i> Lihat Riwayat</a>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama_user); ?>
            </span>
            <a href="../logout.php" class="logout-btn" title="Keluar"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
        </div>
    </div>

    <?php if(!empty($pesan_gagal)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $pesan_gagal; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="custom-card">
        <h4 class="fw-bold mb-1" style="font-size: 17px;"><i class="bi bi-calendar-check me-2 text-primary"></i> Atur Jadwal & Penanggung Jawab Peminjaman</h4>
        <p class="text-muted small mb-4">Periksa kembali barang pilihan Anda dan lengkapi detail waktu serta keperluan peminjaman.</p>

        <!-- RINGKASAN BARANG YANG DIPILIH -->
        <div class="summary-box">
            <span class="fw-bold text-dark d-block mb-2" style="font-size: 13px;"><i class="bi bi-box-seam me-1"></i> Barang/Ruangan yang Dipilih:</span>
            <div>
                <?php 
                foreach ($items_dipilih as $index => $it):
                    $id_sarpras = intval($it['id']);
                    $jml = intval($it['jumlah']);
                    
                    $q_s = mysqli_query($koneksi, "SELECT * FROM sarpras WHERE id = '$id_sarpras'");
                    $data_s = mysqli_fetch_assoc($q_s);
                    if ($data_s):
                        $img = !empty($data_s['foto'] ?? $data_s['gambar']) ? '../uploads/' . htmlspecialchars($data_s['foto'] ?? $data_s['gambar']) : 'https://via.placeholder.com/45';
                ?>
                    <div class="item-row">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= $img; ?>" alt="Foto" class="item-img">
                            <div>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($data_s['nama_sarpras']); ?></span>
                                <div class="text-muted small">Jumlah: <strong><?= $jml; ?> Unit</strong></div>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- FORM INPUT DETAIL PEMINJAMAN -->
        <form action="" method="POST">
            <!-- Kirim ulang data item terpilih via hidden input -->
            <?php foreach ($items_dipilih as $index => $it): ?>
                <input type="hidden" name="items[<?= $index; ?>][id]" value="<?= $it['id']; ?>">
                <input type="hidden" name="items[<?= $index; ?>][jumlah]" value="<?= $it['jumlah']; ?>">
            <?php endforeach; ?>

            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label">Nama Penanggung Jawab / Nama Kegiatan</label>
                    <input type="text" name="nama_peminjam_detail" class="form-control" placeholder="Contoh: Ahmad Faliq (Ketua Kelas)" required autocomplete="off">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Jenis Durasi</label>
                    <select name="jenis_durasi" class="form-select" required>
                        <option value="Hari">Hari</option>
                        <option value="Jam">Per Jam</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tanggal Mulai Pinjam</label>
                    <input type="date" name="tgl_pinjam" class="form-control mb-2" value="<?= date('Y-m-d'); ?>" required>
                    <label class="form-label fw-normal text-muted">Jam Mulai (Opsional)</label>
                    <input type="time" name="jam_pinjam" class="form-control" step="1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Selesai / Kembali</label>
                    <input type="date" name="tgl_kembali" class="form-control mb-2" value="<?= date('Y-m-d'); ?>" required>
                    <label class="form-label fw-normal text-muted">Jam Selesai (Opsional)</label>
                    <input type="time" name="jam_kembali" class="form-control" step="1">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Keperluan / Keterangan Peminjaman</label>
                <input type="text" name="keperluan" class="form-control" placeholder="Contoh: Praktikum Pemrograman / Acara Sekolah" required autocomplete="off">
            </div>

            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold" style="font-size: 13.5px;"><i class="bi bi-arrow-left me-1"></i> Kembali Pilih Barang</a>
                <button type="submit" name="proses_booking" class="btn-submit flex-grow-1">
                    <i class="bi bi-send-fill"></i> Kirim Pengajuan Booking Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>