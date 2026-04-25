<?php
require_once 'config/koneksi.php';

echo "<h2>Debug Gambar Products</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Nama</th><th>Filename di DB</th><th>File Exists?</th><th>Full Path</th><th>Preview</th></tr>";

$result = mysqli_query($conn, "SELECT id, nama_barang, gambar FROM products LIMIT 10");

while ($row = mysqli_fetch_assoc($result)) {
    $full_path = __DIR__ . '/uploads/' . $row['gambar'];
    $exists = file_exists($full_path);
    $url = $exists ? 'uploads/' . $row['gambar'] : 'https://via.placeholder.com/100?text=No+Image';

    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['nama_barang'] . "</td>";
    echo "<td>" . ($row['gambar'] ?? '<em>NULL</em>') . "</td>";
    echo "<td style='color: " . ($exists ? 'green' : 'red') . "; font-weight: bold;'>";
    echo $exists ? '✅ YES' : '❌ NO';
    echo "</td>";
    echo "<td style='font-size: 12px;'>" . $full_path . "</td>";
    echo "<td><img src='$url' style='width: 100px; height: 100px; object-fit: contain;'></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3 style='margin-top: 40px;'>Uploads Folder Info:</h3>";
$uploads_dir = __DIR__ . '/uploads';
if (is_dir($uploads_dir)) {
    echo "<p>✅ Folder uploads exists</p>";
    $files = scandir($uploads_dir);
    echo "<p>Total files: " . count(array_diff($files, ['.', '..'])) . "</p>";
    echo "<pre>";
    print_r(array_diff($files, ['.', '..']));
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Folder uploads TIDAK ada!</p>";
    echo "<p>Buat folder 'uploads' di: " . $uploads_dir . "</p>";
}
?>