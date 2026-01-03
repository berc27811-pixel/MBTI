<?php
// api_visitor.php - 访问追踪API（后端）
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = DatabaseConfig::getInstance();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'register_visitor':
            handleRegisterVisitor($db);
            break;
            
        case 'create_order':
            handleCreateOrder($db);
            break;
            
        case 'update_order_status':
            handleUpdateOrderStatus($db);
            break;
            
        case 'save_test_result':
            handleSaveTestResult($db);
            break;
            
        case 'get_visitor_info':
            handleGetVisitorInfo($db);
            break;
            
        case 'get_order_info':
            handleGetOrderInfo($db);
            break;
            
        case 'get_test_result':
            handleGetTestResult($db);
            break;
            
        case 'get_stats':
            handleGetStats($db);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '无效的操作']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleRegisterVisitor($db) {
    $uuid = $_POST['uuid'] ?? '';
    $ip = $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_POST['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (empty($uuid)) {
        echo json_encode(['success' => false, 'error' => '缺少访客UUID']);
        return;
    }
    
    $existingVisitor = $db->fetchOne("SELECT id FROM visitors WHERE uuid = ?", [$uuid]);
    
    if ($existingVisitor) {
        echo json_encode(['success' => true, 'message' => '访客已存在', 'visitor_id' => $existingVisitor['id']]);
        return;
    }
    
    $data = [
        'uuid' => $uuid,
        'first_visit_ip' => $ip,
        'user_agent' => $userAgent
    ];
    
    $visitorId = $db->insert('visitors', $data);
    
    if ($visitorId) {
        echo json_encode(['success' => true, 'message' => '访客注册成功', 'visitor_id' => $visitorId]);
    } else {
        echo json_encode(['success' => false, 'error' => '访客注册失败']);
    }
}

function handleCreateOrder($db) {
    $visitorUuid = $_POST['visitor_uuid'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $paymentMethod = $_POST['payment_method'] ?? '';
    
    if (empty($visitorUuid) || empty($amount)) {
        echo json_encode(['success' => false, 'error' => '缺少必要参数']);
        return;
    }
    
    $orderNo = generateOrderNo();
    
    $data = [
        'order_no' => $orderNo,
        'visitor_uuid' => $visitorUuid,
        'amount' => $amount,
        'status' => 'pending',
        'payment_method' => $paymentMethod
    ];
    
    $orderId = $db->insert('orders', $data);
    
    if ($orderId) {
        echo json_encode(['success' => true, 'message' => '订单创建成功', 'order_id' => $orderId, 'order_no' => $orderNo]);
    } else {
        echo json_encode(['success' => false, 'error' => '订单创建失败']);
    }
}

function handleUpdateOrderStatus($db) {
    $orderNo = $_POST['order_no'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($orderNo) || empty($status)) {
        echo json_encode(['success' => false, 'error' => '缺少必要参数']);
        return;
    }
    
    $updateData = ['status' => $status];
    
    if ($status === 'paid') {
        $updateData['paid_at'] = date('Y-m-d H:i:s');
    }
    
    $rowCount = $db->update('orders', $updateData, 'order_no = ?', [$orderNo]);
    
    if ($rowCount > 0) {
        echo json_encode(['success' => true, 'message' => '订单状态更新成功']);
    } else {
        echo json_encode(['success' => false, 'error' => '订单状态更新失败']);
    }
}

function handleSaveTestResult($db) {
    $orderNo = $_POST['order_no'] ?? '';
    $mbtiType = $_POST['mbti_type'] ?? '';
    $scores = $_POST['scores'] ?? '{}';
    $answers = $_POST['answers'] ?? '{}';
    
    if (empty($orderNo) || empty($mbtiType)) {
        echo json_encode(['success' => false, 'error' => '缺少必要参数']);
        return;
    }
    
    $order = $db->fetchOne("SELECT id, status FROM orders WHERE order_no = ?", [$orderNo]);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => '订单不存在']);
        return;
    }
    
    if ($order['status'] !== 'paid') {
        echo json_encode(['success' => false, 'error' => '订单未支付，无法保存测试结果']);
        return;
    }
    
    $data = [
        'order_no' => $orderNo,
        'mbti_type' => $mbtiType,
        'scores' => $scores,
        'answers' => $answers
    ];
    
    $resultId = $db->insert('test_results', $data);
    
    if ($resultId) {
        echo json_encode(['success' => true, 'message' => '测试结果保存成功', 'result_id' => $resultId]);
    } else {
        echo json_encode(['success' => false, 'error' => '测试结果保存失败']);
    }
}

