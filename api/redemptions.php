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

$COUPONS = [
    1 => ['label' => '20% Sconto Caffè'],
    2 => ['label' => '30% Buono Cinema'],
    3 => ['label' => '25% Buono Pizza'],
    4 => ['label' => '20% Buono Libri'],
];

$stmt = $pdo->prepare("
  SELECT
    coupon_id,
    coupon_code,
    cost,
    redeemed_at
  FROM coupon_redemptions
  WHERE user_id = ?
  ORDER BY redeemed_at DESC
");

$stmt->execute([$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$movements = [];

foreach ($rows as $row) {
    $couponId = (int) $row['coupon_id'];

    $movements[] = [
        'coupon_id' => $couponId,
        'label' => $COUPONS[$couponId]['label'] ?? 'Coupon',
        'code' => $row['coupon_code'],
        'cost' => (int) $row['cost'],
        'redeemed_at' => $row['redeemed_at'],
        'type' => 'redeem',
    ];
}

echo json_encode([
    'movements' => $movements
]);
exit;