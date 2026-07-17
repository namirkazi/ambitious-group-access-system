<?php
// setup_site.php

$secret = "Ambitious@2026"; // Change this to something only you know

if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    exit("Unauthorized");
}

$site = $_GET['site'] ?? '';

$allowedSites = ['AMB', 'MOH'];

if (!in_array($site, $allowedSites, true)) {
    exit("Invalid site.");
}

setcookie(
    'site_code',
    $site,
    time() + (365 * 24 * 60 * 60),
    '/',
    '',
    false,
    true
);

echo "
<h2>Setup Complete</h2>
<p>Site configured as <strong>{$site}</strong>.</p>
<p>You may now delete <strong>setup_site.php</strong>.</p>
";