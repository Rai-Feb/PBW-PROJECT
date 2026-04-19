<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_elektronik";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Database Gagal");
}
?>