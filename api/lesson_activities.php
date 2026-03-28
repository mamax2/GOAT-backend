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

$sql = "
SELECT
  m.id,
  m.creator_id,
  m.participant_id,
  m.validated_at,
  m.updated_at,
  m.created_at,
  a.title,
  a.type AS announcement_type,
  a.category,
  a.subtitle,
  p.role AS participant_role
FROM announcement_matches m
JOIN announcements a ON a.id = m.announcement_id
LEFT JOIN announcement_participants p
  ON p.announcement_id = m.announcement_id
  AND p.user_id = :uid_part
WHERE m.status = 'confirmed'
  AND (m.creator_id = :uid1 OR m.participant_id = :uid2)
ORDER BY COALESCE(m.validated_at, m.updated_at, m.created_at) DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':uid_part' => $userId,
    ':uid1' => $userId,
    ':uid2' => $userId,
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tz = new DateTimeZone('Europe/Rome');
$activities = [];

foreach ($rows as $row) {
    $atRaw = $row['validated_at'] ?? null;
    if ($atRaw === null || $atRaw === '') {
        $atRaw = $row['updated_at'] ?? $row['created_at'];
    }
    try {
        $dt = new DateTimeImmutable($atRaw, $tz);
    } catch (Exception $e) {
        continue;
    }

    $role = resolveLessonRole($userId, $row);

    $subject = trim((string) ($row['category'] ?? ''));
    if ($subject === '') {
        $subject = trim((string) ($row['subtitle'] ?? ''));
    }

    $activities[] = [
        'id' => (int) $row['id'],
        'role' => $role,
        'at' => $dt->format('c'),
        'title' => (string) $row['title'],
        'subject' => $subject,
    ];
}

echo json_encode(['activities' => $activities]);
exit;


function resolveLessonRole(int $userId, array $row): string
{
    $pr = $row['participant_role'] ?? null;
    if ($pr === 'tutor' || $pr === 'student') {
        return $pr;
    }

    $creatorId = (int) $row['creator_id'];
    $type = (string) ($row['announcement_type'] ?? '');

    if ($userId === $creatorId) {
        return $type === 'richiesta' ? 'student' : 'tutor';
    }

    return $type === 'richiesta' ? 'tutor' : 'student';
}
