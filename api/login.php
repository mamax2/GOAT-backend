<?php

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require __DIR__ . '/cors.php';     // CORS compatibile con withCredentials
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/database.php';

header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$email = strtolower(trim($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
  http_response_code(422);
  echo json_encode(['error' => 'Email o password mancanti']);
  exit;
}

$stmt = $pdo->prepare(
  'SELECT id, name, email, password_hash FROM users WHERE email=? LIMIT 1'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Email o password errati']);
  exit;
}

// Rigenera ID sessione
session_regenerate_id(true);

// Salva utente base nella sessione
$_SESSION['user'] = [
  'id' => $user['id'],
  'name' => $user['name'],
  'email' => $user['email']
];

echo json_encode([
  'success' => true,
  'message' => 'Login OK',
  'user' => $_SESSION['user']
]);

exit;
