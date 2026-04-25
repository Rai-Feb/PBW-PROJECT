<?php
require_once 'config/koneksi.php';

$result = mysqli_query($conn, "SELECT id, nama_barang, gambar FROM products LIMIT 5");

echo "<h2>Check Upload Images</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nama</th><th>Filename di DB</th><th>File Exists?</th><th>Full Path</th><th>Image</th></tr>";

while($row = mysqli_fetch_assoc($result)) {
    $full_path = __DIR__ . '/uploads/' . $row['gambar'];
    $exists = file_exists($full_path);
    $url = $exists ? 'uploads/' . $row['gambar'] : 'https://via.placeholder.com/100?text=No+Image';
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['nama_barang'] . "</td>";
    echo "<td>" . $row['gambar'] . "</td>";
    echo "<td>" . ($exists ? '✅ YES' : '❌ NO') . "</td>";
    echo "<td>" . $full_path . "</td>";
    echo "<td><img src='$url' width='100'></td>";
    echo "</tr>";
}
echo "</table>";
?>