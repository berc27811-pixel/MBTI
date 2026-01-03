<?php
// result.php - 测试结果页（SEO优化，动态渲染）
include 'config.php';

// 获取订单号
$order_no = $_GET['order_no'] ?? '';

if (empty($order_no)) {
    header('Location: test.php');
    exit;
}

// 验证订单并获取测试结果
try {
    // 检查订单是否存在且属于当前用户
    $stmt = $pdo->prepare("SELECT o.status, tr.mbti_type, tr.scores 
                           FROM orders o 
                           LEFT JOIN test_results tr ON o.order_no = tr.order_no 
                           WHERE o.order_no = ? AND o.visitor_uuid = ?");
    $stmt->execute([$order_no, $visitor_uuid]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die('订单不存在或无权访问');
    }
    
    // 检查是否已支付
    if ($order['status'] !== 'paid') {
        header('Location: pay.php?order_no=' . $order_no);
        exit;
    }
    
    $mbti_type = $order['mbti_type'];
    $scores = json_decode($order['scores'], true);
    
    // 从 mbti_wiki 表获取详细内容
    $stmt = $pdo->prepare("SELECT * FROM mbti_wiki WHERE mbti_type = ?");
    $stmt->execute([$mbti_type]);
    $wiki = $stmt->fetch();
    
    if (!$wiki) {
        // 如果数据库中没有，使用默认数据
        $wiki = [
            'mbti_type' => $mbti_type,
            'type_name' => $mbti_type . '型人格',
            'type_name_en' => $mbti_type,
            'short_desc' => '了解' . $mbti_type . '型人格的特点、优势、职业建议和人际关系分析',
            'keywords' => $mbti_type . ',MBTI,' . $mbti_type . '型人格,性格测试,人格分析',
            'full_content' => '<p>这是' . $mbti_type . '型人格的详细解析。请在后端管理系统中添加完整内容（不少于1500字）。</p>',
            'strengths' => '[]',
            'weaknesses' => '[]',
            'careers' => '[]',
            'relationships' => '',
            'growth_tips' => '',
            'famous_people' => '[]'
        ];
    }
    
    // 解析JSON字段
    $wiki['strengths'] = json_decode($wiki['strengths'] ?? '[]', true);
    $wiki['weaknesses'] = json_decode($wiki['weaknesses'] ?? '[]', true);
    $wiki['careers'] = json_decode($wiki['careers'] ?? '[]', true);
    $wiki['famous_people'] = json_decode($wiki['famous_people'] ?? '[]', true);
    
} catch (Exception $e) {
    die('获取结果失败：' . $e->getMessage());
}

// SEO优化：动态生成meta标签和标题
$page_title = $wiki['type_name'] . ' - ' . $mbti_type . '型人格深度解析 | MBTI测试结果';
$meta_description = $wiki['short_desc'] ?? '深度解析' . $mbti_type . '型人格的特点、优势、职业建议和人际关系分析';
$meta_keywords = $wiki['keywords'] ?? $mbti_type . ',MBTI,' . $mbti_type . '型人格,性格测试,人格分析,职业建议';
$canonical_url = 'https://你的域名.com/result.php?order_no=' . $order_no;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta标签 -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <meta name="author" content="MBTI人格测试">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    
    <!-- Open Graph标签 -->
    <meta property="og:title" content="<?php echo htmlspecialchars($wiki['type_name'] . ' - ' . $mbti_type . '型人格'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:site_name" content="MBTI人格测试">
    <meta property="og:locale" content="zh_CN">
    
    <!-- Twitter Card标签 -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($wiki['type_name']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="theme.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🧠</text></svg>">
    
    <!-- 结构化数据（Schema.org） -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "<?php echo htmlspecialchars($wiki['type_name']); ?>",
        "description": "<?php echo htmlspecialchars($meta_description); ?>",
        "author": {
            "@type": "Organization",
            "name": "MBTI人格测试"
        },
        "publisher": {
            "@type": "Organization",
            "name": "MBTI人格测试"
        },
        "datePublished": "<?php echo date('Y-m-d'); ?>",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?php echo htmlspecialchars($canonical_url); ?>"
        },
        "keywords": "<?php echo htmlspecialchars($meta_keywords); ?>"
    }
    </script>
    
    <style>
        .result-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .personality-header {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .personality-type {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .personality-name {
            font-size: 24px;
            margin-bottom: 15px;
        }
        .personality-desc {
            font-size: 16px;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto;
        }
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .content-section h2 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .content-section h3 {
            color: #555;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        .tag {
            padding: 8px 16px;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 14px;
        }
        .tag.strength {
            background: #d4edda;
            color: #155724;
        }
        .tag.weakness {
            background: #f8d7da;
            color: #721c24;
        }
        .tag.career {
            background: #d1ecf1;
            color: #0c5460;
        }
        .full-content {
            line-height: 1.8;
            color: #333;
        }
        .full-content p {
            margin-bottom: 15px;
        }
        .full-content ul, .full-content ol {
            margin: 15px 0;
            padding-left: 30px;
        }
        .full-content li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <header class="header">
        <div class="container nav-container">
            <a href="index.php" class="nav-logo">MBTI人格测试</a>
            <a href="test.php" class="btn btn-secondary">重新测试</a>
        </div>
    </header>

    <!-- 结果容器 -->
    <div class="result-container">
        <!-- 人格头部信息 -->
        <div class="personality-header">
            <div class="personality-type"><?php echo htmlspecialchars($mbti_type); ?></div>
            <div class="personality-name"><?php echo htmlspecialchars($wiki['type_name']); ?></div>
            <?php if (!empty($wiki['type_name_en'])): ?>
            <div style="font-size: 18px; opacity: 0.8; margin-bottom: 15px;"><?php echo htmlspecialchars($wiki['type_name_en']); ?></div>
            <?php endif; ?>
            <p class="personality-desc"><?php echo htmlspecialchars($wiki['short_desc'] ?? $meta_description); ?></p>
        </div>

        <!-- 完整内容（SEO核心） -->
        <div class="content-section">
            <h2><?php echo htmlspecialchars($wiki['type_name']); ?>深度解析</h2>
            <div class="full-content">
                <?php echo $wiki['full_content']; ?>
            </div>
        </div>

        <!-- 优势 -->
        <?php if (!empty($wiki['strengths'])): ?>
        <div class="content-section">
            <h2>核心优势</h2>
            <div class="tag-list">
                <?php foreach ($wiki['strengths'] as $strength): ?>
                <span class="tag strength"><?php echo htmlspecialchars($strength); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 待提升点 -->
        <?php if (!empty($wiki['weaknesses'])): ?>
        <div class="content-section">
            <h2>待提升点</h2>
            <div class="tag-list">
                <?php foreach ($wiki['weaknesses'] as $weakness): ?>
                <span class="tag weakness"><?php echo htmlspecialchars($weakness); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 适合职业 -->
        <?php if (!empty($wiki['careers'])): ?>
        <div class="content-section">
            <h2>适合职业</h2>
            <div class="tag-list">
                <?php foreach ($wiki['careers'] as $career): ?>
                <span class="tag career"><?php echo htmlspecialchars($career); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 人际关系分析 -->
        <?php if (!empty($wiki['relationships'])): ?>
        <div class="content-section">
            <h2>人际关系分析</h2>
            <div class="full-content">
                <?php echo nl2br(htmlspecialchars($wiki['relationships'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 成长建议 -->
        <?php if (!empty($wiki['growth_tips'])): ?>
        <div class="content-section">
            <h2>个人成长建议</h2>
            <div class="full-content">
                <?php echo nl2br(htmlspecialchars($wiki['growth_tips'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 名人案例 -->
        <?php if (!empty($wiki['famous_people'])): ?>
        <div class="content-section">
            <h2><?php echo htmlspecialchars($wiki['type_name']); ?>名人</h2>
            <div class="tag-list">
                <?php foreach ($wiki['famous_people'] as $person): ?>
                <span class="tag"><?php echo htmlspecialchars($person); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 维度得分（如果有） -->
        <?php if (!empty($scores)): ?>
        <div class="content-section">
            <h2>维度得分详情</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>外向(E) vs 内向(I)</strong>
                    <div>E: <?php echo $scores['E'] ?? 0; ?> | I: <?php echo $scores['I'] ?? 0; ?></div>
                </div>
                <div>
                    <strong>实感(S) vs 直觉(N)</strong>
                    <div>S: <?php echo $scores['S'] ?? 0; ?> | N: <?php echo $scores['N'] ?? 0; ?></div>
                </div>
                <div>
                    <strong>思考(T) vs 情感(F)</strong>
                    <div>T: <?php echo $scores['T'] ?? 0; ?> | F: <?php echo $scores['F'] ?? 0; ?></div>
                </div>
                <div>
                    <strong>判断(J) vs 感知(P)</strong>
                    <div>J: <?php echo $scores['J'] ?? 0; ?> | P: <?php echo $scores['P'] ?? 0; ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 相关链接 -->
        <div class="content-section">
            <h2>了解更多</h2>
            <p>
                <a href="test.php?type=200">做200题专业版测试</a> | 
                <a href="test.php?type=144">做144题进阶版测试</a> | 
                <a href="test.php?type=90">做90题经典版测试</a> | 
                <a href="index.php">返回首页</a>
            </p>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">MBTI人格测试</h3>
                    <p class="footer-tagline">探索自我，理解他人，发现人格类型的奥秘。</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 MBTI人格测试平台. 保留所有权利.</p>
            </div>
        </div>
    </footer>
</body>
</html>

