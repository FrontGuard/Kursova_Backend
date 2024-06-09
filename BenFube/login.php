<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Підготовка запиту для вибірки користувача з бази даних
    $stmt = $conn->prepare("SELECT id, username, password, role, blocked_until FROM users WHERE username = ?");
    if ($stmt === false) {
        // Логування помилки підготовки запиту
        error_log("Помилка підготовки запиту: " . $conn->error);
        $error = "Помилка сервера. Спробуйте пізніше.";
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $current_time = date('Y-m-d H:i:s');
            if ($user['blocked_until'] && $user['blocked_until'] > $current_time) {
                $blocked_until = date('d.m.Y H:i', strtotime($user['blocked_until']));
                $error = "Ваш акаунт заблоковано до $blocked_until";
            } elseif (password_verify($password, $user['password'])) {
                // Встановлення сесійних змінних
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: index.php");
                exit;
            } else {
                // Невірний пароль
                $error = "Невірне ім'я користувача або пароль";
            }
        } else {
            // Невірне ім'я користувача
            $error = "Невірне ім'я користувача або пароль";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Увійти</title>
</head>
<body>
<h1>Увійти</h1>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <label for="username">Ім'я користувача:</label>
    <input type="text" id="username" name="username" required><br>
    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required><br>
    <button type="submit">Увійти</button>
</form>
<p>Не маєте облікового запису? <a href="register.php">Зареєструватися</a></p>
<?php if (isset($error)) echo "<p>$error</p>"; ?>
</body>
</html>
