<?php
session_start();
include 'config.php';

// Перевірка, чи користувач адміністратор
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Отримання списку користувачів
$stmt = $conn->prepare("SELECT id, username, role, blocked_until FROM users");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Блокування користувача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['block_duration'])) {
    $user_id = $_POST['user_id'];
    $block_duration = (int)$_POST['block_duration'];

    // Обчислення дати і часу блокування
    $blocked_until = date('Y-m-d H:i:s', strtotime("+$block_duration days"));

    $stmt = $conn->prepare("UPDATE users SET blocked_until = ? WHERE id = ?");
    $stmt->bind_param("si", $blocked_until, $user_id);
    if ($stmt->execute()) {
        echo "Користувач успішно заблокований до $blocked_until.";
    } else {
        echo "Помилка при блокуванні користувача.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління користувачами</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Управління користувачами</h1>
    <div class="users">
        <?php if ($users): ?>
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <p>Ім'я користувача: <?php echo htmlspecialchars($user['username']); ?></p>
                    <p>Роль: <?php echo htmlspecialchars($user['role']); ?></p>
                    <p>Заблоковано до: <?php echo $user['blocked_until'] ? htmlspecialchars($user['blocked_until']) : 'Не заблоковано'; ?></p>
                    <form action="manage_users.php" method="post">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <label for="block_duration">Тривалість блокування (днів):</label>
                        <input type="number" name="block_duration" id="block_duration" min="1" required>
                        <button type="submit">Заблокувати</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Користувачів не знайдено.</p>
        <?php endif; ?>
    </div>
    <a href="account.php" class="button">Назад до облікового запису</a>
</div>
</body>
</html>
