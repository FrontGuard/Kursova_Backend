<?php
$servername = "localhost";
$username = "root"; // замініть на вашого користувача бази даних
$password = ""; // замініть на ваш пароль бази даних
$dbname = "video_hosting";

// Створення підключення
$conn = new mysqli($servername, $username, $password, $dbname);

// Перевірка підключення
if ($conn->connect_error) {
    die("Підключення не вдалось: " . $conn->connect_error);
}
?>
