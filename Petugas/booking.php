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

// ==========================================
// PROSES SIMPAN DATA KETIKA FORM DISUBMIT
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_booking'])) {
    $id_user = intval($_POST['id_user']);
    $nama_peminjam_detail = mysqli_real_escape_string($koneksi, $_POST['nama_peminjam_detail']);
    $keperluan = mysqli_real_escape_string($koneksi, $_POST['keperluan']);
    $jenis_durasi = mysqli_real_escape_string($koneksi, $_POST['jenis_durasi']);
    $tgl_pinjam = mysqli_real_escape_string($koneksi, $_POST['tgl_pinjam']);
    $tgl_kembali = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
    
    // Jam 24 jam fleksibel (Jika kosong diset NULL)
    $jam_pinjam = !empty($_POST['jam_pinjam']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['jam_pinjam']) . "'" : "NULL";
    $jam_kembali = !empty($_POST['jam_kembali']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['jam_kembali']) . "'" : "NULL";
    
    $catatan_petugas = mysqli_real_escape_string($koneksi, $_POST['catatan_petugas'] ?? '');
    $status = 'Menunggu'; // Status default awal

    // 1. Simpan ke tabel utama peminjaman
    $query_peminjaman = "INSERT INTO peminjaman (id_user, nama_peminjam_detail, jenis_durasi, tgl_pinjam, jam_pinjam, tgl_kembali, jam_kembali, keperluan, status, catatan_petugas) 
                         VALUES ('$id_user', '$nama_peminjam_detail', '$jenis_durasi', '$tgl_pinjam', $jam_pinjam, '$tgl_kembali', $jam_kembali, '$keperluan', '$status', '$catatan_petugas')";
    
    if (mysqli_query($koneksi, $query_peminjaman)) {
        $id_peminjaman_baru = mysqli_insert_id($koneksi);

        // 2. Simpan setiap item barang ke tabel detail_peminjaman & kurangi stok sarpras
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $it) {
                $id_sarpras = intval($it['id']);
                $jumlah = intval($it['jumlah']);

                // Masukkan ke detail peminjaman
                mysqli_query($koneksi, "INSERT INTO detail_peminjaman (id_peminjaman, id_sarpras, jumlah) VALUES ('$id_peminjaman_baru', '$id_sarpras', '$jumlah')");
                
                // Kurangi stok sarpras secara otomatis
                mysqli_query($koneksi, "UPDATE sarpras SET stok = stok - $jumlah WHERE id = '$id_sarpras'");
            }
        }

        echo "<script>alert('Peminjaman berhasil disimpan dan diajukan!'); window.location='peminjaman.php';</script>";
        exit;
    } else {
        $error_msg = mysqli_error($koneksi);
        echo "<script>alert('Gagal menyimpan peminjaman: $error_msg');</script>";
    }
}

// Periksa apakah ada item yang dikirim via POST dari katalog
$items_dipilih = $_POST['items'] ?? [];
if (empty($items_dipilih)) {
    header("Location: index.php");
    exit;
}

$query_users = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'peminjam' ORDER BY nama ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Booking Jadwal - Petugas</title>
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
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 15px; background: #fff; padding: 12px 20px;
            border-radius: 14px; border: 1px solid var(--border-color);
        }
        .toggle-btn { background: #f1f5f9; border: 1px solid var(--border-color); color: var(--primary-blue); padding: 8px 12px; border-radius: 10px; font-size: 16px; cursor: pointer; }
        .form-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); max-width: 750px; margin: 0 auto; }
        .selected-item-row { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; }
        .form-control, .form-select { font-size: 13px; padding: 9px 12px; border-radius: 10px; border-color: var(--border-color); }
        label { font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block; text-transform: uppercase; }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <i class="bi bi-buildings-fill text-primary"></i>
        <span class="brand-text">SARPRAS <span class="text-info">PETUGAS</span></span>
    </div>
    <div class="menu">
        <a href="index.php" class="active"><i class="bi bi-journal-plus"></i><span>Dashboard Booking</span></a>
        <a href="peminjaman.php"><i class="bi bi-file-earmark-text-fill"></i><span>Peminjaman</span></a>
        <a href="akun.php"><i class="bi bi-people-fill"></i><span>Cek Akun</span></a>
    </div>
    <div class="p-2 border-top">
        <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
    </div>
</aside>

