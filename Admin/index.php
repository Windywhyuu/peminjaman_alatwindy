<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php?pesan=Akses ditolak!");
    exit;
}

// 1. AMBIL DATA PEMINJAMAN AKTIF UNTUK CEK APAKAH ADA YANG BOOKING 24 JAM (FULL DAY)
// Status selain Selesai dan Ditolak
$query_full_booked = mysqli_query($koneksi, "
    SELECT DISTINCT d.id_sarpras 
    FROM detail_peminjaman d
    JOIN peminjaman p ON d.id_peminjaman = p.id
    WHERE p.status != 'Selesai' AND p.status != 'Ditolak' 
    AND (p.jam_pinjam IS NULL OR p.jam_pinjam = '00:00:00' OR p.jam_kembali IS NULL OR p.jam_kembali = '00:00:00')
");
$sarpras_full_booked = [];
while ($row_fb = mysqli_fetch_assoc($query_full_booked)) {
    $sarpras_full_booked[] =$row_fb['id_sarpras'];
}

// 2. HITUNG JUMLAH STOK BARANG YANG SEDANG DIPAKAI (UNTUK BARANG NON-RUANGAN)
// Jika barang dipinjam, kita kurangi dari stok total di database
$query_stok_terpakai = mysqli_query($koneksi, "
    SELECT d.id_sarpras, SUM(d.jumlah) AS total_dipakai
    FROM detail_peminjaman d
    JOIN peminjaman p ON d.id_peminjaman = p.id
    WHERE p.status != 'Selesai' AND p.status != 'Ditolak'
    GROUP BY d.id_sarpras
");
$stok_terpakai_map = [];
while ($row_stok = mysqli_fetch_assoc($query_stok_terpakai)) {$stok_terpakai_map[$row_stok['id_sarpras']] =$row_stok['total_dipakai'];
}

$query_sarpras = mysqli_query($koneksi, "
    SELECT sarpras.*, kategori.nama_kategori 
    FROM sarpras 
    JOIN kategori ON sarpras.id_kategori = kategori.id
    WHERE sarpras.kondisi = 'Baik'
    ORDER BY kategori.id ASC, sarpras.id ASC
");

$query_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id ASC");
$data_kategori_array = [];
while ($kat = mysqli_fetch_assoc($query_kategori)) {
    $data_kategori_array[] =$kat;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking & Katalog Sarpras - Administrator</title>
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
        .stock-badge {
            position: absolute; top: 8px; right: 8px; background: rgba(13, 37, 63, 0.85); color: #FFFFFF; font-size: 10px; font-weight: 700;
            padding: 3px 8px; border-radius: 12px; z-index: 2;
        }
        .product-content { display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between; margin-bottom: 12px; }
        .product-title {
            font-size: 13px; font-weight: 700; color: var(--text-dark); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; min-height: 36px; margin-bottom: 0;
        }
        .btn-add-item {
            width: 100%; background: #0284c7; color: #FFFFFF; border: none; padding: 8px; border-radius: 8px; font-weight: 700;
            font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.2s; flex-shrink: 0;
        }
        .btn-add-item:hover { background: #0369a1; }
        .btn-booked {
            width: 100%; background: #e2e8f0; color: #64748b; border: none; padding: 8px; border-radius: 8px; font-weight: 700;
            font-size: 12px; cursor: not-allowed; display: flex; align-items: center; justify-content: center; gap: 6px; flex-shrink: 0;
        }
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
        <i class="bi bi-shield-lock-fill text-primary"></i>
        <span class="brand-text">SARPRAS <span class="text-info">ADMIN</span></span>
    </div>
    <div class="menu">
        <a href="index.php" class="active"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard & Booking</span></a>
        <a href="kategori.php"><i class="bi bi-tags-fill"></i><span>Kategori Sarpras</span></a>
        <a href="sarpras.php"><i class="bi bi-boxes"></i><span>Kelola Sarpras</span></a>
        <a href="user.php"><i class="bi bi-people-fill"></i><span>Kelola User</span></a>
        <a href="peminjaman.php"><i class="bi bi-file-earmark-text-fill"></i><span>Peminjaman</span></a>
        <a href="log.php"><i class="bi bi-journal-text"></i><span>Log Peminjaman</span></a>
    </div>
    <div class="p-2 border-top">
        <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i><span>Logout Sistem</span></a>
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
                <i class="bi bi-person-circle me-1"></i> <?= $_SESSION['nama']; ?> (Admin)
            </span>
        </div>
    </div>

    <div class="category-pills">
        <button class="pill-btn active" onclick="filterKategori('', this)">
            Semua
        </button>
        <?php foreach ($data_kategori_array as$kat) : ?>
            <button class="pill-btn" onclick="filterKategori('<?php echo htmlspecialchars(strtolower(trim($kat['nama_kategori']))); ?>', this)">
                <?php echo htmlspecialchars($kat['nama_kategori']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="products-grid" id="productList">
        <?php while ($row = mysqli_fetch_assoc($query_sarpras)) : 
            $id_sarpras =$row['id'];
            $stok_db = intval($row['stok']);
            $nama_kategori = strtolower(trim($row['nama_kategori']));
            
           // Cek apakah kategori ini adalah ruangan atau barang biasa
$is_ruangan = (strpos($nama_kategori, 'ruang') !== false || strpos($nama_kategori, 'room') !== false || $stok_db <= 1);

// Hitung sisa stok aktual berdasarkan barang yang sedang dipinjam aktif
$jumlah_dipakai = isset($stok_terpakai_map[$id_sarpras]) ? intval($stok_terpakai_map[$id_sarpras]) : 0;
$sisa_stok = $stok_db - $jumlah_dipakai;
            
            // Logika ketersediaan tombol:
            // 1. Jika Ruangan: hanya diblokir total jika dibooking 24 jam penuh (Full Day). Jika tidak, tetap bisa dibooking untuk jam kosong.
            // 2. Jika Barang biasa: diblokir jika sisa stok <= 0 (habis).
            $sudah_full_booked = in_array($id_sarpras,$sarpras_full_booked);
            
            if ($is_ruangan) {
                $tidak_bisa_dibooking =$sudah_full_booked; 
            } else {
                $tidak_bisa_dibooking = ($sisa_stok <= 0);
            }
        ?>
            <div class="product-card" data-name="<?php echo htmlspecialchars(strtolower(trim($row['nama_sarpras']))); ?>" data-category="<?php echo htmlspecialchars($nama_kategori); ?>">
                <div class="product-img-box">
                    <span class="badge-kategori"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
                    
                    <?php if (!$is_ruangan) : ?>
                        <span class="stock-badge">Stok: <?php echo max(0, $sisa_stok); ?> / <?php echo$stok_db; ?></span>
                    <?php endif; ?>

                    <?php if ($tidak_bisa_dibooking) : ?>
                        <span class="badge bg-danger position-absolute bottom-0 start-0 m-2 z-2" style="font-size: 10px;">
                            <?php echo $is_ruangan ? 'Dibooking 24 Jam' : 'Stok Habis'; ?>
                        </span>
                    <?php endif; ?>

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
                </div>

                <?php if ($tidak_bisa_dibooking) : ?>
                    <button type="button" class="btn-booked" disabled>
                        <i class="bi bi-slash-circle"></i> <?php echo $is_ruangan ? 'Penuh 24 Jam' : 'Stok Habis'; ?>
                    </button>
                <?php else : ?>
                    <button type="button" class="btn-add-item" onclick="tambahItem(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nama_sarpras']); ?>', <?php echo max(1,$sisa_stok); ?>)">
                        <i class="bi bi-plus-circle-fill"></i> Pilih Booking
                    </button>
                <?php endif; ?>
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
let kategoriAktif = "";

window.addEventListener('DOMContentLoaded', () => {
    jalankanPencarianDanFilter();
});

function tambahItem(id, nama, stokTersedia) {
    let item = keranjang.find(p => p.id === id);
    if (item) {
        if (item.jumlah >= stokTersedia) { alert("Stok maksimal yang tersedia telah tercapai!"); return; }
        item.jumlah++;
    } else {
        keranjang.push({ id, nama, stok: stokTersedia, jumlah: 1 });
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
    summaryText.innerText = `${totalQty} Barang Dipilih untuk Booking`;

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
    kategoriAktif = kategori.trim().toLowerCase();
    document.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    jalankanPencarianDanFilter();
}

function jalankanPencarianDanPlayer() {} // helper

function jalankanPencarianDanFilter() {
    const keyword = document.getElementById("searchProduct").value.toLowerCase().trim();
    document.querySelectorAll(".product-card").forEach(product => {
        const nama = (product.dataset.name || "").toLowerCase().trim();
        const kategori = (product.dataset.category || "").toLowerCase().trim();
        
        const cocokKategori = (kategoriAktif === "" || kategori === kategoriAktif);
        const cocokKeyword = nama.includes(keyword);

        if (cocokKeyword && cocokKategori) {
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