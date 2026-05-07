<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Models\Registerstudent;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function payment(Request $request)
    {
        $trans_time = date('Y-m-d H:i:s');
        $key = env('SBI_PAYMENT_KEY');

        $s_inst_code = $request->s_inst_code;
        $paying_for = $request->paying_for; // ENROLMENT
        $part_sem = $request->part_sem;     // Part_I
        $type = $request->type;             // REGULAR or CASUAL
        $exam_year = $request->exam_year;
        $merchIdVal = env('SBI_MERCHANT_ID');
        $actionUrl = env('SBI_PAYMENT_API');
        $reg_no = $request->s_appl_form_num;
        // amount start
        $latefine = $request->latefine;
        $fee = DB::table('pharmacy_fees')
            ->where('fees_type', $paying_for)
            ->where('anual', $request->part_sem)
            ->select('fees_amount')
            ->first();

        $regular_amount = $fee->fees_amount ?? 0;

        if ($latefine === true) {
            $fine_type = ($paying_for == "ENROLLMENT") ? 'ENROLLMENT_LATE_FEES' : null;

            if ($fine_type) {
                $fine = DB::table('pharmacy_fees')
                    ->where('fees_type', $fine_type)
                    ->where('anual', $request->part_sem)
                    ->select('fees_amount')
                    ->first();

                $fine_amount = $fine->fees_amount ?? 0;
                $amount = $regular_amount + $fine_amount;
            } else {
                $amount = $regular_amount;
            }
        } else {
            $amount = $regular_amount;
        }
        // amount end
     
        $count = count($reg_no);
        
        $total_amount = $amount * $count;

        $reg_list = implode(',', $reg_no);

        if ($total_amount) {
            $other_data = "{$exam_year}_{$paying_for}_{$s_inst_code}_{$reg_list}_{$part_sem}_{$type}";

            $orderid = '';
            for ($i = 0; $i < 10; $i++) {
                $d = rand(1, 30) % 2;
                $d = $d ? chr(rand(65, 90)) : chr(rand(48, 57));
                $orderid .= $d;
            }
            $marid = '5';
            $merchant_order_num = $orderid;
            $base_url = env('APP_URL') . '/enrollment/payment/';
            $success_url = "{$base_url}success";
            $fail_url = "{$base_url}faill";

            $requestParameter = "{$merchIdVal}|DOM|IN|INR|$total_amount|$other_data|$success_url|$fail_url|SBIEPAY|$merchant_order_num|$marid|NB|ONLINE|ONLINE";

            $EncryptTrans = encryptedString($requestParameter, $key);
            PaymentTransaction::create([

                'order_id' => $orderid,
                'initiated_by' => $s_inst_code,
                'initiated_at' => now(),
                'paying_for' => $paying_for,
                'trans_amount' => $total_amount,
                'semester' => $part_sem,
                'form_no' => $reg_list,
                'form_type' => $type
            ]);

            // $fees_data->update([
            //     'order_id' => $orderid,
            // ]);

            auditTrail($s_inst_code, "Payment initiated for students: {$reg_list} with order id : {$orderid} for {$paying_for}");

            return response()->json([
                'error' => false,
                'message' => 'Payment Data Found',

                'EncryptTrans' => $EncryptTrans,
                'merchIdVal' => $merchIdVal,
                'actionUrl' => $actionUrl
            ]);
            // return view('paynow', [
            //     'EncryptTrans' => $EncryptTrans,
            //     'merchIdVal' => $merchIdVal,
            //     'actionUrl' => $actionUrl
            // ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong, Try Again Later'
            ]);
        }
    }

    public function enrollmentPaymentSuccess(Request $request)
    {
        $trans_details = sbiDecrypt($request->encData);
        $data = explode('|', $trans_details);

        $order_id = $data[0];
        $trans_id = $data[1];
        $trans_status = $data[2];
        $trans_amount = $data[3];
        $currency = $data[4];
        $trans_mode = $data[5];
        $message = $data[7];
        $trans_time = $data[10];
        $country_code = $data[11];
        $marchnt_id = $data[13];
        $other_data = explode('_', $data[6]);

        $exam_year = $other_data[0];
        $paying_for = $other_data[1];
        $s_inst_code = $other_data[2];
        $s_appl_form_num = explode(',', $other_data[3]);
        $form_num = $other_data[3];
        $part_sem = $other_data[4];
        $type = $other_data[6];
        if ($paying_for == 'ENROLLMENT') {

            $tranction = PaymentTransaction::where('order_id', $order_id)->first();

            if ($tranction) {
                $tranction->update([
                    'trans_id' => $trans_id,
                    'trans_status' => $trans_status,
                    'trans_amount' => $trans_amount,
                    'trans_mode' => $trans_mode,
                    'trans_time' => $trans_time,
                    'country_code' => $country_code,
                    'marchnt_id' => $marchnt_id,
                    'trans_details' => $trans_details,
                    'is_verified' => 1,
                ]);

                Payment::create([
                    'order_id' => $order_id,
                    'trans_id' => $trans_id,
                    'paid_type' => $paying_for,
                    'paid_amount' => $trans_amount,
                    'paid_at' => $trans_time,
                    'payment_mode' => $trans_mode,
                    'detail' => $trans_details,
                    'exam_year' => $exam_year,
                ]);


                Registerstudent::whereIn('s_appl_form_num', $s_appl_form_num)->update(['s_part1_status' => 4]);
                // auditTrail($user_id, "Payment for Exam Year: {$exam_year}, ORDER ID: {$order_id}, TRANSACTION ID: {$form_num}, Semester {$part_sem}, Type: {$type}");

                return redirect()->route('payment.redirect', [
                    'trans_id' => $trans_id,
                    'order_id' => $order_id,
                    'paying_for' => $paying_for,
                    'message' => $message,
                    'currency' => $currency,
                    'trans_amount' => $trans_amount,
                    'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
                    'trans_status' => $trans_status,
                ]);
            }

        }
    }

    public function enrollmentPaymentFail(Request $request)
    {
        // Merchant Order Number|SBIePayRefID/ATRN|Transaction Status|Amount|Currency|Pay Mode|Other Details|Reason/Message|Bank Code|Bank Reference Number|Transaction Date|Country|CIN|Merchant ID|Total Fee GST |Ref1|Ref2|Ref3|Ref4|Ref5|Ref6|Ref7|Ref8|Ref9
        // "C8YD3U722D|NA|FAIL|1|INR|NB|2025_ENROLLMENT_BMD_PHARMA01633,PHARMA12997_Part_I_REGULAR|User Cancel Transaction|NA|NA|2025-06-20 13:08:25|IN|00|1001954|0.00^0.00|||||||||

        $trans_details = sbiDecrypt($request->encData);
        $data = explode('|', $trans_details);

        $order_id = $data[0];
        $trans_id = $data[1];
        $trans_status = $data[2];
        $trans_amount = $data[3];
        $currency = $data[4];
        $trans_mode = $data[5];
        $message = $data[7];
        $trans_time = $data[10];
        $country_code = $data[11];
        $marchnt_id = $data[13];
        $other_data = explode('_', $data[6]);

        $exam_year = $other_data[0];
        $paying_for = $other_data[1];
        $s_inst_code = $other_data[2];
        $s_appl_form_num = explode(',', $other_data[3]);
        $form_num = $other_data[3];
        $part_sem = $other_data[4];
        $type = $other_data[6];

        $tranction = PaymentTransaction::where('order_id', $order_id)->first();

        if ($tranction) {
            $tranction->update([
                'trans_id' => $trans_id,
                'trans_status' => $trans_status,
                'trans_amount' => $trans_amount,
                'trans_mode' => $trans_mode,
                'trans_time' => $trans_time,
                'country_code' => $country_code,
                'marchnt_id' => $marchnt_id,
                'trans_details' => $trans_details,
                'is_verified' => 1,
            ]);

            auditTrail($form_num, "Payment {$trans_status}, FORM NO:{$form_num} ORDER ID: {$order_id}, TRANSACTION ID: {$trans_id} in {$exam_year}");


            return redirect()->route('payment.redirect', [
                'trans_id' => $trans_id,
                'order_id' => $order_id,
                'paying_for' => $paying_for,
                'message' => $message,
                'currency' => $currency,
                'trans_amount' => $trans_amount,
                'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
                'trans_status' => $trans_status,
            ]);
        }
    }

    public function paymentOffline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paying_for' => ['required'],
            'demand_date' => ['required'],
            'demand_no' => ['required'],
            'part_sem' => ['required'],
            'type' => ['required'],
            's_appl_form_num' => ['required'],
        ], [], [
            'paying_for' => 'Paying For',
            'part_sem' => 'Semester',
            'type' => 'Payment Type',
            's_appl_form_num' => 'Student Form Number',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
        $demand_date = $request->demand_date;
        $demand_no = $request->demand_no;
        $s_inst_code = $request->s_inst_code;
        $part_sem = $request->part_sem;
        $paying_for = $request->paying_for;
        $type = $request->type;
        $s_appl_form_num = $request->s_appl_form_num;

        $orderid = '';
        for ($i = 0; $i < 10; $i++) {
            $d = rand(1, 30) % 2;
            $d = $d ? chr(rand(65, 90)) : chr(rand(48, 57));
            $orderid .= $d;
        }
        $trans_id = $demand_no;
      
        $latefine = $request->latefine;
        //amount start
        $fee = DB::table('pharmacy_fees')
            ->where('fees_type', $paying_for)
            ->where('anual', $request->part_sem)
            ->select('fees_amount')
            ->first();

        $regular_amount = $fee->fees_amount ?? 0;

        if ($latefine === true) {
            $fine_type = ($paying_for == "ENROLLMENT") ? 'ENROLLMENT_LATE_FEES' : null;

            if ($fine_type) {
                $fine = DB::table('pharmacy_fees')
                    ->where('fees_type', $fine_type)
                    ->where('anual', $request->part_sem)
                    ->select('fees_amount')
                    ->first();

                $fine_amount = $fine->fees_amount ?? 0;
                $amount = $regular_amount + $fine_amount;
            } else {
                $amount = $regular_amount;
            }
        } else {
            $amount = $regular_amount;
        }
        // amount end

        $count = count($s_appl_form_num);
        
        $total_amount = $amount * $count;

        $reg_list = implode(',', $s_appl_form_num);
        $marchent_id = env('SBI_PAYMENT_MERCHANT_ID');
        $trans_status = 'SUCCESS';
        PaymentTransaction::create([
            'order_id' => $orderid,
            'initiated_by' => $s_inst_code,
            'initiated_at' => $demand_date,
            'paying_for' => $paying_for,
            'trans_amount' => $total_amount,
            'semester' => $part_sem,
            'form_no' => $reg_list,
            'form_type' => $type,
            'trans_id' => $trans_id,
            'trans_status' => $trans_status,
            'trans_mode' => 'OFFLINE',
            'trans_time' => now(),
            'country_code' => 'IND',
            'marchnt_id' => $marchent_id,
            'trans_details' => 'Offline payment details',
            'is_verified' => 1,
        ]);
        Payment::create([
            'order_id' => $orderid,
            'exam_year' => $request->exam_year,
            'form_id' => $reg_list,
            'inst_code' => $s_inst_code,
            'trans_id' => $trans_id,
            'paid_type' => $paying_for,
            'paid_amount' => $total_amount,
            'paid_at' => now(),
            'payment_mode' => 'OFFLINE',
            'detail' => 'Offline payment details',
        ]);

        Registerstudent::whereIn('s_appl_form_num', $s_appl_form_num)->update(['s_part1_status' => 4]);

        Enrollment::whereIn('enrl_form_num', $s_appl_form_num)->update(['enrl_is_paid' => 1, 'enrl_type' => $type]);
        foreach ($s_appl_form_num as $formnum) {
            auditTrail($formnum, "Payment for Exam Year: {$request->exam_year}, ORDER ID: {$orderid}, TRANSACTION ID: {$trans_id}, Semester {$part_sem}, Type: {$type}");
        }
        return [
            'paying_for' => $paying_for,
            'trans_id' => $trans_id,
            'order_id' => $orderid,
            'message' => "Payment offline success",
            'currency' => "INR",
            'trans_amount' => $total_amount,
            'trans_time' => now(),
            'trans_status' => $trans_status,
        ];
    }

}
