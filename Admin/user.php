<?php
session_start();
include '../koneksi.php';

/* =========================
   CEK LOGIN ADMIN
========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$pesan = "";
$tipe_pesan = "";
$tab = $_GET['tab'] ?? 'list'; // Pilihan tab: list, tambah, edit
$edit_id = intval($_GET['id'] ?? 0);

/* =========================
   PROSES TAMBAH / EDIT / HAPUS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi       = $_POST['aksi'] ?? '';
    $id         = intval($_POST['id'] ?? 0);
    $nama       = trim($_POST['nama'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = $_POST['role'] ?? 'peminjam';
    $status     = $_POST['status_akun'] ?? 'Aktif';

    if ($aksi === 'tambah') {
        if ($nama !== '' && $username !== '' && $password !== '') {
            $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username'");
            if (mysqli_num_rows($cek) > 0) {
                $pesan = "Username sudah digunakan oleh pengguna lain!";
                $tipe_pesan = "danger";
                $tab = 'tambah';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $q = "INSERT INTO users (nama, username, password, role, status_akun) VALUES ('$nama', '$username', '$password_hash', '$role', '$status')";
                if (mysqli_query($koneksi, $q)) {
                    mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Menambah user baru: $username')");
                    $pesan = "Akun pengguna berhasil ditambahkan.";
                    $tipe_pesan = "success";
                    $tab = 'list';
                } else {
                    $pesan = "Gagal menambahkan pengguna: " . mysqli_error($koneksi);
                    $tipe_pesan = "danger";
                    $tab = 'tambah';
                }
            }
        } else {
            $pesan = "Nama, Username, dan Password wajib diisi!";
            $tipe_pesan = "danger";
            $tab = 'tambah';
        }
    } elseif ($aksi === 'edit') {
        if ($id > 0 && $nama !== '' && $username !== '') {
            $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username' AND id != $id");
            if (mysqli_num_rows($cek) > 0) {
                $pesan = "Username sudah digunakan oleh pengguna lain!";
                $tipe_pesan = "danger";
                $tab = 'edit';
                $edit_id = $id;
            } else {
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $q = "UPDATE users SET nama = '$nama', username = '$username', password = '$password_hash', role = '$role', status_akun = '$status' WHERE id = $id";
                } else {
                    $q = "UPDATE users SET nama = '$nama', username = '$username', role = '$role', status_akun = '$status' WHERE id = $id";
                }

                if (mysqli_query($koneksi, $q)) {
                    mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Mengubah user ID: $id')");
                    $pesan = "Data pengguna berhasil diperbarui.";
                    $tipe_pesan = "success";
                    $tab = 'list';
                } else {
                    $pesan = "Gagal memperbarui pengguna: " . mysqli_error($koneksi);
                    $tipe_pesan = "danger";
                    $tab = 'edit';
                    $edit_id = $id;
                }
            }
        } else {
            $pesan = "Data tidak lengkap!";
            $tipe_pesan = "danger";
            $tab = 'edit';
            $edit_id = $id;
        }
    }
}

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    if ($id_hapus === intval($_SESSION['id'])) {
        $pesan = "Anda tidak dapat menghapus akun yang sedang digunakan sendiri!";
        $tipe_pesan = "danger";
    } else {
        mysqli_query($koneksi, "DELETE FROM users WHERE id = $id_hapus");
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('".$_SESSION['nama']."', 'Menghapus user ID: $id_hapus')");
        header("Location: user.php?pesan=terhapus");
        exit;
    }
}

// Ambil data untuk mode edit
$data_edit = ['id' => '', 'nama' => '', 'username' => '', 'role' => 'peminjam', 'status_akun' => 'Aktif'];
if ($tab === 'edit' && $edit_id > 0) {
    $q_edit = mysqli_query($koneksi, "SELECT * FROM users WHERE id = $edit_id");
    if (mysqli_num_rows($q_edit) > 0) {
        $data_edit = mysqli_fetch_assoc($q_edit);
    }
}

// Ambil daftar pengguna & statistik singkat
$q_users = mysqli_query($koneksi, "SELECT * FROM users ORDER BY id DESC");
$total_users = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users"));
$total_aktif = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE status_akun='Aktif'"));
$total_admin = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE role='admin'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Sarpras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --accent-color: #0284c7;
        }

        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg-canvas); color: var(--text-dark); font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; }

        /* SIDEBAR BUKA-TUTUP DISAMAKAN DENGAN KATEGORI */
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

        /* MAIN CONTENT & TOP NAVBAR DISAMAKAN */
        .main-wrapper {
            margin-left: 240px; min-height: 100vh; padding: 24px 32px;
            transition: margin-left 0.3s ease;
        }
        .main-wrapper.expanded { margin-left: 0; }

        .top-navbar-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; background: #fff; padding: 14px 24px;
            border-radius: 14px; border: 1px solid var(--border-color);
        }
        .toggle-btn {
            background: #f1f5f9; border: 1px solid var(--border-color); color: var(--primary-blue);
            padding: 8px 12px; border-radius: 10px; font-size: 16px; cursor: pointer;
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            background: var(--primary-blue-light);
            color: var(--primary-blue);
        }
        .stat-info h4 {
            font-size: 16px;
            font-weight: 800;
            margin: 0 0 2px 0;
            color: var(--text-dark);
        }
        .stat-info span {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* CONTAINER UTAMA KONTEN */
        .custom-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .content-card-body {
            padding: 24px;
        }

        /* NAVIGASI TAB */
        .nav-tabs-custom {
            display: flex;
            gap: 8px;
            border-bottom: 1px solid var(--border-color);
            padding: 0 24px;
            background: #fafbfc;
        }
        .nav-tabs-custom a {
            padding: 14px 18px;
            font-weight: 700;
            font-size: 13px;
            color: var(--text-muted);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-tabs-custom a:hover { color: var(--text-dark); }
        .nav-tabs-custom a.active {
            color: var(--primary-blue);
            border-bottom-color: var(--primary-blue);
            background: var(--card-bg);
        }

        /* TABEL PENGGUNA */
        .table { font-size: 13px; }
        .table th { background-color: var(--primary-blue) !important; color: #fff !important; font-weight: 600; padding: 12px; border: none; }
        .table td { padding: 12px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }

        .user-avatar-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar-initial {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--primary-blue-light);
            color: var(--primary-blue);
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            border: 1px solid rgba(13, 37, 63, 0.1);
            flex-shrink: 0;
        }

        /* BADGE ROLE */
        .badge-role {
            font-weight: 700;
            font-size: 11.5px;
            padding: 5px 12px;
            border-radius: 20px;
            background: #e2e8f0;
            color: #0f172a;
            letter-spacing: 0.2px;
            border: 1px solid #cbd5e1;
            display: inline-block;
        }

        /* CARD FORM */
        .auth-form-card {
            max-width: 500px;
            margin: 10px auto;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        .auth-form-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .auth-form-header .icon-box {
            width: 48px; height: 48px;
            background: var(--primary-blue-light);
            color: var(--primary-blue);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            margin: 0 auto 12px auto;
        }
        .auth-form-header h3 {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0 0 4px 0;
        }
        .auth-form-header p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 0;
        }

        /* FORM STYLING */
        .form-label-custom {
            font-weight: 700;
            font-size: 11.5px;
            color: var(--text-muted);
            margin-bottom: 4px;
            display: block;
            text-transform: uppercase;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            padding: 9px 12px;
            font-size: 13px;
            color: var(--text-dark);
            background: #fff;
            transition: all 0.2s;
            width: 100%;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: none;
        }

        /* BUTTONS */
        .btn-primary-custom {
            background: var(--primary-blue);
            border: none;
            color: white;
            font-weight: 700;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 13px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            width: 100%;
        }
        .btn-primary-custom:hover { background: var(--primary-blue-dark); color: white; }

        .btn-light-custom {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            font-weight: 700;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 13px;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .btn-light-custom:hover { background: var(--bg-canvas); color: var(--text-dark); }
    </style>
</head>
<body>

    <!-- SIDEBAR ADMIN -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-shield-lock-fill"></i>
            <span class="brand-text">SARPRAS <span class="text-info">ADMIN</span></span>
        </div>
        <div class="menu">
            <a href="index.php"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard & Booking</span></a>
            <a href="kategori.php"><i class="bi bi-tags-fill"></i><span>Kategori Sarpras</span></a>
            <a href="sarpras.php"><i class="bi bi-boxes"></i><span>Kelola Sarpras</span></a>
            <a href="user.php" class="active"><i class="bi bi-people-fill"></i><span>Kelola User</span></a>
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
            <button class="toggle-btn" id="sidebarToggle" title="Buka/Tutup Menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold" style="font-size: 13px;">
                    <i class="bi bi-person-circle me-1"></i> <?= $_SESSION['nama']; ?> (Admin)
                </span>
            </div>
        </header>

            <!-- STATISTIK RINGKAS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-info">
                        <h4><?= $total_users; ?></h4>
                        <span>Total Pengguna</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #d1e7dd; color: #0f5132;"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="stat-info">
                        <h4><?= $total_aktif; ?></h4>
                        <span>Akun Aktif</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;"><i class="bi bi-shield-fill-check"></i></div>
                    <div class="stat-info">
                        <h4><?= $total_admin; ?></h4>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($pesan)): ?>
                <div class="alert alert-<?= $tipe_pesan; ?> alert-dismissible fade show" role="alert" style="border-radius: 12px; margin-bottom: 20px; font-size: 13px;">
                    <?= htmlspecialchars($pesan); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- KONTEN UTAMA KARTU AKUN -->
            <div class="custom-card">
                <!-- Navigasi Tab Modern -->
                <div class="nav-tabs-custom">
                    <a href="user.php?tab=list" class="<?= ($tab === 'list') ? 'active' : ''; ?>">
                        <i class="bi bi-list-ul"></i> Daftar Pengguna
                    </a>
                    <a href="user.php?tab=tambah" class="<?= ($tab === 'tambah') ? 'active' : ''; ?>">
                        <i class="bi bi-person-plus-fill"></i> Tambah Akun Baru
                    </a>
                    <?php if ($tab === 'edit'): ?>
                        <a href="user.php?tab=edit&id=<?= $edit_id; ?>" class="active">
                            <i class="bi bi-pencil-square"></i> Edit Akun
                        </a>
                    <?php endif; ?>
                </div>

                <div class="content-card-body">
                    <?php if ($tab === 'list'): ?>
                        <!-- TAB: DAFTAR PENGGUNA -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Nama / Kelas</th>
                                        <th>Username</th>
                                        <th>Role / Level</th>
                                        <th>Status Akun</th>
                                        <th style="text-align: right; width: 140px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($q_users) == 0): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data pengguna sistem.</td></tr>
                                    <?php else: ?>
                                        <?php $no = 1; while ($u = mysqli_fetch_assoc($q_users)): 
                                            $is_aktif = ($u['status_akun'] === 'Aktif');
                                            $initial = strtoupper(substr($u['nama'], 0, 1));
                                        ?>
                                            <tr>
                                                <td style="font-weight: 600; color: var(--text-muted);"><?= $no++; ?></td>
                                                <td>
                                                    <div class="user-avatar-cell">
                                                        <div class="user-avatar-initial"><?= $initial; ?></div>
                                                        <div>
                                                            <div style="font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars($u['nama']); ?></div>
                                                            <div style="font-size: 11px; color: var(--text-muted);">ID: #<?= $u['id']; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code style="background: var(--bg-canvas); padding: 3px 8px; border-radius: 6px; color: var(--primary-blue); font-size: 12px;"><?= htmlspecialchars($u['username']); ?></code>
                                                </td>
                                                <td>
                                                    <span class="badge-role">
                                                        <?= ucfirst($u['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11.5px; background: <?= $is_aktif ? '#d1e7dd' : '#f8d7da'; ?>; color: <?= $is_aktif ? '#0f5132' : '#842029'; ?>;">
                                                        <i class="bi bi-circle-fill" style="font-size: 6px; vertical-align: middle; margin-right: 4px;"></i> <?= htmlspecialchars($u['status_akun']); ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: right;">
                                                    <a href="user.php?tab=edit&id=<?= $u['id']; ?>" class="btn btn-sm btn-light border text-muted px-2 py-1" title="Ubah Akun" style="border-radius: 8px;">
                                                        <i class="bi bi-pencil-square text-primary"></i>
                                                    </a>
                                                    <?php if($u['id'] != $_SESSION['id']): ?>
                                                        <a href="user.php?hapus=<?= $u['id']; ?>" class="btn btn-sm btn-light border text-danger px-2 py-1" style="border-radius: 8px;" onclick="return confirm('Yakin ingin menghapus pengguna ini?')" title="Hapus Akun">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($tab === 'tambah'): ?>
                        <!-- TAB: TAMBAH PENGGUNA -->
                        <div class="auth-form-card">
                            <div class="auth-form-header">
                                <div class="icon-box">
                                    <i class="bi bi-person-plus-fill"></i>
                                </div>
                                <h3>Tambah Akun Baru</h3>
                                <p>Daftarkan akun peminjam, petugas, atau admin baru ke sistem.</p>
                            </div>

                            <form method="POST" action="user.php?tab=tambah">
                                <input type="hidden" name="aksi" value="tambah">
                                
                                <div class="mb-3">
                                    <label class="form-label-custom">Nama / Kelas</label>
                                    <input type="text" class="form-control" name="nama" required placeholder="Contoh: XII RPL 1 / Budi" autocomplete="off">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-custom">Username</label>
                                    <input type="text" class="form-control" name="username" required placeholder="Username login" autocomplete="off">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-custom">Password</label>
                                    <input type="password" class="form-control" name="password" required placeholder="••••••••">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-custom">Role / Level Akses</label>
                                    <select class="form-select" name="role">
                                        <option value="peminjam">Peminjam</option>
                                        <option value="petugas">Petugas</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label-custom">Status Akun</label>
                                    <select class="form-select" name="status_akun">
                                        <option value="Aktif">Aktif</option>
                                        <option value="Dibekukan">Dibekukan</option>
                                    </select>
                                </div>

                                <div class="d-flex flex-column gap-2">
                                    <button type="submit" class="btn-primary-custom"><i class="bi bi-save me-1"></i> Simpan Akun Baru</button>
                                    <a href="user.php?tab=list" class="btn-light-custom">Batal</a>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($tab === 'edit'): ?>
                        <!-- TAB: EDIT PENGGUNA -->
                        <div class="auth-form-card">
                            <div class="auth-form-header">
                                <div class="icon-box">
                                    <i class="bi bi-pencil-square"></i>
                                </div>
                                <h3>Edit Akun Pengguna</h3>
                                <p>Perbarui informasi akun <strong><?= htmlspecialchars($data_edit['nama']); ?></strong>.</p>
                            </div>

                            <form method="POST" action="user.php?tab=edit&id=<?= $data_edit['id']; ?>">
                                <input type="hidden" name="aksi" value="edit">
                                <input type="hidden" name="id" value="<?= $data_edit['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label-custom">Nama / Kelas</label>
                                    <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($data_edit['nama']); ?>" required autocomplete="off">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-custom">Username</label>
                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($data_edit['username']); ?>" required autocomplete="off">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-custom">Password Baru <span style="font-weight: normal; color: var(--text-muted); font-size: 11px;">(Opsional)</span></label>
                                    <input type="password" class="form-control" name="password" placeholder="Biarkan kosong jika tidak diubah">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-custom">Role / Level Akses</label>
                                    <select class="form-select" name="role">
                                        <option value="peminjam" <?= ($data_edit['role'] === 'peminjam') ? 'selected' : ''; ?>>Peminjam</option>
                                        <option value="petugas" <?= ($data_edit['role'] === 'petugas') ? 'selected' : ''; ?>>Petugas</option>
                                        <option value="admin" <?= ($data_edit['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label-custom">Status Akun</label>
                                    <select class="form-select" name="status_akun">
                                        <option value="Aktif" <?= ($data_edit['status_akun'] === 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="Dibekukan" <?= ($data_edit['status_akun'] === 'Dibekukan') ? 'selected' : ''; ?>>Dibekukan</option>
                                    </select>
                                </div>

                                <div class="d-flex flex-column gap-2">
                                    <button type="submit" class="btn-primary-custom"><i class="bi bi-check2-circle me-1"></i> Simpan Perubahan</button>
                                    <a href="user.php?tab=list" class="btn-light-custom">Batal</a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('mainWrapper');
        const toggleBtn = document.getElementById('sidebarToggle');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainWrapper.classList.toggle('expanded');
        });
    </script>
</body>
</html>