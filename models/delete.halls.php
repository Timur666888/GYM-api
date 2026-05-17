<?php

require '../config.php';


if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM halls WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: /views/view.halls.php");
exit();
?>
