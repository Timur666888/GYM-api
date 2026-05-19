<?php
// Вспомогательные функции
function sendError($code, $message) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

function sendSuccess($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Функции для Bookings
function getAllBookings($pdo) {
    $stmt = $pdo->query("
        SELECT b.*, c.name as client_name, t.name as trainer_name, 
               h.name as hall_name, w.name as workout_name 
        FROM bookings b
        JOIN clients c ON b.client_id = c.id
        JOIN trainers t ON b.trainer_id = t.id
        JOIN halls h ON b.hall_id = h.id
        JOIN workouts w ON b.workout_id = w.id
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ");
    sendSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function getBookingById($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as client_name, t.name as trainer_name, 
               h.name as hall_name, w.name as workout_name 
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
        sendSuccess($booking);
    } else {
        sendError(404, 'Booking not found');
    }
}

function createBooking($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['client_id']) || !isset($data['trainer_id']) || 
        !isset($data['hall_id']) || !isset($data['workout_id']) || 
        !isset($data['booking_date']) || !isset($data['booking_time'])) {
        sendError(400, 'Missing required fields');
    }
    
    // Проверка на конфликт
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE hall_id = ? AND booking_date = ? AND booking_time = ?
    ");
    $stmt->execute([$data['hall_id'], $data['booking_date'], $data['booking_time']]);
    if ($stmt->fetchColumn() > 0) {
        sendError(409, 'Hall is already booked at this time');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO bookings (client_id, trainer_id, hall_id, workout_id, booking_date, booking_time) 
        VALUES (:client_id, :trainer_id, :hall_id, :workout_id, :booking_date, :booking_time)
    ");
    $stmt->execute([
        'client_id' => $data['client_id'],
        'trainer_id' => $data['trainer_id'],
        'hall_id' => $data['hall_id'],
        'workout_id' => $data['workout_id'],
        'booking_date' => $data['booking_date'],
        'booking_time' => $data['booking_time']
    ]);
    
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    sendSuccess($stmt->fetch(PDO::FETCH_ASSOC), 201);
}

function updateBooking($pdo, $id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        sendError(400, 'Invalid JSON data');
    }
    
    $updates = [];
    $params = ['id' => $id];
    
    $fields = ['client_id', 'trainer_id', 'hall_id', 'workout_id', 'booking_date', 'booking_time'];
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = :$field";
            $params[$field] = $data[$field];
        }
    }
    
    if (empty($updates)) {
        sendError(400, 'No fields to update');
    }
    
    $sql = "UPDATE bookings SET " . implode(", ", $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
        $stmt->execute(['id' => $id]);
        sendSuccess($stmt->fetch(PDO::FETCH_ASSOC));
    } else {
        sendError(404, 'Booking not found or no changes made');
    }
}
``
function deleteBooking($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        sendSuccess(['message' => 'Booking deleted successfully']);
    } else {
        sendError(404, 'Booking not found');
    }
}

// Функции для Clients
function getAllClients($pdo) {
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
    sendSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createClient($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && isset($data['name'])) {
        $stmt = $pdo->prepare("INSERT INTO clients (name) VALUES (?)");
        $stmt->execute([$data['name']]);
        sendSuccess(['id' => $pdo->lastInsertId(), 'name' => $data['name']], 201);
    } else {
        sendError(400, 'Name is required');
    }
}

// Функции для Trainers
function getAllTrainers($pdo) {
    $stmt = $pdo->query("SELECT * FROM trainers ORDER BY name");
    sendSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createTrainer($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && isset($data['name'])) {
        $stmt = $pdo->prepare("INSERT INTO trainers (name) VALUES (?)");
        $stmt->execute([$data['name']]);
        sendSuccess(['id' => $pdo->lastInsertId(), 'name' => $data['name']], 201);
    } else {
        sendError(400, 'Name is required');
    }
}

// Функции для Halls
function getAllHalls($pdo) {
    $stmt = $pdo->query("SELECT * FROM halls ORDER BY name");
    sendSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createHall($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && isset($data['name'])) {
        $stmt = $pdo->prepare("INSERT INTO halls (name) VALUES (?)");
        $stmt->execute([$data['name']]);
        sendSuccess(['id' => $pdo->lastInsertId(), 'name' => $data['name']], 201);
    } else {
        sendError(400, 'Name is required');
    }
}

// Функции для Workouts
function getAllWorkouts($pdo) {
    $stmt = $pdo->query("SELECT * FROM workouts ORDER BY name");
    sendSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createWorkout($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && isset($data['name'])) {
        $stmt = $pdo->prepare("INSERT INTO workouts (name) VALUES (?)");
        $stmt->execute([$data['name']]);
        sendSuccess(['id' => $pdo->lastInsertId(), 'name' => $data['name']], 201);
    } else {
        sendError(400, 'Name is required');
    }
}

// Функции для получения данных для отображения
function getAllBookingsData($pdo) {
    $stmt = $pdo->query("
        SELECT b.*, c.name as client_name, t.name as trainer_name, 
               h.name as hall_name, w.name as workout_name 
        FROM bookings b
        JOIN clients c ON b.client_id = c.id
        JOIN trainers t ON b.trainer_id = t.id
        JOIN halls h ON b.hall_id = h.id
        JOIN workouts w ON b.workout_id = w.id
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllClientsData($pdo) {
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllTrainersData($pdo) {
    $stmt = $pdo->query("SELECT * FROM trainers ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllHallsData($pdo) {
    $stmt = $pdo->query("SELECT * FROM halls ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllWorkoutsData($pdo) {
    $stmt = $pdo->query("SELECT * FROM workouts ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createBookingFromForm($pdo) {
    $stmt = $pdo->prepare("
        INSERT INTO bookings (client_id, trainer_id, hall_id, workout_id, booking_date, booking_time) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['client_id'], 
        $_POST['trainer_id'], 
        $_POST['hall_id'], 
        $_POST['workout_id'], 
        $_POST['booking_date'], 
        $_POST['booking_time']
    ]);
}
?>