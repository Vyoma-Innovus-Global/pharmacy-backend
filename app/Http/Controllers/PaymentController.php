<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Models\Registerstudent;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    //get Enrollment payment fees data
    public function getEnrollmentPaymentdata(Request $request)
    {
        $inst_code = $request->inst_code;
        $paying_for = $request->paying_for; // ENROLMENT
        $part_sem = $request->part_sem;     // Part_I
        $form_num = $request->form_num;
        $reg_num = $request->reg_no;
        $type = $request->type;
        $late_fine = false;
        $totamount = 0;
        $pay_btn = false;
        try{
            $fee = DB::table('pharmacy_fees')
                ->where('fees_type', $paying_for)
                ->where('anual', $request->part_sem)
                ->select('fees_amount')
                ->first();
            if($type == 'REGULAR'){
            $amount = $fee->fees_amount ?? 0;
            }
            else{
                $amount = 150;
            }
            //$amount = $fee->fees_amount;
            $checkLateFees = DB::table('pharmacy_schedule_master')
                            ->where('schedule_type', 'ENROLLMENT_PAYMENT')
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();

            $today = date('Y-m-d');
            if($checkLateFees){
                $pay_btn = true;
                if ($checkLateFees && $checkLateFees->late_start_at <= now()) {
                $late_fine = true;
                }
                else{
                    $late_fine = false;
                }
            }
            else{
                $pay_btn = false;
            }

            if($late_fine){
                $fee = DB::table('pharmacy_fees')
                ->where('fees_type', 'ENROLLMENT_LATE_FEES')
                ->where('anual', $request->part_sem)
                ->select('fees_amount')
                ->first();
                $totamount = $amount + $fee->fees_amount;
            }else{
                $totamount = $amount;
            }

            $enrl_data = Enrollment::where('enrl_reg_no', $reg_num)
                        ->where('is_enrollment', 1)
                        ->where('enrl_part_sem', $part_sem)
                        ->where('enrl_inst_code', $inst_code)
                        ->where('enrl_form_num', $form_num)
                        ->where('enrl_is_paid', NULL)
                        ->first();

            return response()->json([
                                'error' => false,
                                'enrlData' => $enrl_data,
                                'amount' => $totamount,
                                'start_date' => isset($checkLateFees->start_date) ? $checkLateFees->start_date : '',
                                'end_date' => isset($checkLateFees->end_date) ? $checkLateFees->end_date : '',
                                'lateFine' => $late_fine,
                                'payButton' => $pay_btn
                            ], 200);
        }
        catch (Exception $e) {
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ]);
                        }

    }


    public function payment(Request $request)
    {
        $trans_time = date('Y-m-d H:i:s');
        $key = 'IlzvLopkj/XEopyGTmrJPvNGu2v/NwWFX0qo2F2U1uA=';//env('SBI_PAYMENT_KEY');

       /* $s_inst_code = 'SLG';//$request->s_inst_code;
        $paying_for = 'ENROLLMENT';//$request->paying_for; // ENROLMENT
        $part_sem = 'Part_I';//$request->part_sem;     // Part_I
        $type = 'REGULAR';//$request->type;             // REGULAR or CASUAL
        $exam_year = '2025';//$request->exam_year;
        $reg_no = array('250020365');//$request->s_appl_form_num;
        $latefine = false;//$request->latefine;
         */

		 $s_inst_code = $request->s_inst_code;
        $paying_for = $request->paying_for; // ENROLMENT
        $part_sem = $request->part_sem;     // Part_I
        $type = $request->type;             // REGULAR or CASUAL
        $exam_year = $request->exam_year;
        $reg_no = $request->s_appl_form_num;
        $latefine = $request->latefine;


        $fee = DB::table('pharmacy_fees')
            ->where('fees_type', $paying_for)
            ->where('anual', $part_sem)
            ->select('fees_amount')
            ->first();
        if($type == 'REGULAR'){
            $regular_amount = $fee->fees_amount ?? 0;
        }
        else{
            $regular_amount = 150;
        }



        if ($latefine === true) {
            $fine_type = ($paying_for == "ENROLLMENT") ? 'ENROLLMENT_LATE_FEES' : null;

            if ($fine_type) {
                $fine = DB::table('pharmacy_fees')
                    ->where('fees_type', $fine_type)
                    ->where('anual', $part_sem)
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
		#dd($total_amount);

        $reg_list = implode(',', $reg_no);
        #$exam_year  =   2025;
        $part_sem_for_other = str_replace("_","-",$part_sem);// 'Part-I';
        if ($total_amount) {
            $other_data = "{$exam_year}_{$paying_for}_{$s_inst_code}_{$reg_list}_{$part_sem_for_other}_{$type}";

            $orderid = '';
            for ($i = 0; $i < 10; $i++) {
                $d = rand(1, 30) % 2;
                $d = $d ? chr(rand(65, 90)) : chr(rand(48, 57));
                $orderid .= $d;
            }

            $merchant_order_num = $orderid;
			#$total_amount	=	1;
			#dd($amount);
			#$other_data = "sachi_12345_test_X12345_2025_{$amount}";
			$data_1	=	$this->getPaymentData($merchant_order_num, $total_amount, $other_data);

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


            //dd($requestParameter,$EncryptTrans);
            auditTrail($s_inst_code, "Payment initiated for students: {$reg_list} with order id : {$orderid} for {$paying_for}");
			/*dd([
                'error' => false,
                'message' => 'Payment Data Found',

                'encryptTrans' => $data_1['transaction_id'],
                'merchIdVal' => $data_1['marchant_id'],
                'actionUrl' => $data_1['payment_api']
            ]);

			$data = [
			'EncryptTrans' => $data_1['transaction_id'],
			'merchIdVal' => $data_1['marchant_id'],
			];

			$inputs = '';
			foreach ($data as $key => $value) {
				$inputs .= "<input type='hidden' name='$key' value='$value'>";
			}

		 return "
        <form id='autoForm' method='POST' action='".$data_1['payment_api']."'>
            $inputs

        </form>
        <script>
            document.getElementById('autoForm').submit();
        </script>
    ";
	*/

            return response()->json([
                'error' => false,
                'message' => 'Payment Data Found',

                'encryptTrans' => $data_1['transaction_id'],
                'merchIdVal' => $data_1['marchant_id'],
                'actionUrl' => $data_1['payment_api']
            ]);

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
        $part_sem =  str_replace("-","_",$other_data[4]) ;;
        $type = $other_data[5];
        $status_col = '';

        if($part_sem == 'Part_I'){
            $status_col = 's_part1_status';
        }else{
            $status_col = 's_part2_status';
        }
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
                    'form_id' => $form_num,
                    'inst_code' => $s_inst_code,
                    'paid_type' => $paying_for,
                    'paid_amount' => $trans_amount,
                    'paid_at' => $trans_time,
                    'payment_mode' => $trans_mode,
                    'detail' => $trans_details,
                    'exam_year' => $exam_year,
                ]);

				$s_appl_form_num_audit	=	implode(',',$s_appl_form_num);
                Registerstudent::whereIn('s_appl_form_num', $s_appl_form_num)->update([$status_col => 4]);
                Enrollment::whereIn('enrl_form_num', $s_appl_form_num)->update(['enrl_is_paid' => 1, 'enrl_type' => $type]);
               # auditTrail($s_appl_form_num, "Payment for Exam Year: {$exam_year}, ORDER ID: {$order_id}, TRANSACTION ID: {$form_num}, Semester {$part_sem}, Type: {$type}");

                return view('redirect.payment', [
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
        elseif ($paying_for == 'REVIEW') {

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
                DB::table('pharmacy_appl_review_apply')->whereIn('form_num', $s_appl_form_num)->update(['payment_transactions_id' => $trans_id,'review_status'=>2]);
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
        // Log the incoming request
        Log::info('=== PAYMENT FAIL CALLBACK ===');
        Log::info('Request Data: ' . json_encode($request->all()));

        // Merchant Order Number|SBIePayRefID/ATRN|Transaction Status|Amount|Currency|Pay Mode|Other Details|Reason/Message|Bank Code|Bank Reference Number|Transaction Date|Country|CIN|Merchant ID|Total Fee GST |Ref1|Ref2|Ref3|Ref4|Ref5|Ref6|Ref7|Ref8|Ref9
        // "C8YD3U722D|NA|FAIL|1|INR|NB|2025_ENROLLMENT_BMD_PHARMA01633,PHARMA12997_Part_I_REGULAR|User Cancel Transaction|NA|NA|2025-06-20 13:08:25|IN|00|1001954|0.00^0.00|||||||||

        $trans_details = sbiDecrypt($request->encData);
        Log::info('Decrypted Transaction Details: ' . $trans_details);

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

        Log::info('Parsed Data:', [
            'order_id' => $order_id,
            'trans_id' => $trans_id,
            'trans_status' => $trans_status,
            'trans_amount' => $trans_amount,
            'message' => $message
        ]);

        $tranction = PaymentTransaction::where('order_id', $order_id)->first();

        if ($tranction) {
            Log::info('Transaction found in database:', ['order_id' => $order_id, 'paying_for' => $tranction->paying_for]);            $tranction->update([
                'trans_id' => $trans_id,
                'trans_status' => $trans_status,
                'trans_amount' => $trans_amount,
                'trans_mode' => $trans_mode,
                'trans_time' => $trans_time,
                'country_code' => $country_code,
                'marchnt_id' => $marchnt_id,
                'trans_details' => $trans_details,
                'is_verified' => 0,
            ]);

            // Check if it's an institute payment
            if ($tranction->paying_for === 'INSTITUTE_FEE') {
                $inst_code = $other_data[0];
                $admin_user_id = $other_data[1];

                auditTrail($inst_code, "Institute payment FAILED - ORDER ID: {$order_id}, Status: {$trans_status}, Message: {$message}");

                return view('redirect.payment', [
                    'trans_id' => $trans_id,
                    'order_id' => $order_id,
                    'paying_for' => 'INSTITUTE_FEE',
                    'message' => $message,
                    'currency' => $currency,
                    'trans_amount' => $trans_amount,
                    'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
                    'trans_status' => $trans_status,
                ]);
            }

            // Handle enrollment payment
            $exam_year = $other_data[0];
            $paying_for = $other_data[1];
            $s_inst_code = $other_data[2];
            $s_appl_form_num = explode(',', $other_data[3]);
            $form_num = $other_data[3];
            $part_sem = str_replace("-","_",$other_data[4]);
            $type = $other_data[5];

            auditTrail($form_num, "Payment {$trans_status}, FORM NO:{$form_num} ORDER ID: {$order_id}, TRANSACTION ID: {$trans_id} in {$exam_year}");

            return view('redirect.payment', [
                'trans_id' => $trans_id,
                'order_id' => $order_id,
                'paying_for' => $paying_for,
                'message' => $message,
                'currency' => $currency,
                'trans_amount' => $trans_amount,
                'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
                'trans_status' => $trans_status,
            ]);
        } else {
            Log::error('Transaction not found in database:', ['order_id' => $order_id]);
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
        $status_col = '';

        if($part_sem == 'Part_I'){
            $status_col = 's_part1_status';
        }else{
            $status_col = 's_part2_status';
        }

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

        if($type == 'REGULAR'){
            $regular_amount = $fee->fees_amount ?? 0;
        }
        else{
            $regular_amount = 150;
        }

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

        Registerstudent::whereIn('s_appl_form_num', $s_appl_form_num)->update([$status_col => 4]);

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

    public function reviewPayment(Request $request)
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
        $fee = DB::table('pharmacy_fees')
            ->where('fees_type', $paying_for)
            ->where('anual', $request->part_sem)
            ->select('fees_amount')
            ->first();

        $regular_amount = $fee->fees_amount ?? 0;


        $amount = $regular_amount;
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
            $base_url = env('APP_URL') . '/student/review-payment/';
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

                'encryptTrans' => $EncryptTrans,
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

	function check_payment(){
		$orderid	=	rand(1000, 99999999);
		$amount		=	250;
		$other_data = "sachi_12345_test_X12345_2025_{$amount}";

		$data_1	=	$this->getPaymentData($orderid, $amount, $other_data);
		#print_r($data_1);exit();
		#$data_1['transaction_id']	=	"Jt8gdP2bBjnnrli606VPLqH8/gsBqCKWRPHIHk9NZqjZ49ANEAQ4Pz1gOMfR75EAvmJbUE+thyvHRkOn8vxwwqvBqbb85X5yj6OJaPboanklC2DJ+Px2m3mkze/kJDGeXSScuE5iWMz33LxFmS6mNgPs6N1OTVkr1Xu6SZCVscpRSYQUJxzaGeNODbrwdXDvG9hOA3s7sqb61bk/FGMWaytquGjK1dzXmLAdpL0FOo1rsxGCZbXnHTAp/kYdf9rKSJUnicyptzgcFRcmp/SErC+HB/OCAjlzEIBIWBA44mglcFr6Bjs/HdIarb7rbdT9IclpHvd/uZtb4TGvQ/TfAX/Yt3H2optL1AMTGZTqk+F/ixikOZUAI0vnCukpo213";
		#$data_1['marchant_id']	='1001954';
		#$data_1['payment_api']	=	'https://www.sbiepay.sbi/secure/AggregatorHostedListener';
		$data = [
			'EncryptTrans' => $data_1['transaction_id'],
			'merchIdVal' => $data_1['marchant_id'],
			];

			$inputs = '';
			foreach ($data as $key => $value) {
				$inputs .= "<input type='hidden' name='$key' value='$value'>";
			}

		 return "
        <form id='autoForm' method='POST' action='".$data_1['payment_api']."'>
            $inputs

        </form>
        <script>
            document.getElementById('autoForm').submit();
        </script>
    ";

		#print_r($data);
	}

	function getPaymentData($orderid, $pay_amount, $other_data)
	{
    $marid = '5';
    $marchent_id = '1001954';
    $api_key = "https://www.sbiepay.sbi/secure/AggregatorHostedListener";

    $base_url = rtrim(env('APP_URL'), '/') . "/payment/";
    $success_url = "{$base_url}success";
    $fail_url =  "{$base_url}fail";
	#dd('x'.$pay_amount);
    $paymentData = "{$marchent_id}|DOM|IN|INR|{$pay_amount}|{$other_data}|{$success_url}|{$fail_url}|SBIEPAY|{$orderid}|{$marid}|NB|ONLINE|ONLINE,pWhMnIEMc4q6hKdi2Fx50Ii8CKAoSIqv9ScSpwuMHM4=";

		return [
			'transaction_id' => $this->sbiEncrypt($paymentData),
			'marchant_id' => $marchent_id,
			'payment_api' => $api_key
		];
	}

	function sbiEncrypt($data)
{
    $key = "IlzvLopkj/XEopyGTmrJPvNGu2v/NwWFX0qo2F2U1uA=";

    $cipherText = openssl_encrypt(
        $data,
        'aes-128-cbc',
        $key,
        OPENSSL_RAW_DATA,
        substr($key, 0, 16)
    );

    return base64_encode($cipherText);
}

/**
 * @OA\Post(
 *     path="/api/institute-payment",
 *     tags={"Payment"},
 *     summary="Initiate institute payment",
 *     description="Initiate SBI ePay payment for institute admin (₹5 payment)",
 *     security={{"token": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"admin_user_id", "amount", "inst_code"},
 *             @OA\Property(property="admin_user_id", type="integer", example=1, description="Admin user ID"),
 *             @OA\Property(property="amount", type="number", format="double", example=5, description="Payment amount"),
 *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute code"),
 *             @OA\Property(property="payment_purpose", type="string", example="Institute Fee", nullable=true, description="Purpose of payment")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment initiated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Payment initiated successfully"),
 *             @OA\Property(property="order_id", type="string", example="A3B5C7D9E1"),
 *             @OA\Property(property="encryptTrans", type="string", example="encrypted_transaction_string_here"),
 *             @OA\Property(property="merchIdVal", type="string", example="1001954"),
 *             @OA\Property(property="actionUrl", type="string", example="https://www.sbiepay.sbi/secure/AggregatorHostedListener"),
 *             @OA\Property(property="amount", type="number", format="double", example=5)
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - Token missing or invalid"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
public function institutePayment(Request $request)
{
    $validator = Validator::make($request->all(), [
        'admin_user_id' => 'required',
        'amount' => 'required|numeric|min:1',
        'inst_code' => 'required',
        'payment_purpose' => 'nullable|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => true,
            'message' => $validator->errors()->first()
        ], 400);
    }

    try {
        $trans_time = date('Y-m-d H:i:s');
        $admin_user_id = $request->admin_user_id;
        $inst_code = $request->inst_code;
        $amount = $request->amount;
        $payment_purpose = $request->payment_purpose ?? 'Institute Fee';

        // Generate order ID
        $orderid = '';
        for ($i = 0; $i < 10; $i++) {
            $d = rand(1, 30) % 2;
            $d = $d ? chr(rand(65, 90)) : chr(rand(48, 57));
            $orderid .= $d;
        }

        $other_data = "{$inst_code}_{$admin_user_id}_{$payment_purpose}";
        $data_1 = $this->getPaymentData($orderid, $amount, $other_data);

        // Save transaction record
        PaymentTransaction::create([
            'order_id' => $orderid,
            'initiated_by' => $inst_code,
            'initiated_at' => now(),
            'paying_for' => 'INSTITUTE_FEE',
            'trans_amount' => $amount,
            'semester' => NULL,
            'form_no' => $admin_user_id,
            'form_type' => 'INSTITUTE'
        ]);

        auditTrail($inst_code, "Institute payment initiated by admin_user_id: {$admin_user_id} with order id: {$orderid} for amount: {$amount}");

        return response()->json([
            'error' => false,
            'message' => 'Payment initiated successfully',
            'order_id' => $orderid,
            'encryptTrans' => $data_1['transaction_id'],
            'merchIdVal' => $data_1['marchant_id'],
            'actionUrl' => $data_1['payment_api'],
            'amount' => $amount
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => 'Payment initiation failed: ' . $e->getMessage()
        ], 500);
    }
}

// Institute Payment Success Handler
public function institutePaymentSuccess(Request $request)
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

    $inst_code = $other_data[0];
    $admin_user_id = $other_data[1];
    $payment_purpose = $other_data[2] ?? 'Institute Fee';

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
            'form_id' => $admin_user_id,
            'inst_code' => $inst_code,
            'paid_type' => 'INSTITUTE_FEE',
            'paid_amount' => $trans_amount,
            'paid_at' => $trans_time,
            'payment_mode' => $trans_mode,
            'detail' => $trans_details,
            'exam_year' => date('Y'),
        ]);

        auditTrail($inst_code, "Institute payment successful - ORDER ID: {$order_id}, TRANSACTION ID: {$trans_id}, Amount: {$trans_amount}");

        return response()->json([
            'error' => false,
            'message' => 'Payment successful',
            'trans_id' => $trans_id,
            'order_id' => $order_id,
            'trans_amount' => $trans_amount,
            'trans_status' => $trans_status,
            'trans_time' => date('d-m-Y h:i a', strtotime($trans_time))
        ], 200);
    }

    return response()->json([
        'error' => true,
        'message' => 'Transaction not found'
    ], 404);
}

// Institute Payment Failure Handler
public function institutePaymentFail(Request $request)
{
    $trans_details = sbiDecrypt($request->encData);
    $data = explode('|', $trans_details);

    $order_id = $data[0];
    $trans_id = $data[1];
    $trans_status = $data[2];
    $message = $data[7];

    $tranction = PaymentTransaction::where('order_id', $order_id)->first();

    if ($tranction) {
        $tranction->update([
            'trans_id' => $trans_id,
            'trans_status' => $trans_status,
            'trans_details' => $trans_details,
            'is_verified' => 0,
        ]);

        auditTrail($tranction->initiated_by, "Institute payment failed - ORDER ID: {$order_id}, Status: {$trans_status}");
    }

    return response()->json([
        'error' => true,
        'message' => 'Payment failed: ' . $message,
        'order_id' => $order_id,
        'trans_status' => $trans_status
    ], 400);
}

}
