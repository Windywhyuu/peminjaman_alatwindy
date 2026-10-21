<?php
session_start();
include '../koneksi.php';

/* =========================
   CEK LOGIN (ADMIN / PETUGAS)
========================= */
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'petugas' && $_SESSION['role'] != 'admin')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user              = intval($_POST['id_user']);
    $nama_peminjam_detail = mysqli_real_escape_string($koneksi, $_POST['nama_peminjam_detail']);
    $jenis_durasi         = mysqli_real_escape_string($koneksi, $_POST['jenis_durasi']);
    $tgl_pinjam           = mysqli_real_escape_string($koneksi, $_POST['tgl_pinjam']);
    $tgl_kembali          = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
    $jam_pinjam           = mysqli_real_escape_string($koneksi, $_POST['jam_pinjam'] ?? '07:00');
    $jam_kembali          = mysqli_real_escape_string($koneksi, $_POST['jam_kembali'] ?? '15:00');
    $keperluan            = mysqli_real_escape_string($koneksi, $_POST['keperluan']);
    $status               = 'Disetujui'; // Langsung disetujui karena diinput oleh Admin/Petugas
    $items                = $_POST['items'] ?? [];

    if (empty($items)) {
        echo "<script>alert('Pilih minimal 1 barang atau ruangan!'); window.location='../admin/index.php';</script>";
        exit;
    }

    // Mulai Transaksi Database
    mysqli_begin_transaction($koneksi);

    try {
        // 1. Simpan ke tabel peminjaman (header) termasuk jam pinjam & kembali
        $query_peminjaman = "INSERT INTO peminjaman (id_user, nama_peminjam_detail, jenis_durasi, tgl_pinjam, tgl_kembali, jam_pinjam, jam_kembali, keperluan, status)  
                             VALUES ('$id_user', '$nama_peminjam_detail', '$jenis_durasi', '$tgl_pinjam', '$tgl_kembali', '$jam_pinjam', '$jam_kembali', '$keperluan', '$status')";
        
        if (!mysqli_query($koneksi, $query_peminjaman)) {
            throw new Exception("Gagal menyimpan data peminjaman utama.");
        }

        // Ambil ID peminjaman yang baru saja dimasukkan
        $id_peminjaman = mysqli_insert_id($koneksi);

        // 2. Simpan setiap item ke tabel detail_peminjaman
        foreach ($items as $item) {
            $id_sarpras = intval($item['id']);
            $jumlah     = intval($item['jumlah']);

            $query_detail = "INSERT INTO detail_peminjaman (id_peminjaman, id_sarpras, jumlah)  
                             VALUES ('$id_peminjaman', '$id_sarpras', '$jumlah')";
            
            if (!mysqli_query($koneksi, $query_detail)) {
                throw new Exception("Gagal menyimpan detail barang.");
            }
        }

        // 3. Catat ke log aktivitas dengan aman
        $nama_user = $_SESSION['nama'] ?? 'Administrator';
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas) VALUES ('$nama_user', 'Membuat booking peminjaman untuk: $nama_peminjam_detail')";
        @mysqli_query($koneksi, $log_query);

        // Commit transaksi jika semua berhasil
        mysqli_commit($koneksi);

        echo "<script>alert('Booking peminjaman berhasil disimpan!'); window.location='../admin/peminjaman.php';</script>";
        exit;

    } catch (Exception $e) {
        // Rollback jika terjadi kesalahan
        mysqli_rollback($koneksi);
        $err_msg = addslashes($e->getMessage());
        echo "<script>alert('Terjadi kesalahan: " . $err_msg . "'); window.location='../admin/index.php';</script>";
        exit;
    }
} else {
    header("Location: ../admin/index.php");
    exit;
}
?>