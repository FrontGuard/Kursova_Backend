<?php
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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
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
