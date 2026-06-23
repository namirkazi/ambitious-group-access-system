<?php

require_once '../includes/config.php';

$pdo = getDB();

$stmt = $pdo->prepare(

"

UPDATE employees

SET active=0

WHERE id=?

"

);

$stmt->execute([

$_GET['id']

]);

header(
"Location: ../employees.php"
);

exit;