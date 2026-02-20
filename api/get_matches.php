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
  m.id              AS match_id,
  m.status          AS match_status,
  m.validation_code,
  m.validated_by,
  m.validated_at,
  m.created_at      AS match_created_at,

  m.creator_id,
  m.participant_id,

  a.id              AS announcement_id,
  a.title,
  a.subtitle,
  a.description,
  a.event_date,
  a.event_time,
  a.credits,

  CASE
    WHEN m.creator_id = :uid_creator THEN 'creator'
    WHEN m.participant_id = :uid_participant THEN 'participant'
    ELSE 'unknown'
  END AS my_side,

  p.role AS participant_role

FROM announcement_matches m
JOIN announcements a
  ON a.id = m.announcement_id
LEFT JOIN announcement_participants p
  ON p.announcement_id = m.announcement_id
  AND p.user_id = m.participant_id

WHERE
  (m.creator_id = :uid_where1 OR m.participant_id = :uid_where2)
  AND a.is_visible = 1

ORDER BY m.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':uid_creator' => $userId,
    ':uid_participant' => $userId,
    ':uid_where1' => $userId,
    ':uid_where2' => $userId,
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $rows
]);
exit;