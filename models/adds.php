
<?php
    require '../config.php';

    if (isset($_POST['add_client']) && !empty($_POST['new_client'])) {
        $stmt = $pdo->prepare("INSERT INTO clients (name) VALUES (?)");
        $stmt->execute([$_POST['new_client']]);
        header("Location: /");
        exit;
    }
    if (isset($_POST['add_trainer']) && !empty($_POST['new_trainer'])) {
        $stmt = $pdo->prepare("INSERT INTO trainers (name) VALUES (?)");
        $stmt->execute([$_POST['new_trainer']]);
        header("Location: /");
        exit;
    }
    if (isset($_POST['add_halls']) && !empty($_POST['new_halls'])) {
        $stmt = $pdo->prepare("INSERT INTO halls (name) VALUES (?)");
        $stmt->execute([$_POST['new_halls']]);
        header("Location: /");
        exit;
    }
    if (isset($_POST['add_workouts']) && !empty($_POST['new_workouts'])) {
        $stmt = $pdo->prepare("INSERT INTO workouts (name) VALUES (?)");
        $stmt->execute([$_POST['new_workouts']]);
        header("Location: /");
        exit;
    }
    ?>