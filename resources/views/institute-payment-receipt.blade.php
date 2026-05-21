<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - WBSCTE&VE&SD</title>
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
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .receipt-container {
            background: white;
            max-width: 800px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
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

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 20px;
            background: white;
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
        }

        .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .success-badge {
            background: #10b981;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            margin: 30px auto 20px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .checkmark {
            width: 24px;
            height: 24px;
            background: white;
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .content {
            padding: 40px;
        }

        .section-title {
            color: #667eea;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
        }

        .amount-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .amount-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .amount-value {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .amount-currency {
            font-size: 18px;
            opacity: 0.9;
        }

        .footer {
            background: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer-note {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(102, 126, 234, 0.05);
            font-weight: 900;
            pointer-events: none;
            z-index: 0;
        }

        .content-wrapper {
            position: relative;
            z-index: 1;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
            }
            .buttons, .btn {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .amount-value {
                font-size: 36px;
            }
            .content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="watermark">PAID</div>

        <div class="header">
            <div class="logo">🎓</div>
            <h1>WBSCTE&VE&SD</h1>
            <p>West Bengal State Council of Technical Education,<br>Vocational Education and Skill Development</p>
        </div>

        <div class="content">
            <div class="content-wrapper">
                <div style="text-align: center;">
                    <div class="success-badge">
                        <span class="checkmark">✓</span>
                        Payment Successful
                    </div>
                </div>

                <div class="amount-box">
                    <div class="amount-label">Amount Paid</div>
                    <div class="amount-value">₹{{ $trans_amount }}</div>
                    <div class="amount-currency">Indian Rupees</div>
                </div>

                <div class="section-title">Transaction Details</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Transaction ID</div>
                        <div class="info-value">{{ $trans_id }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Order ID</div>
                        <div class="info-value">{{ $order_id }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value" style="color: #10b981;">{{ $trans_status }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date & Time</div>
                        <div class="info-value">{{ $trans_time }}</div>
                    </div>
                </div>

                <div class="section-title">Payment Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Institute Code</div>
                        <div class="info-value">{{ $inst_code ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Purpose</div>
                        <div class="info-value">Institute Registration Fee</div>
                    </div>
                    @if(isset($inst_name))
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <div class="info-label">Institute Name</div>
                        <div class="info-value">{{ $inst_name }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-note">
                This is a computer-generated receipt and does not require a signature.<br>
                For any queries, please contact WBSCTE&VE&SD support.<br>
                <strong>Payment Gateway:</strong> SBI ePay | <strong>Receipt Generated:</strong> {{ date('d-m-Y h:i A') }}
            </div>
            <div class="buttons">
                <button class="btn btn-primary" onclick="window.print()">
                    <span>🖨️</span> Print Receipt
                </button>
                <a href="{{ url('/payment/receipt/' . $order_id) }}" class="btn btn-primary" download>
                    <span>📥</span> Download PDF
                </a>
                <a href="{{ url('/') }}" class="btn btn-secondary">
                    <span>🏠</span> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-print option (uncomment if needed)
        // window.onload = function() {
        //     setTimeout(() => window.print(), 500);
        // };
    </script>
</body>
</html>
