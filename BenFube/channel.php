<?php
session_start();
include 'config.php';

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $channel_user_id = $_GET['user_id'];

    // Отримання інформації про користувача
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $channel_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $channel_user = $result->fetch_assoc();
    } else {
        echo "Користувача не знайдено.";
        exit;
    }

    // Отримання відео користувача
    $stmt = $conn->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $channel_user_id);
    $stmt->execute();
    $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    echo "Не вказано ID користувача.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Канал <?php echo htmlspecialchars($channel_user['username']); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Канал <?php echo htmlspecialchars($channel_user['username']); ?></h1>
    <div class="videos">
        <?php if ($videos): ?>
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <h3><a href="video_view_comments.php?id=<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title']); ?></a></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                        <video width="320" height="280" controls>
                            <source src="<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
                            Ваш браузер не підтримує відтворення відео.
                        </video>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Відео не знайдено.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
