<?php
session_start();

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'add_comment' && isset($_POST['comment']) && isset($_POST['video_id']) && isset($_SESSION['user_id'])) {
            $comment = $_POST['comment'];
            $video_id = $_POST['video_id'];
            $user_id = $_SESSION['user_id'];

            // Вставка коментаря в базу даних
            $stmt = $conn->prepare("INSERT INTO comments (video_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $video_id, $user_id, $comment);

            if ($stmt->execute()) {
                echo "Коментар успішно доданий.";
            } else {
                echo "Помилка при додаванні коментаря.";
            }

            $stmt->close();
        } elseif ($action == 'like_video' && isset($_POST['video_id']) && isset($_SESSION['user_id'])) {
            $video_id = $_POST['video_id'];
            $user_id = $_SESSION['user_id'];

            // Перевірка, чи вже є лайк від цього користувача для цього відео
            $stmt = $conn->prepare("SELECT * FROM video_likes WHERE video_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $video_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Вставка лайка в базу даних
                $stmt = $conn->prepare("INSERT INTO video_likes (video_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $video_id, $user_id);

                if ($stmt->execute()) {
                    // Оновлення кількості лайків у таблиці videos
                    $stmt = $conn->prepare("UPDATE videos SET likes = likes + 1 WHERE id = ?");
                    $stmt->bind_param("i", $video_id);
                    $stmt->execute();
                    echo "Лайк успішно доданий.";
                } else {
                    echo "Помилка при додаванні лайка.";
                }
            } else {
                echo "Ви вже лайкнули це відео.";
            }

            $stmt->close();
        } else {
            echo "Невірні дані.";
        }
    } else {
        echo "Невірний запит.";
    }
} else {
    echo "Невірний метод запиту.";
}

$conn->close();
?>
