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

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['coupon_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'coupon_id missing']);
    exit;
}

$couponId = (int) $input['coupon_id'];
$userId = (int) $_SESSION['user']['id'];

$coupons = [
    1 => ['cost' => 500, 'code' => 'GOAT-CAFFE-20'],
    2 => ['cost' => 500, 'code' => 'GOAT-CINEMA-30'],
    3 => ['cost' => 500, 'code' => 'GOAT-PIZZA-25'],
    4 => ['cost' => 500, 'code' => 'GOAT-LIBRI-20'],
];

if (!isset($coupons[$couponId])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coupon']);
    exit;
}

$coupon = $coupons[$couponId];

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare(
        'SELECT id FROM coupon_redemptions WHERE user_id = ? AND coupon_id = ?'
    );
    $check->execute([$userId, $couponId]);

    if ($check->fetch()) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Coupon already redeemed']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT goat_coins FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || $user['goat_coins'] < $coupon['cost']) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Not enough credits']);
        exit;
    }

    $pdo->prepare(
        'UPDATE users SET goat_coins = goat_coins - ? WHERE id = ?'
    )->execute([$coupon['cost'], $userId]);

    $pdo->prepare(
        'INSERT INTO coupon_redemptions (user_id, coupon_id, coupon_code, cost)
         VALUES (?, ?, ?, ?)'
    )->execute([
                $userId,
                $couponId,
                $coupon['code'],
                $coupon['cost'],
            ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'code' => $coupon['code'],
    ]);
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}