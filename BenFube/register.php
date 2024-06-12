<?php
session_start();
include 'config.php';

// Перевірка, чи була відправлена форма реєстрації
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Перевірка на наявність користувача з таким самим нікнеймом
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error_message = "Користувач з таким нікнеймом уже зареєстрований.";
    } else {
        // Перевірка на наявність користувача з такою самою електронною поштою
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Користувач з такою електронною поштою уже зареєстрований.";
        } else {
            // Додавання нового користувача в базу даних
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';
                header("Location: index.php");
                exit;
            } else {
                $error_message = "Виникла помилка під час реєстрації. Будь ласка, спробуйте пізніше.";
            }
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реєстрація</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>Реєстрація</h2>
    <?php
    if (isset($error_message)) {
        echo "<p class='error'>$error_message</p>";
    }
    ?>
    <form action="register.php" method="post">
        <label for="username">Нікнейм:</label>
        <input type="text" id="username" name="username" required>

        <label for="email">Електронна пошта:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Пароль:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Зареєструватися</button>
    </form>
    <p>Вже є акаунт? <a href="login.php">Увійти</a></p>
</div>
</body>
</html>
