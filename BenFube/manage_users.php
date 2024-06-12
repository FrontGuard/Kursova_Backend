<?php
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
            $blocked_until = date('Y-m-d H:i:s', strtotime(" +$days days-3"));
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
