<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$pdo   = getDB();
$stmt  = $pdo->query("SELECT id, name, department FROM hosts WHERE active = 1 ORDER BY name");
$hosts = $stmt->fetchAll();
echo json_encode($hosts);
