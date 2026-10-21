<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: ../login.php"); 
    exit; 
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'tampil';
$error = "";

/* =========================
   PROSES TAMBAH & EDIT SARPRAS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_sarpras'])) {
    $id_sarpras = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $id_kat     = intval($_POST['id_kategori']);
    $kode       = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $nama       = mysqli_real_escape_string($koneksi, $_POST['nama_sarpras']);
    $jenis      = mysqli_real_escape_string($koneksi, $_POST['jenis']);
    $stok       = intval($_POST['stok']);
    $kondisi    = mysqli_real_escape_string($koneksi, $_POST['kondisi']);

    $nama_baru  = $_POST['gambar_lama'] ?? '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $tmp_file  = $_FILES['gambar']['tmp_name'];
        $nama_file = uniqid() . '_' . preg_replace("/\s+/", "_", $_FILES['gambar']['name']);
        $tujuan    = '../uploads/' . $nama_file;
        
        if (move_uploaded_file($tmp_file, $tujuan)) {
            if (!empty($_POST['gambar_lama']) && file_exists('../uploads/' . $_POST['gambar_lama'])) {
                unlink('../uploads/' . $_POST['gambar_lama']);
            }
            $nama_baru = $nama_file;
        }
    }

    if ($id_sarpras > 0) {
        $query_update = "UPDATE sarpras SET 
            id_kategori = '$id_kat', 
            kode_barang = '$kode', 
            nama_sarpras = '$nama', 
            jenis = '$jenis', 
            stok = '$stok', 
            kondisi = '$kondisi', 
            gambar = '$nama_baru' 
            WHERE id = $id_sarpras";
        
        if (mysqli_query($koneksi, $query_update)) {
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Mengubah sarpras ID: $id_sarpras')");
            header("Location: sarpras.php?pesan=sukses_edit");
            exit;
        } else {
            $error = "Gagal memperbarui sarpras: " . mysqli_error($koneksi);
        }
    } else {
        $query_insert = "INSERT INTO sarpras (id_kategori, kode_barang, nama_sarpras, jenis, stok, kondisi, gambar) 
                         VALUES ('$id_kat', '$kode', '$nama', '$jenis', '$stok', '$kondisi', '$nama_baru')";
        
        if (mysqli_query($koneksi, $query_insert)) {
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Menambah sarpras: $nama')");
            header("Location: sarpras.php?pesan=sukses_tambah");
            exit;
        } else {
            $error = "Gagal menambah sarpras: " . mysqli_error($koneksi);
        }
    }
}

/* =========================
   PROSES HAPUS MASSAL
========================= */
if (isset($_POST['hapus_pilih']) && isset($_POST['id_pilih'])) {
    foreach ($_POST['id_pilih'] as $id_pilih) {
        $id_pilih = intval($id_pilih);
        $q_foto = mysqli_query($koneksi, "SELECT gambar FROM sarpras WHERE id = $id_pilih");
        $data_f = mysqli_fetch_assoc($q_foto);
        if ($data_f && !empty($data_f['gambar']) && file_exists("../uploads/" . $data_f['gambar'])) {
            unlink("../uploads/" . $data_f['gambar']);
        }
        mysqli_query($koneksi, "DELETE FROM sarpras WHERE id = $id_pilih");
    }
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Menghapus beberapa sarpras secara massal')");
    header("Location: sarpras.php?pesan=sukses_hapus_banyak");
    exit;
}

