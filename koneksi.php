<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_sarpras_sekolah";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>