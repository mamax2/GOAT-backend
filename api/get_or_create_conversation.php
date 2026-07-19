<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$matchId = (int) ($input['match_id'] ?? 0);

if ($matchId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'match_id required']);
    exit;
}

// Verifica che l'utente sia creator o participant del match
$mStmt = $pdo->prepare("
  SELECT id FROM announcement_matches
  WHERE id = :mid AND (creator_id = :uid OR participant_id = :uid2)
  LIMIT 1
");
$mStmt->execute([':mid' => $matchId, ':uid' => $userId, ':uid2' => $userId]);
if (!$mStmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Match not found']);
    exit;
}

// Cerca conversazione esistente
$cStmt = $pdo->prepare("SELECT id FROM conversations WHERE match_id = ? LIMIT 1");
$cStmt->execute([$matchId]);
$existing = $cStmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    echo json_encode([
        'success' => true,
        'conversation_id' => (int) $existing['id'],
    ]);
    exit;
}

// Crea nuova conversazione
$pdo->prepare("INSERT INTO conversations (match_id) VALUES (?)")->execute([$matchId]);
$conversationId = (int) $pdo->lastInsertId();

echo json_encode([
    'success' => true,
    'conversation_id' => $conversationId,
]);
exit;
