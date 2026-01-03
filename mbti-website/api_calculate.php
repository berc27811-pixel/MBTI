<?php
// api_calculate.php - 接收答题，计算结果，生成订单
include 'config.php';

header('Content-Type: application/json; charset=utf-8');

// 1. 接收前端传来的答案
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['ans']) || !isset($input['type'])) {
    echo json_encode(['status' => 'error', 'msg' => '参数错误']);
    exit;
}

$answers = $input['ans']; // 答案数组，如 ['E', 'I', 'S', 'N', ...]
$testType = $input['type']; // '90', '144', '200'

// 2. 计算 MBTI 结果
function calculateMBTI($answers) {
    $scores = [
        'E' => 0, 'I' => 0,
        'S' => 0, 'N' => 0,
        'T' => 0, 'F' => 0,
        'J' => 0, 'P' => 0
    ];
    
    // 统计每个维度的得分
    foreach ($answers as $answer) {
        if (isset($scores[$answer])) {
            $scores[$answer]++;
        }
    }
    
    // 确定MBTI类型
    $type = '';
    $type .= ($scores['E'] >= $scores['I']) ? 'E' : 'I';
    $type .= ($scores['S'] >= $scores['N']) ? 'S' : 'N';
    $type .= ($scores['T'] >= $scores['F']) ? 'T' : 'F';
    $type .= ($scores['J'] >= $scores['P']) ? 'J' : 'P';
    
    return [
        'type' => $type,
        'details' => $scores
    ];
}

$result = calculateMBTI($answers);

// 3. 生成订单号
$order_no = 'MBTI' . date('YmdHis') . rand(1000, 9999);
$price = 9.90;

// 4. 将结果和订单存入数据库
try {
    $pdo->beginTransaction();
    
    // 插入订单
    $stmt = $pdo->prepare("INSERT INTO orders (order_no, visitor_uuid, amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$order_no, $visitor_uuid, $price]);
    
    // 插入测试结果
    $stmt = $pdo->prepare("INSERT INTO test_results (order_no, mbti_type, scores, answers) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $order_no, 
        $result['type'], 
        json_encode($result['details'], JSON_UNESCAPED_UNICODE), 
        json_encode($answers, JSON_UNESCAPED_UNICODE)
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success', 
        'order_no' => $order_no, 
        'price' => $price,
        'mbti_type' => $result['type']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error', 
        'msg' => '系统繁忙: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

