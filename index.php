<?php
require 'config.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $stmt = $pdo->prepare("INSERT INTO bookings (client_id, trainer_id, hall_id, workout_id, booking_date, booking_time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['client_id'], $_POST['trainer_id'], $_POST['hall_id'], $_POST['workout_id'], $_POST['booking_date'], $_POST['booking_time']]);
    header("Location: index.php");

$check_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM bookings 
    WHERE hall_id = ? 
      AND booking_date = ? 
      AND booking_time = ?
");
$check_stmt->execute([
    $_POST['hall_id'],
    $_POST['booking_date'],
    $_POST['booking_time']
]);

if ($check_stmt->fetchColumn() > 0) {
    // Зал уже занят в это время
    $error = "Извините, выбранный зал уже занят на это время.";
}

//     $sql = "SELECT * FROM bookings WHERE client_id = :client_id OR trainer_id = :trainer_id OR hall_id = :hall_id OR workout_id = :workout_id";
// $stmt = $pdo->prepare($sql);
// $stmt->bindParam(":client_id", $_POST['client_id']);
// $stmt->bindParam(":trainer_id", $_POST['trainer_id']);
// $stmt->bindParam(":hall_id", $_POST['hall_id']);
// $stmt->bindParam(":workout_id", $_POST['workout_id']);
// $stmt->execute();
}

$clients = $pdo->query("SELECT * FROM clients")->fetchAll();
$trainers = $pdo->query("SELECT * FROM trainers")->fetchAll();
$halls = $pdo->query("SELECT * FROM halls")->fetchAll();
$workouts = $pdo->query("SELECT * FROM workouts")->fetchAll();

$bookings = $pdo->query("
    SELECT b.*, c.name as client_name, t.name as trainer_name, h.name as hall_name, w.name as workout_name 
    FROM bookings b
    JOIN clients c ON b.client_id = c.id
    JOIN trainers t ON b.trainer_id = t.id
    JOIN halls h ON b.hall_id = h.id
    JOIN workouts w ON b.workout_id = w.id
    ORDER BY b.booking_date DESC, b.booking_time DESC
")->fetchAll();
?>

<!DOCTYPE html>

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
    <title>Фитнес-клуб - Управление тренировками</title>
        <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1><img width="50" height="50" src="https://img.icons8.com/ios/50/gum-.png" alt="gum-"/> GYMнастика</h1>
            <p>Только у нас самые крутые тренера и тренеровки</p>
            <a class="nav-link" href="view.clients.php">Список клиентов</a>
            <a class="nav-link" href="view.trainers.php">Список тренеров</a>
        </div>
    </nav>
<hr>
<h2>📅 Запись на тренировку</h2>
<form method="post">
    <div class="form-group">
        <label>Клиент:</label>
        <select name="client_id" required>
            <option value="">-- Выберите клиента --</option>
            <?php foreach ($clients as $client): ?>
                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Тренер:</label>
        <select name="trainer_id" required>
            <option value="">-- Выберите тренера --</option>
            <?php foreach ($trainers as $trainer): ?>
                <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Зал:</label>
        <select name="hall_id" required>
            <option value="">-- Выберите зал --</option>
            <?php foreach ($halls as $hall): ?>
                <option value="<?= $hall['id'] ?>"><?= htmlspecialchars($hall['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Тренировка:</label>
        <select name="workout_id" required>
            <option value="">-- Выберите тип --</option>
            <?php foreach ($workouts as $workout): ?>
                <option value="<?= $workout['id'] ?>"><?= htmlspecialchars($workout['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Дата:</label>
        <input type="date" name="booking_date" required>
    </div>

    <div class="form-group">
        <label>Время:</label>
        <input type="time" name="booking_time" required>
    </div>
    

    <button type="submit" name="book">Записать</button>
    <a href="#Добавить" class="btn btn-success">Добавление</a>
</form>
<hr>
<h2>📋 Список записей</h2>
<table>
        <th>Клиент</th><th>Тренер</th><th>Зал</th><th>Тренировка</th><th>Дата</th><th>Время</th><th>Действие</th>
    </tr>
    <?php foreach ($bookings as $booking): ?>
    <tr>
        <td><?= htmlspecialchars($booking['client_name']) ?></td>
        <td><?= htmlspecialchars($booking['trainer_name']) ?></td>
        <td><?= htmlspecialchars($booking['hall_name']) ?></td>
        <td><?= htmlspecialchars($booking['workout_name']) ?></td>
        <td><?= $booking['booking_date'] ?></td>
        <td><?= $booking['booking_time'] ?></td>
        <td>
            <a href="update.php?id=<?= $booking['id'] ?>" class="btn btn-warning ">Изменить</a>
            <a href="delete.php?id=<?php echo $booking['id'] ?>" type="button" class="btn btn-danger"
            onclick="return confirm('Вы уверены, что хотите удалить этого клиента?')">Отменить</a>
</td>
    </tr>
    <?php endforeach; ?>
</table>

<hr>
<div id = "Добавить">
<h3>➕ Быстрое добавление новых данных</h3>
<form method="post">
    <input type="text" name="new_client" placeholder="Новый клиент">
    <button type="submit" name="add_client">Добавить клиента</button>
    <input type="text" name="new_trainer" placeholder="Новый тренер">
    <button type="submit" name="add_trainer">Добавить тренера</button>
    <input type="text" name="new_halls" placeholder="Новый зал">
    <button type="submit" name="add_halls">Добавить зал</button>
</form>
</div>
<?php
if (isset($_POST['add_client']) && !empty($_POST['new_client'])) {
    $stmt = $pdo->prepare("INSERT INTO clients (name) VALUES (?)");
    $stmt->execute([$_POST['new_client']]);
    header("Location: index.php");
}
if (isset($_POST['add_trainer']) && !empty($_POST['new_trainer'])) {
    $stmt = $pdo->prepare("INSERT INTO trainers (name) VALUES (?)");
    $stmt->execute([$_POST['new_trainer']]);
    header("Location: /");
}
if (isset($_POST['add_halls']) && !empty($_POST['new_halls'])) {
    $stmt = $pdo->prepare("INSERT INTO halls (name) VALUES (?)");
    $stmt->execute([$_POST['new_halls']]);
    header("Location: /");
}
?>
</body>
</html>