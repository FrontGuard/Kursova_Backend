<?php
session_start();
include 'config.php';

// Отримання всіх тегів
$result = $conn->query("SELECT * FROM tags");
$tags = $result->fetch_all(MYSQLI_ASSOC);

// Фільтрація відео за тегами
$selected_tag_id = isset($_GET['tag_id']) ? $_GET['tag_id'] : null;
if ($selected_tag_id) {
    $stmt = $conn->prepare("SELECT videos.* FROM videos 
                            JOIN video_tags ON videos.id = video_tags.video_id 
                            WHERE video_tags.tag_id = ? 
                            UNION 
                            SELECT videos.* FROM videos 
                            WHERE videos.id NOT IN (SELECT video_id FROM video_tags WHERE tag_id = ?) 
                            ORDER BY videos.id DESC");
    $stmt->bind_param("ii", $selected_tag_id, $selected_tag_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM videos ORDER BY id DESC");
}
$stmt->execute();
$videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="account.php" class="button">Акаунт</a>
                <a href="logout.php" class="button">Вихід</a>
            <?php else: ?>
                <a href="login.php" class="button">Увійти</a>
                <a href="register.php" class="button">Зареєструватися</a>
            <?php endif; ?>
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
                    <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                        <video width="320" height="240" controls>
                            <source src="<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
                            Ваш браузер не підтримує відтворення відео.
                        </video>
                    </a>
                    <div class="video-actions">
                        <button class="like-button" data-video-id="<?php echo $video['id']; ?>">Лайк</button>
                        <span class="like-count"><?php echo $video['likes']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Відео не знайдено.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.like-button').on('click', function() {
            var button = $(this);
            var videoId = button.data('video-id');

            $.ajax({
                url: 'like_comment.php',
                type: 'POST',
                data: { video_id: videoId },
                success: function(response) {
                    var likeCountSpan = button.siblings('.like-count');
                    var newLikeCount = parseInt(likeCountSpan.text()) + 1;
                    likeCountSpan.text(newLikeCount);
                    button.prop('disabled', true);
                }
            });
        });

        $('form.comment-form').on('submit', function(event) {
            event.preventDefault();
            var form = $(this);
            var videoId = form.find('input[name="video_id"]').val();
            var comment = form.find('textarea[name="comment"]').val();

            $.ajax({
                url: 'like_comment.php',
                type: 'POST',
                data: { video_id: videoId, comment: comment },
                success: function(response) {
                    form.find('textarea[name="comment"]').val('');
                    form.after('<p><strong>' + response.username + ':</strong> ' + response.comment + '</p>');
                }
            });
        });
    });
</script>
</body>
</html>
