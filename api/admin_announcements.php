<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}
// TODO
// fai INSERT, UPDATE, DELETE a seconda della richiesta
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // CREA UN ANNUNCIO

    $data = json_decode(file_get_contents('php://input'), true);

    $sql = "
      INSERT INTO announcements
      (type, title, subtitle, description, category, icon, duration_hours, total_spots, remaining_spots, credits, event_date, event_time, cta_label, cta_action, priority)
      VALUES
      (:type, :title, :subtitle, :description, :category, :icon, :duration_hours, :total_spots, :remaining_spots, :credits, :event_date, :event_time, :cta_label, :cta_action, :priority)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':type' => $data['type'],
        ':title' => $data['title'],
        ':subtitle' => $data['subtitle'] ?? null,
        ':description' => $data['description'],
        ':category' => $data['category'] ?? null,
        ':icon' => $data['icon'] ?? null,
        ':duration_hours' => $data['duration_hours'],
        ':total_spots' => $data['total_spots'],
        ':remaining_spots' => $data['remaining_spots'],
        ':credits' => $data['credits'],
        ':event_date' => $data['event_date'] ?? null,
        ':event_time' => $data['event_time'] ?? null,
        ':cta_label' => $data['cta_label'],
        ':cta_action' => $data['cta_action'] ?? null,
        ':priority' => $data['priority'] ?? 0,
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// Aggiungi UPDATE, DELETE se ti servono
