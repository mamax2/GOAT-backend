<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json; charset=utf-8');

$adminKey = $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';

if ($adminKey !== GOAT_ADMIN_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = trim((string) ($input['validation_code'] ?? ''));

if ($code === '') {
    http_response_code(400);
    echo json_encode(['error' => 'validation_code missing']);
    exit;
}

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
    SELECT
      m.id,
      m.status,
      m.creator_id,
      m.participant_id,
      m.announcement_id,
      a.credits
    FROM announcement_matches m
    JOIN announcements a ON a.id = m.announcement_id
    WHERE m.validation_code = ?
    LIMIT 1
    FOR UPDATE
  ");
    $stmt->execute([$code]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Match not found']);
        exit;
    }

    if ($match['status'] === 'confirmed') {
        $pdo->commit();
        echo json_encode(['success' => true, 'status' => 'confirmed', 'already_confirmed' => true]);
        exit;
    }

    if ($match['status'] !== 'pending') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Match not confirmable']);
        exit;
    }

    $creatorId = (int) $match['creator_id'];
    $participantId = (int) $match['participant_id'];
    $credits = (int) $match['credits'];

    $uStmt = $pdo->prepare("
    SELECT id, goat_coins
    FROM users
    WHERE id IN (?, ?)
    FOR UPDATE
  ");
    $uStmt->execute([$creatorId, $participantId]);
    $users = $uStmt->fetchAll(PDO::FETCH_ASSOC);

    $balances = [];
    foreach ($users as $u)
        $balances[(int) $u['id']] = (int) $u['goat_coins'];

    if (!isset($balances[$participantId]) || !isset($balances[$creatorId])) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Users not found']);
        exit;
    }

    if ($balances[$participantId] < $credits) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Participant has not enough credits']);
        exit;
    }

    $deb = $pdo->prepare("UPDATE users SET goat_coins = goat_coins - ? WHERE id = ?");
    $deb->execute([$credits, $participantId]);

    $cre = $pdo->prepare("UPDATE users SET goat_coins = goat_coins + ? WHERE id = ?");
    $cre->execute([$credits, $creatorId]);

    // 4) Confirm match
    $upd = $pdo->prepare("
    UPDATE announcement_matches
    SET status = 'confirmed',
        validated_at = NOW(),
        updated_at = NOW()
    WHERE id = ?
  ");
    $upd->execute([(int) $match['id']]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'status' => 'confirmed',
        'transferred_credits' => $credits,
        'creator_id' => $creatorId,
        'participant_id' => $participantId,
    ]);
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}