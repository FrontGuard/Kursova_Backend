<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка, чи надійшли дані через AJAX
    if (isset($_POST['title']) && isset($_POST['description'])) {
        // Отримання значень полів "Назва відео" і "Опис відео" з AJAX-запиту
        $title = $_POST['title'];
        $description = $_POST['description'];

        // Отримання ідентифікатора відео
        $video_id = $_POST['video_id'];

        // Оновлення інформації про відео в базі даних
        $stmt = $conn->prepare("UPDATE videos SET title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $description, $video_id);

        if ($stmt->execute()) {
            // Повернення успішного статусу до клієнта
            echo "Дані про відео успішно оновлено.";
        } else {
            // Повернення помилки до клієнта
            echo "Помилка при оновленні даних відео.";
        }

        $stmt->close();
    } else {
        // Повернення помилки до клієнта, якщо не надійшли необхідні дані
        echo "Необхідні дані не надійшли.";
    }
} else {
    // Повернення помилки до клієнта, якщо запит не є POST-запитом
    echo "Невірний метод запиту.";
}

$conn->close();
?>
