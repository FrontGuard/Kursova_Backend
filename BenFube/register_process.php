<?php
include 'config.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Хешування пароля

$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    echo "Ви успішно зареєстровані. Будь ласка, <a href='login.php'>увійдіть</a>.";
} else {
    echo "Помилка: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
