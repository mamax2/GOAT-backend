<?php
// MAMP MySQL: user root / pass root
$DB_NAME = 'goat';
$DB_USER = 'root';
$DB_PASS = 'root';

// Socket standard MAMP
$MAMP_SOCKET = '/Applications/MAMP/tmp/mysql/mysql.sock';

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_TIMEOUT => 5,
];

try {
  if (is_readable($MAMP_SOCKET)) {
    // Preferisci il socket (più stabile su macOS)
    $dsn = "mysql:unix_socket=$MAMP_SOCKET;dbname=$DB_NAME;charset=utf8mb4";
  } else {
    // Fallback TCP sulla porta giusta (8889)
    $dsn = "mysql:host=127.0.0.1;port=8889;dbname=$DB_NAME;charset=utf8mb4";
  }
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'DB connection failed', 'detail' => $e->getMessage(), 'dsn' => $dsn]);
  exit;
}
