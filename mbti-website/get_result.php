<?php
require 'config.php';

$order_no = $_GET['order_no'] ?? '';

$stmt = $pdo->prepare("SELECT status FROM orders WHERE order_no = ? AND visitor_uuid = ?");
$stmt->execute([$order_no, $visitor_uuid]);
$order = $stmt->fetch();

if (!$order || $order['status'] !== 'paid') {
    echo json_encode(['status' => 'forbidden', 'msg' => '请先支付']);
    exit;
}

$stmt = $pdo->prepare("SELECT mbti_type, scores FROM test_results WHERE order_no = ?");
$stmt->execute([$order_no]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['status' => 'success', 'data' => $result]);
