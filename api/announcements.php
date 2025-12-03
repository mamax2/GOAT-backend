<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

$type = $_GET['type'] ?? null;

if (!$type) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing type']);
  exit;
}

try {
  $sql = "
    SELECT
      id,
      type,
      title,
      subtitle,
      description,
      category,
      icon,
      duration_hours,
      total_spots,
      remaining_spots,
      credits,
      event_date,
      event_time,
      cta_label,
      cta_action
    FROM announcements
    WHERE is_visible = 1 AND type = :type
    ORDER BY priority DESC, created_at DESC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':type' => $type]);
  $rows = $stmt->fetchAll();

  echo json_encode(['success' => true, 'data' => $rows]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
