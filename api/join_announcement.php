<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}

$userId = (int) $_SESSION['user']['id'];
$input = json_decode(file_get_contents('php://input'), true);
$announcementId = (int) ($input['announcement_id'] ?? 0);

if ($announcementId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'announcement_id missing']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT id, type, remaining_spots, expires_at, created_by, credits
  FROM announcements
  WHERE id = ?
    AND is_visible = 1
    AND (expires_at IS NULL OR expires_at >= NOW())
  LIMIT 1
");
$stmt->execute([$announcementId]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$announcement) {
  http_response_code(404);
  echo json_encode(['error' => 'Announcement not available']);
  exit;
}

if ((int) $announcement['remaining_spots'] <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'No spots available']);
  exit;
}

$creatorId = (int) $announcement['created_by'];

if ($creatorId === $userId) {
  http_response_code(403);
  echo json_encode(['error' => 'Cannot join your own announcement']);
  exit;
}

$check = $pdo->prepare("
  SELECT 1
  FROM announcement_participants
  WHERE announcement_id = ? AND user_id = ?
");
$check->execute([$announcementId, $userId]);

if ($check->fetch()) {
  http_response_code(409);
  echo json_encode(['error' => 'Already joined']);
  exit;
}

$role = $announcement['type'] === 'richiesta' ? 'tutor' : 'student';

$validationCode = 'GOAT-' . strtoupper(bin2hex(random_bytes(3)));

$pdo->beginTransaction();

try {

  $pdo->prepare("
    INSERT INTO announcement_participants
    (announcement_id, user_id, role)
    VALUES (?, ?, ?)
  ")->execute([
        $announcementId,
        $userId,
        $role
      ]);

  $pdo->prepare("
    INSERT INTO announcement_matches
    (announcement_id, creator_id, participant_id, status, validation_code)
    VALUES (?, ?, ?, 'pending', ?)
  ")->execute([
        $announcementId,
        $creatorId,
        $userId,
        $validationCode
      ]);

  $pdo->prepare("
    UPDATE announcements
    SET remaining_spots = remaining_spots - 1
    WHERE id = ? AND remaining_spots > 0
  ")->execute([$announcementId]);

  $pdo->commit();

  echo json_encode([
    'success' => true
  ]);
  exit;

} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error' => 'Join failed']);
  exit;
}