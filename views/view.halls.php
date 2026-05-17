<?php

require '../config.php';

$stmt = $pdo->prepare("SELECT * FROM `halls`");
$stmt->execute();
$halls = $stmt->fetchAll(PDO::FETCH_ASSOC);



?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link rel="stylesheet" href="../style.css">
<nav class="navbar">
   <div class="container">
       <h1><img width="50" height="50" src="https://img.icons8.com/ios/50/gum-.png" alt="gum-"/> Фитнес клуб</h1>
       <a class="nav-link" href="/index.php">Вернуться</a>
   </div>
</nav>
<br>
<table>
    
        <th>залы</th>
        <th>Действие</th>
    
    <?php foreach ($halls as $hall): ?>
    <tr>
        <td><?= htmlspecialchars($hall['name']) ?></td>
<td>
            <a href="/models/delete.halls.php?id=<?= $hall['id'] ?>" class="btn btn-danger btn-sm" 
               onclick="return confirm('Вы уверены, что хотите удалить этот зал?')">
                Удалить
            </a>
            </td>   
    </tr>
    <?php endforeach; ?>
</table>