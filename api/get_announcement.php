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
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}

$stmt = $pdo->prepare("
  SELECT
  id,
  type,
  title,
  subtitle,
  description,
  category,
  duration_hours,
  total_spots,
  remaining_spots,
  credits,
  event_date,
  event_time,
  expires_at,
  cta_label,
  cta_action,
  priority,
  is_visible,
  created_by
FROM announcements
WHERE id = ?
LIMIT 1
");
$stmt->execute([$id]);
$a = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$a) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

if ((int) $a['created_by'] !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

echo json_encode(['data' => $a]);
exit;