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

// Proses Aksi Status Peminjaman & Pengembalian Stok
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $aksi = $_GET['aksi'];

    if ($aksi == 'diterima') {
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Diterima' WHERE id = '$id'");
    } elseif ($aksi == 'dipinjam') {
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Dipinjam' WHERE id = '$id'");
    } elseif ($aksi == 'selesai') {
        // Cek status sebelumnya agar stok tidak ter-update ganda jika diklik berkali-kali
        $q_cek = mysqli_query($koneksi, "SELECT status FROM peminjaman WHERE id = '$id'");
        $d_cek = mysqli_fetch_assoc($q_cek);

        if ($d_cek && $d_cek['status'] != 'Selesai') {
            // Ambil detail barang untuk dikembalikan stoknya ke tabel sarpras
            $q_detail = mysqli_query($koneksi, "SELECT id_sarpras, jumlah FROM detail_peminjaman WHERE id_peminjaman = '$id'");
            while ($d = mysqli_fetch_assoc($q_detail)) {
                $id_sarpras = $d['id_sarpras'];
                $jumlah_pinjam = $d['jumlah'];
                mysqli_query($koneksi, "UPDATE sarpras SET stok = stok + $jumlah_pinjam WHERE id = '$id_sarpras'");
            }
        }

        $waktu_sekarang = date('Y-m-d H:i:s');
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Selesai', tgl_dikembalikan_asli = '$waktu_sekarang' WHERE id = '$id'");
    } elseif ($aksi == 'tolak') {
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Ditolak' WHERE id = '$id'");
    } elseif ($aksi == 'hapus') {
        mysqli_query($koneksi, "DELETE FROM detail_peminjaman WHERE id_peminjaman = '$id'");
        mysqli_query($koneksi, "DELETE FROM peminjaman WHERE id = '$id'");
    }
    header("Location: peminjaman.php");
    exit;
}

