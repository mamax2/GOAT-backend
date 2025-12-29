<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];

$stmt = $pdo->prepare("
  SELECT
  a.id,
  a.title,
  a.subtitle,
  a.description,
  a.duration_hours,
  a.remaining_spots,
  a.credits,
  p.joined_at
FROM announcement_participants p
JOIN announcements a ON a.id = p.announcement_id
WHERE p.user_id = ?
AND a.is_visible = 1
ORDER BY p.joined_at DESC
");

$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

echo json_encode(['data' => $rows]);