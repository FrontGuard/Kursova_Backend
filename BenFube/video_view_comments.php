<?php
session_start();
include 'config.php';

// Перевірка наявності ID відео в запиті
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $video_id = $_GET['id'];

    // Отримання інформації про відео з бази даних
    $stmt = $conn->prepare("SELECT videos.*, users.username AS author, videos.created_at, COUNT(video_views.id) AS views 
                            FROM videos 
                            JOIN users ON videos.user_id = users.id 
                            LEFT JOIN video_views ON videos.id = video_views.video_id 
                            WHERE videos.id = ?");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $video = $result->fetch_assoc();

        // Перевірка наявності колонки created_at
        $upload_time = isset($video['created_at']) ? $video['created_at'] : 'Недоступний';

    } else {
        echo "Відео не знайдено.";
        exit;
    }

    // Збереження перегляду в базу даних
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Перевірка, чи користувач переглядав відео протягом останніх 6 місяців
        $stmt = $conn->prepare("SELECT * FROM video_views WHERE user_id = ? AND video_id = ? AND viewed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)");
        $stmt->bind_param("ii", $user_id, $video_id);
        $stmt->execute();
        $view_result = $stmt->get_result();

        // Якщо користувач ще не переглядав відео протягом останніх 6 місяців, зберігаємо перегляд в базі даних
        if ($view_result->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO video_views (user_id, video_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $video_id);
            $stmt->execute();
            $stmt->close();
        }
    }
} else {
    echo "Не вказано ID відео.";
    exit;
}

// Перевірка, чи користувач увійшов у систему
$logged_in = isset($_SESSION['user_id']);
?>


<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Перегляд відео</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="lesheet" href="CSS/stylechannel.css">
</head>
<style>/* Загальні стилі */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f9f9f9;
    }



    /* Стилі для заголовків */
    h1 {
        margin-top: 90px;
        font-size: 2.5em;
        margin-bottom: 20px;
        background: linear-gradient(to right, #ff0000, #000000);
        color: transparent;
        -webkit-background-clip: text;
        background-clip: text;
    }

    h2 {
        font-size: 1.8em;
        color: #333;
    }

    /* Стилі для відео */
    video {
        width: 100%;
        height: auto;
        margin-bottom: 20px;
    }

    /* Стилі для відео карток */
    .video-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 20px;
        background-color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        z-index: 1; /* Додано зміну z-index */
    }

    .video-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        transform: translateY(-5px);
        z-index: 2; /* Збільшуємо z-index при наведенні, щоб прев'ю було наверху */
    }

    .video-card h3 {
        color: #000000;
        margin: 10px 0;
        font-size: 1.25em;
    }

    .video-card h3 a {
        color: #ff0000;
        text-decoration: none;
    }

    .video-card p {
        margin-bottom: 10px;
        color: #555;
        font-size: 0.9em;
    }

    .video-preview img {
        border-radius: 8px;
        width: 100%; /* Ширина 100% */
        height: auto; /* Автоматична висота */
    }

    /* Кнопки */
    .button {
        display: inline-block;
        padding: 10px 20px;
        background-color: #000000;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        cursor: pointer;
        margin-right: 10px;
        transition: background-color 0.3s ease;
    }

    .button:hover {
        background-color: #ff0000;
    }

    /* Стилі для хедера */
    header {
        position: fixed;
        top: 0;
        width: 100%;
        background-color: #62bd62;
        padding: 10px 0;
        text-align: center;
        z-index: 3;
    }

    /* Стилі для навігаційного списку */
    nav ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
        display: inline-block;
    }

    nav ul li {
        display: inline-block;
        margin-right: 10px;
    }

    /* Ваші стилі */
    .header {
        text-align: center;
        margin-bottom: 20px;
    }

    .header h1 {
        color: #333;
    }

    .user-actions {
        margin-bottom: 20px;
    }

    .user-actions a.button {
        margin-right: 10px;
    }

    .tags {
        margin-bottom: 20px;
    }

    .tags h2 {
        color: #333;
        margin-bottom: 10px;
    }

    .tag {
        display: inline-block;
        padding: 5px 10px;
        background-color: #ddd;
        color: #333;
        text-decoration: none;
        border-radius: 5px;
        margin-right: 5px;
    }

    .videos {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .video-card {
        width: calc(33.33% - 20px);
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .video-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        transform: translateY(-5px);
    }

    .video-card h3 {
        color: #333;
        margin: 10px 0;
        font-size: 1.25em;
    }

    .video-card p {
        color: #666;
        font-size: 0.9em;
    }

    .video-preview {
        margin-bottom: 10px;
    }

    .video-preview img {
        border-radius: 8px;
        width: 100%;
        height: auto;
    }

    .video-tags {
        margin-top: 10px;
    }

    .video-tags .tag {
        background-color: #ddd;
        color: #333;
        padding: 3px 8px;
        border-radius: 3px;
        margin-right: 5px;
        margin-bottom: 5px;
        display: inline-block;
    }

    form {
        max-width: 300px;
        margin: 0 auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    label {
        display: block;
        margin-bottom: 5px;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }

    button {
        width: 100%;
        padding: 10px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #0056b3;
    }

    p {
        text-align: left;
        margin-top: 20px;
    }

    a {
        color: #007bff;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    .error-message {
        color: #ff0000;
        text-align: center;
        margin-top: 20px;
    }

    .comment {
        margin-bottom: 20px;
        padding: 10px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow: hidden; /* Додано для уникнення переповнення */
    }

    .comment p {
        margin: 0;
    }

    .comment-time {
        font-size: 0.8em;
        color: #888;
    }

    textarea {
        width: 294px; /* Встановлює фіксовану ширину */
        height: 100px; /* Встановлює фіксовану висоту */
        resize: none; /* Забороняє змінювати розмір елементу */
    }
    .reply {
        margin-left: 20px;
        border-left: 2px solid #ccc;
        padding-left: 10px;
        overflow: hidden; /* Додано для уникнення переповнення */
    }

    .reply p {
        margin: 0;
    }

    .reply-time {
        font-size: 0.8em;
        color: #888;
    }

    /* Стилі для кнопки Лайк */
    #likeBtn {
        background-color: #ff0000;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 25px; /* Зробити кнопку овальною */
        cursor: pointer;
    }

    #likeBtn:hover {
        background-color: #cc0000;
    }
