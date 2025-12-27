<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? null;

if (!$type) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing type']);
  exit;
}

$allowed = ['richiesta', 'offerta', 'lastminute'];
if (!in_array($type, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid type']);
  exit;
}

try {
  // Base SELECT
  $sql = "
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
      created_at
    FROM announcements
    WHERE is_visible = 1
    AND (expires_at IS NULL OR expires_at >= NOW())
  ";

  $params = [];

  // Filtro tab
  if ($type === 'lastminute') {
    // “finiscono in giornata” 
    $sql .= " AND expires_at IS NOT NULL AND DATE(expires_at) = CURDATE() ";
  } else {
    $sql .= " AND type = :type ";
    $params[':type'] = $type;
  }

  $sql .= " ORDER BY expires_at ASC, priority DESC ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['success' => true, 'data' => $rows]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Server error']);
  exit;
}