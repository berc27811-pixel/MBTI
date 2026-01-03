<?php
// pay.php - 对接支付平台
require 'config.php';

$order_no = $_GET['order_no'] ?? '';

if (empty($order_no)) {
    die("订单号不能为空");
}

// 检查订单是否存在且属于当前用户
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ? AND visitor_uuid = ?");
$stmt->execute([$order_no, $visitor_uuid]);
$order = $stmt->fetch();

if (!$order) {
    die("订单不存在或已失效");
}

if ($order['status'] == 'paid') {
    header("Location: result.html?order_no=" . $order_no);
    exit;
}

// === 这里接入你的支付接口 ===
// 示例：调用易支付/码支付/官方接口
// $pay_url = YourPaymentSDK::createOrder($order_no, $order['amount'], 'notify.php', 'return.php');
// header("Location: " . $pay_url);

// 模拟开发模式：直接显示支付链接（生产环境请替换为真实跳转）
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支付订单 - MBTI人格测试</title>
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="payment-page">
    <div class="payment-container">
        <div class="payment-header">
            <h1>订单支付</h1>
            <p class="order-info">订单号：<?php echo htmlspecialchars($order_no); ?></p>
        </div>
        
        <div class="payment-content">
            <div class="payment-amount">
                <span class="amount-label">支付金额</span>
                <span class="amount-value">¥<?php echo number_format($order['amount'], 2); ?></span>
            </div>
            
            <div class="payment-methods">
                <h3>选择支付方式</h3>
                <div class="payment-method-list">
                    <button class="payment-btn alipay" onclick="selectPayment('alipay')">
                        <span class="payment-icon">支付宝</span>
                    </button>
                    <button class="payment-btn wechat" onclick="selectPayment('wechat')">
                        <span class="payment-icon">微信支付</span>
                    </button>
                </div>
            </div>
            
            <div class="payment-tips">
                <p>提示：</p>
                <ul>
                    <li>请确保订单号正确</li>
                    <li>支付完成后会自动跳转到结果页面</li>
                    <li>如有问题请联系客服</li>
                </ul>
            </div>
        </div>
        
        <div class="payment-footer">
            <a href="index.html" class="back-link">返回首页</a>
        </div>
    </div>
    
    <script>
        function selectPayment(method) {
            // 开发模式：模拟支付
            if (confirm(`确定使用${method === 'alipay' ? '支付宝' : '微信支付'}支付 ¥<?php echo $order['amount']; ?>？`)) {
                // 模拟支付成功，跳转到结果页
                window.location.href = 'result.html?order_no=<?php echo $order_no; ?>';
            }
            
            // 生产环境：调用支付接口
            // 真实环境中，这里应该调用支付SDK并跳转到支付网关
            // 例如：
            // window.location.href = '/payment-gateway.php?order_no=<?php echo $order_no; ?>&method=' + method;
        }
    </script>
</body>
</html>
