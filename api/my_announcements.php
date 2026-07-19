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
  id, title, subtitle, description,
  duration_hours, remaining_spots, credits
FROM announcements
WHERE created_by = ?
AND is_visible = 1
ORDER BY created_at DESC
");

$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

echo json_encode(['data' => $rows]);