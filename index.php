<?php
require_once './config.php';
require_once './api/helpers.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// API Роутинг
if (strpos($path, '/api/') === 0) {
    header('Content-Type: application/json');
    
    // Определяем тип endpoint
    $endpoint = str_replace('/api/', '', $path);
    $resource = explode('/', $endpoint)[0];
    $id = isset(explode('/', $endpoint)[1]) ? explode('/', $endpoint)[1] : null;
    
    switch ($request_method) {
        case 'GET':
            switch ($resource) {
                case 'bookings':
                    if ($id) {
                        getBookingById($pdo, $id);
                    } else {
                        getAllBookings($pdo);
                    }
                    break;
                case 'clients':
                    getAllClients($pdo);
                    break;
                case 'trainers':
                    getAllTrainers($pdo);
                    break;
                case 'halls':
                    getAllHalls($pdo);
                    break;
                case 'workouts':
                    getAllWorkouts($pdo);
                    break;
                default:
                    sendError(404, 'API endpoint not found');
            }
            break;
            
        case 'POST':
            switch ($resource) {
                case 'bookings':
                    createBooking($pdo);
                    break;
                case 'clients':
                    createClient($pdo);
                    break;
                case 'trainers':
                    createTrainer($pdo);
                    break;
                case 'halls':
                    createHall($pdo);
                    break;
                case 'workouts':
                    createWorkout($pdo);
                    break;
                default:
                    sendError(404, 'API endpoint not found');
            }
            break;
            
        case 'PUT':
            if ($resource === 'bookings' && $id) {
                updateBooking($pdo, $id);
            } else {
                sendError(404, 'API endpoint not found');
            }
            break;
            
        case 'DELETE':
            if ($resource === 'bookings' && $id) {
                deleteBooking($pdo, $id);
            } else {
                sendError(404, 'API endpoint not found');
            }
            break;
            
        default:
            sendError(405, 'Method not allowed');
    }
    exit;
}

// Обработка формы записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    createBookingFromForm($pdo);
    header("Location: index.php");
    exit;
}

// Получение данных для отображения
$clients = getAllClientsData($pdo);
$trainers = getAllTrainersData($pdo);
$halls = getAllHallsData($pdo);
$workouts = getAllWorkoutsData($pdo);
$bookings = getAllBookingsData($pdo);
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Фитнес-клуб - Управление тренировками</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./style.css">
    <script src="/js/api.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1><img width="50" height="50" src="https://img.icons8.com/ios/50/gum-.png" alt="gum-"/> Фитнес клуб</h1>
            <a class="nav-link" href="/views/view.clients.php">Список клиентов</a>
            <a class="nav-link" href="/views/view.trainers.php">Список тренеров</a>
            <a class="nav-link" href="/views/view.halls.php">Список залов</a>
            <a class="nav-link" href="/views/view.workouts.php">Список тренировок</a>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2>Запись на тренировку</h2>
        <form method="post">
            <div class="form-group mb-3">
                <label>Клиент:</label>
                <select name="client_id" class="form-control" required>
                    <option value="">-- Выберите клиента --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label>Тренер:</label>
                <select name="trainer_id" class="form-control" required>
                    <option value="">-- Выберите тренера --</option>
                    <?php foreach ($trainers as $trainer): ?>
                        <option value="<?= $trainer['id'] ?>"><?= htmlspecialchars($trainer['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label>Зал:</label>
                <select name="hall_id" class="form-control" required>
                    <option value="">-- Выберите зал --</option>
                    <?php foreach ($halls as $hall): ?>
                        <option value="<?= $hall['id'] ?>"><?= htmlspecialchars($hall['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label>Тренировка:</label>
                <select name="workout_id" class="form-control" required>
                    <option value="">-- Выберите тип --</option>
                    <?php foreach ($workouts as $workout): ?>
                        <option value="<?= $workout['id'] ?>"><?= htmlspecialchars($workout['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label>Дата:</label>
                <input type="date" name="booking_date" class="form-control" required>
            </div>

            <div class="form-group mb-3">
                <label>Время:</label>
                <input type="time" name="booking_time" class="form-control" required>
            </div>

            <button type="submit" name="book" class="btn btn-primary">Записать</button>
        </form>
    </div>

    <hr>

    <div class="container mt-4">
        <h2>Список записей</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Клиент</th><th>Тренер</th><th>Зал</th><th>Тренировка</th><th>Дата</th><th>Время</th><th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?= htmlspecialchars($booking['client_name']) ?></td>
                    <td><?= htmlspecialchars($booking['trainer_name']) ?></td>
                    <td><?= htmlspecialchars($booking['hall_name']) ?></td>
                    <td><?= htmlspecialchars($booking['workout_name']) ?></td>
                    <td><?= $booking['booking_date'] ?></td>
                    <td><?= $booking['booking_time'] ?></td>
                    <td>
                        <a href="/models/update.php?id=<?= $booking['id'] ?>" class="btn btn-warning btn-sm">Изменить</a>
                        <a href="/models/delete.php?id=<?= $booking['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Вы уверены?')">Отменить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <hr>

    <div class="container mt-4" id="addData">
        <h3>Добавление новых данных</h3>
        <form method="post" action="models/adds.php">
            <div class="row mb-3">
                <div class="col">
                    <input type="text" name="new_client" class="form-control" placeholder="Новый клиент">
                </div>
                <div class="col">
                    <button type="submit" name="add_client" class="btn btn-success">Добавить клиента</button>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <input type="text" name="new_trainer" class="form-control" placeholder="Новый тренер">
                </div>
                <div class="col">
                    <button type="submit" name="add_trainer" class="btn btn-success">Добавить тренера</button>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <input type="text" name="new_halls" class="form-control" placeholder="Новый зал">
                </div>
                <div class="col">
                    <button type="submit" name="add_halls" class="btn btn-success">Добавить зал</button>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <input type="text" name="new_workouts" class="form-control" placeholder="Новая тренировка">
                </div>
                <div class="col">
                    <button type="submit" name="add_workouts" class="btn btn-success">Добавить тренировку</button>
                </div>
            </div>
        </form>
    </div>

</body>
</html>
