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

$sql = "
SELECT
  c.id AS conversation_id,
  c.match_id,
  c.created_at AS conversation_created_at,

  CASE WHEN m.creator_id = :uid THEN m.participant_id ELSE m.creator_id END AS other_user_id,
  ou.name AS other_user_name,
  ou.avatar_url AS other_user_avatar_url,

  (SELECT id FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_id,
  (SELECT text FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_text,
  (SELECT sender_id FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_sender_id,
  (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_created_at,

  (SELECT COUNT(*) FROM messages msg WHERE msg.conversation_id = c.id AND msg.sender_id != :uid_unread AND msg.read_at IS NULL) AS unread_count

FROM conversations c
JOIN announcement_matches m ON m.id = c.match_id
JOIN users ou ON ou.id = (CASE WHEN m.creator_id = :uid_ou THEN m.participant_id ELSE m.creator_id END)

WHERE (m.creator_id = :uid_where1 OR m.participant_id = :uid_where2)
ORDER BY (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) IS NULL,
  COALESCE((SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1), c.created_at) DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':uid' => $userId,
  ':uid_ou' => $userId,
  ':uid_where1' => $userId,
  ':uid_where2' => $userId,
  ':uid_unread' => $userId,
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$list = [];
foreach ($rows as $r) {
  $list[] = [
    'conversation_id' => (int) $r['conversation_id'],
    'match_id' => (int) $r['match_id'],
    'created_at' => $r['conversation_created_at'],
    'other_user' => [
      'id' => (int) $r['other_user_id'],
      'name' => $r['other_user_name'],
      'avatar_url' => $r['other_user_avatar_url'],
    ],
    'last_message' => $r['last_message_id'] ? [
      'id' => (int) $r['last_message_id'],
      'text' => $r['last_message_text'],
      'sender_id' => (int) $r['last_message_sender_id'],
      'created_at' => $r['last_message_created_at'],
    ] : null,
    'unread_count' => (int) $r['unread_count'],
  ];
}

echo json_encode([
  'success' => true,
  'data' => $list,
]);
exit;
