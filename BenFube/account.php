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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];

    // Перевірка наявності користувача з таким же нікнеймом або поштою
    $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    $stmt->execute();
    $existing_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing_user) {
        if ($existing_user['username'] === $username) {
            $update_message = "Користувач з таким нікнеймом уже зареєстрований.";
        } elseif ($existing_user['email'] === $email) {
            $update_message = "Користувач з такою поштою уже зареєстрований.";
        }
    } else {
        // Підготовка та виконання запиту на оновлення даних користувача
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $stmt->close();

        // Оновлення змінних сесії з новими даними користувача
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        // Повідомлення про успішне оновлення
        $update_message = "Профіль успішно оновлено.";
    }
}

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
    <title>Акаунт</title>
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<style>/* Загальні стилі */ body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; } /* Стилі для заголовків */ h1 { margin-top: 60px; font-size: 2.5em; margin-bottom: 20px; background: linear-gradient(to right, #ff0000, #000000); color: transparent; -webkit-background-clip: text; background-clip: text; } h2 { font-size: 1.8em; color: #333; } /* Стилі для відео */ video { width: 100%; height: auto; margin-bottom: 20px; } /* Стилі для відео карток */ .video-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px; margin-bottom: 20px; background-color: white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; z-index: 1; } .video-card:hover { box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); transform: translateY(-5px); z-index: 2; } .video-card h3 { color: #000000; margin: 10px 0; font-size: 1.25em; } .video-card h3 a { color: #ff0000; text-decoration: none; } .video-card p { margin-bottom: 10px; color: #555; font-size: 0.9em; } .video-preview img { border-radius: 8px; width: 100%; height: auto; } /* Кнопки */ .button { display: inline-block; padding: 10px 20px; background-color: #000000; color: white; text-decoration: none; border-radius: 5px; cursor: pointer; margin-right: 10px; transition: background-color 0.3s ease; } .button:hover { background-color: #ff0000; } /* Стилі для хедера */ header { position: fixed; top: 0; width: 100%; background-color: #62bd62; padding: 1px 0; text-align: center; z-index: 3; } /* Стилі для навігаційного списку */ nav ul { list-style-type: none; margin: 0; padding: 0; display: inline-block; } nav ul li { display: inline-block; margin-right: 10px; } /* Ваші стилі */ .header { text-align: center; margin-bottom: 20px; } .header h1 { color: #333; } .user-actions { margin-bottom: 20px; } .user-actions a.button { margin-right: 10px; } .tags { margin-bottom: 20px; } .tags h2 { color: #333; margin-bottom: 10px; } .tag { display: inline-block; padding: 5px 10px; background-color: #ddd; color: #333; text-decoration: none; border-radius: 5px; margin-right: 5px; } .videos { display: flex; flex-wrap: wrap; justify-content: space-between; } .video-card { width: calc(33.33% - 20px); margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background-color: #fff; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; } .video-card:hover { box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); transform: translateY(-5px); } .video-card h3 { color: #333; margin: 10px 0; font-size: 1.25em; } .video-card p { color: #666; font-size: 0.9em; } .video-preview { margin-bottom: 10px; } .video-preview img { border-radius: 8px; width: 100%; height: auto; } .video-tags { margin-top: 10px; } .video-tags .tag { background-color: #ddd; color: #333; padding: 3px 8px; border-radius: 3px; margin-right: 5px; margin-bottom: 5px; display: inline-block; } form { max-width: 300px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); } label { display: block; margin-bottom: 5px; } input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; } button { width: 100%; padding: 10px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; } button:hover { background-color: #0056b3; } p { text-align: left; margin-top: 20px; } a { color: #007bff; text-decoration: none; } a:hover { text-decoration: underline; } .error-message { color: #ff0000; text-align: center; margin-top: 20px; } .comment { margin-bottom: 20px; padding: 10px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); overflow: hidden; } .comment p { margin: 0; } .comment-time { font-size: 0.8em; color: #888; } textarea { width: 294px; height: 100px; resize: none; } .reply { margin-left: 20px; border-left: 2px solid #ccc; padding-left: 10px; overflow: hidden; } .reply p { margin: 0; } .reply-time { font-size: 0.8em; color: #888; } /* Стилі для кнопки Лайк */ #likeBtn { background-color: #ff0000; color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; } #likeBtn:hover { background-color: #cc0000; }</style>
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
<div class="container">
    <h1>Ласкаво просимо, <?php echo htmlspecialchars($user['username']); ?></h1>
    <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>

    <!-- Форма для редагування даних користувача -->
    <h2>Редагувати акаунт</h2>
    <?php if (isset($update_message)): ?>
        <p><?php echo $update_message; ?></p>
    <?php endif; ?>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="username">Ім'я користувача:</label><br>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"><br>
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"><br>
        <input type="submit" value="Зберегти зміни">
    </form>

    <h2>Статистика</h2>
    <p>Кількість завантажених відео: <?php echo $video_count; ?></p>
    <p>Загальна кількість переглядів: <?php echo $view_count; ?></p>
    <p>Загальна кількість лайків: <?php echo $like_count; ?></p>

    <h2>Ваші відео</h2>
    <div class="videos">
        <?php if ($user_videos): ?>
            <?php foreach ($user_videos as $video): ?>
                <div class="video-card">
                    <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                        <img src="<?php echo htmlspecialchars($video['preview_image_path'] ?? 'default-preview.jpg'); ?>" alt="Preview" class="video-preview" width="320" height="240">
                    </a>
                    <h3><a href="video_view_comments.php?id=<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title']); ?></a></h3>
                    <p>Кількість лайків: <?php echo $video['like_count']; ?></p>
                    <p>Дата завантаження: <?php echo htmlspecialchars($video['created_at']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Ви ще не завантажили жодного відео.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
