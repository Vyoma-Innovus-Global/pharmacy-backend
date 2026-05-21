<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Redirecting...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .success-icon svg {
            width: 50px;
            height: 50px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        h1 {
            color: #1f2937;
            font-size: 28px;
            margin-bottom: 10px;
        }

        p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .details {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #6b7280;
            font-size: 14px;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <svg viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1>Payment Successful!</h1>
        <p>Your payment has been processed successfully.</p>

        <div class="details" id="details" style="display: none;">
            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value" id="trans_id">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order ID:</span>
                <span class="detail-value" id="order_id">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount:</span>
                <span class="detail-value" id="amount">-</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value" style="color: #10b981;" id="status">-</span>
            </div>
        </div>

        <div class="spinner"></div>
        <p style="font-size: 14px; color: #9ca3af;">Redirecting to receipt page...</p>
    </div>

    <script>
        // Get payment data from PHP (passed from controller)
        const paymentData = {
            trans_id: '{{ $trans_id }}',
            order_id: '{{ $order_id }}',
            trans_amount: '{{ $trans_amount }}',
            trans_status: '{{ $trans_status }}',
            trans_time: '{{ $trans_time }}',
            inst_code: '{{ $inst_code }}',
            inst_name: '{{ $inst_name ?? "N/A" }}'
        };

        // Display details
        document.getElementById('trans_id').textContent = paymentData.trans_id;
        document.getElementById('order_id').textContent = paymentData.order_id;
        document.getElementById('amount').textContent = '₹' + paymentData.trans_amount;
        document.getElementById('status').textContent = paymentData.trans_status;
        document.getElementById('details').style.display = 'block';

        // Store in sessionStorage for React app
        sessionStorage.setItem('payment_receipt_data', JSON.stringify(paymentData));

        // Redirect to React receipt page
        setTimeout(() => {
            // Adjust the path based on your React app routing
            const basePath = '/emp/pharmacy';
            const receiptUrl = `${basePath}/portal/payment-receipt?order_id=${paymentData.order_id}&trans_id=${paymentData.trans_id}&amount=${paymentData.trans_amount}&status=${paymentData.trans_status}&time=${encodeURIComponent(paymentData.trans_time)}`;
            window.location.href = receiptUrl;
        }, 2000);
    </script>
</body>
</html>
