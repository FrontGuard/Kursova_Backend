<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Отримуємо роль користувача

// Отримання всіх тегів
$result = $conn->query("SELECT * FROM tags");
$tags = $result->fetch_all(MYSQLI_ASSOC);

// Фільтрація відео за тегами
$selected_tag_id = isset($_GET['tag_id']) ? $_GET['tag_id'] : null;
if ($selected_tag_id) {
    // Отримати відео з обраним тегом
    $stmt_with_tag = $conn->prepare("SELECT DISTINCT videos.* FROM videos 
                            JOIN video_tags ON videos.id = video_tags.video_id 
                            WHERE video_tags.tag_id = ?");
    $stmt_with_tag->bind_param("i", $selected_tag_id);
    $stmt_with_tag->execute();
    $videos_with_tag = $stmt_with_tag->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_with_tag->close();

    // Отримати відео без обраного тегу
    $stmt_without_tag = $conn->prepare("SELECT * FROM videos WHERE id NOT IN 
                            (SELECT video_id FROM video_tags WHERE tag_id = ?)");
    $stmt_without_tag->bind_param("i", $selected_tag_id);
    $stmt_without_tag->execute();
    $videos_without_tag = $stmt_without_tag->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_without_tag->close();

    // Об'єднати результати
    $videos = array_merge($videos_with_tag, $videos_without_tag);
} else {
    // Якщо тег не обрано, відображаємо всі відео
    $result = $conn->query("SELECT * FROM videos ORDER BY id DESC");
    $videos = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Головна сторінка</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Відео Платформа</h1>
        <div class="user-actions">
            <h1>Вітаємо, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>Це головна сторінка вашого акаунту.</p>

            <?php if ($user_role === 'admin'): ?>
                <a href="manage_users.php" class="button">Управління користувачами</a>
            <?php endif; ?>

            <a href="manage_videos.php" class="button">Управління відео</a>
            <a href="logout.php" class="button">Вийти</a>
            <a href="history.php" class="button">Історія переглядів</a>
            <a href="upload.php" class="button">Завантажити відео</a>
        </div>
    </div>

    <!-- Плашка з тегами -->
    <div class="tags">
        <h2>Теги</h2>
        <?php foreach ($tags as $tag): ?>
            <a href="index.php?tag_id=<?php echo $tag['id']; ?>" class="tag"><?php echo htmlspecialchars($tag['name']); ?></a>
        <?php endforeach; ?>
        <a href="index.php" class="tag">Всі відео</a>
    </div>

    <!-- Відображення відео -->
    <div class="videos">
        <?php if ($videos): ?>
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <h3><a href="video_view_comments.php?id=<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title']); ?></a></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>

                    <!-- Додавання прев'ю -->
                    <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                        <img src="<?php echo $video['preview_image_path']; ?>" alt="Preview" class="video-preview" width="320" height="280">
                    </a>

                    <?php
                    // Отримання тегів для відео
                    $stmt = $conn->prepare("SELECT tags.name FROM tags 
                                            JOIN video_tags ON tags.id = video_tags.tag_id 
                                            WHERE video_tags.video_id = ?");
                    $stmt->bind_param("i", $video['id']);
                    $stmt->execute();
                    $video_tags_result = $stmt->get_result();
                    $stmt->close();

                    // Виведення тегів
                    if ($video_tags_result->num_rows > 0) {
                        echo "<div class='video-tags'><strong>Теги:</strong>";
                        while ($tag_row = $video_tags_result->fetch_assoc()) {
                            echo "<span class='tag'>" . htmlspecialchars($tag_row['name']) . "</span>";
                        }
                        echo "</div>";
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Відео не знайдено.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
