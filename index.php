<?php
session_start();
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Informasi Peminjaman Sarpras - Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f4f7fa; 
            color: #2c3e50;
        }
        
        /* Floating Navbar Biru Akademik */
        .floating-nav {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1100px;
            background: rgba(13, 37, 63, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 50rem;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 8px 24px;
        }

        /* Hero Section Instansi Pendidikan */
        .hero-section {
            background: linear-gradient(135deg, rgba(13, 37, 63, 0.92) 0%, rgba(26, 82, 118, 0.85) 100%), 
                        url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 170px 0 100px 0;
            border-bottom-left-radius: 2.5rem;
            border-bottom-right-radius: 2.5rem;
        }

        /* Container Katalog Horizontal Scroll dengan Jarak (*Gap*) Lebih Longgar */
        .scrollable-katalog {
            display: flex;
            overflow-x: auto;
            gap: 2rem;
            padding: 10px 5px 25px 5px;
            scroll-snap-type: x mandatory;
            scrollbar-width: thin;
            scrollbar-color: #0284c7 #e2e8f0;
        }
        .scrollable-katalog::-webkit-scrollbar {
            height: 10px;
        }
        .scrollable-katalog::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .scrollable-katalog::-webkit-scrollbar-thumb {
            background: #0284c7;
            border-radius: 10px;
        }

        .custom-card {
            flex: 0 0 340px;
            scroll-snap-align: start;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }
        .custom-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(13, 37, 63, 0.1);
            border-color: #0284c7;
        }

        .badge-category {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            font-weight: 600;
        }

        /* FOOTER DENGAN WALLPAPER GEDUNG SEKOLAH YANG SAMAR */
        .school-footer {
            position: relative;
            background: linear-gradient(rgba(13, 37, 63, 0.88), rgba(13, 37, 63, 0.92)), 
                        url('https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
            color: #cbd5e1;
            padding: 70px 0 30px 0;
            margin-top: 80px;
        }

        /* Tombol Floating WhatsApp */
        .wa-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #25d366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .wa-float:hover {
            background-color: #20ba5a;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
        }
    </style>
