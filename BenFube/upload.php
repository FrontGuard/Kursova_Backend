<?php
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
<style>/* Загальні стилі */ body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f9f9f9; } /* Стилі для заголовків */ h1 { margin-top: 60px; font-size: 2.5em; margin-bottom: 20px; background: linear-gradient(to right, #ff0000, #000000); color: transparent; -webkit-background-clip: text; background-clip: text; } h2 { font-size: 1.8em; color: #333; } /* Стилі для відео */ video { width: 100%; height: auto; margin-bottom: 20px; } /* Стилі для відео карток */ .video-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px; margin-bottom: 20px; background-color: white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; z-index: 1; } .video-card:hover { box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); transform: translateY(-5px); z-index: 2; } .video-card h3 { color: #000000; margin: 10px 0; font-size: 1.25em; } .video-card h3 a { color: #ff0000; text-decoration: none; } .video-card p { margin-bottom: 10px; color: #555; font-size: 0.9em; } .video-preview img { border-radius: 8px; width: 100%; height: auto; } /* Кнопки */ .button { display: inline-block; padding: 10px 20px; background-color: #000000; color: white; text-decoration: none; border-radius: 5px; cursor: pointer; margin-right: 10px; transition: background-color 0.3s ease; } .button:hover { background-color: #ff0000; } /* Стилі для хедера */ header { position: fixed; top: 0; width: 100%; background-color: #62bd62; padding: 1px 0; text-align: center; z-index: 3; } /* Стилі для навігаційного списку */ nav ul { list-style-type: none; margin: 0; padding: 0; display: inline-block; } nav ul li { display: inline-block; margin-right: 10px; } /* Ваші стилі */ .header { text-align: center; margin-bottom: 20px; } .header h1 { color: #333; } .user-actions { margin-bottom: 20px; } .user-actions a.button { margin-right: 10px; } .tags { margin-bottom: 20px; } .tags h2 { color: #333; margin-bottom: 10px; } .tag { display: inline-block; padding: 5px 10px; background-color: #ddd; color: #333; text-decoration: none; border-radius: 5px; margin-right: 5px; } .videos { display: flex; flex-wrap: wrap; justify-content: space-between; } .video-card { width: calc(33.33% - 20px); margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background-color: #fff; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; } .video-card:hover { box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); transform: translateY(-5px); } .video-card h3 { color: #333; margin: 10px 0; font-size: 1.25em; } .video-card p { color: #666; font-size: 0.9em; } .video-preview { margin-bottom: 10px; } .video-preview img { border-radius: 8px; width: 100%; height: auto; } .video-tags { margin-top: 10px; } .video-tags .tag { background-color: #ddd; color: #333; padding: 3px 8px; border-radius: 3px; margin-right: 5px; margin-bottom: 5px; display: inline-block; } form { max-width: 300px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); } label { display: block; margin-bottom: 5px; } input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; } button { width: 100%; padding: 10px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; } button:hover { background-color: #0056b3; } p { text-align: left; margin-top: 20px; } a { color: #007bff; text-decoration: none; } a:hover { text-decoration: underline; } .error-message { color: #ff0000; text-align: center; margin-top: 20px; } .comment { margin-bottom: 20px; padding: 10px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); overflow: hidden; } .comment p { margin: 0; } .comment-time { font-size: 0.8em; color: #888; } textarea { width: 294px; height: 100px; resize: none; } .reply { margin-left: 20px; border-left: 2px solid #ccc; padding-left: 10px; overflow: hidden; } .reply p { margin: 0; } .reply-time { font-size: 0.8em; color: #888; } /* Стилі для кнопки Лайк */ #likeBtn { background-color: #ff0000; color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; } #likeBtn:hover { background-color: #cc0000; }/* Стилі для форми пошуку */  form.search-form { max-width: 400px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);}  form.search-form label { display: block; margin-bottom: 10px; color: #333;}  form.search-form input[type="text"] { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}  form.search-form input[type="submit"] { width: 100%; padding: 10px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;}  form.search-form input[type="submit"]:hover { background-color: #0056b3;}</style>

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
            <label for="video">Відео(формат mp4):</label>
            <input type="file" id="video" name="video" accept="video/*" required>
        </div>
        <div>
            <label for="preview">Прев'ю(373 на 290):</label>
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
