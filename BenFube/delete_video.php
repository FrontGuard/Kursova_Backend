<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Перевірка, чи надійшло значення id відео
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_videos.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Отримуємо роль користувача
$video_id = $_GET['id'];

// Отримання відео для перевірки права на видалення
if ($user_role !== 'admin') {
    // Перевірка, чи відео належить користувачеві
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $video_id, $user_id);
} else {
    // Якщо адміністратор, отримуємо відео без перевірки користувача
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->bind_param("i", $video_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows != 1) {
    header("Location: manage_videos.php");
    exit;
}
$stmt->close();

// Видалення відео
$stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
$stmt->bind_param("i", $video_id);

if ($stmt->execute()) {
    header("Location: manage_videos.php");
    exit;
} else {
    echo "Помилка при видаленні відео. Будь ласка, спробуйте знову.";
}

$stmt->close();
?>
