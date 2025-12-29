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

$input = json_decode(file_get_contents('php://input'), true);
$announcementId = (int) ($input['announcement_id'] ?? 0);

if ($announcementId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'announcement_id missing']);
    exit;
}

$pdo->beginTransaction();

try {
    // Verifica che esista la partecipazione
    $chk = $pdo->prepare("
    SELECT id
    FROM announcement_participants
    WHERE announcement_id = ? AND user_id = ?
    LIMIT 1
  ");
    $chk->execute([$announcementId, $userId]);
    $p = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$p) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Not joined']);
        exit;
    }

    // Cancella la partecipazione
    $del = $pdo->prepare("
    DELETE FROM announcement_participants
    WHERE announcement_id = ? AND user_id = ?
  ");
    $del->execute([$announcementId, $userId]);

    // Ripristina un posto (limita a total_spots)
    $upd = $pdo->prepare("
    UPDATE announcements
    SET remaining_spots = LEAST(remaining_spots + 1, total_spots)
    WHERE id = ?
  ");
    $upd->execute([$announcementId]);

    $pdo->commit();

    echo json_encode(['success' => true]);
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Leave failed']);
    exit;
}