</head>
<body>

    <!-- FLOATING NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark floating-nav">
        <div class="container-fluid px-2">
            <a class="navbar-brand fw-bold text-uppercase d-flex align-items-center" href="index.php">
                <i class="bi bi-mortarboard-fill text-info fs-4 me-2"></i>SARPRAS <span class="text-info ms-1">SEKOLAH</span>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link text-white fw-semibold active" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link text-light opacity-75" href="#katalog">Katalog</a></li>
                    <li class="nav-item"><a class="nav-link text-light opacity-75" href="#informasi">Prosedur & Akun</a></li>
                    <?php if(isset($_SESSION['role'])): ?>
                        <li class="nav-item">
                            <a href="<?= $_SESSION['role']; ?>/index.php" class="btn btn-info btn-sm rounded-pill px-4 fw-bold text-dark shadow-sm">Panel <?= ucfirst($_SESSION['role']); ?></a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="login.php" class="btn btn-info btn-sm rounded-pill px-4 fw-bold text-dark shadow-sm"><i class="bi bi-box-arrow-in-right me-1"></i> Login Sistem</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <header class="hero-section text-center">
        <div class="container">
            <span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">Portal Layanan Sarana & Prasarana Pendidikan</span>
            <h1 class="display-4 fw-extrabold mb-3">Manajemen Peminjaman Fasilitas Sekolah</h1>
            <p class="lead mb-4 text-light opacity-90 col-md-8 mx-auto">Layanan pengajuan penggunaan ruang, perangkat multimedia, dan alat penunjang kegiatan belajar mengajar secara terintegrasi dan akuntabel.</p>
            <a href="#katalog" class="btn btn-info btn-lg rounded-pill px-5 fw-bold text-dark shadow">Eksplorasi Katalog <i class="bi bi-arrow-down ms-1"></i></a>
        </div>
    </header>

    <!-- KATALOG SARPRAS (HORIZONTAL SCROLL) -->
    <div class="container my-5 py-3" id="katalog">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Daftar Inventaris & Ruangan</h2>
                <p class="text-muted small mb-0">Geser panel ke samping untuk melihat ketersediaan sarana prasarana</p>
            </div>
            <span class="text-primary fw-bold small"><i class="bi bi-arrow-left-right me-1"></i> Geser Panel</span>
        </div>

        <div class="scrollable-katalog px-2">
            <?php 
            $query = mysqli_query($koneksi, "SELECT sarpras.*, kategori.nama_kategori FROM sarpras JOIN kategori ON sarpras.id_kategori=kategori.id");
            while($row = mysqli_fetch_assoc($query)):
            ?>
            <div class="card custom-card h-100 overflow-hidden">
                <?php if(!empty($row['gambar'])): ?>
                    <img src="uploads/<?= $row['gambar']; ?>" class="card-img-top" style="height: 210px; object-fit: cover;" alt="<?= $row['nama_sarpras']; ?>">
                <?php else: ?>
                    <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height: 210px;">
                        <i class="bi bi-image fs-1 opacity-50"></i>
                    </div>
                <?php endif; ?>
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge badge-category px-3 py-1 rounded-pill"><?= $row['nama_kategori']; ?></span>
                        <?php if($row['kondisi'] == 'Baik'): ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Tersedia</span>
                        <?php elseif($row['kondisi'] == 'Perbaikan'): ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">Maintenance</span>
                        <?php else: ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Ditutup</span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold mb-2 text-dark"><?= $row['nama_sarpras']; ?></h5>
                    <p class="text-muted small mb-4">Kategori Unit: <strong class="text-dark"><?= $row['jenis']; ?></strong> | Kuantitas: <strong class="text-dark"><?= $row['stok']; ?> Unit</strong></p>
                    
                    <div class="mt-auto pt-2">
                        <?php if($row['kondisi'] == 'Baik'): ?>
                            <a href="login.php" class="btn btn-outline-primary w-100 rounded-pill fw-bold py-2 shadow-sm">Ajukan Peminjaman <i class="bi bi-arrow-right ms-1"></i></a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 rounded-pill fw-bold py-2" disabled>Belum Dapat Dipinjam</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- SEKSI INFORMASI AKADEMIK & PROSEDUR -->
    <div class="container my-5 py-4" id="informasi">
        <div class="row g-4">
            <!-- Ketentuan & Prosedur Peminjaman -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                            <i class="bi bi-file-earmark-text fs-4"></i>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark">Ketentuan Penggunaan Sarpras</h4>
                    </div>
                    <ol class="text-secondary small ps-3 mb-0 lh-lg">
                        <li>Pengajuan peminjaman fasilitas sekolah wajib dilakukan sekurang-kurangnya 1 (satu) hari sebelum jadwal penggunaan.</li>
                        <li>Durasi peminjaman dapat disesuaikan berdasarkan kebutuhan: <strong>Per Jam</strong> (untuk keperluan jam pelajaran/praktikum) atau <strong>Per Hari</strong> (untuk kegiatan ekstrakurikuler/acara institusi).</li>
                        <li>Pihak peminjam (perwakilan kelas atau pendidik) bertanggung jawab penuh atas keamanan, kebersihan, dan keutuhan barang selama masa peminjaman berlangsung.</li>
                        <li>Segala bentuk kerusakan atau kendala teknis wajib dilaporkan segera kepada petugas unit layanan sarana dan prasarana.</li>
                    </ol>
                </div>
            </div>

            <!-- Informasi Kredensial Akun Kelas -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-start border-primary border-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 me-3">
                            <i class="bi bi-shield-lock fs-4"></i>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark">Informasi Akses & Akun Kelas</h4>
                    </div>
                    <p class="text-secondary small mb-3">
                        Setiap rombongan belajar / kelas di lingkungan sekolah telah difasilitasi 1 (satu) akun otorisasi resmi yang dikelola oleh pengurus kelas atau penanggung jawab inventaris.
                    </p>
                    <div class="alert alert-light border small mb-0 rounded-3 p-3 text-secondary">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i> <strong>Kendala Akses Akun:</strong> 
                        Bagi perwakilan kelas yang belum menerima data kredensial (Username/Password) atau mengalami kendala login, silakan berkoordinasi langsung dengan pihak Administrasi Sekolah atau Unit Sarpras.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER SEKOLAH DENGAN WALLPAPER SAMAR (OVERLAY) -->
    <footer class="school-footer">
        <div class="container">
            <div class="row g-4 mb-4">
                <!-- Profil Singkat Sekolah -->
                <div class="col-md-5">
                    <h5 class="text-white fw-bold text-uppercase mb-3 d-flex align-items-center">
                        <i class="bi bi-mortarboard-fill text-info me-2 fs-4"></i> SMK Negeri 3 Kendal
                    </h5>
                    <p class="small text-light opacity-75 lh-base">
                        Pusat layanan informasi dan pengelolaan sarana serta prasarana sekolah secara terpadu. Mendukung kelancaran kegiatan belajar mengajar, praktikum kejuruan, dan pengembangan kompetensi peserta didik.
                    </p>
                </div>
                <!-- Alamat & Kontak -->
                <div class="col-md-4">
                    <h6 class="text-white fw-bold text-uppercase mb-3">Kontak & Lokasi</h6>
                    <ul class="list-unstyled small text-light opacity-75 lh-lg">
                        <li><i class="bi bi-geo-alt-fill text-info me-2"></i> Jl. Lingkar Tentara Pelajar, Kebondalem, Kendal, Jawa Tengah</li>
                        <li><i class="bi bi-telephone-fill text-info me-2"></i> (0294) 381xxx</li>
                        <li><i class="bi bi-envelope-fill text-info me-2"></i> sarpras@smkn3kendal.sch.id</li>
                    </ul>
                </div>
                <!-- Jam Layanan -->
                <div class="col-md-3">
                    <h6 class="text-white fw-bold text-uppercase mb-3">Jam Operasional Unit</h6>
                    <p class="small text-light opacity-75 mb-1">Senin - Kamis: 07:00 - 15:30 WIB</p>
                    <p class="small text-light opacity-75 mb-1">Jumat: 07:00 - 14:00 WIB</p>
                    <p class="small text-warning">Sabtu & Minggu / Libur: Tutup</p>
                </div>
            </div>
            <hr class="border-secondary opacity-50">
            <div class="text-center pt-2">
                <p class="mb-0 small text-light opacity-50">&copy; 2026 <strong>Unit Layanan Sarana & Prasarana SMK Negeri 3 Kendal</strong>. Seluruh Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- TOMBOL FLOATING WHATSAPP -->
    <a href="https://wa.me/6281234567890?text=Halo%20Admin%20Sarpras,%20saya%20ingin%20bertanya%20mengenai%20peminjaman%20sarpras%20sekolah." 
       class="wa-float" 
       target="_blank" 
       title="Hubungi Admin via WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>