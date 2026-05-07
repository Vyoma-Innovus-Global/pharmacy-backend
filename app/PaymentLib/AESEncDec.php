<?php

namespace App\PaymentLib;

class AESEncDec

{
    public  function encrypt($data,  $key)
    {
        $algo = 'aes-128-cbc';

        $iv = substr($key, 0, 16);
        //echo $iv;
        $cipherText = openssl_encrypt(
            $data,
            $algo,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        $cipherText = base64_encode($cipherText);

        return $cipherText;
    }


    public function decrypt($cipherText,  $key)
    {
        $algo = 'aes-128-cbc';

        $iv = substr($key, 0, 16);
        //echo $iv;
        $cipherText = base64_decode($cipherText);

        $plaintext = openssl_decrypt(
            $cipherText,
            $algo,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        return $plaintext;
    }
}
