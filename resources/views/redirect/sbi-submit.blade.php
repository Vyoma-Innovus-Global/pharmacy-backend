<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opening SBIePay</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f8fafc;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            padding: 24px;
        }

        .panel {
            width: min(440px, 100%);
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            padding: 28px;
            text-align: center;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        p {
            margin: 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.5;
        }

        .loader {
            width: 32px;
            height: 32px;
            border: 4px solid #e2e8f0;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 20px auto 0;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <main class="panel">
        <h1>Opening Payment Gateway</h1>
        <p>Please wait while we securely connect you to SBIePay.</p>
        <div class="loader"></div>
    </main>

    <form id="sbiPaymentForm" method="POST" action="{{ $actionUrl }}">
        <input type="hidden" name="EncryptTrans" value="{{ $encryptTrans }}">
        <input type="hidden" name="merchIdVal" value="{{ $merchIdVal }}">
    </form>

    <script>
        window.addEventListener('load', () => {
            document.getElementById('sbiPaymentForm').submit();
        });
    </script>
</body>
</html>