/* =========================
   PROSES EDIT MASSAL STOK
========================= */
if (isset($_POST['simpan_edit_pilih']) && isset($_POST['edit_data'])) {
    foreach ($_POST['edit_data'] as $id => $val) {
        $id   = intval($id);
        $stok = intval($val['stok']);
        mysqli_query($koneksi, "UPDATE sarpras SET stok = '$stok' WHERE id = $id");
    }
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Mengubah stok sarpras massal')");
    header("Location: sarpras.php?pesan=sukses_edit_banyak");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sarpras - Admin</title>
    
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
        body { margin: 0; background: var(--bg-canvas); color: var(--text-dark); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; overflow-x: hidden; }

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
            margin-left: 240px; min-height: 100vh; padding: 24px 32px 60px 32px;
            transition: margin-left 0.3s ease;
        }
        .main-wrapper.expanded { margin-left: 0; }

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

        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; background: var(--card-bg); padding: 18px 24px;
            border-radius: 14px; border: 1px solid var(--border-color);
        }

        .page-title {
            font-size: 16px; font-weight: 800; color: var(--text-dark); margin: 0;
            display: flex; align-items: center; gap: 10px;
        }

        .btn-custom {
            background: var(--primary-blue); color: #fff; border: none; padding: 8px 16px;
            border-radius: 8px; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 6px; text-decoration: none;
            transition: background 0.2s;
        }
        .btn-custom:hover { background: var(--primary-blue-dark); color: #fff; }

        /* KATEGORI FILTER PILLS */
        .category-pills {
            display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;
        }
        .pill-btn {
            background: #ffffff; border: 1px solid var(--border-color); color: var(--text-muted);
            padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; cursor: pointer;
            white-space: nowrap; transition: all 0.2s;
        }
        .pill-btn:hover, .pill-btn.active {
            background: var(--primary-blue); border-color: var(--primary-blue); color: #fff;
        }

        /* PRODUCT GRID STYLING */
        .product-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 18px;
        }

        .product-card {
            background: var(--card-bg); border: 1px solid var(--border-color);
            border-radius: 14px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;
            position: relative; display: flex; flex-direction: column; justify-content: space-between;
            padding: 12px;
        }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(13, 37, 63, 0.06); }

        .product-card-img-wrap {
            width: 100%; height: 140px; background: #f8f9fa; border-radius: 10px; position: relative; overflow: hidden;
            display: flex; align-items: center; justify-content: center; margin-bottom: 10px;
        }
        .product-card-img-wrap img { width: 100%; height: 100%; object-fit: cover; }

        .product-badge-number {
            position: absolute; top: 6px; left: 6px; background: rgba(13, 37, 63, 0.85);
            color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px; z-index: 5;
        }

        .product-card-body { display: flex; flex-direction: column; gap: 4px; flex-grow: 1; margin-bottom: 10px; }
        .product-card-title { font-weight: 700; font-size: 13.5px; color: var(--text-dark); margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 36px; line-height: 1.3; }
        
        .product-card-footer {
            padding-top: 10px; border-top: 1px solid var(--border-color);
            display: flex; justify-content: space-between; align-items: center; background: transparent;
        }

        /* Checkbox Seleksi Tersembunyi */
        .select-checkbox-wrap { position: absolute; top: 8px; right: 8px; z-index: 10; display: none; }
        .select-checkbox-wrap input { width: 18px; height: 18px; cursor: pointer; }
        .mode-select-active .select-checkbox-wrap { display: block; }
        
        .form-control, .form-select { font-size: 13px; padding: 8px 12px; border-radius: 8px; border-color: var(--border-color); }
        .form-control:focus, .form-select:focus { border-color: var(--primary-blue); box-shadow: none; }
        label { font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block; text-transform: uppercase; }
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
            <a href="sarpras.php" class="active"><i class="bi bi-boxes"></i><span>Kelola Sarpras</span></a>
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
            <button class="toggle-btn" id="sidebarToggle" title="Buka/Tutup Menu"><i class="bi bi-list"></i></button>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                    <i class="bi bi-person-circle me-1"></i> <?= $_SESSION['nama']; ?> (Admin)
                </span>
            </div>
        </header>

        <div class="container-fluid px-0">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['pesan'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        if ($_GET['pesan'] == 'sukses_tambah') echo "Data sarpras berhasil ditambahkan!";
                        elseif ($_GET['pesan'] == 'sukses_edit') echo "Data sarpras berhasil diperbarui!";
                        elseif ($_GET['pesan'] == 'sukses_hapus_banyak') echo "Item sarpras terpilih berhasil dihapus!";
                        elseif ($_GET['pesan'] == 'sukses_edit_banyak') echo "Perubahan stok sarpras terpilih berhasil disimpan!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- KONDISI KONTEN: FORM TAMBAH / EDIT ATAU GRID UTAMA -->
            <?php if ($aksi === 'tambah' || $aksi === 'edit'): ?>
                <?php 
                    $id_sarpras = ""; $id_kat = ""; $kode = ""; $nama = ""; $jenis = "Barang"; $stok = ""; $kondisi = "Baik"; $gambar = "";
                    if ($aksi === 'edit' && isset($_GET['id'])) {
                        $id_edit = intval($_GET['id']);
                        $q_isi = mysqli_query($koneksi, "SELECT * FROM sarpras WHERE id = $id_edit");
                        $d_isi = mysqli_fetch_assoc($q_isi);
                        if ($d_isi) {
                            $id_sarpras = $d_isi['id'];
                            $id_kat     = $d_isi['id_kategori'];
                            $kode       = $d_isi['kode_barang'];
                            $nama       = $d_isi['nama_sarpras'];
                            $jenis      = $d_isi['jenis'];
                            $stok       = $d_isi['stok'];
                            $kondisi    = $d_isi['kondisi'];
                            $gambar     = $d_isi['gambar'];
                        }
                    }
                ?>
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="bi bi-<?= $aksi === 'edit' ? 'pencil-square' : 'plus-circle-fill'; ?>"></i> 
                        <?= $aksi === 'edit' ? 'Edit Data Sarpras' : 'Tambah Sarpras Baru'; ?>
                    </h1>
                    <a href="sarpras.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                </div>

                <div class="custom-card">
                    <form action="sarpras.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $id_sarpras; ?>">
                        <input type="hidden" name="gambar_lama" value="<?= $gambar; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Kode Barang</label>
                                <input type="text" class="form-control" name="kode_barang" value="<?= htmlspecialchars($kode); ?>" placeholder="Contoh: INV-001" required autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Nama Sarpras</label>
                                <input type="text" class="form-control" name="nama_sarpras" value="<?= htmlspecialchars($nama); ?>" placeholder="Nama Barang / Ruangan" required autocomplete="off">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Kategori</label>
                                <select name="id_kategori" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php 
                                    $kat = mysqli_query($koneksi, "SELECT * FROM kategori"); 
                                    while($k = mysqli_fetch_assoc($kat)) {
                                        $sel = ($k['id'] == $id_kat) ? 'selected' : '';
                                        echo "<option value='".$k['id']."' $sel>".$k['nama_kategori']."</option>"; 
                                    } 
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Jenis Unit</label>
                                <select name="jenis" class="form-select" required>
                                    <option value="Barang" <?= ($jenis=='Barang')?'selected':''; ?>>Barang</option>
                                    <option value="Ruangan" <?= ($jenis=='Ruangan')?'selected':''; ?>>Ruangan</option>
                                    <option value="Kendaraan" <?= ($jenis=='Kendaraan')?'selected':''; ?>>Kendaraan</option>
                                    <option value="Lainnya" <?= ($jenis=='Lainnya')?'selected':''; ?>>Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Stok / Jumlah</label>
                                <input type="number" class="form-control" name="stok" value="<?= $stok; ?>" placeholder="Jumlah Unit" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Kondisi</label>
                                <select name="kondisi" class="form-select" required>
                                    <option value="Baik" <?= ($kondisi=='Baik')?'selected':''; ?>>Baik</option>
                                    <option value="Perbaikan" <?= ($kondisi=='Perbaikan')?'selected':''; ?>>Perbaikan</option>
                                    <option value="Ditutup" <?= ($kondisi=='Ditutup')?'selected':''; ?>>Ditutup</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Foto Sarpras (Opsional)</label>
                                <?php if (!empty($gambar)): ?>
                                    <div class="mb-2"><img src="../uploads/<?= $gambar; ?>" alt="Foto" width="70" class="img-thumbnail rounded"></div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="gambar" accept="image/*">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" name="simpan_sarpras" class="btn-custom border-0 px-4 py-2 rounded-pill"><i class="bi bi-save me-1"></i> Simpan Data</button>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <?php
                $q_hitung = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM sarpras");
                $d_hitung = mysqli_fetch_assoc($q_hitung);
                $total_sarpras = $d_hitung['total'];

                // Ambil daftar kategori untuk tombol filter
                $query_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
                ?>

                <!-- PAGE HEADER DENGAN PENCARIAN & TOMBOL AKSI -->
                <div class="page-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                        <h1 class="page-title mb-1">
                            <i class="bi bi-boxes"></i> Kelola Inventaris Sarpras 
                            <span class="badge bg-primary bg-opacity-10 text-primary ms-2" style="font-size: 11px; font-weight: 700;">Total: <?= $total_sarpras; ?> Item</span>
                        </h1>
                        <small class="text-muted">Kelola, cari, dan edit data sarana prasarana sekolah.</small>
                    </div>

                    <!-- Kotak Pencarian di Tengah -->
                    <div style="width: 100%; max-width: 300px;">
                        <div class="input-group input-group-sm shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari barang, kode..." onkeyup="jalankanPencarianDanFilter()">
                        </div>
                    </div>

                    <!-- Tombol-tombol Aksi -->
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-warning btn-sm fw-bold px-3 text-dark border rounded-pill" id="btnToggleEdit" onclick="toggleModeEdit()">
                            <i class="bi bi-pencil-square me-1"></i> Edit Pilih
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm fw-bold px-3 rounded-pill" id="btnToggleHapus" onclick="toggleModeHapus()">
                            <i class="bi bi-trash3 me-1"></i> Hapus Pilih
                        </button>
                        <a href="sarpras.php?aksi=tambah" class="btn-custom rounded-pill px-3 py-1">
                            <i class="bi bi-plus-circle-fill"></i> Tambah
                        </a>
                    </div>
                </div>

                <!-- FILTER KATEGORI PILLS -->
                <div class="category-pills">
                    <button class="pill-btn active" onclick="filterKategori('semua', this)">Semua Kategori</button>
                    <?php while ($kat = mysqli_fetch_assoc($query_kategori)) : ?>
                        <button class="pill-btn" onclick="filterKategori('<?php echo strtolower($kat['nama_kategori']); ?>', this)">
                            <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                        </button>
                    <?php endwhile; ?>
                </div>

                <!-- FORM CONTAINER UNTUK AKSI MASSAL -->
                <form action="sarpras.php" method="POST" id="formBulkAction">
                    
                    <!-- Panel Hapus Massal -->
                    <div id="panelAksiHapus" class="mb-3 p-3 bg-white border rounded-3 d-none justify-content-between align-items-center shadow-sm">
                        <span class="text-danger fw-bold" style="font-size: 12.5px;">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Centang item sarpras yang ingin dihapus massal.
                        </span>
                        <button type="submit" name="hapus_pilih" onclick="return confirm('Yakin ingin menghapus item yang dicentang?')" class="btn btn-danger btn-sm px-3 fw-bold rounded-pill">
                            <i class="bi bi-trash3-fill me-1"></i> Hapus yang Dipilih
                        </button>
                    </div>

                    <!-- Panel Edit Stok Massal -->
                    <div id="panelAksiEdit" class="mb-3 p-3 bg-white border rounded-3 d-none justify-content-between align-items-center shadow-sm">
                        <span class="text-dark fw-bold" style="font-size: 12.5px;">
                            <i class="bi bi-info-circle-fill me-1 text-warning"></i> Ubah jumlah stok pada item yang dicentang, lalu klik Simpan.
                        </span>
                        <button type="submit" name="simpan_edit_pilih" class="btn btn-warning text-white btn-sm px-3 fw-bold rounded-pill">
                            <i class="bi bi-save-fill me-1"></i> Simpan Perubahan Stok
                        </button>
                    </div>

                    <!-- GRID SARPRAS -->
                    <div class="product-grid" id="productGridContainer">
                        <?php 
                        $no = 1;
                        $query_sarpras = mysqli_query($koneksi, "SELECT sarpras.*, kategori.nama_kategori FROM sarpras JOIN kategori ON sarpras.id_kategori = kategori.id ORDER BY sarpras.id ASC");
                        while ($row = mysqli_fetch_assoc($query_sarpras)) : 
                        ?>
                        <div class="product-card item-card" id="card-<?= $row['id']; ?>" 
                             data-nama="<?= strtolower(htmlspecialchars($row['nama_sarpras'])); ?>" 
                             data-kode="<?= strtolower(htmlspecialchars($row['kode_barang'])); ?>" 
                             data-kategori="<?= strtolower(htmlspecialchars($row['nama_kategori'])); ?>">
                            
                            <span class="product-badge-number">#<?= $no++; ?></span>

                            <div class="select-checkbox-wrap">
                                <input type="checkbox" name="id_pilih[]" value="<?= $row['id']; ?>" class="item-checkbox" onchange="handleCheckboxChange(this, <?= $row['id']; ?>)">
                            </div>

                            <div class="product-card-img-wrap">
                                <?php if (!empty($row['gambar']) && file_exists("../uploads/".$row['gambar'])) : ?>
                                    <img src="../uploads/<?= htmlspecialchars($row['gambar']); ?>" alt="Foto Sarpras">
                                <?php else : ?>
                                    <div class="text-muted d-flex flex-column align-items-center justify-content-center w-100 h-100 bg-light">
                                        <i class="bi bi-image fs-3"></i>
                                        <small style="font-size: 10px;">Tanpa Foto</small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="product-card-body">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="product-card-category text-uppercase fw-bold text-primary"><?= htmlspecialchars($row['nama_kategori']); ?></span>
                                    <code style="font-size: 10px;" class="text-muted"><?= htmlspecialchars($row['kode_barang']); ?></code>
                                </div>
                               
<h6 class="product-card-title" title="<?= htmlspecialchars($row['nama_sarpras']); ?>">
    <?= htmlspecialchars($row['nama_sarpras']); ?>
</h6>

<div class="mt-2 d-flex gap-1 flex-wrap">
    <span class="badge border"
          style="font-size: 9.5px; background-color: #F1F3F5; color: #495057; border-color: #CED4DA !important;">
        <?= htmlspecialchars($row['jenis']); ?>
    </span>

    <span class="badge border"
          style="font-size: 9.5px; background-color: #E8F5E9; color: #2E7D32; border-color: #A5D6A7 !important;">
        <?= htmlspecialchars($row['kondisi']); ?>
    </span>
</div>

                                <!-- Input Edit Stok Massal -->
                                <div class="edit-inputs-group mt-2 d-none" id="edit-inputs-<?= $row['id']; ?>">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Stok</span>
                                        <input type="number" name="edit_data[<?= $row['id']; ?>][stok]" value="<?= $row['stok']; ?>" class="form-control" placeholder="Stok">
                                    </div>
                                </div>
                            </div>

                            <div class="product-card-footer">
                                <div>
                                    <small class="text-muted" style="font-size: 10.5px; text-transform: uppercase; font-weight: 700;">Stok:</small>
                                    <div class="fw-bold text-dark" style="font-size: 13px;"><?= $row['stok']; ?> Unit</div>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="sarpras.php?aksi=edit&id=<?= $row['id']; ?>" class="btn btn-sm btn-light border text-muted px-2 py-1 rounded-2" title="Edit Detail">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="sarpras.php?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-light border text-danger px-2 py-1 rounded-2" title="Hapus Item" onclick="return confirm('Hapus item ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar Buka-Tutup
        const sidebar = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('mainWrapper');
        const toggleBtn = document.getElementById('sidebarToggle');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                mainWrapper.classList.toggle('expanded');
            });
        }

        let kategoriAktif = 'semua';

        // Filter Kategori Pill
        function filterKategori(kategori, btn) {
            kategoriAktif = kategori;
            document.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            jalankanPencarianDanFilter();
        }

        // Pencarian & Filter Gabungan
        function jalankanPencarianDanFilter() {
            const keyword = document.getElementById("searchInput").value.toLowerCase();
            document.querySelectorAll(".item-card").forEach(card => {
                const nama = card.dataset.nama;
                const kode = card.dataset.kode;
                const kategori = card.dataset.kategori;
                
                const cocokKeyword = (nama.includes(keyword) || kode.includes(keyword) || kategori.includes(keyword));
                const cocokKategori = (kategoriAktif === 'semua' || kategori === kategoriAktif);

                if (cocokKeyword && cocokKategori) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });
        }

        // Mode Hapus Pilih Massal
        function toggleModeHapus() {
            const container = document.getElementById('productGridContainer');
            const panelAksi = document.getElementById('panelAksiHapus');
            const btnToggle = document.getElementById('btnToggleHapus');
            
            document.getElementById('panelAksiEdit').classList.add('d-none');
            document.getElementById('panelAksiEdit').classList.remove('d-flex');
            const btnEdit = document.getElementById('btnToggleEdit');
            btnEdit.classList.remove('btn-warning', 'text-white');
            btnEdit.classList.add('btn-outline-warning', 'text-dark');
            document.querySelectorAll('.edit-inputs-group').forEach(el => el.classList.add('d-none'));

            container.classList.toggle('mode-select-active');

            if (panelAksi.classList.contains('d-none')) {
                panelAksi.classList.remove('d-none');
                panelAksi.classList.add('d-flex');
                btnToggle.classList.remove('btn-outline-danger');
                btnToggle.classList.add('btn-secondary');
                btnToggle.innerHTML = '<i class="bi bi-x-circle me-1"></i> Batal Hapus';
            } else {
                panelAksi.classList.remove('d-flex');
                panelAksi.classList.add('d-none');
                btnToggle.classList.remove('btn-secondary');
                btnToggle.classList.add('btn-outline-danger');
                btnToggle.innerHTML = '<i class="bi bi-trash3 me-1"></i> Hapus Pilih';
                document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
                document.querySelectorAll('.product-card').forEach(c => c.style.background = 'var(--card-bg)');
            }
        }

        // Mode Edit Pilih Massal
        function toggleModeEdit() {
            const container = document.getElementById('productGridContainer');
            const panelAksi = document.getElementById('panelAksiEdit');
            const btnToggle = document.getElementById('btnToggleEdit');

            document.getElementById('panelAksiHapus').classList.add('d-none');
            document.getElementById('panelAksiHapus').classList.remove('d-flex');
            const btnHapus = document.getElementById('btnToggleHapus');
            btnHapus.classList.remove('btn-secondary');
            btnHapus.classList.add('btn-outline-danger');
            btnHapus.innerHTML = '<i class="bi bi-trash3 me-1"></i> Hapus Pilih';

            container.classList.toggle('mode-select-active');

            if (panelAksi.classList.contains('d-none')) {
                panelAksi.classList.remove('d-none');
                panelAksi.classList.add('d-flex');
                btnToggle.classList.remove('btn-outline-warning', 'text-dark');
                btnToggle.classList.add('btn-warning', 'text-white');
                btnToggle.innerHTML = '<i class="bi bi-x-circle me-1"></i> Batal Edit';
            } else {
                panelAksi.classList.remove('d-flex');
                panelAksi.classList.add('d-none');
                btnToggle.classList.remove('btn-warning', 'text-white');
                btnToggle.classList.add('btn-outline-warning', 'text-dark');
                btnToggle.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit Pilih';
                document.querySelectorAll('.item-checkbox').forEach(cb => {
                    cb.checked = false;
                    handleCheckboxChange(cb, cb.value);
                });
            }
        }

        function handleCheckboxChange(checkbox, id) {
            const editInputs = document.getElementById('edit-inputs-' + id);
            const card = document.getElementById('card-' + id);
            if (!editInputs) return;

            if (checkbox.checked) {
                editInputs.classList.remove('d-none');
                card.style.background = '#f8fafc';
            } else {
                editInputs.classList.add('d-none');
                card.style.background = 'var(--card-bg)';
            }
        }
    </script>
</body>
</html>