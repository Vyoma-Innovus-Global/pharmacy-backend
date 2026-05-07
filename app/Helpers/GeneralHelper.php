<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\AuditTrail;
use App\Models\StudentActivity;
use App\Models\PaymentTransaction;
use App\Models\Schedule;
use App\Models\Registerstudent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\PaymentLib\AESEncDec;
use Illuminate\Support\Facades\Config;

function authUserId()
{
    $header = request()->headers->get('auth-user-data');
    return json_decode($header, true)['user_id'];
}

if (!function_exists('generateLaravelLog')) {

    function generateLaravelLog($e)
    {
        $routeArray = app('request')->route()->getAction();
        $controllerAction = class_basename($routeArray['controller']);
        list($controller, $action) = explode('@', $controllerAction);

        Log::info($controller . '||' . $action . ' ERROR-' . $e->getMessage()

            . "\nFile path :" . $e->getFile()
            . "\nline no :" . $e->getLine());
        // dd($controller, $action);
    }
}

if (!function_exists("auditTrail")) {
    function auditTrail($user_id, $task)
    {
        AuditTrail::create([
            'audittrail_user_id' => $user_id,
            'audittrail_ip' => request()->ip(),
            'audittrail_task' => $task,
            'audittrail_date' => now()
        ]);
    }
}

if (!function_exists("studentActivite")) {
    function studentActivite($user_id, $task)
    {
        StudentActivity::create([
            'a_stu_id' => $user_id,
            'a_ip' => request()->ip(),
            'a_task' => $task,
            'a_date' => now()
        ]);
    }
}

if (!function_exists("searchAssociative")) {
    function searchAssociative($arr, $key, $value)
    {
        foreach ($arr as $data) {
            if ($data[$key] == $value) {
                return true;
            } else {
                return false;
            }
        }
    }
}

if (!function_exists('sessionYear')) {
    function sessionYear($year)
    {
        $a = $year;
        $b = Str::charAt(($year + 1), 2) . Str::charAt(($year + 1), 3);

        return "{$a}-{$b}";
    }
}

//CHANGE DATE FORMATE OF A DATE
if (!function_exists('formatDate')) {
    function formatDate($date, $fromFormat = 'Y-m-d', $toFormat = 'd-M-Y')
    {
        $dt = new DateTime();
        if ($date != null) {
            $datetime = $dt->createFromFormat($fromFormat, $date)->format($toFormat);
            return $datetime;
        } else {
            return '---';
        }
    }
}

//generate otp
if (!function_exists('generateOTP')) {
    function generateOTP()
    {
        $possible_letters = '1234567890';
        $code = '';
        for ($x = 0; $x < 6; $x++) {
            $code .= ($num = substr($possible_letters, mt_rand(0, strlen($possible_letters) - 1), 1));
        }
        return $code;
    }
}

//get time difference in minute
if (!function_exists('getTimeDiffInMinute')) {
    function getTimeDiffInMinute($time1, $time2)
    {
        $minutes = (strtotime($time1) - strtotime($time2)) / 60;

        return $minutes;
    }
}

//send Sms
if (!function_exists('send_sms')) {
    function send_sms($phone_to, $sms_message)
    {
        $is_send_otp = Config::get('app.env') == 'production' ? true : false;

        if ($is_send_otp) {
            $template_id = 0;
            $response = Http::withoutVerifying()
                ->withQueryParameters([
                    'ukey' => 'xa1a8ogxRdKjGM62zMO3yti3P',
                    'msisdn' => urlencode($phone_to),
                    'language' => 0,
                    'credittype' => 7,
                    'senderid' => 'TVESD',
                    'templateid' => urlencode($template_id),
                    'message' => $sms_message,
                    'filetype' => 2
                ])->get('https://125.16.147.178/VoicenSMS/webresources/CreateSMSCampaignGet');

            return $response;
        } else {
            return true;
        }
    }
}

if (!function_exists('sessionYear')) {
    function sessionYear($year)
    {
        $a = (int) $year - 1;
        $b = Str::charAt($year, 2) . Str::charAt($year, 3);

        return "{$a}-{$b}";
    }
}