// Proses Edit Data Peminjaman Lengkap
if (isset($_POST['update_peminjaman'])) {
    $id_edit = intval($_POST['id_peminjaman']);
    $pj = mysqli_real_escape_string($koneksi, $_POST['nama_peminjam_detail']);
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $durasi = mysqli_real_escape_string($koneksi, $_POST['jenis_durasi']);
    $tgl_p = mysqli_real_escape_string($koneksi, $_POST['tgl_pinjam']);
    $jam_p = !empty($_POST['jam_pinjam']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['jam_pinjam']) . "'" : "NULL";
    $tgl_k = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
    $jam_k = !empty($_POST['jam_kembali']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['jam_kembali']) . "'" : "NULL";
    $keperluan = mysqli_real_escape_string($koneksi, $_POST['keperluan']);
    $catatan_petugas = mysqli_real_escape_string($koneksi, $_POST['catatan_petugas']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);

    $query_update = "UPDATE peminjaman SET 
        nama_peminjam_detail = '$pj', 
        nama_barang = '$nama_barang', 
        jenis_durasi = '$durasi', 
        tgl_pinjam = '$tgl_p', 
        jam_pinjam = $jam_p, 
        tgl_kembali = '$tgl_k', 
        jam_kembali = $jam_k, 
        keperluan = '$keperluan', 
        catatan_petugas = '$catatan_petugas', 
        status = '$status' 
        WHERE id = '$id_edit'";

    mysqli_query($koneksi, $query_update);
    header("Location: peminjaman.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjaman - Petugas Sarpras</title>

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
        body { margin: 0; background: var(--bg-canvas); color: var(--text-dark); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; }

        /* SIDEBAR */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 240px;
            background: var(--sidebar-bg); border-right: 1px solid var(--border-color);
            z-index: 1050; display: flex; flex-direction: column; padding: 24px 16px;
        }
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
        .table th { background-color: var(--primary-blue) !important; color: #fff !important; font-weight: 600; padding: 14px 12px; border: none; }
        .table td { padding: 14px 12px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        
        .badge-status { padding: 6px 12px; font-weight: 700; border-radius: 50px; font-size: 11px; display: inline-block; }
        .time-badge { background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; gap: 4px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">
        <i class="bi bi-buildings-fill text-primary"></i>
        <span class="brand-text">SARPRAS <span class="text-info">PETUGAS</span></span>
    </div>
    <div class="menu">
        <a href="index.php"><i class="bi bi-journal-plus"></i><span>Dashboard Booking</span></a>
        <a href="peminjaman.php" class="active"><i class="bi bi-file-earmark-text-fill"></i><span>Peminjaman</span></a>
        <a href="laporan.php"><i class="bi bi-file-earmark-bar-graph-fill"></i><span>Laporan Barang</span></a>
        <a href="akun.php"><i class="bi bi-people-fill"></i><span>Cek Akun</span></a>
    </div>
    <div class="p-2 border-top">
        <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
    
    <div class="top-header">
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-size: 16px;">Kelola Peminjaman Sarpras</h4>
            <p class="text-muted small mb-0">Pantau jadwal booking dan status sirkulasi barang sarpras.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold"><i class="bi bi-printer me-1"></i> Cetak</button>
            <a href="index.php" class="btn btn-primary btn-sm px-3 rounded-pill fw-bold" style="background: var(--primary-blue); border: none;"><i class="bi bi-plus-lg me-1"></i> Buat Booking Baru</a>
        </div>
    </div>

    <div class="custom-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th width="40">No</th>
                        <th>Penanggung Jawab</th>
                        <th>Nama Barang</th>
                        <th>Jadwal & Jam Peminjaman</th>
                        <th>Keperluan</th>
                        <th>Catatan Petugas</th>
                        <th>Status</th>
                        <th class="text-end" width="130">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    $query = mysqli_query($koneksi, "
                        SELECT peminjaman.*, users.nama AS nama_user 
                        FROM peminjaman 
                        LEFT JOIN users ON peminjaman.id_user = users.id 
                        ORDER BY peminjaman.id DESC
                    ");
                    
                    if ($query && mysqli_num_rows($query) > 0) {
                        while($row = mysqli_fetch_assoc($query)): 
                            $id_pinjam = $row['id'];
                            
                            // Logika Tampilan Nama Barang
                            $nama_barang_tampil = '';
                            if (!empty($row['nama_barang'])) {
                                $nama_barang_tampil = '<span class="fw-bold text-dark">' . htmlspecialchars($row['nama_barang']) . '</span>';
                            } else {
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
                                $nama_barang_tampil = !empty($list_barang) ? implode('', $list_barang) : '<span class="text-muted italic">- Tidak Ada Item -</span>';
                            }

                            // Format Tampilan Jam yang Jelas
                            $jam_formatted = '';
                            if (!empty($row['jam_pinjam']) && !empty($row['jam_kembali']) && $row['jam_pinjam'] != '00:00:00') {
                                $jam_p = date('H.i', strtotime($row['jam_pinjam']));
                                $jam_k = date('H.i', strtotime($row['jam_kembali']));
                                $jam_formatted = "<div class='mt-1'><span class='time-badge'><i class='bi bi-clock-history'></i> $jam_p - $jam_k WIB</span></div>";
                            } else {
                                $jam_formatted = "<div class='mt-1'><span class='badge bg-light text-muted border fw-normal'><i class='bi bi-calendar-check me-1'></i> Full Day</span></div>";
                            }
                    ?>
                    <tr>
                        <td class="text-muted fw-bold"><?= $no++; ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_peminjam_detail']); ?></div>
                            <small class="text-muted"><i class="bi bi-person-circle me-1"></i>Akun: <?= htmlspecialchars($row['nama_user'] ?? 'User Dihapus'); ?></small>
                        </td>
                        <td><?= $nama_barang_tampil; ?></td>
                        <td>
                            <div class="fw-semibold text-dark small">
                                <?= date('d M Y', strtotime($row['tgl_pinjam'])); ?>
                                <?php if($row['tgl_pinjam'] != $row['tgl_kembali']): ?>
                                    s/d <?= date('d M Y', strtotime($row['tgl_kembali'])); ?>
                                <?php endif; ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 ms-1" style="font-size: 10px;"><?= htmlspecialchars($row['jenis_durasi']); ?></span>
                            </div>
                            <?= $jam_formatted; ?>
                        </td>
                        <td><small class="text-dark"><?= htmlspecialchars($row['keperluan']); ?></small></td>
                        <td>
                            <small class="text-muted">
                                <?= !empty($row['catatan_petugas']) ? '<i class="bi bi-chat-left-text me-1 text-primary"></i>' . htmlspecialchars($row['catatan_petugas']) : '<span class="text-black-50">-</span>'; ?>
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
                                <span class="badge-status bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Menunggu</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <?php if($row['status'] == 'Menunggu' || empty($row['status'])): ?>
                                    <a href="peminjaman.php?aksi=diterima&id=<?= $row['id']; ?>" class="btn btn-success btn-sm px-2 py-1 rounded-2" title="Terima Booking"><i class="bi bi-check-lg"></i></a>
                                    <a href="peminjaman.php?aksi=tolak&id=<?= $row['id']; ?>" class="btn btn-danger btn-sm px-2 py-1 rounded-2" title="Tolak"><i class="bi bi-x-lg"></i></a>
                                <?php elseif($row['status'] == 'Diterima'): ?>
                                    <a href="peminjaman.php?aksi=dipinjam&id=<?= $row['id']; ?>" class="btn btn-info btn-sm px-2 py-1 rounded-2 text-white" title="Ubah ke Status Dipinjam"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php elseif($row['status'] == 'Dipinjam'): ?>
                                    <a href="peminjaman.php?aksi=selesai&id=<?= $row['id']; ?>" class="btn btn-primary btn-sm px-2 py-1 rounded-2" onclick="return confirm('Proses barang kembali dan pulihkan stok sarpras?')" title="Selesai (Kembalikan Barang)"><i class="bi bi-box-arrow-in-down-left"></i></a>
                                <?php endif; ?>
                                
                                <!-- Tombol Edit Modal -->
                                <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 rounded-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>" title="Edit Data">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="peminjaman.php?aksi=hapus&id=<?= $row['id']; ?>" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-2" onclick="return confirm('Hapus catatan riwayat ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>

                    <!-- MODAL EDIT PEMINJAMAN LENGKAP -->
                    <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow rounded-4">
                                <div class="modal-header bg-dark text-white px-4 py-3">
                                    <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Peminjaman #<?= $row['id']; ?></h6>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="" method="POST">
                                    <div class="modal-body p-4 text-start">
                                        <input type="hidden" name="id_peminjaman" value="<?= $row['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Penanggung Jawab</label>
                                            <input type="text" name="nama_peminjam_detail" class="form-control" value="<?= htmlspecialchars($row['nama_peminjam_detail']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Nama Barang</label>
                                            <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($row['nama_barang'] ?? ''); ?>" placeholder="Masukkan nama barang">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Jenis Durasi</label>
                                            <select name="jenis_durasi" class="form-select" required>
                                                <option value="Hari" <?= ($row['jenis_durasi'] == 'Hari') ? 'selected' : ''; ?>>Hari</option>
                                                <option value="Jam" <?= ($row['jenis_durasi'] == 'Jam') ? 'selected' : ''; ?>>Per Jam</option>
                                            </select>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted">Tanggal Pinjam</label>
                                                <input type="date" name="tgl_pinjam" class="form-control" value="<?= $row['tgl_pinjam']; ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted">Tanggal Kembali</label>
                                                <input type="date" name="tgl_kembali" class="form-control" value="<?= $row['tgl_kembali']; ?>" required>
                                            </div>
                                        </div>

                                        <!-- INPUT JAM PINJAM & KEMBALI -->
                                        <div class="row mb-3 bg-light p-3 rounded-3 border mx-0">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted"><i class="bi bi-clock me-1"></i>Jam Pinjam</label>
                                                <input type="time" name="jam_pinjam" class="form-control" value="<?= $row['jam_pinjam']; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted"><i class="bi bi-clock-fill me-1"></i>Jam Kembali</label>
                                                <input type="time" name="jam_kembali" class="form-control" value="<?= $row['jam_kembali']; ?>">
                                            </div>
                                            <small class="text-muted mt-2" style="font-size: 11px;"><i class="bi bi-info-circle me-1"></i>Kosongkan jika peminjaman bersifat seharian penuh (Full Day).</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Keperluan</label>
                                            <input type="text" name="keperluan" class="form-control" value="<?= htmlspecialchars($row['keperluan']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Catatan Petugas</label>
                                            <textarea name="catatan_petugas" class="form-control" rows="2" placeholder="Catatan internal atau kendala pengembalian..."><?= htmlspecialchars($row['catatan_petugas'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Status Peminjaman</label>
                                            <select name="status" class="form-select" required>
                                                <option value="Menunggu" <?= ($row['status'] == 'Menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                                <option value="Diterima" <?= ($row['status'] == 'Diterima') ? 'selected' : ''; ?>>Diterima</option>
                                                <option value="Dipinjam" <?= ($row['status'] == 'Dipinjam') ? 'selected' : ''; ?>>Dipinjam</option>
                                                <option value="Selesai" <?= ($row['status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                                                <option value="Ditolak" <?= ($row['status'] == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light px-4 py-3">
                                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="update_peminjaman" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php 
                        endwhile; 
                    } else {
                        echo '<tr><td colspan="8" class="text-center text-muted py-4">Belum ada riwayat peminjaman.</td></tr>';
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