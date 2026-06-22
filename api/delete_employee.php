<?php
session_start();

if (
    !isset($_SESSION['admin_logged_in'])
) {
    http_response_code(403);
    exit('Access denied');
}

if (
    $_SESSION['role'] != 'hr'
    &&
    $_SESSION['role'] != 'admin'
) {
    http_response_code(403);
    exit('Insufficient privileges');
}

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