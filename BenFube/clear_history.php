<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Видалення історії переглядів користувача
$stmt = $conn->prepare("DELETE FROM video_views WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo "Історію переглядів очищено.";
} else {
    echo "Помилка при очищенні історії.";
}

$stmt->close();
$conn->close();

header("Location: history.php"); // Перенаправлення назад на сторінку історії
exit;
?>
