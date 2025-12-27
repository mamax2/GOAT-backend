<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed = [
    'http://localhost:4200',
    'http://127.0.0.1:4200',
    'http://localhost:8100',
    'http://127.0.0.1:8100',
];

if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}