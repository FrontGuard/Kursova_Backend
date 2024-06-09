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
    <link rel="stylesheet" href="stylesupload.css">
</head>
<body>
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
                    <label>
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
