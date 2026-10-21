<?php
session_start();
include '../koneksi.php';

/* =========================
   CEK LOGIN (PETUGAS / ADMIN)
========================= */
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'petugas' &&$_SESSION['role'] != 'admin')) {
    header("Location: ../login.php?pesan=Akses ditolak!");
    exit;
}

$today = date('Y-m-d');

// 1. Ambil data sarpras sekaligus hitung pemakaian stok aktif untuk barang biasa hari ini
$query_sarpras = mysqli_query($koneksi, "
    SELECT sarpras.*, kategori.nama_kategori,
    (
        SELECT COALESCE(SUM(dp.jumlah), 0)
        FROM detail_peminjaman dp
        JOIN peminjaman p ON dp.id_peminjaman = p.id
        WHERE dp.id_sarpras = sarpras.id
        AND p.status IN ('Diterima', 'Dipinjam')
        AND '$today' BETWEEN p.tgl_pinjam AND p.tgl_kembali
    ) AS total_dipakai
    FROM sarpras 
    JOIN kategori ON sarpras.id_kategori = kategori.id
    WHERE sarpras.kondisi = 'Baik'
    ORDER BY kategori.id ASC, sarpras.id ASC
");

// 2. Ambil daftar ID sarpras yang sedang dibooking secara FULL DAY (24 Jam) pada hari ini
$query_full_booked = mysqli_query($koneksi, "
    SELECT DISTINCT d.id_sarpras 
    FROM detail_peminjaman d
    JOIN peminjaman p ON d.id_peminjaman = p.id
    WHERE p.status IN ('Diterima', 'Dipinjam')
    AND '$today' BETWEEN p.tgl_pinjam AND p.tgl_kembali
    AND (p.jam_pinjam IS NULL OR p.jam_pinjam = '00:00:00' OR p.jam_kembali IS NULL OR p.jam_kembali = '00:00:00')
");
$sarpras_full_booked = [];
while ($row_fb = mysqli_fetch_assoc($query_full_booked)) {
    $sarpras_full_booked[] =$row_fb['id_sarpras'];
}

$query_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id ASC");
$kategori_pertama = "";
$data_kategori_array = [];
while ($kat = mysqli_fetch_assoc($query_kategori)) {
    if (empty($kategori_pertama)) {
        $kategori_pertama = strtolower($kat['nama_kategori']);
    }
    $data_kategori_array[] =$kat;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard & Booking Sarpras - Petugas</title>
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
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; height: 38px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 0 16px 0 38px; font-size: 13px; outline: none; }
        .search-box input:focus { border-color: var(--primary-blue); }
        .search-box i { position: absolute; left: 13px; top: 11px; color: var(--text-muted); }
        .category-pills { display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px; }
        .pill-btn {
            background: #ffffff; border: 1px solid var(--border-color); color: var(--text-muted); padding: 6px 16px; border-radius: 50px;
            font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: all 0.2s;
        }
        .pill-btn:hover, .pill-btn.active { background: var(--primary-blue); border-color: var(--primary-blue); color: #fff; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 18px; }
        .product-card {
            background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px;
            display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(13, 37, 63, 0.06); }
        .product-img-box {
            width: 100%; height: 130px; border-radius: 10px; overflow: hidden; background: #F8F9FA; margin-bottom: 12px;
            display: flex; align-items: center; justify-content: center; position: relative; flex-shrink: 0;
        }
        .product-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .badge-kategori {
            position: absolute; top: 8px; left: 8px; background: rgba(255, 255, 255, 0.95); color: var(--primary-blue);
            font-size: 9.5px; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,0,0,0.05); z-index: 2;
        }
        .product-content { display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between; margin-bottom: 12px; }
        .product-title {
            font-size: 13px; font-weight: 700; color: var(--text-dark); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; min-height: 36px; margin-bottom: 4px;
        }
        .product-stock-info {
            font-size: 11.5px; color: var(--text-muted); font-weight: 600; margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;
        }
        .status-booking-badge {
            font-size: 10.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px; text-align: center; margin-bottom: 6px; display: block;
        }
        .status-tersedia { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-full { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .btn-add-item {
            width: 100%; background: #0284c7; color: #FFFFFF; border: none; padding: 8px; border-radius: 8px; font-weight: 700;
            font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.2s; flex-shrink: 0;
        }
        .btn-add-item:hover { background: #0369a1; }
        .btn-add-item:disabled { background: #cbd5e1; cursor: not-allowed; }

        .ecommerce-bottom-bar {
            position: fixed; bottom: 0; left: 240px; right: 0; background: #ffffff; border-top: 1px solid var(--border-color); padding: 14px 32px;
            display: flex; align-items: center; justify-content: space-between; box-shadow: 0 -4px 15px rgba(0,0,0,0.04); z-index: 1040;
            transform: translateY(100%); transition: transform 0.3s ease, left 0.3s ease;
        }
        .ecommerce-bottom-bar.show { transform: translateY(0); }
        .ecommerce-bottom-bar.expanded { left: 0; }
        .btn-proses-checkout {
            background: var(--primary-blue); color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 700;
            font-size: 13px; display: flex; align-items: center; gap: 8px; cursor: pointer;
        }
        .btn-proses-checkout:hover { background: var(--primary-blue-dark); }
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
        <a href="cek_akun.php"><i class="bi bi-people-fill"></i><span>Cek Akun</span></a>
    </div>
    <div class="p-2 border-top">
        <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
    </div>
</aside>

<div class="main-wrapper" id="mainWrapper">
    <div class="top-navbar-bar">
        <button class="toggle-btn" id="sidebarToggle" title="Buka/Tutup Menu"><i class="bi bi-list"></i></button>
        <div class="search-box mb-0 ms-2">
            <i class="bi bi-search"></i>
            <input type="text" id="searchProduct" placeholder="Cari nama barang atau ruangan..." autocomplete="off">
        </div>
        <div class="d-flex align-items-center ms-auto">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'Petugas'); ?> (Petugas)
            </span>
        </div>
    </div>

    <div class="category-pills">
        <?php foreach ($data_kategori_array as $index =>$kat) : ?>
            <button class="pill-btn <?php echo ($index === 0) ? 'active' : ''; ?>" onclick="filterKategori('<?php echo strtolower($kat['nama_kategori']); ?>', this)">
                <?php echo htmlspecialchars($kat['nama_kategori']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="products-grid" id="productList">
        <?php while ($row = mysqli_fetch_assoc($query_sarpras)) :$id_sarpras = intval($row['id']);$stok_total = intval($row['stok']);$total_dipakai = intval($row['total_dipakai']);$sisa_stok = $stok_total -$total_dipakai;
            if ($sisa_stok < 0)$sisa_stok = 0;
            
            $nama_kategori = strtolower(trim($row['nama_kategori']));
            
           // Cek apakah item ini berupa ruangan (berdasarkan nama kategori atau stok <= 1)
$is_ruangan = (strpos($nama_kategori, 'ruang') !== false || strpos($nama_kategori, 'room') !== false || $stok_total <= 1);
$sudah_full_booked = in_array($id_sarpras, $sarpras_full_booked);
            
            // Logika ketersediaan:
            if ($is_ruangan) {
                $is_full =$sudah_full_booked;
            } else {
                $is_full = ($sisa_stok <= 0);
            }
        ?>
            <div class="product-card" data-name="<?php echo strtolower($row['nama_sarpras']); ?>" data-category="<?php echo $nama_kategori; ?>">
                <div class="product-img-box">
                    <span class="badge-kategori"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
                    <?php if (!empty($row['foto'] ?? $row['gambar'])) : ?>
                        <img src="../uploads/<?php echo htmlspecialchars($row['foto'] ?? $row['gambar']); ?>" alt="Foto">
                    <?php else : ?>
                        <i class="bi bi-image text-muted fs-3"></i>
                    <?php endif; ?>
                </div>
                <div class="product-content">
                    <div class="product-title" title="<?php echo htmlspecialchars($row['nama_sarpras']); ?>">
                        <?php echo htmlspecialchars($row['nama_sarpras']); ?>
                    </div>
                    
                    <?php if (!$is_ruangan) : ?>
                        <div class="product-stock-info">
                            <span>Total Stok: <strong><?php echo $stok_total; ?></strong></span>
                            <span>Sisa: <strong class="text-primary"><?php echo $sisa_stok; ?></strong></span>
                        </div>
                    <?php else : ?>
                        <div class="product-stock-info">
                            <span>Status Ruangan</span>
                            <span><strong><?php echo $sudah_full_booked ? 'Penuh 24 Jam' : 'Ada Jam Kosong'; ?></strong></span>
                        </div>
                    <?php endif; ?>

                    <!-- Keterangan Status -->
                    <?php if ($is_full): ?>
                        <span class="status-booking-badge status-full">
                            <i class="bi bi-x-circle-fill me-1"></i> <?php echo $is_ruangan ? 'Full Booking 24 Jam' : 'Stok Habis Hari Ini'; ?>
                        </span>
                    <?php else: ?>
                        <span class="status-booking-badge status-tersedia">
                            <i class="bi bi-check-circle-fill me-1"></i> <?php echo $is_ruangan ? 'Tersedia Jam Kosong' : 'Tersedia Hari Ini'; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn-add-item" <?php echo $is_full ? 'disabled' : ''; ?> onclick="tambahItem(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nama_sarpras']); ?>', <?php echo $is_ruangan ? 1 : $sisa_stok; ?>)">
                    <i class="bi bi-plus-circle-fill"></i> <?php echo $is_full ? 'Penuh / Habis' : 'Pilih Booking'; ?>
                </button>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- FORM POST KE booking.php -->
<form action="booking.php" method="POST" id="formBooking" onsubmit="return validasiCheckout()">
    <div id="hiddenItemsContainer"></div>
    <div class="ecommerce-bottom-bar" id="bottomBar">
        <div class="fw-bold text-primary d-flex align-items-center gap-2" style="font-size: 14px;">
            <i class="bi bi-box-seam-fill"></i> <span id="summaryText">0 Barang Dipilih untuk Booking</span>
        </div>
        <button type="submit" class="btn-proses-checkout">
            <span>Atur Jadwal & Booking</span><i class="bi bi-calendar-check-fill"></i>
        </button>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainWrapper').classList.toggle('expanded');
    document.getElementById('bottomBar').classList.toggle('expanded');
});

let keranjang = [];
let kategoriAktif = "<?php echo $kategori_pertama; ?>";

window.addEventListener('DOMContentLoaded', () => {
    jalankanPencarianDanFilter();
});

function tambahItem(id, nama, maxStok) {
    let item = keranjang.find(p => p.id === id);
    if (item) {
        if (item.jumlah >= maxStok) { 
            alert("Jumlah melebihi batas stok/tersedia yang ada!"); 
            return; 
        }
        item.jumlah++;
    } else {
        if (maxStok <= 0) {
            alert("Maaf, item ini sudah penuh atau habis!");
            return;
        }
        keranjang.push({ id, nama, stok: maxStok, jumlah: 1 });
    }
    updateTampilan();
}

function updateTampilan() {
    const bottomBar = document.getElementById("bottomBar");
    const summaryText = document.getElementById("summaryText");
    const hiddenContainer = document.getElementById("hiddenItemsContainer");

    if (keranjang.length === 0) {
        bottomBar.classList.remove("show");
        hiddenContainer.innerHTML = "";
        return;
    }

    bottomBar.classList.add("show");
    let totalQty = keranjang.reduce((sum, item) => sum + item.jumlah, 0);
    summaryText.innerText = `${totalQty} Barang/Ruangan Dipilih untuk Booking`;

    let hiddenHtml = "";
    keranjang.forEach((item, index) => {
        hiddenHtml += `<input type="hidden" name="items[${index}][id]" value="${item.id}">`;
        hiddenHtml += `<input type="hidden" name="items[${index}][jumlah]" value="${item.jumlah}">`;
    });
    hiddenContainer.innerHTML = hiddenHtml;
}

function validasiCheckout() {
    if (keranjang.length === 0) {
        alert('Pilih minimal 1 barang atau ruangan!');
        return false;
    }
    return true;
}

function filterKategori(kategori, btn) {
    kategoriAktif = kategori;
    document.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    jalankanPencarianDanFilter();
}

function jalankanPencarianDanFilter() {
    const keyword = document.getElementById("searchProduct").value.toLowerCase();
    document.querySelectorAll(".product-card").forEach(product => {
        const nama = product.dataset.name;
        const kategori = product.dataset.category;
        if (nama.includes(keyword) && kategori === kategoriAktif) {
            product.style.display = "flex";
        } else {
            product.style.display = "none";
        }
    });
}

document.getElementById("searchProduct").addEventListener("input", jalankanPencarianDanFilter);
</script>
</body>
</html>