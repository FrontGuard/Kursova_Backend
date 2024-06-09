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
// Перевірка, чи користувач є адміністратором
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Використання функції isAdmin для захисту сторінок
function checkAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit;
    }
}
?>
