<?php
// config.php - 核心框架：锁定用户和连接数据库
session_start();

// 数据库配置
$db_host = 'localhost';
$db_name = 'mbti_dewater_icu';
$db_user = 'mbti_dewater_icu';
$db_pass = 'fTasYKzah8mbCEwa';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 身份识别逻辑（解决UV/IP变动问题的核心）
if (!isset($_COOKIE['visitor_uuid'])) {
    $uuid = bin2hex(random_bytes(16));
    setcookie('visitor_uuid', $uuid, time() + 31536000, "/");
    $_COOKIE['visitor_uuid'] = $uuid;
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO visitors (uuid, first_visit_ip, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$uuid, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}
$visitor_uuid = $_COOKIE['visitor_uuid'];

// 加载题库数据
require __DIR__ . '/data/questions.php';
?>
