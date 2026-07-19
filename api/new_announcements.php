<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/availability_helpers.php';

header('Content-Type: application/json; charset=utf-8');


if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}


$CTA_BY_TYPE = [
    'richiesta' => 'Proponiti',
    'offerta' => 'Partecipa',
];

$type = $data['type'] ?? null;

if (!isset($CTA_BY_TYPE[$type])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid announcement type']);
    exit;
}

$ctaLabel = $CTA_BY_TYPE[$type];

$expiresAt = null;
if (!empty($data['is_lastminute'])) {
    // scade oggi alle 23:59
    $expiresAt = date('Y-m-d 23:59:59');
}

$availRows = parseAvailabilitiesList($data['availabilities'] ?? null);

$sql = "
INSERT INTO announcements (
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
  created_by
) VALUES (
  :type,
  :title,
  :subtitle,
  :description,
  :category,
  :duration_hours,
  :total_spots,
  :remaining_spots,
  :credits,
  :event_date,
  :event_time,
  :expires_at,
  :cta_label,
  :cta_action,
  :priority,
  :created_by
)";

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':type' => $type,
        ':title' => trim($data['title']),
        ':subtitle' => $data['subtitle'] ?? null,
        ':description' => trim($data['description']),
        ':category' => $data['category'] ?? null,
        ':duration_hours' => (float) $data['duration_hours'],
        ':total_spots' => (int) $data['total_spots'],
        ':remaining_spots' => (int) $data['total_spots'],
        ':credits' => (int) $data['credits'],
        ':event_date' => $data['event_date'] ?? null,
        ':event_time' => $data['event_time'] ?? null,
        ':expires_at' => $expiresAt,
        ':cta_label' => $ctaLabel,
        ':cta_action' => $data['cta_action'] ?? null,
        ':priority' => !empty($data['is_lastminute']) ? 10 : 0,
        ':created_by' => $userId,
    ]);

    $announcementId = (int) $pdo->lastInsertId();

    if ($availRows !== []) {
        $ins = $pdo->prepare('
          INSERT INTO announcement_availability (announcement_id, avail_date, start_time, end_time)
          VALUES (:aid, :ad, :st, :et)
        ');
        foreach ($availRows as $r) {
            $ins->execute([
                ':aid' => $announcementId,
                ':ad' => $r['avail_date'],
                ':st' => $r['start_time'],
                ':et' => $r['end_time'],
            ]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Could not create announcement']);
    exit;
}

echo json_encode([
    'success' => true,
    'id' => $announcementId,
]);
exit;
