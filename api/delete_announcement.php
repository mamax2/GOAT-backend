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
$userRole = $_SESSION['user']['user_role'] ?? 'student/tutor';

$input = json_decode(file_get_contents('php://input'), true);
$announcementId = (int) ($input['id'] ?? 0);

if ($announcementId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'id missing']);
    exit;
}

// Recupera ownership
$stmt = $pdo->prepare("
  SELECT id, created_by
  FROM announcements
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$announcementId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Announcement not found']);
    exit;
}

$createdBy = (int) $row['created_by'];
$isOwner = ($createdBy === $userId);
$isAdmin = ($userRole === 'admin');

if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$upd = $pdo->prepare("
  UPDATE announcements
  SET is_visible = 0
  WHERE id = ?
");
$upd->execute([$announcementId]);

echo json_encode(['success' => true]);
exit;