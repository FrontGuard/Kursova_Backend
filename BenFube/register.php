<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Зареєструватися</title>
</head>
<body>
<h1>Зареєструватися</h1>
<form action="register_process.php" method="post">
    <label for="username">Ім'я користувача:</label>
    <input type="text" id="username" name="username" required><br>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required><br>
    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required><br>
    <button type="submit">Зареєструватися</button>
</form>
<p>Вже маєте обліковий запис? <a href="login.php">Увійти</a></p>
</body>
</html>
