<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="2;url={{ $frontend_url }}">
    <title>Student Payment - Redirecting</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            padding: 24px;
        }

        .card {
            width: min(520px, 100%);
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 12px;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            text-align: center;
        }

        .header {
            padding: 24px;
            color: #ffffff;
            background: {{ $status === 'SUCCESS' ? '#166534' : '#991b1b' }};
        }

        h1 {
            margin: 0;
            font-size: 23px;
            line-height: 1.25;
        }

        .subtitle {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .body {
            padding: 26px;
        }

        .message {
            margin: 0 0 18px;
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
        }

        .details {
            display: grid;
            gap: 8px;
            margin: 0 auto 20px;
            max-width: 360px;
            text-align: left;
            font-size: 13px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .label {
            color: #64748b;
            font-weight: 700;
        }

        .value {
            color: #1e293b;
            font-weight: 700;
            text-align: right;
            word-break: break-word;
        }

        .loader {
            width: 32px;
            height: 32px;
            border: 4px solid #e2e8f0;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 18px;
        }

        a {
            color: #1d4ed8;
            font-weight: 700;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <main class="card">
        <header class="header">
            <h1>{{ $status === 'SUCCESS' ? 'Payment Successful' : 'Payment Status' }}</h1>
            <p class="subtitle">Taking you back to the student portal</p>
        </header>

        <section class="body">
            <p class="message">{{ $message }}</p>

            <div class="details">
                @if ($order_id)
                    <div class="row">
                        <span class="label">Order ID</span>
                        <span class="value">{{ $order_id }}</span>
                    </div>
                @endif

                @if ($txn_no)
                    <div class="row">
                        <span class="label">Transaction ID</span>
                        <span class="value">{{ $txn_no }}</span>
                    </div>
                @endif

                @if ($amount !== '')
                    <div class="row">
                        <span class="label">Amount</span>
                        <span class="value">{{ $currency }} {{ $amount }}</span>
                    </div>
                @endif
            </div>

            <div class="loader"></div>
            <p class="message">
                If you are not redirected automatically,
                <a href="{{ $frontend_url }}">open the student payment status</a>.
            </p>
        </section>
    </main>

    <script>
        const frontendUrl = @json($frontend_url);
        setTimeout(() => {
            window.location.replace(frontendUrl);
        }, 900);
    </script>
</body>
</html>
