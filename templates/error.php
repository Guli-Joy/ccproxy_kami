<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>系统错误</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.min.css">
    <style>
        .error-container {
            text-align: center;
            padding: 40px 20px;
        }
        .error-code {
            font-size: 120px;
            color: #f44336;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 24px;
            color: #666;
            margin-bottom: 20px;
        }
        .error-description {
            color: #999;
            margin-bottom: 30px;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <div class="error-message">系统暂时无法处理您的请求</div>
        <div class="error-description">
            我们已经记录了这个问题，并将尽快修复。
        </div>
        <a href="javascript:history.back()" class="back-button">返回上一页</a>
    </div>
</body>
</html> 