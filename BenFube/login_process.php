<?php
// register_process.php
include 'config.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Хешування паролю

$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    // Успішно зареєстровано
    echo "Ви успішно зареєстровані. Будь ласка, <a href='login.php'>увійдіть</a>.";
} else {
    // Помилка реєстрації
    echo "Помилка: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>
