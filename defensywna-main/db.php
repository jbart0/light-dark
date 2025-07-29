<?php
$conn = new mysqli("192.168.1.1", "f22305", "ItrjzveHI8", "db_f22305");

if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Ustawienie kodowania na UTF-8
$conn->set_charset("utf8");
?>