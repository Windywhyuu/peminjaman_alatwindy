<?php
session_start();
include '../koneksi.php';

// Validasi hak akses Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php?pesan=Akses ditolak!");
    exit;
}

$items = $_POST['items'] ?? [];

// Jika halaman diakses langsung tanpa kirim data items, coba ambil dari session/POST jika ada, atau kembalikan ke index
if (empty($items) && $_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<script>alert('Pilih minimal 1 barang atau ruangan terlebih dahulu!'); window.location='index.php';</script>";
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
    
    // Jam 24 jam (Jika kosong diset NULL)
    $jam_pinjam = !empty($_POST['jam_pinjam']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['jam_pinjam']) . "'" : "NULL";
    $jam_kembali = !empty($_POST['jam_kembali']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['jam_kembali']) . "'" : "NULL";
    
    $catatan_petugas = mysqli_real_escape_string($koneksi, $_POST['catatan_petugas']);
    $status = 'Menunggu'; // Status default saat admin/petugas membuat booking

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

$query_users = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'peminjam' ORDER BY nama ASC");

// Ambil daftar peminjaman aktif untuk ditampilkan di panel status
$query_peminjaman_aktif = mysqli_query($koneksi, "
    SELECT p.tgl_pinjam, p.tgl_kembali, p.jam_pinjam, p.jam_kembali, u.nama AS nama_user, s.nama_sarpras 
    FROM peminjaman p 
    JOIN users u ON p.id_user = u.id
    JOIN detail_peminjaman d ON p.id = d.id_peminjaman 
    JOIN sarpras s ON d.id_sarpras = s.id
    WHERE p.status != 'Ditolak' AND p.status != 'Selesai'
    ORDER BY p.tgl_pinjam ASC
");
$jadwal_sibuk = [];
if ($query_peminjaman_aktif) {
    while ($row = mysqli_fetch_assoc($query_peminjaman_aktif)) {
        $jadwal_sibuk[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Booking Sarpras</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; color: #334155; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; padding: 40px 0; }
        .container-wrapper { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; background: #ffffff; padding: 20px 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .card-panel { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); margin-bottom: 20px; }
        .form-label { font-size: 12.5px; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-control, .form-select { font-size: 13.5px; padding: 10px 14px; border-radius: 8px; border-color: #cbd5e1; }
        .form-control:focus, .form-select:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        .section-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
        .booking-item-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 10px; }
        .badge-booked { background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px; }
        .btn-primary-custom { background-color: #0d253f; border-color: #0d253f; color: #ffffff; font-weight: 600; padding: 10px 24px; border-radius: 8px; }
        .btn-primary-custom:hover { background-color: #071628; border-color: #071628; }
        .selected-item-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<div class="container-wrapper">
    
    <div class="page-header">
        <div>
            <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-calendar-plus text-primary me-2"></i>Formulir Atur Jadwal & Booking Sarpras</h5>
            <span class="text-muted" style="font-size: 13px;">Lengkapi data peminjaman dan periksa ketersediaan jadwal.</span>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm px-3 fw-semibold rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
        </a>
    </div>

    <div class="row g-4">
        <!-- FORMULIR UTAMA -->
        <div class="col-lg-7">
            <div class="card-panel">
                <form action="" method="POST" id="formBookingAction">                    
                    <!-- Teruskan data items terpilih secara tersembunyi -->
                    <?php foreach ($items as $idx => $it) : ?>
                        <input type="hidden" name="items[<?php echo $idx; ?>][id]" value="<?php echo intval($it['id']); ?>">
                        <input type="hidden" name="items[<?php echo $idx; ?>][jumlah]" value="<?php echo intval($it['jumlah']); ?>">
                    <?php endforeach; ?>

                    <div class="section-title">
                        <i class="bi bi-box-seam me-1 text-primary"></i> Barang / Ruangan yang Dipilih
                    </div>
                    
                    <div class="mb-4" style="max-height: 160px; overflow-y: auto;">
                        <?php 
                        foreach ($items as $it) {
                            $id_s = intval($it['id']);
                            $jml = intval($it['jumlah']);
                            $q_sr = mysqli_query($koneksi, "SELECT nama_sarpras FROM sarpras WHERE id = $id_s");
                            $d_sr = mysqli_fetch_assoc($q_sr);
                            $nama_barang = $d_sr['nama_sarpras'] ?? 'Barang tidak ditemukan';
                        ?>
                            <div class="selected-item-box">
                                <span class="fw-bold text-dark" style="font-size: 13px;"><?php echo htmlspecialchars($nama_barang); ?></span>
                                <span class="badge bg-secondary">Jumlah: <?php echo $jml; ?></span>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="section-title">
                        <i class="bi bi-person-fill me-1 text-primary"></i> 1. Informasi Pemohon & Kegiatan
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Akun Pemohon (Kelas / Guru)</label>
                        <select name="id_user" class="form-select" required>
                            <option value="">-- Pilih Akun Pemohon --</option>
                            <?php while ($u = mysqli_fetch_assoc($query_users)) : ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nama']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Penanggung Jawab Lapangan</label>
                        <input type="text" name="nama_peminjam_detail" class="form-control" placeholder="Cth: Ahmad Dani (Ketua Kelas)" required autocomplete="off">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Keperluan / Nama Kegiatan</label>
                        <input type="text" name="keperluan" class="form-control" placeholder="Cth: Praktikum Jaringan / Acara Pentas Seni" required autocomplete="off">
                    </div>

                    <div class="section-title">
                        <i class="bi bi-clock-fill me-1 text-primary"></i> 2. Pengaturan Tanggal & Waktu (Fleksibel 24 Jam)
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis Durasi Peminjaman</label>
                        <select name="jenis_durasi" class="form-select" required>
                            <option value="Jam">Per Jam</option>
                            <option value="Hari">Hari (Full Day)</option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tgl_pinjam" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tgl_kembali" class="form-control" required>
                        </div>
                    </div>

                    <!-- PENGATURAN JAM BEBAS 24 JAM -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Jam Mulai (Opsional)</label>
                            <input type="time" name="jam_pinjam" class="form-control" step="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Selesai (Opsional)</label>
                            <input type="time" name="jam_kembali" class="form-control" step="1">
                        </div>
                        <div class="col-12 mt-1">
                            <small class="text-muted" style="font-size: 11.5px;"><i class="bi bi-info-circle me-1"></i>Jam mendukung rentang 24 jam penuh. Kosongkan jika peminjaman seharian penuh.</small>
                        </div>
                    </div>

                    <!-- CATATAN PETUGAS -->
                    <div class="mb-4">
                        <label class="form-label">Catatan Petugas (Opsional)</label>
                        <textarea name="catatan_petugas" class="form-control" rows="2" placeholder="Tambahan catatan khusus dari petugas..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="index.php" class="btn btn-light border px-4 fw-semibold text-secondary">Batal</a>
                        <button type="submit" name="simpan_booking" class="btn btn-primary-custom">Simpan & Ajukan Booking</button>
                    </div>

                </form>
            </div>
        </div>

        <!-- DAFTAR JADWAL TERISI -->
        <div class="col-lg-5">
            <div class="card-panel">
                <div class="section-title d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-check me-1 text-primary"></i> Jadwal & Tanggal Terisi</span>
                    <span class="badge bg-secondary"><?php echo count($jadwal_sibuk); ?> Aktif</span>
                </div>
                <p class="text-muted" style="font-size: 12.5px;">Daftar agenda peminjaman aktif yang sudah tercatat di sistem:</p>
                <div style="max-height: 480px; overflow-y: auto;" class="pe-1">
                    <?php if (count($jadwal_sibuk) > 0) : ?>
                        <?php foreach ($jadwal_sibuk as $js) : ?>
                            <div class="booking-item-card">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="fw-bold text-dark" style="font-size: 12.5px;">
                                        <i class="bi bi-box-seam me-1 text-secondary"></i> <?= htmlspecialchars($js['nama_sarpras']); ?>
                                    </span>
                                    <span class="badge-booked">Terisi</span>
                                </div>
                                <div class="text-muted mb-1" style="font-size: 11.5px;">
                                    <i class="bi bi-calendar-event me-1"></i> <b><?= date('d M Y', strtotime($js['tgl_pinjam'])); ?></b> s/d <b><?= date('d M Y', strtotime($js['tgl_kembali'])); ?></b>
                                </div>
                                <div class="text-muted d-flex justify-content-between" style="font-size: 11px;">
                                    <span><i class="bi bi-clock me-1"></i> Pukul: <?= !empty($js['jam_pinjam']) ? date('H:i', strtotime($js['jam_pinjam'])) . ' - ' . date('H:i', strtotime($js['jam_kembali'])) : 'Full Day'; ?></span>
                                    <span class="text-primary fw-semibold"><?= htmlspecialchars($js['nama_user']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="text-center py-5 text-muted" style="font-size: 13px;">
                            <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
                            Belum ada jadwal peminjaman tercatat.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>