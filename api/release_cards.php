<?php

require_once __DIR__ . '/../includes/config.php';

$pdo = getDB();

$pdo->exec("
UPDATE visitor_cards vc
JOIN visit_logs vl
ON vc.card_number = vl.card_number
SET vc.status = 'available'
WHERE
    vc.status = 'in_use'
    AND vl.status = 'checked_in'
    AND DATE(vl.check_in) < CURDATE()
");