if (!function_exists('getFinancialYear')) {
    function getFinancialYear($currentSession, $type = "")
    {
        $current = explode("-", $currentSession);

        $y = $current[0];
        $yy = $current[1];
        $m = date('m');

        $financial_year = array();
        if ($type == 'regular') {
            $year = $y . '-' . ($yy);
            array_push($financial_year, $year);
        } elseif ($type == 'continuing') {
            for ($i = 0; $i <= 2; $i++) {
                if ($i == 0) {
                    $year = $y - 1 . '-' . ($yy - 1);
                    array_push($financial_year, $year);
                } else if ($i == 1) {
                    $year = ($y - ($i + 1)) . '-' . ($yy - 2);
                    array_push($financial_year, $year);
                }
            }
        } else {
            for ($i = 0; $i <= 2; $i++) {
                if ($i == 0) {
                    $year = $y . '-' . ($yy);
                    array_push($financial_year, $year);
                } else if ($i == 1) {
                    $year = ($y - $i) . '-' . ($yy - 1);
                    array_push($financial_year, $year);
                } else {
                    $year = ($y - $i) . '-' . ($yy - 2);
                    array_push($financial_year, $year);
                }
            }
        }

        return $financial_year;
    }
}

//generate random code
if (!function_exists('generateRandomCode')) {
    function generateRandomCode($length = 6)
    {
        $possible_letters = '23456789BCDFGHJKMNPQRSTVWXYZ';
        $code = '';
        for ($x = 0; $x < $length; $x++) {
            $code .= ($num = substr($possible_letters, mt_rand(0, strlen($possible_letters) - 1), 1));
        }
        return $code;
    }
}

if (!function_exists('encryptHEXFormat')) {
    function encryptHEXFormat($data, $key = null)
    {
        if ($key == null) {
            $key = env('ENC_KEY');
        }
        return bin2hex(openssl_encrypt($data, 'aes-256-ecb', $key, OPENSSL_RAW_DATA));
    }
}

if (!function_exists('decryptHEXFormat')) {
    function decryptHEXFormat($data, $key = null)
    {
        if ($key == null) {
            $key = env('ENC_KEY');
        }
        return trim(openssl_decrypt(hex2bin($data), 'aes-256-ecb', $key, OPENSSL_RAW_DATA));
    }
}

if (!function_exists('str_to_hex')) {
    function str_to_hex($string)
    {
        $hexstr = unpack('H*', $string);
        return array_shift($hexstr);
    }
}
if (!function_exists('counsCategory')) {
    function counsCategory()
    {
        $arr = [
            'OBCA' => 'OBC-A',
            'OBCB' => 'OBC-B',
            'GENERAL' => 'GENERAL',
            'SC' => 'SC',
            'ST' => 'ST',
            'DQFGEN' => 'DISTRICT QUOTA FEMALE GENERAL',
            'DQFSC' => 'DISTRICT QUOTA FEMALE SCHEDULED CASTE',
            'DQFST' => 'DISTRICT QUOTA FEMALE SCHEDULED TRIBE',
            'DQOGEN' => 'DISTRICT QUOTA OPEN GENERAL',
            'DQOSC' => 'DISTRICT QUOTA OPEN SCHEDULED CASTE',
            'DQOST' => 'DISTRICT QUOTA OPEN SCHEDULED TRIBE',
            'DQFPC' => 'DISTRICT QUOTA FEMALE PHYSICALLY CHALLENGED',
            'DQOPC' => 'DISTRICT QUOTA OPEN PHYSICALLY CHALLENGED',
            'SQOBCA' => 'STATE QUOTA OBC-A',
            'SQOBCB' => 'STATE QUOTA OBC-B',
            'SQST' => 'STATE QUOTA SCHEDULED TRIBE',
            'SQSC' => 'STATE QUOTA SCHEDULED CASTE',
            'SQGEN' => 'STATE QUOTA GENERAL',
            'SQPC' => 'STATE QUOTA PHYSICALLY CHALLENGED',
            'EXS' => 'WARDS OF EX-SERVICEMAN',
            'EXSM' => 'WARDS OF EX-SERVICEMAN',
            'LLQ' => 'LAND LOOSER QUOTA',
            'TFW' => 'TUTION FEE WAIVER',
            'GEN' => 'GENERAL',
            'PWD' => 'PC',
            'EWS' => 'ECONOMICALLY WEAKER SECTIONS',
            'SQFPC' => 'STATE QUOTA FEMALE PHYSICALLY CHALLENGED',
            'SQFGEN' => 'STATE QUOTA FEMALE GENERAL',
            'SQFSC' => 'STATE QUOTA FEMALE SCHEDULED CASTE',
            'SQFST' => 'STATE QUOTA FEMALE SCHEDULED TRIBE',
            'SQFOBCA' => 'STATE QUOTA FEMALE OBC-A',
            'SQFOBCB' => 'STATE QUOTA FEMALE OBC-B',
            'SQOPC' => 'STATE QUOTA OPEN PHYSICALLY CHALLENGED',
            'SQOGEN' => 'STATE QUOTA OPEN GENERAL',
            'SQOSC' => 'STATE QUOTA OPEN SCHEDULED CASTE',
            'SQOST' => 'STATE QUOTA OPEN SCHEDULED TRIBE',
            'SQOOBCA' => 'STATE QUOTA OPEN OBC-A',
            'SQOOBCB' => 'STATE QUOTA OPEN OBC-B',
            'DQFOBCA' => 'DISTRICT QUOTA FEMALE OBC-A',
            'DQFOBCB' => 'DISTRICT QUOTA FEMALE OBC-B',
            'DQOOBCA' => 'DISTRICT QUOTA OPEN OBC-A',
            'DQOOBCB' => 'DISTRICT QUOTA OPEN OBC-B',
            'SQFLLQ' => 'STATE QUOTA FEMALE LAND LOOSER QUOTA',
            'SQOLLQ' => 'STATE QUOTA OPEN LAND LOOSER QUOTA',
            'DQOLLQ' => 'DISTRICT QUOTA OPEN LAND LOOSER QUOTA',
            'SQPWD' => 'STATE QUOTA PHYSICALLY CHALLENGED',
        ];

        return $arr;
    }
}

