account.php<?php
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
    <title>Акаунт</title>
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
            <li><a href="index.php" class="button">Головна</a>
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
    <form action="update_account.php" method="post">
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
                    <h3><a href="video_view_comments.php?id=<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title']); ?></a></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                        <img src="<?php echo htmlspecialchars($video['preview_image_path'] ?? 'default-preview.jpg'); ?>" alt="Preview" class="video-preview" width="320" height="280">
                    </a>
                    <p>Перегляди: <?php echo $video['view_count']; ?></p>
                    <p>Лайки: <?php echo $video['like_count']; ?></p>
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
channel.php<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Отримання імені користувача з сесії

// Отримання відео користувача
$stmt = $conn->prepare("SELECT * FROM videos WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ваш канал</title>
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
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
    <h1>Канал: <?php echo htmlspecialchars($username); ?></h1>
    <div class="videos">
        <?php if ($videos): ?>
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    <p>Завантажено:
                        <?php
                        if (isset($video['created_at'])) {
                            echo date("d.m.Y H:i:s", strtotime($video['created_at']));
                        } else {
                            echo "Дата невідома";
                        }
                        ?>
                    </p>
                    <?php if (!empty($video['preview_image_path'])): ?>
                        <a href="video_view_comments.php?id=<?php echo htmlspecialchars($video['id']); ?>">
                            <img src="<?php echo htmlspecialchars($video['preview_image_path']); ?>" alt="Preview" class="video-preview" width="320" height="240">
                        </a>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Ви ще не завантажили жодного відео.</p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
clear_history.php<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Видалення історії переглядів користувача
$stmt = $conn->prepare("DELETE FROM video_views WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo "Історію переглядів очищено.";
} else {
    echo "Помилка при очищенні історії.";
}

$stmt->close();
$conn->close();

header("Location: history.php"); // Перенаправлення назад на сторінку історії
exit;
?>
config.php<?php
$servername = "localhost";
$username = "root"; // замініть на вашого користувача бази даних
$password = ""; // замініть на ваш пароль бази даних
$dbname = "video_hosting";

// Створення підключення
$conn = new mysqli($servername, $username, $password, $dbname);

// Перевірка підключення
if ($conn->connect_error) {
    die("Підключення не вдалось: " . $conn->connect_error);
}
// Перевірка, чи користувач є адміністратором
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Використання функції isAdmin для захисту сторінок
function checkAdmin() {
    if (!isAdmin()) {
        header("Location: index.php");
        exit;
    }
}
?>
delete_video.php<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Перевірка, чи надійшло значення id відео
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_videos.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Отримуємо роль користувача
$video_id = $_GET['id'];

// Отримання відео для перевірки права на видалення
if ($user_role !== 'admin') {
    // Перевірка, чи відео належить користувачеві
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $video_id, $user_id);
} else {
    // Якщо адміністратор, отримуємо відео без перевірки користувача
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->bind_param("i", $video_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows != 1) {
    header("Location: manage_videos.php");
    exit;
}
$stmt->close();

// Видалення відео
$stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
$stmt->bind_param("i", $video_id);

if ($stmt->execute()) {
    header("Location: manage_videos.php");
    exit;
} else {
    echo "Помилка при видаленні відео. Будь ласка, спробуйте знову.";
}

$stmt->close();
?>
edit_video.php<?php
session_start();
include 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Отримуємо ідентифікатор відео
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($video_id <= 0) {
    header("Location: manage_videos.php");
    exit;
}

// Отримуємо роль користувача
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Отримання відео для редагування
if ($user_role !== 'admin') {
    // Перевірка, чи відео належить користувачеві
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $video_id, $user_id);
} else {
    // Якщо адміністратор, отримуємо відео без перевірки користувача
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->bind_param("i", $video_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows != 1) {
    header("Location: manage_videos.php");
    exit;
}
$video = $result->fetch_assoc();
$stmt->close();

// Оновлення прев'ю відео та інших полів
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Оновлення прев'ю відео
    if (!empty($_FILES['preview_image']['name'])) {
        if ($_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['preview_image']['tmp_name'];
            $file_name = uniqid() . '_' . $_FILES['preview_image']['name'];
            $file_path = 'uploads/' . $file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                $stmt = $conn->prepare("UPDATE videos SET preview_image_path = ? WHERE id = ?");
                $stmt->bind_param("si", $file_path, $video_id);
                if ($stmt->execute()) {
                    $video['preview_image_path'] = $file_path;
                }
                $stmt->close();
            }
        }
    }

    // Оновлення інших полів відео
    if (isset($_POST['title']) && isset($_POST['description'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];

        $stmt = $conn->prepare("UPDATE videos SET title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $description, $video_id);

        if ($stmt->execute()) {
            $video['title'] = $title;
            $video['description'] = $description;
            echo "Відео успішно оновлено.";
        } else {
            echo "Помилка при оновленні відео.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редагувати відео</title>
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
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
    <h1>Редагувати відео</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
        <label for="title">Назва відео:</label><br>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($video['title']); ?>" required><br><br>
        <label for="description">Опис відео:</label><br>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($video['description']); ?></textarea><br><br>
        <!-- Показуємо поточне прев'ю відео -->
        <?php if (!empty($video['preview_image_path'])): ?>
            <img src="<?php echo htmlspecialchars($video['preview_image_path']); ?>" alt="Preview" class="current-preview" width="320" height="240">
        <?php endif; ?>
        <label for="preview_image">Завантажте нове прев'ю відео:</label><br>
        <input type="file" id="preview_image" name="preview_image"><br><br>
        <input type="submit" value="Зберегти зміни">
    </form>
    <a href="manage_videos.php" class="button">Назад до списку відео</a>
</div>

<script>
    document.getElementById('title').addEventListener('input', function() {
        document.querySelector('.current-title').innerText = this.value;
    });

    document.getElementById('description').addEventListener('input', function() {
        document.querySelector('.current-description').innerText = this.value;
    });
</script>
</body>
</html>
edit_video_process.php<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка, чи надійшли дані через AJAX
    if (isset($_POST['title']) && isset($_POST['description'])) {
        // Отримання значень полів "Назва відео" і "Опис відео" з AJAX-запиту
        $title = $_POST['title'];
        $description = $_POST['description'];

        // Отримання ідентифікатора відео
        $video_id = $_POST['video_id'];

        // Оновлення інформації про відео в базі даних
        $stmt = $conn->prepare("UPDATE videos SET title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $description, $video_id);

        if ($stmt->execute()) {
            // Повернення успішного статусу до клієнта
            echo "Дані про відео успішно оновлено.";
        } else {
            // Повернення помилки до клієнта
            echo "Помилка при оновленні даних відео.";
        }

        $stmt->close();
    } else {
        // Повернення помилки до клієнта, якщо не надійшли необхідні дані
        echo "Необхідні дані не надійшли.";
    }
} else {
    // Повернення помилки до клієнта, якщо запит не є POST-запитом
    echo "Невірний метод запиту.";
}

$conn->close();
?>/1/



history.php<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch viewing history
$stmt = $conn->prepare("SELECT videos.*, video_views.viewed_at FROM video_views JOIN videos ON video_views.video_id = videos.id WHERE video_views.user_id = ? ORDER BY video_views.viewed_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$viewing_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Історія переглядів</title>
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
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
    <h1>Історія переглядів</h1>
    <div class="videos">
        <?php if ($viewing_history): ?>
            <?php foreach ($viewing_history as $video): ?>
                <div class="video-card">
                    <h3><a href="video_view_comments.php?id=<?php echo $video['id']; ?>"><?php echo htmlspecialchars($video['title']); ?></a></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    <a href="video_view_comments.php?id=<?php echo $video['id']; ?>">
                        <img src="<?php echo htmlspecialchars($video['preview_image_path'] ?? 'default-preview.jpg'); ?>" alt="Preview" class="video-preview" width="320" height="280">
                    </a>
                    <p>Дата перегляду: <?php echo htmlspecialchars($video['viewed_at']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>У вас немає історії переглядів.</p>
        <?php endif; ?>
    </div>

    <form action="clear_history.php" method="POST">
        <input type="submit" value="Очистити історію переглядів">
    </form>


</div>
</body>
</html>
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
<link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
            <div><h1>BenFube</h1></div>

            <?php if ($user_role === 'admin'): ?>
                <a href="manage_users.php" class="button">Управління користувачами</a>
            <?php endif; ?>


            <li><a href="manage_videos.php" class="button">Управління відео</a></li>
            <li><a href="history.php" class="button">Історія переглядів</a></li>
            <li><a href="upload.php" class="button">Завантажити відео</a></li>
            <li><a href="logout.php" class="button">Вийти</a></li>
            <li><a href="index.php" class="button">Головна</a></li>
            <li><a href="account.php" class="button">Профіль</a></li>




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
like_comment.php<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo "Ви повинні бути увійшли до системи.";
        exit;
    }

    $user_id = $_SESSION['user_id'];

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add_comment' && isset($_POST['comment']) && isset($_POST['video_id'])) {
            $video_id = $_POST['video_id'];
            $comment = $_POST['comment'];

            // Вставка коментаря в базу даних
            $stmt = $conn->prepare("INSERT INTO comments (video_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $video_id, $user_id, $comment);

            if ($stmt->execute()) {
                echo "Коментар успішно доданий.";
            } else {
                echo "Помилка при додаванні коментаря.";
            }

            $stmt->close();
        } elseif ($action === 'add_reply' && isset($_POST['reply']) && isset($_POST['comment_id'])) {
            $comment_id = $_POST['comment_id'];
            $reply = $_POST['reply'];

            // Вставка відповіді в базу даних
            $stmt = $conn->prepare("INSERT INTO comment_replies (user_id, comment_id, reply_text, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $user_id, $comment_id, $reply);

            if ($stmt->execute()) {
                echo "Відповідь успішно додана.";
            } else {
                echo "Помилка при додаванні відповіді.";
            }

            $stmt->close();
        } elseif ($action === 'like_video' && isset($_POST['video_id'])) {
            $video_id = $_POST['video_id'];

            // Перевірка, чи вже є лайк від цього користувача для цього відео
            $stmt = $conn->prepare("SELECT * FROM video_likes WHERE video_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $video_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Вставка лайка в базу даних
                $stmt = $conn->prepare("INSERT INTO video_likes (video_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $video_id, $user_id);

                if ($stmt->execute()) {
                    // Оновлення кількості лайків у таблиці videos
                    $stmt = $conn->prepare("UPDATE videos SET likes = likes + 1 WHERE id = ?");
                    $stmt->bind_param("i", $video_id);
                    $stmt->execute();
                    echo "Лайк успішно доданий.";
                } else {
                    echo "Помилка при додаванні лайка.";
                }
            } else {
                echo "Ви вже лайкнули це відео.";
            }

            $stmt->close();
        } else {
            echo "Невірні дані.";
        }
    } else {
        echo "Невірний запит.";
    }
} else {
    echo "Невірний метод запиту.";
}

$conn->close();
?>
login.php<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Підготовка запиту для вибірки користувача з бази даних
    $stmt = $conn->prepare("SELECT id, username, password, role, blocked_until FROM users WHERE username = ?");
    if ($stmt === false) {
        // Логування помилки підготовки запиту
        error_log("Помилка підготовки запиту: " . $conn->error);
        $error = "Помилка сервера. Спробуйте пізніше.";
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $current_time = date('Y-m-d H:i:s');
            if ($user['blocked_until'] && $user['blocked_until'] > $current_time) {
                $blocked_until = date('d.m.Y H:i', strtotime($user['blocked_until']));
                $error = "Ваш акаунт заблоковано до $blocked_until";
            } elseif (password_verify($password, $user['password'])) {
                // Встановлення сесійних змінних
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: index.php");
                exit;
            } else {
                // Невірний пароль
                $error = "Невірне ім'я користувача або пароль";
            }
        } else {
            // Невірне ім'я користувача
            $error = "Невірне ім'я користувача або пароль";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/stylechannel.css">
    <title>Увійти</title>
</head>
<body>
<h1>Увійти</h1>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <label for="username">Ім'я користувача:</label>
    <input type="text" id="username" name="username" required><br>
    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required><br>
    <button type="submit">Увійти</button>
</form>
<p>Не маєте облікового запису? <a href="register.php">Зареєструватися</a></p>
<?php if (isset($error)) echo "<p>$error</p>"; ?>
</body>
</html>
login_process.php<?php
// register_process.php
include 'config.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Хешування паролю

$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    // Успішно зареєстровано
    echo "Ви успішно зареєстровані. Будь ласка, <a href='login.php'>увійдіть</a>.";
} else {
    // Помилка реєстрації
    echo "Помилка: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>
logout.php<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit;
?>/2/




manage_users.php<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Перевірка, чи користувач має права адміністратора
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Обробка дій з користувачами (блокування та розблокування)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];

    // Перевірка, чи не є користувач адміністратором
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();

    if ($role !== 'admin') {
        if ($action === 'block' && isset($_POST['days'])) {
            $days = intval($_POST['days']);
            $blocked_until = date('Y-m-d H:i:s', strtotime("+$days days"));
            $stmt = $conn->prepare("UPDATE users SET blocked_until = ? WHERE id = ?");
            $stmt->bind_param("si", $blocked_until, $user_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'unblock') {
            $stmt = $conn->prepare("UPDATE users SET blocked_until = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Отримання списку користувачів
$stmt = $conn->query("SELECT * FROM users");
$users = $stmt->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управління користувачами</title>
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
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
    <h1>Управління користувачами</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Ім'я користувача</th>
            <th>Email</th>
            <th>Статус блокування</th>
            <th>Дії</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo $user['username']; ?></td>
                <td><?php echo $user['email']; ?></td>
                <td>
                    <?php
                    $blocked_until = $user['blocked_until'];
                    if ($blocked_until && strtotime($blocked_until) > time()) {
                        echo "Заблокований до " . htmlspecialchars($blocked_until);
                    } else {
                        echo "Активний";
                    }
                    ?>
                </td>
                <td>
                    <?php if ($user['role'] !== 'admin'): ?>
                        <?php if ($blocked_until && strtotime($blocked_until) > time()): ?>
                            <form action="" method="post">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="unblock">
                                <button type="submit">Розблокувати</button>
                            </form>
                        <?php else: ?>
                            <form action="" method="post">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="block">
                                <input type="number" name="days" min="1" placeholder="Кількість днів" required>
                                <button type="submit">Заблокувати</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <a href="index.php" class="button">Назад</a>
</div>
</body>
</html>
manage_videos.php<?php
session_start();
include 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Отримуємо роль користувача

// Отримання відео
if ($user_role === 'admin') {
    // Якщо адміністратор, отримуємо всі відео
    $stmt = $conn->prepare("SELECT videos.*, users.username FROM videos JOIN users ON videos.user_id = users.id");
    $stmt->execute();
    $videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Якщо звичайний користувач, отримуємо лише його відео
    $stmt = $conn->prepare("SELECT * FROM videos WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
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
    <title>Управління відео</title>
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
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
    <h1>Ваші відео</h1>
    <div class="videos">
        <?php if ($videos): ?>
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    <?php if ($user_role === 'admin'): ?>
                        <p>Завантажено: <?php echo htmlspecialchars($video['username']); ?></p>
                    <?php endif; ?>
                    <a href="edit_video.php?id=<?php echo htmlspecialchars($video['id']); ?>" class="button">Редагувати</a>
                    <a href="delete_video.php?id=<?php echo $video['id']; ?>" class="button">Видалити</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Ви ще не завантажили жодного відео.</p>
        <?php endif; ?>
    </div>
    <a href="account.php" class="button">Назад до облікового запису</a>
</div>
</body>
</html>
register.php<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/stylechannel.css">
    <title>Зареєструватися</title>
</head>
<body>
<h1>Зареєструватися</h1>
<form action="register_process.php" method="post">
    <label for="username">Ім'я користувача:</label>
    <input type="text" id="username" name="username" required><br>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required><br>
    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required><br>
    <button type="submit">Зареєструватися</button>
</form>
<p>Вже маєте обліковий запис? <a href="login.php">Увійти</a></p>
</body>
</html>
register_process.php<?php
include 'config.php';

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Хешування пароля

$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    echo "Ви успішно зареєстровані. Будь ласка, <a href='login.php'>увійдіть</a>.";
} else {
    echo "Помилка: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
update_account.php<?php
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
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
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
update_account_process.php<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

function updateEmail($conn, $user_id, $new_email) {
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $new_email, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $new_email = $_POST['email'];

        if (updateEmail($conn, $user_id, $new_email)) {
            $_SESSION['email'] = $new_email;
            header("Location: account.php");
            exit;
        } else {
            echo "Помилка при оновленні електронної пошти. Будь ласка, спробуйте знову.";
        }
    } else {
        echo "Необхідні дані не надійшли.";
    }
} else {
    echo "Недопустимий метод запиту.";
}

$conn->close();
?>
update_video_info.php<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title']) && isset($_POST['description']) && isset($_SESSION['user_id'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $user_id = $_SESSION['user_id'];

        // Оновлення інформації про відео в базі даних
        $stmt = $conn->prepare("UPDATE videos SET title = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $title, $description, $video_id, $user_id);
        if ($stmt->execute()) {
            echo "Дані успішно оновлено.";
        } else {
            echo "Помилка при оновленні даних.";
        }
        $stmt->close();
    } else {
        echo "Невірні дані.";
    }
} else {
    echo "Невірний метод запиту.";
}

$conn->close();
?>
upload.php<?php
session_start();
include 'config.php';

// Перевірка, чи користувач увійшов у систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Якщо форма відправлена
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    $user_id = $_SESSION['user_id'];

    // Перевірка, чи всі поля заповнені
    if (empty($title) || empty($description) || empty($_FILES['video']) || empty($tags)) {
        echo "Будь ласка, заповніть всі поля та виберіть теги.";
    } else {
        // Обробка завантаження відео
        if ($_FILES['video']['error'] == 0) {
            $video_path = 'uploads/' . basename($_FILES['video']['name']);
            move_uploaded_file($_FILES['video']['tmp_name'], $video_path);

            // Додавання прев'ю до відео
            $preview_path = 'previews/' . basename($_FILES['preview']['name']);
            move_uploaded_file($_FILES['preview']['tmp_name'], $preview_path);

            // Збереження відео в базу даних
            $stmt = $conn->prepare("INSERT INTO videos (title, description, video_path, preview_image_path, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $title, $description, $video_path, $preview_path, $user_id);
            $stmt->execute();
            $video_id = $stmt->insert_id;
            $stmt->close();

            // Збереження тегів для відео
            if (!empty($tags) && count($tags) <= 15) {
                $stmt = $conn->prepare("INSERT INTO video_tags (video_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tag_id) {
                    $stmt->bind_param("ii", $video_id, $tag_id);
                    $stmt->execute();
                }
                $stmt->close();
            }

            header("Location: index.php");
            exit;
        } else {
            echo "Помилка завантаження відео.";
        }
    }
}

// Отримання всіх тегів
$result = $conn->query("SELECT * FROM tags");
$tags = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Завантажити відео</title>
    <link rel="stylesheet" href="CSS/stylechannel.css">
</head>
<body>
<header>
    <nav>
        <ul>
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
    <h1>Завантажити відео</h1>
    <form action="upload.php" method="post" enctype="multipart/form-data" id="uploadForm">
        <div>
            <label for="title">Назва:</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div>
            <label for="description">Опис:</label>
            <textarea id="description" name="description" required></textarea>
        </div>
        <div>
            <label for="tags">Теги:</label>
            <div id="tagsContainer">
                <?php foreach ($tags as $tag): ?>
                    <label style="display: inline-block; margin-right: 10px;">
                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>">
                        <?php echo htmlspecialchars($tag['name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <small>Виберіть до 15 тегів</small>
        </div>
        <div>
            <label for="video">Відео:</label>
            <input type="file" id="video" name="video" accept="video/*" required>
        </div>
        <div>
            <label for="preview">Прев'ю:</label>
            <input type="file" id="preview" name="preview" accept="image/*" required>
        </div>
        <button type="submit">Завантажити</button>
    </form>
    <div id="selectedTagsCount">Вибрано тегів: 0</div>
</div>

<script>
    const selectedTagsCount = document.getElementById('selectedTagsCount');
    const checkboxes = document.querySelectorAll('input[name="tags[]"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            let count = 0;
            checkboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    count++;
                }
            });
            selectedTagsCount.textContent = 'Вибрано тегів: ' + count;
        });
    });
</script>
</body>
</html>
upload_process.php<?php
session_start();

include 'config.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    echo "Користувач не увійшов в систему. Завантаження відео неможливе.";
    exit;
}

$title = $_POST['title'];
$description = $_POST['description'];
$video = $_FILES['video'];

// Перевірка на помилки завантаження
if ($video['error'] === UPLOAD_ERR_OK) {
    $uploads_dir = 'uploads';

    // Перевірка наявності директорії і її створення при відсутності
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }

    $video_path = $uploads_dir . '/' . basename($video['name']);
    if (move_uploaded_file($video['tmp_name'], $video_path)) {
        $user_id = $_SESSION['user_id']; // Отримання ID авторизованого користувача

        $stmt = $conn->prepare("INSERT INTO videos (title, description, video_path, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $description, $video_path, $user_id);

        if ($stmt->execute()) {
            echo "Відео успішно завантажено!";
        } else {
            echo "Помилка: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Помилка переміщення завантаженого файлу.";
    }
} else {
    echo "Помилка завантаження відео: " . $video['error'];
}

$conn->close();
?>video_view_comments.php<?php
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
        margin-top: 60px;
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
