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
$type = $_GET['type'] ?? null;

if (!$type || !in_array($type, ['richiesta', 'offerta', 'lastminute'], true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid type']);
  exit;
}

try {
  $params = [
    ':uid' => $userId,
  ];

  $sql = "
      SELECT
        a.id,
        a.type,
        a.title,
        a.subtitle,
        a.description,
        a.category,
        a.duration_hours,
        a.total_spots,
        a.remaining_spots,
        a.credits,
        a.event_date,
        a.event_time,
        a.expires_at,
        a.cta_label,
        a.cta_action,
        a.priority,
        a.created_at
      FROM announcements a
      WHERE a.is_visible = 1
        AND (a.expires_at IS NULL OR a.expires_at >= NOW())
        AND a.created_by != :uid
    ";

  // TAB LOGIC
  if ($type === 'lastminute') {
    $sql .= " AND a.expires_at IS NOT NULL AND DATE(a.expires_at) = CURDATE() ";
  } else {
    $sql .= " AND a.type = :type ";
    $params[':type'] = $type;
  }

  $sql .= "
      ORDER BY
        CASE WHEN a.expires_at IS NOT NULL THEN 0 ELSE 1 END,
        a.expires_at ASC,
        a.priority DESC,
        a.created_at DESC
    ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  echo json_encode([
    'success' => true,
    'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
  ]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'error' => 'Server error',
    'debug' => $e->getMessage() // ⬅️ toglilo in prod
  ]);
  exit;
}