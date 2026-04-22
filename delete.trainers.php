<?php

require 'config.php';


if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM trainers WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: view.trainers.php");
exit();
?>
