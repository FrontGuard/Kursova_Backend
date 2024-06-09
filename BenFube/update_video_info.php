<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title']) && isset($_POST['description']) && isset($_SESSION['user_id'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $user_id = $_SESSION['user_id'];

        // Оновлення інформації про відео в базі даних
        $stmt = $conn->prepare("UPDATE videos SET title = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $title, $description, $video_id, $user_id);
        if ($stmt->execute()) {
            echo "Дані успішно оновлено.";
        } else {
            echo "Помилка при оновленні даних.";
        }
        $stmt->close();
    } else {
        echo "Невірні дані.";
    }
} else {
    echo "Невірний метод запиту.";
}

$conn->close();
?>
