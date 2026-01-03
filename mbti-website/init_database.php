<?php
// init_database.php - 数据库初始化脚本
header('Content-Type: text/html; charset=utf-8');

require_once 'db_config.php';

echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库初始化 - MBTI访客追踪系统</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #007bff;
            margin-top: 30px;
        }
        .success {
            color: #28a745;
            background-color: #d4edda;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            color: #17a2b8;
            background-color: #d1ecf1;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .sql-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            overflow-x: auto;
        }
        .sql-box pre {
            margin: 0;
            font-family: Consolas, Monaco, monospace;
            font-size: 13px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px 10px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>数据库初始化 - MBTI访客追踪系统</h1>';

try {
    $db = DatabaseConfig::getInstance();
    
    echo '<div class="info">✓ 数据库连接成功！</div>';
    
    $errors = [];
    $successes = [];
    
    // 1. 创建 visitors 表
    echo '<h2>1. 创建 visitors 表（访客表）</h2>';
    $sqlVisitors = "CREATE TABLE IF NOT EXISTS `visitors` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `uuid` VARCHAR(64) NOT NULL UNIQUE COMMENT '唯一身份ID',
        `first_visit_ip` VARCHAR(45) COMMENT '首次访问IP',
        `user_agent` TEXT COMMENT '用户代理字符串',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        KEY `idx_uuid` (`uuid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访客信息表'";
    
    echo '<div class="sql-box"><pre>' . htmlspecialchars($sqlVisitors) . '</pre></div>';
    
    try {
        $db->query($sqlVisitors);
        echo '<div class="success">✓ visitors 表创建成功！</div>';
        $successes[] = 'visitors';
    } catch (Exception $e) {
        echo '<div class="error">✗ visitors 表创建失败：' . htmlspecialchars($e->getMessage()) . '</div>';
        $errors[] = 'visitors';
    }
    
    // 2. 创建 orders 表
    echo '<h2>2. 创建 orders 表（订单表）</h2>';
    $sqlOrders = "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_no` VARCHAR(32) NOT NULL UNIQUE COMMENT '订单号',
        `visitor_uuid` VARCHAR(64) NOT NULL COMMENT '关联访客UUID',
        `amount` DECIMAL(10,2) NOT NULL COMMENT '订单金额',
        `status` ENUM('pending', 'paid') DEFAULT 'pending' COMMENT '支付状态',
        `payment_method` VARCHAR(20) COMMENT '支付方式',
        `paid_at` DATETIME COMMENT '支付时间',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        KEY `idx_order_no` (`order_no`),
        KEY `idx_visitor_uuid` (`visitor_uuid`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单信息表'";
    
    echo '<div class="sql-box"><pre>' . htmlspecialchars($sqlOrders) . '</pre></div>';
    
    try {
        $db->query($sqlOrders);
        echo '<div class="success">✓ orders 表创建成功！</div>';
        $successes[] = 'orders';
    } catch (Exception $e) {
        echo '<div class="error">✗ orders 表创建失败：' . htmlspecialchars($e->getMessage()) . '</div>';
        $errors[] = 'orders';
    }
    
    // 3. 创建 test_results 表
    echo '<h2>3. 创建 test_results 表（测试结果表）</h2>';
    $sqlTestResults = "CREATE TABLE IF NOT EXISTS `test_results` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_no` VARCHAR(32) NOT NULL COMMENT '关联订单号',
        `mbti_type` VARCHAR(4) COMMENT 'MBTI类型，例如INTJ',
        `scores` JSON COMMENT '详细维度得分',
        `answers` JSON COMMENT '用户选择的答案',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        KEY `idx_order_no` (`order_no`),
        KEY `idx_mbti_type` (`mbti_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='测试结果表'";
    
    echo '<div class="sql-box"><pre>' . htmlspecialchars($sqlTestResults) . '</pre></div>';
    
    try {
        $db->query($sqlTestResults);
        echo '<div class="success">✓ test_results 表创建成功！</div>';
        $successes[] = 'test_results';
    } catch (Exception $e) {
        echo '<div class="error">✗ test_results 表创建失败：' . htmlspecialchars($e->getMessage()) . '</div>';
        $errors[] = 'test_results';
    }
    
    // 4. 创建 mbti_wiki 表（SEO内容表）
    echo '<h2>4. 创建 mbti_wiki 表（MBTI人格百科表）</h2>';
    $sqlMbtiWiki = "CREATE TABLE IF NOT EXISTS `mbti_wiki` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `mbti_type` VARCHAR(4) NOT NULL UNIQUE COMMENT 'MBTI类型，如INTJ',
        `type_name` VARCHAR(100) NOT NULL COMMENT '类型中文名称',
        `type_name_en` VARCHAR(100) COMMENT '类型英文名称',
        `short_desc` TEXT COMMENT '简短描述（用于meta description）',
        `keywords` TEXT COMMENT '关键词，逗号分隔',
        `full_content` LONGTEXT NOT NULL COMMENT '完整内容（不少于1500字）',
        `strengths` JSON COMMENT '优势列表',
        `weaknesses` JSON COMMENT '劣势列表',
        `careers` JSON COMMENT '适合职业列表',
        `relationships` TEXT COMMENT '人际关系分析',
        `growth_tips` TEXT COMMENT '成长建议',
        `famous_people` JSON COMMENT '名人案例',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        KEY `idx_mbti_type` (`mbti_type`),
        FULLTEXT KEY `ft_content` (`full_content`, `keywords`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MBTI人格百科表（SEO优化）'";
    
    echo '<div class="sql-box"><pre>' . htmlspecialchars($sqlMbtiWiki) . '</pre></div>';
    
    try {
        $db->query($sqlMbtiWiki);
        echo '<div class="success">✓ mbti_wiki 表创建成功！</div>';
        $successes[] = 'mbti_wiki';
    } catch (Exception $e) {
        echo '<div class="error">✗ mbti_wiki 表创建失败：' . htmlspecialchars($e->getMessage()) . '</div>';
        $errors[] = 'mbti_wiki';
    }
    
    // 显示所有表的结构
    echo '<h2>表结构详情</h2>';
    
    foreach (['visitors', 'orders', 'test_results', 'mbti_wiki'] as $tableName) {
        $checkTable = $db->fetchOne("SHOW TABLES LIKE '{$tableName}'");
        
        if ($checkTable) {
            echo '<h3>' . $tableName . ' 表结构</h3>';
            $columns = $db->fetchAll("SHOW COLUMNS FROM {$tableName}");
            echo '<table>
                <tr style="background-color: #f8f9fa;">
                    <th>字段名</th>
                    <th>类型</th>
                    <th>允许NULL</th>
                    <th>键</th>
                    <th>默认值</th>
                    <th>额外</th>
                </tr>';
            
            foreach ($columns as $column) {
                echo '<tr>
                    <td>' . htmlspecialchars($column['Field']) . '</td>
                    <td>' . htmlspecialchars($column['Type']) . '</td>
                    <td>' . ($column['Null'] === 'NO' ? '否' : '是') . '</td>
                    <td>' . htmlspecialchars($column['Key']) . '</td>
                    <td>' . htmlspecialchars($column['Default'] ?? 'NULL') . '</td>
                    <td>' . htmlspecialchars($column['Extra']) . '</td>
                </tr>';
            }
            
            echo '</table>';
        }
    }
    
    // 总结
    echo '<h2>初始化总结</h2>';
    if (empty($errors)) {
        echo '<div class="success">✓ 所有表创建成功！共创建 ' . count($successes) . ' 张表</div>';
    } else {
        echo '<div class="error">✗ 部分表创建失败：' . implode(', ', $errors) . '</div>';
        echo '<div class="success">✓ 成功创建：' . implode(', ', $successes) . '</div>';
    }
    
    echo '<h2>下一步操作：</h2>
    <p>数据库初始化完成！现在您可以：</p>
    <a href="index.html" class="button">返回首页</a>
    <a href="test_tracker.html" class="button">测试访问追踪</a>';
    
} catch (Exception $e) {
    echo '<div class="error">✗ 错误：' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p>请检查数据库配置信息是否正确：</p>
    <ul>
        <li>数据库名：mbti_dewater_icu</li>
        <li>用户名：mbti_dewater_icu</li>
        <li>密码：fTasYKzah8mbCEwa</li>
    </ul>';
}

echo '</div>
</body>
</html>';
?>
