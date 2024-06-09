<?php
session_start();
include 'config.php';

// Перевірка наявності ID відео в запиті
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $video_id = $_GET['id'];

    // Отримання інформації про відео з бази даних
    $stmt = $conn->prepare("SELECT videos.*, users.username AS author FROM videos JOIN users ON videos.user_id = users.id WHERE videos.id = ?");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $video = $result->fetch_assoc();
    } else {
        echo "Відео не знайдено.";
        exit;
    }

    // Збереження перегляду в базу даних
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO video_views (user_id, video_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $video_id);
        $stmt->execute();
        $stmt->close();
    }
} else {
    echo "Не вказано ID відео.";
    exit;
}

// Перевірка, чи користувач увійшов у систему
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
<p>Автор: <a href="channel.php?user_id=<?php echo $video['user_id']; ?>"><?php echo htmlspecialchars($video['author']); ?></a></p>

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
            echo "<div class='comment'>";
            echo "<p><strong>" . htmlspecialchars($comment['username']) . ":</strong> " . nl2br(htmlspecialchars($comment['comment'])) . "</p>";
            echo "<div class='replies' data-comment-id='" . $comment['id'] . "'>";

            // Отримання відповідей на коментарі
            $stmt_replies = $conn->prepare("SELECT comment_replies.reply_text, comment_replies.created_at, users.username 
                                            FROM comment_replies 
                                            JOIN users ON comment_replies.user_id = users.id 
                                            WHERE comment_replies.comment_id = ? 
                                            ORDER BY comment_replies.created_at ASC");
            $stmt_replies->bind_param("i", $comment['id']);
            $stmt_replies->execute();
            $replies_result = $stmt_replies->get_result();

            if ($replies_result->num_rows > 0) {
                while ($reply = $replies_result->fetch_assoc()) {
                    echo "<div class='reply'>";
                    echo "<p><strong>" . htmlspecialchars($reply['username']) . ":</strong> " . nl2br(htmlspecialchars($reply['reply_text'])) . "</p>";
                    echo "</div>";
                }
            }

            if ($logged_in) {
                echo "<form class='replyForm' data-comment-id='" . $comment['id'] . "'>";
                echo "<textarea name='reply' rows='2' cols='50' required></textarea><br>";
                echo "<button type='submit'>Відповісти</button>";
                echo "</form>";
            }

            echo "</div>"; // .replies
            echo "</div>"; // .comment
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

        $(document).on('submit', '.replyForm', function(event) {
            event.preventDefault();
            var form = $(this);
            $.ajax({
                url: 'like_comment.php',
                method: 'POST',
                data: {
                    action: 'add_reply',
                    reply: form.find('textarea[name="reply"]').val(),
                    comment_id: form.data('comment-id')
                },
                success: function(response) {
                    $('#comments').load('video_view_comments.php?id=<?php echo $video_id; ?> #comments > *');
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
