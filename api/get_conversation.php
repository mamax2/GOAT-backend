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

$stmt = $pdo->prepare("
  SELECT
    c.id AS conversation_id,
    c.match_id,
    c.created_at,
    CASE WHEN m.creator_id = :uid THEN m.participant_id ELSE m.creator_id END AS other_user_id
  FROM conversations c
  JOIN announcement_matches m ON m.id = c.match_id
  WHERE c.id = :cid AND (m.creator_id = :uid2 OR m.participant_id = :uid3)
  LIMIT 1
");
$stmt->execute([
    ':cid' => $conversationId,
    ':uid' => $userId,
    ':uid2' => $userId,
    ':uid3' => $userId,
]);
$conv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conv) {
    http_response_code(404);
    echo json_encode(['error' => 'Conversation not found']);
    exit;
}

$otherId = (int) $conv['other_user_id'];
$uStmt = $pdo->prepare('SELECT id, name, avatar_url FROM users WHERE id = ? LIMIT 1');
$uStmt->execute([$otherId]);
$other = $uStmt->fetch(PDO::FETCH_ASSOC);
if (!$other) {
    http_response_code(404);
    echo json_encode(['error' => 'Other user not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'conversation_id' => (int) $conv['conversation_id'],
        'match_id' => (int) $conv['match_id'],
        'created_at' => $conv['created_at'],
        'other_user' => [
            'id' => (int) $other['id'],
            'name' => $other['name'],
            'avatar_url' => $other['avatar_url'] ?? null,
        ],
    ],
]);
exit;
