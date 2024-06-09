<?php
session_start();
include 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Отримуємо роль користувача

// Отримання відео
if ($user_role === 'admin') {
    // Якщо адміністратор, отримуємо всі відео
    $stmt = $conn->prepare("SELECT videos.*, users.username FROM videos JOIN users ON videos.user_id = users.id");
    $stmt->execute();
    $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Якщо звичайний користувач, отримуємо лише його відео
    $stmt = $conn->prepare("SELECT * FROM videos WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління відео</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Ваші відео</h1>
    <div class="videos">
        <?php if ($videos): ?>
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    <?php if ($user_role === 'admin'): ?>
                        <p>Завантажено: <?php echo htmlspecialchars($video['username']); ?></p>
                    <?php endif; ?>
                    <a href="edit_video.php?id=<?php echo htmlspecialchars($video['id']); ?>" class="button">Редагувати</a>
                    <a href="delete_video.php?id=<?php echo $video['id']; ?>" class="button">Видалити</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Ви ще не завантажили жодного відео.</p>
        <?php endif; ?>
    </div>
    <a href="account.php" class="button">Назад до облікового запису</a>
</div>
</body>
</html>
