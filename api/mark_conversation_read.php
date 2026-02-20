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
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$conversationId = (int) ($input['conversation_id'] ?? 0);

if ($conversationId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id required']);
    exit;
}

// Verifica che l'utente faccia parte della conversazione
$check = $pdo->prepare("
  SELECT c.id FROM conversations c
  JOIN announcement_matches m ON m.id = c.match_id
  WHERE c.id = :cid AND (m.creator_id = :uid OR m.participant_id = :uid2)
  LIMIT 1
");
$check->execute([':cid' => $conversationId, ':uid' => $userId, ':uid2' => $userId]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Conversation not found']);
    exit;
}

// Segna come letti i messaggi ricevuti dall'utente 
$upd = $pdo->prepare("
  UPDATE messages
  SET read_at = NOW()
  WHERE conversation_id = :cid AND sender_id != :uid AND read_at IS NULL
");
$upd->execute([':cid' => $conversationId, ':uid' => $userId]);
$markedCount = $upd->rowCount();

echo json_encode([
    'success' => true,
    'marked_count' => (int) $markedCount,
]);
exit;
