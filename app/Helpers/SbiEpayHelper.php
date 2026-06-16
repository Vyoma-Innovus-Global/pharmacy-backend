<?php

function sbiEncrypt($data)
{
    $key = env('SBI_PAYMENT_KEY');
    $iv = sbiPaymentIv($key);

    $cipherText = openssl_encrypt(
        $data,
        'aes-128-cbc',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return base64_encode($cipherText);
}

function sbiDecrypt($data)
{
    $key = env('SBI_PAYMENT_KEY');
    $iv = sbiPaymentIv($key);

    $plaintext = openssl_decrypt(
        base64_decode($data),
        'aes-128-cbc',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return $plaintext;
}

function sbiPaymentIv($key)
{
    if (!is_string($key) || strlen($key) < 16) {
        throw new RuntimeException('SBI payment key is missing or invalid. Please configure SBI_PAYMENT_KEY with at least 16 characters.');
    }

    return substr($key, 0, 16);
}

function encryptHEXFormat($data)
{
    $key = env('ENC_KEY');
    return bin2hex(openssl_encrypt($data, 'aes-256-ecb', $key, OPENSSL_RAW_DATA));
}

function decryptHEXFormat($data)
{
    $key = env('ENC_KEY');
    return trim(openssl_decrypt(hex2bin($data), 'aes-256-ecb', $key, OPENSSL_RAW_DATA));
}

function getPaymentData($uri, $orderid, $pay_amount, $other_data)
{
    $marid = '5';
    $marchent_id = env('SBI_PAYMENT_MERCHANT_ID', env('SBI_MERCHANT_ID'));
    $api_key = env('SBI_PAYMENT_API');

    if (empty($marchent_id)) {
        throw new RuntimeException('SBI payment merchant id is missing. Please configure SBI_PAYMENT_MERCHANT_ID.');
    }

    if (empty($api_key)) {
        throw new RuntimeException('SBI payment API URL is missing. Please configure SBI_PAYMENT_API.');
    }

    $appUrl = rtrim(env('APP_URL'), '/');
    $base_url = str_ends_with($appUrl, '/api')
        ? "{$appUrl}/{$uri}"
        : "{$appUrl}/api/{$uri}";
    $success_url = "{$base_url}/success";
    $fail_url = "{$base_url}/fail";

    $paymentData = "{$marchent_id}|DOM|IN|INR|{$pay_amount}|{$other_data}|{$success_url}|{$fail_url}|SBIEPAY|{$orderid}|{$marid}|NB|ONLINE|ONLINE,pWhMnIEMc4q6hKdi2Fx50Ii8CKAoSIqv9ScSpwuMHM4=";

    \Illuminate\Support\Facades\Log::info('[SBI Payment] Plain payload before encryption', [
        'merchant_id' => $marchent_id,
        'amount' => $pay_amount,
        'other_data' => $other_data,
        'success_url' => $success_url,
        'fail_url' => $fail_url,
        'aggregator_id' => 'SBIEPAY',
        'order_id' => $orderid,
        'merchant_response_id' => $marid,
        'payment_mode' => 'NB',
        'transaction_type' => 'ONLINE',
        'plain_payload' => $paymentData,
    ]);

    return [
        'transaction_id' => sbiEncrypt($paymentData),
        'marchant_id' => $marchent_id,
        'payment_api' => $api_key
    ];
}

function getPaymentVerifyData($marchent_id, $order_id, $amount)
{
    $url = "https://www.sbiepay.sbi/payagg/statusQuery/getStatusQuery";

    $queryRequest = "|$marchent_id|$order_id|$amount";
    $queryRequest33 = http_build_query(['queryRequest' => $queryRequest, "aggregatorId" => "SBIEPAY", "merchantId" => $marchent_id]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSLVERSION, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $queryRequest33);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo curl_error($ch);
    }
    curl_close($ch);

    return $response;
}