if (!function_exists('casteValue')) {
    function casteValue($key)
    {

        $arr = counsCategory();
        return $arr[$key];
    }
}

if (!function_exists('encryptedString')) {
    function encryptedString($requestParameter, $key)
    {
        $aes = new AESEncDec();
        $EncryptTrans = $aes->encrypt($requestParameter, $key);
        return $EncryptTrans;
    }
}

if (!function_exists('cast')) {
    function cast()
    {
        $category_preference = [
            'tfw' => 1,
            'ews' => 2,

            'sqfllq' => 3,
            'sqollq' => 4,
            'dqollq' => 5,

            'exs' => 6,

            'sqfpc' => 7,
            'sqfgen' => 8,
            'sqfsc' => 9,
            'sqfst' => 9,
            'sqfobca' => 9,
            'sqfobcb' => 9,

            'sqopc' => 10,
            'sqpwd' => 11,

            'sqogen' => 12,
            'sqosc' => 13,
            'sqost' => 13,
            'sqoobca' => 13,
            'sqoobcb' => 13,

            'dqfpc' => 14,
            'dqfgen' => 15,
            'dqfsc' => 16,
            'dqfst' => 16,
            'dqfobca' => 16,
            'dqfobcb' => 16,

            'dqopc' => 17,

            'dqogen' => 18,
            'dqosc' => 19,
            'dqost' => 19,
            'dqoobca' => 19,
            'dqoobcb' => 19
        ];
        return array_keys($category_preference);
    }
}

if (!function_exists('castFresh')) {
    function castFresh()
    {
        $category_preference = array(
            'tfw' => 1,

            'sqogen' => 12
        );
        return array_keys($category_preference);
    }
}

if (!function_exists('castSpot')) {
    function castSpot()
    {
        $category_preference = array(
            'tfw' => 1,
            'sqpwd' => 2,
            'sqogen' => 12,
            'sqosc' => 10,
            'sqost' => 9
        );
        return array_keys($category_preference);
    }
}

if (!function_exists('config_schedule')) {
    function config_schedule($event)
    {
        $time = date('Y-m-d H:i:s');
        $data = Schedule::where('sch_event', $event)->where('sch_start_dt', '<=', $time)->where('sch_end_dt', '>=', $time)->first();

        if ($data) {
            return [
                'round' => $data->sch_round,
                'event' => $data->sch_event,
                'status' => true,
            ];
        } else {
            return [
                'round' => '',
                'event' => '',
                'status' => false,
            ];
        }
    }
}

if (!function_exists('generateManagementApplicationNumber')) {
    function generateManagementApplicationNumber($val)
    {
        $sl_num = str_pad($val, 6, '0', STR_PAD_LEFT);
        $appl_no = date('y') . $sl_num;

        return $appl_no;
    }
}