</style>
<body>
<header>
    <nav>
        <ul>
            <div><h1>BenFube</h1></div>
            <li><a href="index.php" class="button">Головна</a>
            <li><a href="account.php" class="button">Профіль</a>
            <li><a href="upload.php" class="button">Завантажити нове відео</a></li>
            <li><a href="history.php" class="button">Переглянути історію переглядів</a></li>
            <li><a href="manage_videos.php" class="button">Керувати відео</a></li>
            <li><a href="logout.php" class="button">Вийти</a>
        </ul>
    </nav>
</header>
<h1><?php echo htmlspecialchars($video['title']); ?></h1>

<video width="640" height="480" controls>
    <source src="<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
    Ваш браузер не підтримує відтворення відео.
</video>
<p>Кількість переглядів: <?php echo htmlspecialchars($video['views']); ?></p>
<p>Час завантаження: <?php echo htmlspecialchars($upload_time); ?></p>
<p>Опис:<?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
<?php if ($logged_in): ?>
    <button id="likeBtn" data-id="<?php echo $video_id; ?>">Лайк (<?php echo $video['likes']; ?>)</button>
<?php else: ?>
    <p>Будь ласка, <a href="login.php">увійдіть</a> щоб поставити лайк.</p>
<?php endif; ?>
<p>Автор: <a href="channel.php?user_id=<?php echo $video['user_id']; ?>"><?php echo htmlspecialchars($video['author']); ?></a></p>

<h2>Коментарі</h2>
<?php if ($logged_in): ?>
    <form id="commentForm">
        <textarea name="comment" rows="4" cols="40" required></textarea><br>
        <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
        <button type="submit">Опублікувати коментар</button>
    </form>
<?php else: ?>
    <p>Будь ласка, <a href="login.php">увійдіть</a> щоб залишити коментар.</p>
<?php endif; ?>

<div id="comments">
    <?php
    // Отримання коментарів з бази даних
    $stmt = $conn->prepare("SELECT comments.id, comments.comment, comments.created_at, users.username 
                            FROM comments 
                            JOIN users ON comments.user_id = users.id 
                            WHERE comments.video_id = ? 
                            ORDER BY comments.created_at DESC");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $comments_result = $stmt->get_result();

    if ($comments_result->num_rows > 0) {
        while ($comment = $comments_result->fetch_assoc()) {
            echo "<div class='comment'>";
            echo "<p><strong>" . htmlspecialchars($comment['username']) . ":</strong> " . nl2br(htmlspecialchars($comment['comment'])) . "</p>";
            echo "<p class='comment-time'>" . htmlspecialchars($comment['created_at']) . "</p>"; // Час коментаря
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
                    echo "<p class='reply-time'>" . htmlspecialchars($reply['created_at']) . "</p>"; // Час відповіді
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
    // Функція для відображення часу назад
    function timeSince(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        let interval = Math.floor(seconds / 31536000);

        if (interval > 1) {
            return interval + " роки";
        }
        interval = Math.floor(seconds / 2592000);
        if (interval > 1) {
            return interval + " місяці";
        }
        interval = Math.floor(seconds / 86400);
        if (interval > 1) {
            return interval + " дні";
        }
        interval = Math.floor(seconds / 3600);
        if (interval > 1) {
            return interval + " години";
        }
        interval = Math.floor(seconds / 60);
        if (interval > 1) {
            return interval + " хвилини";
        }
        return Math.floor(seconds) + " секунди";
    }

    $(document).ready(function() {
        // Отримання часу назад для кожного коментаря
        $('.comment').each(function() {
            const commentDate = new Date($(this).find('.comment-time').text());
            const timeAgo = timeSince(commentDate);
            $(this).find('.comment-time').text(timeAgo + ' тому');
        });

        // Отримання часу назад для кожної відповіді
        $('.reply').each(function() {
            const replyDate = new Date($(this).find('.reply-time').text());
            const timeAgo = timeSince(replyDate);
            $(this).find('.reply-time').text(timeAgo + ' тому');
        });

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
