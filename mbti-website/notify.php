<?php
// notify.php - 接收支付平台的异步通知
require 'config.php';

// 记录日志（调试用）
file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 收到支付通知\n", FILE_APPEND);
file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - GET: " . json_encode($_GET) . "\n", FILE_APPEND);
file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - POST: " . json_encode($_POST) . "\n", FILE_APPEND);

// 1. 获取支付平台传来的参数
$data = $_POST; // 大多数支付平台使用POST

if (empty($data)) {
    $data = $_GET;
}

if (empty($data)) {
    file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 错误：没有接收到数据\n", FILE_APPEND);
    die('fail');
}

// 2. 【关键】验证签名（Sign Verification）
// 不同的支付平台算法不同，请务必查看文档实现 verifySign
// 这里提供通用的验证框架，实际使用时需要根据具体支付平台调整

function verifySign($data, $apiKey) {
    // 示例：支付宝签名验证
    // 实际使用时请参考支付宝官方文档
    if (isset($data['sign']) && isset($data['sign_type'])) {
        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['sign_type']);
        
        // 按照支付平台规则重新计算签名
        $calculatedSign = calculateSign($data, $apiKey);
        
        return $sign === $calculatedSign;
    }
    
    // 示例：微信支付签名验证
    // 实际使用时请参考微信支付官方文档
    if (isset($data['sign'])) {
        $sign = $data['sign'];
        unset($data['sign']);
        
        // 按照支付平台规则重新计算签名
        $calculatedSign = calculateWechatSign($data, $apiKey);
        
        return $sign === $calculatedSign;
    }
    
    // 开发模式：跳过签名验证（生产环境必须启用）
    return true;
}

function calculateSign($data, $apiKey) {
    // 按照支付平台规则计算签名
    // 这里只是示例，实际使用时请参考具体支付平台文档
    ksort($data);
    $signStr = '';
    foreach ($data as $key => $value) {
        if ($value !== '' && $key !== 'sign') {
            $signStr .= $key . '=' . $value . '&';
        }
    }
    $signStr = rtrim($signStr, '&');
    $signStr .= $apiKey;
    return md5($signStr);
}

function calculateWechatSign($data, $apiKey) {
    // 微信支付签名计算
    // 这里只是示例，实际使用时请参考微信支付官方文档
    ksort($data);
    $signStr = '';
    foreach ($data as $key => $value) {
        if ($value !== '' && $key !== 'sign') {
            $signStr .= $key . '=' . $value . '&';
        }
    }
    $signStr = rtrim($signStr, '&');
    $signStr .= 'key=' . $apiKey;
    return strtoupper(md5($signStr));
}

// 验证签名（生产环境必须启用）
$apiKey = 'YOUR_PAYMENT_API_KEY'; // 请替换为你的支付平台API密钥

if (!verifySign($data, $apiKey)) {
    file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 错误：签名验证失败\n", FILE_APPEND);
    die('fail');
}

file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 签名验证通过\n", FILE_APPEND);

// 获取订单号和状态
$order_no = $data['out_trade_no'] ?? ''; // 平台传回的订单号
$status = $data['trade_status'] ?? '';   // 平台传回的状态

// 兼容不同支付平台的字段名
if (empty($order_no)) {
    $order_no = $data['out_trade_no'] ?? $data['order_no'] ?? $data['transaction_id'] ?? '';
}

if (empty($status)) {
    $status = $data['trade_status'] ?? $data['status'] ?? $data['result_code'] ?? '';
}

if (empty($order_no)) {
    file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 错误：订单号为空\n", FILE_APPEND);
    die('fail');
}

// 3. 更新数据库状态
// 判断支付成功状态（不同平台的状态码不同）
$successStatuses = ['TRADE_SUCCESS', 'SUCCESS', '1', 'trade_success', 'paid'];

if (in_array($status, $successStatuses)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ?");
        $stmt->execute([$order_no]);
        $order = $stmt->fetch();
        
        if (!$order) {
            file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 错误：订单不存在 {$order_no}\n", FILE_APPEND);
            die('fail');
        }
        
        if ($order['status'] == 'paid') {
            file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 订单已支付，跳过更新\n", FILE_APPEND);
            echo "success";
            exit;
        }
        
        // 更新订单状态为已支付
        $stmt = $pdo->prepare("UPDATE orders SET status = 'paid', paid_at = NOW(), payment_method = ? WHERE order_no = ?");
        $paymentMethod = isset($data['pay_type']) ? $data['pay_type'] : 'unknown';
        $stmt->execute([$paymentMethod, $order_no]);
        
        file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 订单 {$order_no} 已更新为已支付\n", FILE_APPEND);
        
        // 告诉支付平台我收到了
        echo "success";
        
    } catch (Exception $e) {
        file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 错误：" . $e->getMessage() . "\n", FILE_APPEND);
        die('fail');
    }
} else {
    file_put_contents('payment_notify.log', date('Y-m-d H:i:s') . " - 支付状态：{$status}\n", FILE_APPEND);
    die('fail');
}
?>
