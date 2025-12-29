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
$data = json_decode(file_get_contents('php://input'), true);

$id = (int) ($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}

// Ownership check
$stmt = $pdo->prepare("
  SELECT created_by
  FROM announcements
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || (int) $row['created_by'] !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$upd = $pdo->prepare("
  UPDATE announcements SET
    title = :title,
    subtitle = :subtitle,
    description = :description,
    category = :category,
    duration_hours = :duration_hours,
    total_spots = :total_spots,
    remaining_spots = :remaining_spots,
    credits = :credits,
    event_date = :event_date,
    event_time = :event_time,
    updated_at = NOW()
  WHERE id = :id
");

$upd->execute([
    ':id' => $id,
    ':title' => $data['title'],
    ':subtitle' => $data['subtitle'] ?? null,
    ':description' => $data['description'],
    ':category' => $data['category'] ?? null,
    ':duration_hours' => $data['duration_hours'],
    ':total_spots' => $data['total_spots'],
    ':remaining_spots' => $data['remaining_spots'],
    ':credits' => $data['credits'],
    ':event_date' => $data['event_date'] ?? null,
    ':event_time' => $data['event_time'] ?? null,
]);

echo json_encode(['success' => true]);
exit;