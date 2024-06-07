<?php
session_start();

include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    echo "Користувач не увійшов в систему. Завантаження відео неможливе.";
    exit;
}

$title = $_POST['title'];
$description = $_POST['description'];
$video = $_FILES['video'];

// Перевірка на помилки завантаження
if ($video['error'] === UPLOAD_ERR_OK) {
    $uploads_dir = 'uploads';

    // Перевірка наявності директорії і її створення при відсутності
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }

    $video_path = $uploads_dir . '/' . basename($video['name']);
    if (move_uploaded_file($video['tmp_name'], $video_path)) {
        $user_id = $_SESSION['user_id']; // Отримання ID авторизованого користувача

        $stmt = $conn->prepare("INSERT INTO videos (title, description, video_path, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $description, $video_path, $user_id);

        if ($stmt->execute()) {
            echo "Відео успішно завантажено!";
        } else {
            echo "Помилка: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Помилка переміщення завантаженого файлу.";
    }
} else {
    echo "Помилка завантаження відео: " . $video['error'];
}

$conn->close();
?>