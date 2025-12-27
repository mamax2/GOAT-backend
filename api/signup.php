<?php
require __DIR__ . '/cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

require __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');

$errors = [];
if ($name === '' || mb_strlen($name) < 2)
    $errors['name'] = 'Nome troppo corto.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors['email'] = 'Email non valida.';
if (strlen($password) < 8)
    $errors['password'] = 'Minimo 8 caratteri.';
if ($errors) {
    http_response_code(422);
    echo json_encode(['errors' => $errors]);
    exit;
}

$st = $pdo->prepare('SELECT 1 FROM users WHERE email=? LIMIT 1');
$st->execute([$email]);
if ($st->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email già registrata']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)')->execute([$name, $email, $hash]);

echo json_encode(['message' => 'Utente creato. Ora effettua il login.']);
