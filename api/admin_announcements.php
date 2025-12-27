<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // CREA UN ANNUNCIO
    $data = json_decode(file_get_contents('php://input'), true);

    $CTA_BY_TYPE = [
        'richiesta' => 'Proponiti',
        'offerta' => 'Partecipa',
    ];

    $sql = "
  INSERT INTO announcements
  (type, title, subtitle, description, category,  duration_hours, total_spots, remaining_spots, credits, event_date, event_time, expires_at, cta_label, cta_action, priority)
  VALUES
  (:type, :title, :subtitle, :description, :category,  :duration_hours, :total_spots, :remaining_spots, :credits, :event_date, :event_time, :expires_at,  :cta_action, :priority)
";

    $type = $data['type'] ?? null;

    if (!isset($CTA_BY_TYPE[$type])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid announcement type']);
        exit;
    }

    $ctaLabel = $CTA_BY_TYPE[$type];

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':type' => $type,
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
        ':expires_at' => $data['expires_at'] ?? null,
        ':cta_label' => $ctaLabel,
        ':cta_action' => $data['cta_action'] ?? null,
        ':priority' => $data['priority'] ?? 0,
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

