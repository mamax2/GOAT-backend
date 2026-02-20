<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$conversationId = (int) ($_GET['conversation_id'] ?? 0);

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

$stmt = $pdo->prepare("
  SELECT id, conversation_id, sender_id, text, created_at, read_at
  FROM messages
  WHERE conversation_id = :cid
  ORDER BY created_at ASC
");
$stmt->execute([':cid' => $conversationId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$list = [];
foreach ($rows as $r) {
    $list[] = [
        'id' => (int) $r['id'],
        'conversation_id' => (int) $r['conversation_id'],
        'sender_id' => (int) $r['sender_id'],
        'text' => $r['text'],
        'created_at' => $r['created_at'],
        'read_at' => $r['read_at'],
    ];
}

echo json_encode([
    'success' => true,
    'data' => $list,
]);
exit;
