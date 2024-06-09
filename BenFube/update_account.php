<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Отримання інформації про користувача
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Отримання кількості відео, завантажених користувачем
$stmt = $conn->prepare("SELECT COUNT(*) AS video_count FROM videos WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$video_count = $stmt->get_result()->fetch_assoc()['video_count'];
$stmt->close();

// Отримання загальної кількості переглядів на відео, завантажені користувачем
$stmt = $conn->prepare("SELECT COUNT(*) AS view_count FROM video_views JOIN videos ON video_views.video_id = videos.id WHERE videos.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$view_count = $stmt->get_result()->fetch_assoc()['view_count'];
$stmt->close();

// Отримання загальної кількості лайків на відео, завантажені користувачем
$stmt = $conn->prepare("SELECT COUNT(*) AS like_count FROM video_likes JOIN videos ON video_likes.video_id = videos.id WHERE videos.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$like_count = $stmt->get_result()->fetch_assoc()['like_count'];
$stmt->close();

// Отримання відео, завантажених користувачем
$stmt = $conn->prepare("SELECT videos.*, 
    (SELECT COUNT(*) FROM video_views WHERE video_views.video_id = videos.id) AS view_count,
    (SELECT COUNT(*) FROM video_likes WHERE video_likes.video_id = videos.id) AS like_count
    FROM videos WHERE user_id = ? ORDER BY videos.id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оновлення акаунту</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Ласкаво просимо, <?php echo htmlspecialchars($user['username'] ?? ''); ?></h1>
    <p>Email: <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>

    <h2>Редагування акаунту</h2>
    <form action="update_account_process.php" method="POST">
        <label for="username">Ім'я користувача:</label><br>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"><br>

        <input type="submit" value="Зберегти зміни">
    </form>

    <h2>Статистика</h2>
    <p>Кількість завантажених відео: <?php echo $video_count ?? ''; ?></p>
    <p>Загальна кількість переглядів: <?php echo $view_count ?? ''; ?></p>
    <p>Загальна кількість лайків: <?php echo $like_count ?? ''; ?></p>

    <h2>Ваші відео</h2>
    <div class="videos">
        <?php if ($user_videos): ?>
            <?php foreach ($user_videos as $video): ?>
                <div class="video-card">
                    <h3><a href="video_view_comments.php?id=<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title'] ?? ''); ?></a></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'] ?? '')); ?></p>
                    <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                        <img src="<?php echo htmlspecialchars($video['preview_image_path'] ?? 'default-preview.jpg'); ?>" alt="Preview" class="video-preview" width="320" height="240">
                    </a>
                    <p>Перегляди: <?php echo $video['view_count'] ?? ''; ?></p>
                    <p>Лайки: <?php echo $video['like_count'] ?? ''; ?></p>
                    <p>Дата завантаження: <?php echo htmlspecialchars($video['created_at'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Ви ще не завантажили жодного відео.</p>
        <?php endif; ?>
    </div>

    <a href="upload.php" class="button">Завантажити нове відео</a>
    <a href="history.php" class="button">Переглянути історію переглядів</a>
    <a href="logout.php" class="button">Вийти</a>
</div>
</body>
</html>
