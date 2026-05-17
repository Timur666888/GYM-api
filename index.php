<?php
require './config.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

if (strpos($path, '/api/') === 0) {
    header('Content-Type: application/json');
    
    if ($request_method === 'GET' && $path === '/api/bookings') {
        $stmt = $pdo->query("
            SELECT b.*, c.name as client_name, t.name as trainer_name, h.name as hall_name, w.name as workout_name 
            FROM bookings b
            JOIN clients c ON b.client_id = c.id
            JOIN trainers t ON b.trainer_id = t.id
            JOIN halls h ON b.hall_id = h.id
            JOIN workouts w ON b.workout_id = w.id
            ORDER BY b.booking_date DESC, b.booking_time DESC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    if ($request_method === 'GET' && preg_match('/\/api\/bookings\/(\d+)/', $path, $matches)) {
        $id = $matches[1];
        $stmt = $pdo->prepare("
            SELECT b.*, c.name as client_name, t.name as trainer_name, h.name as hall_name, w.name as workout_name 
            FROM bookings b
            JOIN clients c ON b.client_id = c.id
            JOIN trainers t ON b.trainer_id = t.id
            JOIN halls h ON b.hall_id = h.id
            JOIN workouts w ON b.workout_id = w.id
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking) {
            echo json_encode($booking);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found']);
        }
        exit;
    }
    
    if ($request_method === 'POST' && $path === '/api/bookings') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['client_id']) || !isset($data['trainer_id']) || 
            !isset($data['hall_id']) || !isset($data['workout_id']) || 
            !isset($data['booking_date']) || !isset($data['booking_time'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE hall_id = ? AND booking_date = ? AND booking_time = ?
        ");
        $stmt->execute([$data['hall_id'], $data['booking_date'], $data['booking_time']]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Hall is already booked at this time']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO bookings (client_id, trainer_id, hall_id, workout_id, booking_date, booking_time) 
            VALUES (:client_id, :trainer_id, :hall_id, :workout_id, :booking_date, :booking_time)
        ");
        $stmt->execute([
            $data['client_id'], $data['trainer_id'], $data['hall_id'], 
            $data['workout_id'], $data['booking_date'], $data['booking_time']
        ]);
        
        $id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
        $stmt->execute([$id]);
        
        http_response_code(201);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }
    
    if ($request_method === 'PUT' && preg_match('/\/api\/bookings\/(\d+)/', $path, $matches)) {
        $id = $matches[1];
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            exit;
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['client_id'])) {
            $updates[] = "client_id = :client_id";
            $params[] = $data['client_id'];
        }
        if (isset($data['trainer_id'])) {
            $updates[] = "trainer_id = :trainer_id";
            $params[] = $data['trainer_id'];
        }
        if (isset($data['hall_id'])) {
            $updates[] = "hall_id = :hall_id";
            $params[] = $data['hall_id'];
        }
        if (isset($data['workout_id'])) {
            $updates[] = "workout_id = workout_id";
            $params[] = $data['workout_id'];
        }
        if (isset($data['booking_date'])) {
            $updates[] = "booking_date = :booking_date";
            $params[] = $data['booking_date'];
        }
        if (isset($data['booking_time'])) {
            $updates[] = "booking_time = booking_time";
            $params[] = $data['booking_time'];
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }
        
        $params[] = $id;
        $sql = "UPDATE bookings SET " . implode(", ", $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found or no changes made']);
        }
        exit;
    }
    
    if ($request_method === 'DELETE' && preg_match('/\/api\/bookings\/(\d+)/', $path, $matches)) {
        $id = $matches[1];
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Booking deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Booking not found']);
        }
        exit;
    }
    
    if ($request_method === 'GET' && $path === '/api/clients') {
        $stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    // POST /api/clients - создать клиента
    if ($request_method === 'POST' && $path === '/api/clients') {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['name'])) {
            $stmt = $pdo->prepare("INSERT INTO clients (name) VALUES (?)");
            $stmt->execute([$data['name']]);
            http_response_code(201);
            echo json_encode(['id' => $pdo->lastInsertId(), 'name' => $data['name']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
        }
        exit;
    }
    
    // GET /api/trainers - получить всех тренеров
    if ($request_method === 'GET' && $path === '/api/trainers') {
        $stmt = $pdo->query("SELECT * FROM trainers ORDER BY name");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    // POST /api/trainers - создать тренера
    if ($request_method === 'POST' && $path === '/api/trainers') {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['name'])) {
            $stmt = $pdo->prepare("INSERT INTO trainers (name) VALUES (?)");
            $stmt->execute([$data['name']]);
            http_response_code(201);
            echo json_encode(['id' => $pdo->lastInsertId(), 'name' => $data['name']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
        }
        exit;
    }
    
    // GET /api/halls - получить все залы
    if ($request_method === 'GET' && $path === '/api/halls') {
        $stmt = $pdo->query("SELECT * FROM halls ORDER BY name");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    // POST /api/halls - создать зал
    if ($request_method === 'POST' && $path === '/api/halls') {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['name'])) {
            $stmt = $pdo->prepare("INSERT INTO halls (name) VALUES (?)");
            $stmt->execute([$data['name']]);
            http_response_code(201);
            echo json_encode(['id' => $pdo->lastInsertId(), 'name' => $data['name']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
        }
        exit;
    }
    
    // GET /api/workouts - получить все тренировки
    if ($request_method === 'GET' && $path === '/api/workouts') {
        $stmt = $pdo->query("SELECT * FROM workouts ORDER BY name");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    
    // POST /api/workouts - создать тип тренировки
    if ($request_method === 'POST' && $path === '/api/workouts') {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['name'])) {
            $stmt = $pdo->prepare("INSERT INTO workouts (name) VALUES (?)");
            $stmt->execute([$data['name']]);
            http_response_code(201);
            echo json_encode(['id' => $pdo->lastInsertId(), 'name' => $data['name']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
        }
        exit;
    }
    
    // Если API endpoint не найден
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $stmt = $pdo->prepare("INSERT INTO bookings (client_id, trainer_id, hall_id, workout_id, booking_date, booking_time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['client_id'], $_POST['trainer_id'], $_POST['hall_id'], $_POST['workout_id'], $_POST['booking_date'], $_POST['booking_time']]);
    header("Location: index.php");
    exit;
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