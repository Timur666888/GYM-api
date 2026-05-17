<?php
require '../config.php';

// Получаем ID записи из URL
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Если нажата кнопка "Сохранить изменения"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET client_id = ?, trainer_id = ?, hall_id = ?, workout_id = ?, booking_date = ?, booking_time = ? 
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['client_id'],
        $_POST['trainer_id'],
        $_POST['hall_id'],
        $_POST['workout_id'],
        $_POST['booking_date'],
        $_POST['booking_time'],
        $booking_id
    ]);
    header("Location: /");
    exit();
}

// Получаем данные текущей записи
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

// Если запись не найдена — редирект
if (!$booking) {
    header("Location: index.php");
    exit();
}

// Получаем списки для выпадающих меню
$clients = $pdo->query("SELECT * FROM clients")->fetchAll();
$trainers = $pdo->query("SELECT * FROM trainers")->fetchAll();
$halls = $pdo->query("SELECT * FROM halls")->fetchAll();
$workouts = $pdo->query("SELECT * FROM workouts")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Изменить запись</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">

</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2> Изменить запись о тренировке</h2>
        <form method="post">
            <div class="mb-3">
                <label>Клиент</label>
                <select name="client_id" class="form-select" required>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>" <?= $client['id'] == $booking['client_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Тренер</label>
                <select name="trainer_id" class="form-select" required>
                    <?php foreach ($trainers as $trainer): ?>
                        <option value="<?= $trainer['id'] ?>" <?= $trainer['id'] == $booking['trainer_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($trainer['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Зал</label>
                <select name="hall_id" class="form-select" required>
                    <?php foreach ($halls as $hall): ?>
                        <option value="<?= $hall['id'] ?>" <?= $hall['id'] == $booking['hall_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($hall['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Тренировка</label>
                <select name="workout_id" class="form-select" required>
                    <?php foreach ($workouts as $workout): ?>
                        <option value="<?= $workout['id'] ?>" <?= $workout['id'] == $booking['workout_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($workout['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Дата</label>
                <input type="date" name="booking_date" value="<?= $booking['booking_date'] ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Время</label>
                <input type="time" name="booking_time" value="<?= $booking['booking_time'] ?>" class="form-control" required>
            </div>
            
            <button type="submit" name="update" class="btn btn-primary">Сохранить изменения</button>
            <a href="/index.php" class="btn btn-secondary">Отмена</a>
        </form>
    </div>
</body>
</html>