if (!function_exists('resizeImage')) {
    function resizeImage($path, $width = 200, $height = null)
    {
        $path = public_path() . "/" . $path;
        $mime_type = mime_content_type($path);
        // Get the original image's dimensions
        list($original_width, $original_height) = getimagesize($path);

        $aspect_ratio = $original_width / $original_height;
        $height = $width / $aspect_ratio;

        // Create a new image with the desired dimensions
        $resized_image = imagecreatetruecolor($width, $height);

        // Read the original image
        if ($mime_type == "image/webp") {
            $original_image = imagecreatefromwebp($path);
        } else if ($mime_type == "image/jpeg") {
            $original_image = imagecreatefromjpeg($path);
        } else if ($mime_type == "image/png") {
            $original_image = imagecreatefrompng($path);
        }

        // Resize the original image to fit the desired dimensions
        imagecopyresampled($resized_image, $original_image, 0, 0, 0, 0, $width, $height, $original_width, $original_height);

        // Save the resized image, overwriting the original image
        imagejpeg($resized_image, $path);

        // Free up memory
        imagedestroy($original_image);
        imagedestroy($resized_image);
    }
}

if (!function_exists('getOverallStatus')) {
    function getOverallStatus($form_num)
    {
        $student = DB::table('pharmacy_register_student_final as rs')
            ->where('rs.s_appl_form_num', $form_num)->first();

        $status = "DATA_NOT_AVAILABLE";
        return $status;
    }
}


if (!function_exists('failPaymentPharmacy')) {
    function failPaymentPharmacy($orderid, $user_id, $fee_type, $total_amount)
    {
        $trans_time = date('Y-m-d H:i:s');
        PaymentTransaction::create([
            'order_id' => $orderid,
            'pmnt_modified_by' => $user_id,
            'pmnt_stud_id' => $user_id,
            'pmnt_created_on' => $trans_time,
            'trans_amount' => intval($total_amount),
            'pmnt_pay_type' => 'COUNSELLINGFEES'
        ]);
        $message = "Payment initiated for order ID {$orderid}";
        auditTrail($user_id, $message);
        studentActivite($user_id, $message);
    }
}


// if (!function_exists('failSpotPaymentCounselling')) {
//     function failSpotPaymentCounselling($orderid, $user_id, $fee_type, $total_amount)
//     {
//         $trans_time = date('Y-m-d H:i:s');
//         PaymentSpotTransaction::create([
//             'order_id' => $orderid,
//             'pmnt_modified_by' => $user_id,
//             'pmnt_stud_id' => $user_id,
//             'pmnt_created_on' => $trans_time,
//             'trans_amount' => intval($total_amount),
//             'pmnt_pay_type' => 'COUNSELLINGSPOTFEES'
//         ]);
//         $message = "Payment initiated for order ID {$orderid}";
//         auditTrail($user_id, $message);
//         studentActivite($user_id, $message);
//     }
// }

