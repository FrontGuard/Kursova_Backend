<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка, чи надійшли необхідні дані з форми
    if (isset($_POST['username']) && isset($_POST['email'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];

        // Підготовка та виконання запиту на оновлення даних користувача
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);

        if ($stmt->execute()) {
            // Оновлення даних в сесії
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            header("Location: account.php");
            exit;
        } else {
            echo "Помилка при оновленні даних. Будь ласка, спробуйте знову.";
        }

        $stmt->close();
    } else {
        echo "Необхідні дані не надійшли.";
    }
} else {
    echo "Недопустимий метод запиту.";
}

$conn->close();
?>
