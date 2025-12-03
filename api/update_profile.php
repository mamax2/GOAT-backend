<?php

require __DIR__ . '/cors.php';
require __DIR__ . '/../config/session.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$userId = $_SESSION['user']['id'];

$name = trim($data['name'] ?? '');
$mainSubject = trim($data['main_subject'] ?? '');
$avatarUrl = trim($data['avatar_url'] ?? '');
$bio = trim($data['bio'] ?? '');

if ($name === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Name is required']);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE users 
    SET name = ?, main_subject = ?, avatar_url = ?, bio = ?, updated_at = NOW()
    WHERE id = ?
");

$stmt->execute([$name, $mainSubject, $avatarUrl, $bio, $userId]);

echo json_encode(['success' => true, 'message' => 'Profile updated']);
exit;
