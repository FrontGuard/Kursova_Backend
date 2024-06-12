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

// Логіка сортування
$sort_order = "DESC";
if (isset($_GET['sort_asc'])) {
    $sort_order = "ASC";
}

// Логіка пошуку
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : null;
$search_condition = '';
if ($search_query) {
    $search_condition = "AND title LIKE '%$search_query%'";
}

if ($selected_tag_id) {
    // Отримати відео з обраним тегом
    $stmt_with_tag = $conn->prepare("SELECT DISTINCT videos.*, 
                                            (SELECT COUNT(*) FROM video_views WHERE video_views.video_id = videos.id) AS views 
                                     FROM videos 
                                     JOIN video_tags ON videos.id = video_tags.video_id 
                                     JOIN users ON videos.user_id = users.id
                                     WHERE video_tags.tag_id = ? AND (users.role != 'admin' OR ? = 'admin')
                                     $search_condition
                                     ORDER BY views $sort_order");
    $stmt_with_tag->bind_param("is", $selected_tag_id, $user_role);
    $stmt_with_tag->execute();
    $videos_with_tag = $stmt_with_tag->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_with_tag->close();

    // Отримати відео без обраного тегу
    $stmt_without_tag = $conn->prepare("SELECT videos.*, 
                                               (SELECT COUNT(*) FROM video_views WHERE video_views.video_id = videos.id) AS views 
                                        FROM videos 
                                        JOIN users ON videos.user_id = users.id
                                        WHERE videos.id NOT IN 
                                            (SELECT video_id FROM video_tags WHERE tag_id = ?)
                                        AND (users.role != 'admin' OR ? = 'admin')
                                        $search_condition
                                        ORDER BY views $sort_order");
    $stmt_without_tag->bind_param("is", $selected_tag_id, $user_role);
    $stmt_without_tag->execute();
    $videos_without_tag = $stmt_without_tag->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_without_tag->close();

    // Об'єднати результати
    $videos = array_merge($videos_with_tag, $videos_without_tag);
} else {
    // Якщо тег не обрано, відображаємо всі відео
    $stmt = $conn->prepare("SELECT videos.*, 
                                   (SELECT COUNT(*) FROM video_views WHERE video_views.video_id = videos.id) AS views 
                            FROM videos 
                            JOIN users ON videos.user_id = users.id
                            WHERE (users.role != 'admin' OR ? = 'admin')
                            $search_condition
                            ORDER BY views $sort_order");
    $stmt->bind_param("s", $user_role);
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
    <title>Головна сторінка</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<style>/* Загальні стилі */ body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; } /* Стилі для заголовків */ h1 { margin-top: 60px; font-size: 2.5em; margin-bottom: 20px; background: linear-gradient(to right, #ff0000, #000000); color: transparent; -webkit-background-clip: text; background-clip: text; } h2 { font-size: 1.8em; color: #333; } /* Стилі для відео */ video { width: 100%; height: auto; margin-bottom: 20px; } /* Стилі для відео карток */ .video-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px; margin-bottom: 20px; background-color: white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; z-index: 1; } .video-card:hover { box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); transform: translateY(-5px); z-index: 2; } .video-card h3 { color: #000000; margin: 10px 0; font-size: 1.25em; } .video-card h3 a { color: #ff0000; text-decoration: none; } .video-card p { margin-bottom: 10px; color: #555; font-size: 0.9em; } .video-preview img { border-radius: 8px; width: 100%; height: auto; } /* Кнопки */ .button { display: inline-block; padding: 10px 20px; background-color: #000000; color: white; text-decoration: none; border-radius: 5px; cursor: pointer; margin-right: 10px; transition: background-color 0.3s ease; } .button:hover { background-color: #ff0000; } /* Стилі для хедера */ header { position: fixed; top: 0; width: 100%; background-color: #62bd62; padding: 1px 0; text-align: center; z-index: 3; } /* Стилі для навігаційного списку */ nav ul { list-style-type: none; margin: 0; padding: 0; display: inline-block; } nav ul li { display: inline-block; margin-right: 10px; } /* Ваші стилі */ .header { text-align: center; margin-bottom: 20px; } .header h1 { color: #333; } .user-actions { margin-bottom: 20px; } .user-actions a.button { margin-right: 10px; } .tags { margin-bottom: 20px; } .tags h2 { color: #333; margin-bottom: 10px; } .tag { display: inline-block; padding: 5px 10px; background-color: #ddd; color: #333; text-decoration: none; border-radius: 5px; margin-right: 5px; } .videos { display: flex; flex-wrap: wrap; justify-content: space-between; } .video-card { width: calc(33.33% - 20px); margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background-color: #fff; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; } .video-card:hover { box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); transform: translateY(-5px); } .video-card h3 { color: #333; margin: 10px 0; font-size: 1.25em; } .video-card p { color: #666; font-size: 0.9em; } .video-preview { margin-bottom: 10px; } .video-preview img { border-radius: 8px; width: 100%; height: auto; } .video-tags { margin-top: 10px; } .video-tags .tag { background-color: #ddd; color: #333; padding: 3px 8px; border-radius: 3px; margin-right: 5px; margin-bottom: 5px; display: inline-block; } form { max-width: 300px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); } label { display: block; margin-bottom: 5px; } input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; } button { width: 100%; padding: 10px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; } button:hover { background-color: #0056b3; } p { text-align: left; margin-top: 20px; } a { color: #007bff; text-decoration: none; } a:hover { text-decoration: underline; } .error-message { color: #ff0000; text-align: center; margin-top: 20px; } .comment { margin-bottom: 20px; padding: 10px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); overflow: hidden; } .comment p { margin: 0; } .comment-time { font-size: 0.8em; color: #888; } textarea { width: 294px; height: 100px; resize: none; } .reply { margin-left: 20px; border-left: 2px solid #ccc; padding-left: 10px; overflow: hidden; } .reply p { margin: 0; } .reply-time { font-size: 0.8em; color: #888; } /* Стилі для кнопки Лайк */ #likeBtn { background-color: #ff0000; color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; } #likeBtn:hover { background-color: #cc0000; }/* Стилі для форми пошуку */  form.search-form { max-width: 400px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);}  form.search-form label { display: block; margin-bottom: 10px; color: #333;}  form.search-form input[type="text"] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}  form.search-form input[type="submit"] { width: 100%; padding: 10px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;}  form.search-form input[type="submit"]:hover { background-color: #0056b3;}</style>
<body>
<header>
    <nav>
        <ul>
            <div><h1>BenFube</h1></div>
            <?php if ($user_role === 'admin'): ?>
                <a href="manage_users.php" class="button">Управління користувачами</a>
            <?php endif; ?>

            <li><a href="index.php" class="button">Головна</a>
            <li><a href="account.php" class="button">Профіль</a>
            <li><a href="upload.php" class="button">Завантажити нове відео</a></li>
            <li><a href="history.php" class="button">Переглянути історію переглядів</a></li>
            <li><a href="manage_videos.php" class="button">Керувати відео</a></li>
            <li><a href="logout.php" class="button">Вийти</a>
        </ul>
    </nav>
</header>

<div><h1>BenFube</h1></div>

<div class="container">
    <div class="header">
        <div class="user-actions">
            <h1>Вітаємо, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        </div>
    </div>
    <form action="index.php" method="GET" style="margin-bottom: 20px;">
        <label for="search_query">Пошук за назвою:</label>
        <input type="text" id="search_query" name="search_query" placeholder="Введіть назву відео...">
        <input type="submit" value="Знайти" style="background-color: #007bff; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
    </form>
    <!-- Плашка з тегами -->
    <div class="tags">
        <h2>Теги</h2>
        <?php foreach ($tags as $tag): ?>
            <a href="index.php?tag_id=<?php echo $tag['id']; ?>" class="tag"><?php echo htmlspecialchars($tag['name']); ?></a>
        <?php endforeach; ?>
        <a href="index.php" class="tag">Всі відео</a>
    </div>

    <!-- Форма сортування -->
    <form action="index.php" method="GET" style="margin-bottom: 20px;">
        <label>Сортувати за кількістю переглядів:</label>
        <input type="submit" name="sort_desc" value="Найпопулярніше відео платформи    " style="background-color: #4CAF50; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
        <input type="submit" name="sort_asc" value="Найнепопулярніше відео платформи" style="background-color: #f44336; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
    </form>




    <!-- Відображення відео -->
    <div class="videos">
        <?php if ($videos): ?>
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <!-- Додавання прев'ю -->
                    <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                        <img src="<?php echo $video['preview_image_path']; ?>" alt="Preview" class="video-preview" width="373" height="290">
                    </a>

                    <h3><a href="video_view_comments.php?id=<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title']); ?></a></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>

                    <!-- Виведення кількості переглядів і часу завантаження -->
                    <p>Переглядів: <?php echo $video['views']; ?></p>
                    <p>Завантажено: <?php echo date('d.m.Y H:i', strtotime($video['created_at'])); ?></p>

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
</body>
</html>
