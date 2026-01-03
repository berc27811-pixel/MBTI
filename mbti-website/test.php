<?php
include 'config.php';
$type = $_GET['type'] ?? '90'; // 接收 90, 144, 200
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="test.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="theme.css">
    <title>MBTI专业测试 - <?php echo $type; ?>题版</title>
    <style>
        #quiz-app {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .progress-container {
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            height: 10px;
            overflow: hidden;
        }
        #progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #6272ed, #764ba2);
            border-radius: 10px;
            transition: width 0.3s;
            width: 0%;
        }
        #question-area {
            margin: 40px 0;
            min-height: 300px;
        }
        .q-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
            line-height: 1.6;
        }
        #question-area button {
            display: block;
            width: 100%;
            padding: 15px 20px;
            margin: 10px 0;
            background: rgba(98, 114, 237, 0.1);
            border: 2px solid #6272ed;
            border-radius: 8px;
            color: #6272ed;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        #question-area button:hover {
            background: rgba(98, 114, 237, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div id="quiz-app">
        <div class="progress-container">
            <div id="progress-bar"></div>
        </div>
        <div id="question-area"></div>
    </div>

    <script>
        // 将 PHP 的题库直接传给 JS，速度最快，SEO 最好
        const quizData = <?php echo json_encode($all_questions[$type] ?? []); ?>;
        let current = 0;
        let userAnswers = [];

        function renderQuestion() {
            if (current >= quizData.length) {
                submitResult();
                return;
            }
            
            const q = quizData[current];
            const html = `
                <div class="q-title">${current + 1}. ${q.q}</div>
                <button onclick="saveAns('${q.a}')">选项 A</button>
                <button onclick="saveAns('${q.b}')">选项 B</button>
            `;
            document.getElementById('question-area').innerHTML = html;
            document.getElementById('progress-bar').style.width = ((current+1)/quizData.length*100) + '%';
        }

        function saveAns(val) {
            userAnswers.push(val);
            if(current < quizData.length - 1) {
                current++;
                renderQuestion();
            } else {
                submitResult();
            }
        }

        function submitResult() {
            // 发送到后端计算并生成订单
            fetch('api_calculate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({type: '<?php echo $type; ?>', ans: userAnswers})
            }).then(r => r.json()).then(res => {
                if (res.status === 'success') {
                    window.location.href = 'pay.php?order_no=' + res.order_no;
                } else {
                    alert('提交失败：' + (res.msg || '未知错误'));
                }
            }).catch(err => {
                console.error('提交错误:', err);
                alert('网络错误，请重试');
            });
        }

        // 初始化
        if (quizData.length > 0) {
            renderQuestion();
        } else {
            document.getElementById('question-area').innerHTML = '<div class="q-title">题库加载失败，请刷新页面重试</div>';
        }
    </script>
</body>
</html>

