<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #F8F9FB;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #111827;
        }
        .unsubscribe-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .unsubscribe-card p {
            font-size: 0.9375rem;
            line-height: 1.6;
            margin: 0 0 1rem;
        }
        .unsubscribe-card .redirect-text {
            font-size: 0.8125rem;
            color: #6B7280;
        }
        .unsubscribe-card a {
            color: #4F46E5;
            text-decoration: none;
            font-weight: 500;
        }
        .unsubscribe-card a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="unsubscribe-card">
        <p><?= $message ?></p>
        <p class="redirect-text">You will be redirected in 5 seconds.<br>
        If not, <a href="https://www.flattered.com">click here</a>.</p>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = "https://www.flattered.com";
        }, 5000);
    </script>
</body>
</html>
