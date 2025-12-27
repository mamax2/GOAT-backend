<?php
require __DIR__ . '/cors.php';
header('Content-Type: application/json');
require __DIR__ . '/../config/database.php';

$token = $_GET['token'] ?? '';
$email = strtolower(trim($_GET['email'] ?? ''));

if (!$token || !$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametri mancanti']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND verification_token = ? LIMIT 1');
$stmt->execute([$email, $token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(400);
    echo json_encode(['error' => 'Token non valido']);
    exit;
}

$pdo->prepare('UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?')
    ->execute([$user['id']]);

echo json_encode(['message' => 'Email verificata. Ora puoi effettuare il login.']);
