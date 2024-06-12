<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

function updateEmail($conn, $user_id, $new_email) {
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $new_email, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $new_email = $_POST['email'];

        if (updateEmail($conn, $user_id, $new_email)) {
            $_SESSION['email'] = $new_email;
            header("Location: account.php");
            exit;
        } else {
            echo "Помилка при оновленні електронної пошти. Будь ласка, спробуйте знову.";
        }
    } else {
        echo "Необхідні дані не надійшли.";
    }
} else {
    echo "Недопустимий метод запиту.";
}

$conn->close();
?>
