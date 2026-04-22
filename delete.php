<?php

require 'config.php';

function delete($pdo, $id, $sql){
    $stmt = $pdo->prepare($sql);
    $stmt->execute($id);
}

$sql ="DELETE FROM bookings WHERE id = :id";
delete( $pdo, $_GET, $sql);
// header("Location: index.php");
header ("Location: index.php");
