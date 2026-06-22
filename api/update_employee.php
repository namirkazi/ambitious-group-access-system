<?php

require_once '../includes/config.php';

$pdo = getDB();

$stmt = $pdo->prepare(

"

UPDATE employees

SET

title=?,

full_name=?,

phone=?,

email=?,

department=?,

designation=?

WHERE id=?

"

);

$stmt->execute([

$_POST['title'],

$_POST['full_name'],

$_POST['phone'],

$_POST['email'],

$_POST['department'],

$_POST['designation'],

$_POST['id']

]);

header(

"Location: ../employees.php"

);

exit;