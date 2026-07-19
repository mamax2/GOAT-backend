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
$text = trim((string) ($input['text'] ?? ''));

if ($conversationId <= 0 || $text === '') {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id and text required']);
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

$ins = $pdo->prepare("
  INSERT INTO messages (conversation_id, sender_id, text) VALUES (:cid, :uid, :text)
");
$ins->execute([
    ':cid' => $conversationId,
    ':uid' => $userId,
    ':text' => $text,
]);

$messageId = (int) $pdo->lastInsertId();
$stmt = $pdo->prepare("
  SELECT id, conversation_id, sender_id, text, created_at, read_at FROM messages WHERE id = ?
");
$stmt->execute([$messageId]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => [
        'id' => (int) $msg['id'],
        'conversation_id' => (int) $msg['conversation_id'],
        'sender_id' => (int) $msg['sender_id'],
        'text' => $msg['text'],
        'created_at' => $msg['created_at'],
        'read_at' => $msg['read_at'],
    ],
]);
exit;
