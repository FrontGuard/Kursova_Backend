<?php
session_start();

include 'config.php';

// Отримання ID відео з запиту GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $video_id = $_GET['id'];

    // Отримання інформації про відео з бази даних
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $video = $result->fetch_assoc();
    } else {
        echo "Відео не знайдено.";
        exit;
    }
} else {
    echo "Не вказано ID відео.";
    exit;
}

// Перевірка, чи користувач увійшов в систему
$logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Перегляд відео</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
<h1><?php echo htmlspecialchars($video['title']); ?></h1>
<p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
<video width="640" height="480" controls>
    <source src="<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
    Ваш браузер не підтримує відтворення відео.
</video>

<?php if ($logged_in): ?>
    <button id="likeBtn" data-id="<?php echo $video_id; ?>">Лайк (<?php echo $video['likes']; ?>)</button>
<?php else: ?>
    <p>Будь ласка, <a href="login.php">увійдіть</a> щоб поставити лайк.</p>
<?php endif; ?>

<h2>Коментарі</h2>
<?php if ($logged_in): ?>
    <form id="commentForm">
        <textarea name="comment" rows="4" cols="50" required></textarea><br>
        <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
        <button type="submit">Опублікувати коментар</button>
    </form>
<?php else: ?>
    <p>Будь ласка, <a href="login.php">увійдіть</a> щоб залишити коментар.</p>
<?php endif; ?>

<div id="comments">
    <?php
    // Отримання коментарів з бази даних
    $stmt = $conn->prepare("SELECT comments.id, comments.comment, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.video_id = ? ORDER BY comments.created_at DESC");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $comments_result = $stmt->get_result();

    if ($comments_result->num_rows > 0) {
        while ($comment = $comments_result->fetch_assoc()) {
            echo "<p><strong>" . htmlspecialchars($comment['username']) . ":</strong> " . nl2br(htmlspecialchars($comment['comment'])) . "</p>";
        }
    } else {
        echo "<p>Коментарів немає. Будьте першим!</p>";
    }

    $stmt->close();
    ?>
</div>

<script>
    $(document).ready(function() {
        $('#commentForm').submit(function(event) {
            event.preventDefault();
            $.ajax({
                url: 'like_comment.php',
                method: 'POST',
                data: {
                    action: 'add_comment',
                    comment: $('textarea[name="comment"]').val(),
                    video_id: $('input[name="video_id"]').val()
                },
                success: function(response) {
                    $('#comments').load('video_view_comments.php?id=<?php echo $video_id; ?> #comments > *');
                    $('textarea[name="comment"]').val('');
                }
            });
        });

        $('#likeBtn').click(function() {
            var video_id = $(this).data('id');
            $.ajax({
                url: 'like_comment.php',
                method: 'POST',
                data: {
                    action: 'like_video',
                    video_id: video_id
                },
                success: function(response) {
                    if (response.includes('Лайк успішно доданий')) {
                        var likeCount = parseInt($('#likeBtn').text().match(/\d+/)) + 1;
                        $('#likeBtn').text('Лайк (' + likeCount + ')');
                    } else {
                        alert(response);
                    }
                },
                error: function() {
                    alert('Помилка при спробі додати лайк.');
                }
            });
        });
    });
</script>

</body>
</html>
