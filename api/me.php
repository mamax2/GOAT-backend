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

$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT 
        id,
        name,
        email,
        user_role,
        main_subject,
        level,
        goat_coins,
        lessons_as_tutor,
        lessons_as_student,
        avatar_url,
        created_at,
        updated_at
    FROM users WHERE id = ? LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

echo json_encode(['user' => $user]);
exit;
