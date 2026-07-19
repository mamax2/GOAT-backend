<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];

// Segna come letti tutti i messaggi ricevuti dall'utente in tutte le sue conversazioni
$upd = $pdo->prepare("
  UPDATE messages msg
  INNER JOIN conversations c ON c.id = msg.conversation_id
  INNER JOIN announcement_matches m ON m.id = c.match_id
  SET msg.read_at = NOW()
  WHERE (m.creator_id = :uid OR m.participant_id = :uid2)
    AND msg.sender_id != :uid3
    AND msg.read_at IS NULL
");
$upd->execute([
    ':uid' => $userId,
    ':uid2' => $userId,
    ':uid3' => $userId,
]);
$markedCount = $upd->rowCount();

echo json_encode([
    'success' => true,
    'marked_count' => (int) $markedCount,
]);
exit;