<div class="main-wrapper" id="mainWrapper">
    <div class="top-navbar-bar">
        <button class="toggle-btn" id="sidebarToggle" title="Buka/Tutup Menu"><i class="bi bi-list"></i></button>
        <div class="d-flex align-items-center ms-auto">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'Petugas'); ?> (Petugas)
            </span>
        </div>
    </div>

    <div class="form-card">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
            <div>
                <h4 class="fw-bold text-dark mb-1" style="font-size: 18px;"><i class="bi bi-calendar-range text-primary me-2"></i>Atur Jadwal & Konfirmasi Booking</h4>
                <p class="text-muted small mb-0">Lengkapi data pemohon dan durasi peminjaman sarana prasarana.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        </div>

        <!-- FORM ACTION DIKOSONGKAN AGAR DIPROSES HALAMAN INI SENDIRI -->
        <form action="" method="POST">
            <!-- Hidden inputs untuk item keranjang -->
            <?php foreach ($items_dipilih as $index => $it): ?>
                <input type="hidden" name="items[<?= $index; ?>][id]" value="<?= intval($it['id']); ?>">
                <input type="hidden" name="items[<?= $index; ?>][jumlah]" value="<?= intval($it['jumlah']); ?>">
            <?php endforeach; ?>

            <div class="mb-3 text-muted fw-bold" style="font-size: 11px; text-transform: uppercase;"><i class="bi bi-person-badge me-1"></i> 1. Informasi Peminjam</div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>Akun Pemohon (Kelas/Guru)</label>
                    <select name="id_user" class="form-select" required>
                        <option value="">-- Pilih Akun --</option>
                        <?php while ($u = mysqli_fetch_assoc($query_users)) : ?>
                            <option value="<?= $u['id']; ?>"><?= htmlspecialchars($u['nama']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Penanggung Jawab</label>
                    <input type="text" name="nama_peminjam_detail" class="form-control" placeholder="Cth: Ahmad Dani (Ketua Kelas)" required autocomplete="off">
                </div>
            </div>

            <div class="mb-3 text-muted fw-bold mt-4" style="font-size: 11px; text-transform: uppercase;"><i class="bi bi-clock-history me-1"></i> 2. Pengaturan Tanggal & Waktu Booking (24 Jam)</div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tgl_pinjam" class="form-control mb-2" required value="<?= date('Y-m-d'); ?>">
                    <label class="small text-muted fw-normal">Jam Mulai (Opsional)</label>
                    <input type="time" name="jam_pinjam" class="form-control" step="1">
                </div>
                <div class="col-md-6">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="tgl_kembali" class="form-control mb-2" required>
                    <label class="small text-muted fw-normal">Jam Selesai (Opsional)</label>
                    <input type="time" name="jam_kembali" class="form-control" step="1">
                </div>
                <div class="col-12">
                    <small class="text-muted" style="font-size: 11.5px;"><i class="bi bi-info-circle me-1"></i>Kosongkan input jam jika peminjaman bersifat seharian penuh (*Full Day*).</small>
                </div>
            </div>

            <div class="row g-3 mb-3 mt-1">
                <div class="col-md-6">
                    <label>Jenis Durasi</label>
                    <select name="jenis_durasi" class="form-select" required>
                        <option value="Hari">Hari</option>
                        <option value="Jam">Per Jam</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Keperluan Kegiatan</label>
                    <input type="text" name="keperluan" class="form-control" placeholder="Cth: Acara Pentas Seni" required autocomplete="off">
                </div>
            </div>

            <div class="mb-3">
                <label>Catatan Petugas (Opsional)</label>
                <textarea name="catatan_petugas" class="form-control" rows="2" placeholder="Catatan internal atau keterangan tambahan..."></textarea>
            </div>

            <div class="mb-4 mt-4">
                <label class="mb-2"><i class="bi bi-box-seam me-1"></i> Ringkasan Barang/Ruangan yang Dipilih</label>
                <div style="max-height: 180px; overflow-y: auto;">
                    <?php 
                    foreach ($items_dipilih as $it) {
                        $id_sarpras = intval($it['id']);
                        $jml = intval($it['jumlah']);
                        $q_s = mysqli_query($koneksi, "SELECT nama_sarpras FROM sarpras WHERE id = '$id_sarpras'");
                        $s_data = mysqli_fetch_assoc($q_s);
                        $nama_brg = $s_data ? $s_data['nama_sarpras'] : 'Barang tidak ditemukan';
                    ?>
                        <div class="selected-item-row">
                            <span class="fw-bold text-dark"><?= htmlspecialchars($nama_brg); ?></span>
                            <span class="badge bg-primary px-3 py-1">Jumlah: <?= $jml; ?> unit</span>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="index.php" class="btn btn-secondary rounded-pill px-4">Batal</a>
                <button type="submit" name="simpan_booking" class="btn btn-primary rounded-pill px-5 fw-bold">Konfirmasi & Simpan Booking</button>
            </div>
        </form>
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