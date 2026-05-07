<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="images/dollar.png" type="image/png" sizes="16x16">
    <title>Payment Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<style>
    body {
        background: #c1bfbf;
        background-image: url("logo.png");
    }

    .container {
        max-width: 30%;
        margin: 50px auto;
        overflow: hidden;
    }

    .printer-top {
        z-index: 1;
        border: 6px solid #666666;
        height: 6px;
        border-bottom: 0;
        border-radius: 6px 6px 0 0;
        background: #333333;
    }

    .printer-bottom {
        z-index: 0;
        border: 6px solid #666666;
        height: 6px;
        border-top: 0;
        border-radius: 0 0 6px 6px;
        background: #333333;
    }

    .paper-container {
        position: relative;
        overflow: hidden;
        height: 500px;
    }

    .paper {
        background: #ffffff;
        font-family: 'Poppins', sans-serif;
        width: 400px;
        /* height: 500px; */
        position: absolute;
        z-index: 2;
        margin: 0 12px;
        margin-top: -12px;
    }

    .main-contents {
        margin: 0 12px;
        padding: 24px;
    }

    .success-icon {
        text-align: center;
        font-size: 48px;
        height: 72px;
        background: #359d00;
        border-radius: 50%;
        width: 72px;
        height: 72px;
        margin: 16px auto;
        color: #fff;
    }

    .failed-icon {
        text-align: center;
        font-size: 48px;
        height: 72px;
        background: #cd2904;
        border-radius: 50%;
        width: 72px;
        height: 72px;
        margin: 16px auto;
        color: #fff;
    }

    .success-title {
        font-size: 22px;
        font-family: 'Poppins', sans-serif;
        text-align: center;
        color: #5dce07;
        font-weight: bold;
        margin-bottom: 16px;
    }

    .failed-title {
        font-size: 22px;
        font-family: 'Poppins', sans-serif;
        text-align: center;
        color: #cd2904;
        font-weight: bold;
        margin-bottom: 16px;
    }

    .success-description {
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
        line-height: 21px;
        color: #999;
        text-align: center;
        margin-bottom: 24px;
    }

    .order-details {
        text-align: center;
        color: #333;
        font-weight: bold;
    }

    .text-center {}

    .order-number-label {
        font-size: 15px;
        margin-bottom: 8px;
    }

    .order-number {
        border-top: 1px solid #ccc;
        border-bottom: 1px solid #ccc;
        line-height: 48px;
        font-size: 15px;
        padding: 8px 0;
        margin-bottom: 24px;
    }

    .complement {
        font-size: 18px;
        margin-top: 40px;
        margin-bottom: 8px;
        color: #32a852;
    }

    .complement a {
        color: #04a999;
    }
</style>

<body>
    <div class="container">
        <div class="paper" style="border: 2px solid #5dce07;">
            <div class="main-contents">
                @if ($trans_status === 'SUCCESS')
                    <div class="success-icon">&#10004;</div>
                    <div class="success-title">{{ $message }}</div>
                @elseif ($trans_status === 'FAIL' || $trans_status === 'ABORT')
                    <div class="failed-icon">&#10006;</div>
                    <div class="failed-title">{{ $message }}</div>
                @endif

                <div class="order-details">
                    <div class="order-number-label">
                        Amount:
                        <span style="margin-top:2px; color:#071cbe8a"> {{ $currency }} {{ $trans_amount }}</span>
                    </div>

                    <div class="order-number-label">
                        Order ID:
                        <span style="margin-top:2px; color:#071cbe8a"> {{ $order_id }}</span>
                    </div>

                    <div class="order-number-label">
                        Transaction ID:
                        <span style="margin-top:2px; color:#071cbe8a"> {{ $trans_id }}</span>
                    </div>

                    <div class="order-number-label">
                        Transaction Time:
                        <span style="margin-top:2px; color:#071cbe8a"> {{ $trans_time }}</span>
                    </div>

                    {{-- <div style="margin-bottom: 10px;">
                        <a href="#" target="_blank">Download Receipt</a>
                    </div> --}}

                    <div class="complement">

                        @if ($paying_for === 'ENROLLMENT')
                            <a href="{{ env('REDIRECT_ENROLLMENT_URL') }}" style="text-decoration: none;">
                                GO BACK
                            </a>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