function handleGetVisitorInfo($db) {
    $uuid = $_GET['uuid'] ?? '';
    
    if (empty($uuid)) {
        echo json_encode(['success' => false, 'error' => '缺少访客UUID']);
        return;
    }
    
    $visitor = $db->fetchOne("SELECT * FROM visitors WHERE uuid = ?", [$uuid]);
    
    if ($visitor) {
        echo json_encode(['success' => true, 'visitor' => $visitor]);
    } else {
        echo json_encode(['success' => false, 'error' => '访客不存在']);
    }
}

function handleGetOrderInfo($db) {
    $orderNo = $_GET['order_no'] ?? '';
    
    if (empty($orderNo)) {
        echo json_encode(['success' => false, 'error' => '缺少订单号']);
        return;
    }
    
    $order = $db->fetchOne("SELECT * FROM orders WHERE order_no = ?", [$orderNo]);
    
    if ($order) {
        echo json_encode(['success' => true, 'order' => $order]);
    } else {
        echo json_encode(['success' => false, 'error' => '订单不存在']);
    }
}

function handleGetTestResult($db) {
    $orderNo = $_GET['order_no'] ?? '';
    
    if (empty($orderNo)) {
        echo json_encode(['success' => false, 'error' => '缺少订单号']);
        return;
    }
    
    $result = $db->fetchOne("SELECT * FROM test_results WHERE order_no = ?", [$orderNo]);
    
    if ($result) {
        echo json_encode(['success' => true, 'result' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => '测试结果不存在']);
    }
}

function handleGetStats($db) {
    $today = date('Y-m-d');
    
    $stats = [
        'total_visitors' => 0,
        'today_visitors' => 0,
        'total_orders' => 0,
        'paid_orders' => 0,
        'today_orders' => 0,
        'today_paid' => 0,
        'total_revenue' => 0,
        'today_revenue' => 0,
        'total_tests' => 0
    ];
    
    $totalVisitors = $db->fetchOne("SELECT COUNT(*) as count FROM visitors");
    $stats['total_visitors'] = $totalVisitors['count'] ?? 0;
    
    $todayVisitors = $db->fetchOne("SELECT COUNT(*) as count FROM visitors WHERE DATE(created_at) = ?", [$today]);
    $stats['today_visitors'] = $todayVisitors['count'] ?? 0;
    
    $totalOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $totalOrders['count'] ?? 0;
    
    $paidOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'paid'");
    $stats['paid_orders'] = $paidOrders['count'] ?? 0;
    
    $todayOrders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ?", [$today]);
    $stats['today_orders'] = $todayOrders['count'] ?? 0;
    
    $todayPaid = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'paid' AND DATE(created_at) = ?", [$today]);
    $stats['today_paid'] = $todayPaid['count'] ?? 0;
    
    $totalRevenue = $db->fetchOne("SELECT SUM(amount) as sum FROM orders WHERE status = 'paid'");
    $stats['total_revenue'] = $totalRevenue['sum'] ?? 0;
    
    $todayRevenue = $db->fetchOne("SELECT SUM(amount) as sum FROM orders WHERE status = 'paid' AND DATE(created_at) = ?", [$today]);
    $stats['today_revenue'] = $todayRevenue['sum'] ?? 0;
    
    $totalTests = $db->fetchOne("SELECT COUNT(*) as count FROM test_results");
    $stats['total_tests'] = $totalTests['count'] ?? 0;
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

function generateOrderNo() {
    return 'MBTI' . date('YmdHis') . rand(1000, 9999);
}
?>
