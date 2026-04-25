<?php
$dir = 'assets/img/';
$files = scandir($dir);
foreach ($files as $f) {
    if (is_file($dir . $f))
        echo "<img src='$dir$f' width='100'>";
}
?>