if (!function_exists('swapCatArr')) {
    function swapCatArr()
    {
        $swap_category_arr = [
            'ews' => 'sqogen',
            'sqfllq' => 'sqogen',
            'sqollq' => 'sqogen',
            'dqollq' => 'sqogen',

            'exs' => 'sqogen',

            'sqfpc' => 'sqogen',
            'sqfgen' => 'sqogen',
            'sqfsc' => 'sqosc',
            'sqfst' => 'sqost',
            'sqfobca' => 'sqogen',
            'sqfobcb' => 'sqogen',

            'sqopc' => 'sqogen',


            'sqoobca' => 'sqogen',
            'sqoobcb' => 'sqogen',

            'dqfpc' => 'sqogen',
            'dqfgen' => 'sqogen',
            'dqfsc' => 'sqosc',
            'dqfst' => 'sqost',
            'dqfobca' => 'sqogen',
            'dqfobcb' => 'sqogen',

            'dqopc' => 'sqogen',

            'dqogen' => 'sqogen',
            'dqosc' => 'sqosc',
            'dqost' => 'sqost',
            'dqoobca' => 'sqogen',
            'dqoobcb' => 'sqogen'
        ];
        return $swap_category_arr;
    }

    function studentData($data, $rank)
    {
        return json_encode([
            's_id' => $data->s_id,
            's_uuid' => $data->s_uuid,
            's_ref' => md5($data->s_id),
            's_index_num' => $data->s_index_num,
            's_appl_form_num' => $data->s_appl_form_num,
            's_first_name' => $data->s_first_name,
            's_middle_name' => $data->s_middle_name,
            's_last_name' => $data->s_last_name,
            's_full_name' => $data->s_candidate_name,
            's_father_name' => $data->s_father_name,
            's_mother_name' => $data->s_mother_name,
            's_dob' => $data->s_dob,
            's_aadhar_no' => $data->s_aadhar_no,
            's_phone' => $data->s_phone,
            's_email' => $data->s_email,
            's_gender' => $data->s_gender,
            's_religion' => $data->s_religion,
            's_caste' => $data->s_caste,
            's_tfw' => $data->s_tfw,
            's_pwd' => $data->s_pwd,
            's_llq' => $data->s_llq,
            's_exsm' => $data->s_exsm,
            's_alloted_category' => $data->s_alloted_category,
            's_alloted_round' => $data->s_alloted_round,
            's_choice_id' => $data->s_choice_id,
            's_trade_code' => $data->s_trade_code,
            's_inst_code' => $data->s_inst_code,
            'is_alloted' => $data->is_alloted,
            'is_choice_fill_up' => $data->is_choice_fill_up,
            'is_payment' => $data->is_payment,
            'is_upgrade' => $data->is_upgrade,
            's_photo' => $data->s_photo,
            's_home_district' => !is_null($data->s_home_district) ? $data->s_home_district : "",
            's_schooling_district' => !is_null($data->s_schooling_district) ? $data->s_schooling_district : "",
            's_state_id' => $data->s_state_id,
            'is_active' => $data->is_active,
            'is_lock_manual' => $data->is_lock_manual,
            'is_lock_auto' => $data->is_lock_auto,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
            'manual_lock_at' => $data->manual_lock_at,
            'auto_lock_at' => $data->auto_lock_at,
            'rank' => $rank,
            'address' => $data->address,
            'ps' => $data->ps,
            'po' => $data->po,
            'pin' => $data->pin,
            'is_married' => (bool) $data->is_married,
            'is_kanyashree' => (bool) $data->is_kanyashree,
            'role_id' => 2,
        ]);
    }
    if (!function_exists('GenerateRegNo')) {
        function GenerateRegNo($form_id)
        {
            $data = Registerstudent::where('s_appl_form_num', $form_id)->first();
            $sessionYear = $data->s_appl_sess_year;
            $instCode = $data->s_inst_code;
            $sessionParts = explode('-', $sessionYear);
            $sessionCode = substr($sessionParts[0], -2) . substr($sessionParts[1], -2);
            $prefix = 'PHARM' . $sessionCode;
            $registrationId = $prefix . str_pad($counter, 5, '0', STR_PAD_LEFT);
            return $registrationId;
        }
    }
}
if (!function_exists('checkStatus')) {
    function checkStatus($status)
    {
        $statusMap = [
            1 => 'Eligible for registration',
            2 => 'Registration no generated',
            3 => 'Eligible for Enrollment',
            4 => 'Enrollment Payment Done',
            5 => 'Eligible for Exam',
            9 => 'Rejected'
        ];

        return $statusMap[$status] ?? 'Unknown status';
    }
}
function sbiEncrypt($data)
{
    $key = env('SBI_PAYMENT_KEY');

    $cipherText = openssl_encrypt(
        $data,
        'aes-128-cbc',
        $key,
        OPENSSL_RAW_DATA,
        substr($key, 0, 16)
    );

    return base64_encode($cipherText);
}

function sbiDecrypt($data)
{
    $key = env('SBI_PAYMENT_KEY');

    $plaintext = openssl_decrypt(
        base64_decode($data),
        'aes-128-cbc',
        $key,
        OPENSSL_RAW_DATA,
        substr($key, 0, 16)
    );

    return $plaintext;
}
function getPaymentData($orderid, $pay_amount, $other_data)
{
    $marid = '5';
    $marchent_id = env('SBI_PAYMENT_MERCHANT_ID');
    $api_key = env('SBI_PAYMENT_API');

    $base_url = env('APP_URL') . "/payment/";
    $success_url = "{$base_url}success";
    $fail_url = "{$base_url}fail";

    $paymentData = "{$marchent_id}|DOM|IN|INR|{$pay_amount}|{$other_data}|{$success_url}|{$fail_url}|SBIEPAY|{$orderid}|{$marid}|NB|ONLINE|ONLINE,pWhMnIEMc4q6hKdi2Fx50Ii8CKAoSIqv9ScSpwuMHM4=";

    return [
        'transaction_id' => sbiEncrypt($paymentData),
        'marchant_id' => $marchent_id,
        'payment_api' => $api_key
    ];
}
function countChance($year)
{
    $currentYear = date("Y");
    $enrl_year = $year;
    $gapYear = $currentYear - $enrl_year;
    return $gapYear;
}

function convertSubname($subcode)
{
    $data = DB::table('pharmacy_subjects_master')->where('general_code', $subcode)
        ->select('subject_name')->first();
    return $data->subject_name;
}

