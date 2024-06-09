<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов у систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Отримання історії переглядів для користувача
$stmt = $conn->prepare("SELECT videos.*, DATE(video_views.viewed_at) as viewed_date FROM videos 
                        JOIN video_views ON videos.id = video_views.video_id 
                        WHERE video_views.user_id = ? 
                        ORDER BY video_views.viewed_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Групування відео за датою перегляду
$grouped_videos = [];
foreach ($videos as $video) {
    $date = $video['viewed_date'];
    if (!isset($grouped_videos[$date])) {
        $grouped_videos[$date] = [];
    }
    $grouped_videos[$date][] = $video;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Історія переглядів</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Історія переглядів</h1>
        <div class="user-actions">
            <a href="index.php" class="button">На головну</a>
            <a href="logout.php" class="button">Вихід</a>
        </div>
    </div>

    <!-- Відображення відео -->
    <div class="videos">
        <?php if ($grouped_videos): ?>
            <?php foreach ($grouped_videos as $date => $videos_on_date): ?>
                <h2><?php echo htmlspecialchars($date); ?></h2>
                <?php foreach ($videos_on_date as $video): ?>
                    <div class="video-card">
                        <h3><a href="video_view_comments.php?id=<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title']); ?></a></h3>
                        <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                        <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                            <!-- Відображення прев'ю -->
                            <img src="<?php echo htmlspecialchars($video['preview_image_path'] ?? 'default-preview.jpg'); ?>" alt="Preview" class="video-preview" Width="320" height="240">
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
            <?php endforeach; ?>
        <?php else: ?>
            <p>Історія переглядів порожня.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
