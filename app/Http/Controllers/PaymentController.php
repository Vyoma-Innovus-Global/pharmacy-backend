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
    private function institutePaymentFrontendReceiptUrl(array $params): string
    {
        $baseUrl = rtrim(
            env(
                'PAYMENT_FRONTEND_RECEIPT_URL',
                env(
                    'PAYMENT_FRONTEND_STATUS_URL',
                    env('FRONTEND_PAYMENT_STATUS_URL', 'https://sctedved.wb.gov.in/hsvoc/payment-receipt')
                )
            ),
            '?'
        );

        return $baseUrl . '?' . http_build_query([
            'txnNo' => $params['trans_id'] ?? '',
            'transno' => $params['trans_id'] ?? '',
            'orderId' => $params['order_id'] ?? '',
            'status' => $params['trans_status'] ?? '',
            'amount' => $params['trans_amount'] ?? '',
            'currency' => $params['currency'] ?? 'INR',
            'message' => $params['message'] ?? '',
            'time' => $params['trans_time'] ?? '',
            'paying_for' => 'INSTITUTE_FEE',
            'inst_code' => $params['inst_code'] ?? '',
            'payment_purpose' => $params['payment_purpose'] ?? 'Institute Fee',
        ]);
    }

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
        Log::channel('daily')->info('[Payment] getEnrollmentPaymentdata INPUT', [
            'inst_code' => $inst_code,
            'paying_for' => $paying_for,
            'part_sem' => $part_sem,
            'form_num' => $form_num,
            'reg_no' => $reg_num,
            'type' => $type,
            'ip' => $request->ip(),
        ]);
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

            Log::channel('daily')->info('[Payment] getEnrollmentPaymentdata OUTPUT', [
                'inst_code' => $inst_code,
                'paying_for' => $paying_for,
                'part_sem' => $part_sem,
                'amount' => $totamount,
                'late_fine' => $late_fine,
                'pay_btn' => $pay_btn,
                'enrollment_found' => !empty($enrl_data),
            ]);

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
        catch (\Exception $e) {
                            Log::channel('daily')->error('[Payment] getEnrollmentPaymentdata EXCEPTION', [
                                'message' => $e->getMessage(),
                                'line' => $e->getLine(),
                                'file' => $e->getFile(),
                            ]);
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

        Log::channel('daily')->info('[Payment] enrollment payment INPUT', [
            'inst_code' => $s_inst_code,
            'paying_for' => $paying_for,
            'part_sem' => $part_sem,
            'type' => $type,
            'exam_year' => $exam_year,
            'form_count' => $this->countItems($reg_no),
            'latefine' => $latefine,
            'ip' => $request->ip(),
        ]);

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

        Log::channel('daily')->info('[Payment] enrollment amount calculated', [
            'inst_code' => $s_inst_code,
            'paying_for' => $paying_for,
            'regular_amount' => $regular_amount,
            'amount_per_form' => $amount,
            'form_count' => $count,
            'total_amount' => $total_amount,
        ]);

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

            Log::channel('daily')->info('[Payment] enrollment gateway payload created', [
                'order_id' => $orderid,
                'inst_code' => $s_inst_code,
                'paying_for' => $paying_for,
                'amount' => $total_amount,
                'form_count' => $count,
                'merchant_id' => $data_1['marchant_id'],
                'action_url' => $data_1['payment_api'],
                'encrypted_length' => strlen($data_1['transaction_id']),
            ]);
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
            Log::channel('daily')->warning('[Payment] enrollment payment blocked: zero amount', [
                'inst_code' => $s_inst_code,
                'paying_for' => $paying_for,
                'part_sem' => $part_sem,
                'type' => $type,
                'form_count' => $count,
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Something went wrong, Try Again Later'
            ]);
        }
    }

    public function enrollmentPaymentSuccess(Request $request)
    {
        Log::channel('daily')->info('[Payment] enrollment success CALLBACK received', $this->paymentCallbackMeta($request));
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

        Log::channel('daily')->info('[Payment] enrollment success parsed', [
            'order_id' => $order_id,
            'trans_id' => $trans_id,
            'trans_status' => $trans_status,
            'trans_amount' => $trans_amount,
            'currency' => $currency,
            'trans_mode' => $trans_mode,
            'message' => $message,
            'exam_year' => $exam_year,
            'paying_for' => $paying_for,
            'inst_code' => $s_inst_code,
            'form_count' => count($s_appl_form_num),
            'part_sem' => $part_sem,
            'type' => $type,
        ]);

        if($part_sem == 'Part_I'){
            $status_col = 's_part1_status';
        }else{
            $status_col = 's_part2_status';
        }
        if ($paying_for == 'ENROLLMENT') {

            $tranction = PaymentTransaction::where('order_id', $order_id)->first();

            if ($tranction) {
                Log::channel('daily')->info('[Payment] enrollment success transaction found', [
                    'order_id' => $order_id,
                    'transaction_row_id' => $tranction->id ?? null,
                    'existing_status' => $tranction->trans_status,
                ]);

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

                Log::channel('daily')->info('[Payment] enrollment success DB updated', [
                    'order_id' => $order_id,
                    'trans_id' => $trans_id,
                    'form_count' => count($s_appl_form_num),
                    'status_col' => $status_col,
                ]);

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
                Log::channel('daily')->info('[Payment] review success transaction found', [
                    'order_id' => $order_id,
                    'transaction_row_id' => $tranction->id ?? null,
                    'existing_status' => $tranction->trans_status,
                ]);

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

                Log::channel('daily')->info('[Payment] review success DB updated', [
                    'order_id' => $order_id,
                    'trans_id' => $trans_id,
                    'form_count' => count($s_appl_form_num),
                ]);

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

        Log::channel('daily')->warning('[Payment] success callback transaction not handled', [
            'order_id' => $order_id,
            'paying_for' => $paying_for,
            'trans_status' => $trans_status,
        ]);
    }

    public function enrollmentPaymentFail(Request $request)
    {
        // Log the incoming request
        Log::channel('daily')->info('[Payment] enrollment fail CALLBACK received', $this->paymentCallbackMeta($request));

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

        Log::channel('daily')->info('[Payment] enrollment fail parsed', [
            'order_id' => $order_id,
            'trans_id' => $trans_id,
            'trans_status' => $trans_status,
            'trans_amount' => $trans_amount,
            'message' => $message,
            'other_data_count' => count($other_data),
        ]);

        $tranction = PaymentTransaction::where('order_id', $order_id)->first();

        if ($tranction) {
            Log::channel('daily')->info('[Payment] fail transaction found', ['order_id' => $order_id, 'paying_for' => $tranction->paying_for]);
            $tranction->update([
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
            Log::channel('daily')->error('[Payment] fail transaction not found', ['order_id' => $order_id]);
        }
    }

    public function paymentOffline(Request $request)
    {
        Log::channel('daily')->info('[Payment] offline payment INPUT', [
            'inst_code' => $request->s_inst_code,
            'paying_for' => $request->paying_for,
            'part_sem' => $request->part_sem,
            'type' => $request->type,
            'form_count' => $this->countItems($request->s_appl_form_num),
            'demand_no' => $request->demand_no,
            'ip' => $request->ip(),
        ]);

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
            Log::channel('daily')->warning('[Payment] offline payment validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

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
        Log::channel('daily')->info('[Payment] offline payment completed', [
            'order_id' => $orderid,
            'trans_id' => $trans_id,
            'inst_code' => $s_inst_code,
            'paying_for' => $paying_for,
            'total_amount' => $total_amount,
            'form_count' => count($s_appl_form_num),
        ]);

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
        Log::channel('daily')->info('[Payment] review payment INPUT', [
            'inst_code' => $s_inst_code,
            'paying_for' => $paying_for,
            'part_sem' => $part_sem,
            'type' => $type,
            'exam_year' => $exam_year,
            'form_count' => $this->countItems($reg_no),
            'merchant_id' => $merchIdVal,
            'action_url' => $actionUrl,
            'ip' => $request->ip(),
        ]);

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

            Log::channel('daily')->info('[Payment] review gateway payload created', [
                'order_id' => $orderid,
                'inst_code' => $s_inst_code,
                'paying_for' => $paying_for,
                'amount' => $total_amount,
                'form_count' => $count,
                'merchant_id' => $merchIdVal,
                'action_url' => $actionUrl,
                'encrypted_length' => strlen($EncryptTrans),
            ]);

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
            Log::channel('daily')->warning('[Payment] review payment blocked: zero amount', [
                'inst_code' => $s_inst_code,
                'paying_for' => $paying_for,
                'part_sem' => $part_sem,
                'type' => $type,
                'form_count' => $count,
            ]);

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
    Log::channel('daily')->info('[Payment] institute payment INPUT', [
        'admin_user_id' => $request->admin_user_id,
        'amount' => $request->amount,
        'inst_code' => $request->inst_code,
        'payment_purpose' => $request->payment_purpose,
        'ip' => $request->ip(),
    ]);

    $validator = Validator::make($request->all(), [
        'admin_user_id' => 'required',
        'amount' => 'required|numeric|min:1',
        'inst_code' => 'required',
        'payment_purpose' => 'nullable|string'
    ]);

    if ($validator->fails()) {
        Log::channel('daily')->warning('[Payment] institute payment validation failed', [
            'errors' => $validator->errors()->toArray(),
        ]);

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

        Log::channel('daily')->info('[Payment] institute gateway payload created', [
            'order_id' => $orderid,
            'inst_code' => $inst_code,
            'admin_user_id' => $admin_user_id,
            'amount' => $amount,
            'payment_purpose' => $payment_purpose,
            'merchant_id' => $data_1['marchant_id'],
            'action_url' => $data_1['payment_api'],
            'encrypted_length' => strlen($data_1['transaction_id']),
        ]);

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
        Log::channel('daily')->error('[Payment] institute payment EXCEPTION', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Payment initiation failed: ' . $e->getMessage()
        ], 500);
    }
}

// Institute Payment Success Handler
public function institutePaymentSuccess(Request $request)
{
    try {
        Log::channel('daily')->info('[Payment] institute success CALLBACK received', $this->paymentCallbackMeta($request));
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

        Log::channel('daily')->info('[Payment] institute success parsed', [
            'order_id' => $order_id,
            'trans_id' => $trans_id,
            'trans_status' => $trans_status,
            'trans_amount' => $trans_amount,
            'currency' => $currency,
            'trans_mode' => $trans_mode,
            'message' => $message,
            'inst_code' => $inst_code,
            'admin_user_id' => $admin_user_id,
            'payment_purpose' => $payment_purpose,
        ]);

        $tranction = PaymentTransaction::where('order_id', $order_id)->first();

        if ($tranction) {
            Log::channel('daily')->info('[Payment] institute success transaction found', [
                'order_id' => $order_id,
                'transaction_row_id' => $tranction->id ?? null,
                'existing_status' => $tranction->trans_status,
            ]);

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

            Log::channel('daily')->info('[Payment] institute success DB updated', [
                'order_id' => $order_id,
                'trans_id' => $trans_id,
                'inst_code' => $inst_code,
                'amount' => $trans_amount,
            ]);

            return redirect()->away($this->institutePaymentFrontendReceiptUrl([
                'trans_id' => $trans_id,
                'order_id' => $order_id,
                'trans_amount' => $trans_amount,
                'trans_status' => $trans_status,
                'currency' => $currency,
                'message' => $message,
                'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
                'inst_code' => $inst_code,
                'payment_purpose' => $payment_purpose
            ]));
        }

        Log::channel('daily')->warning('[Payment] institute success transaction not found', [
            'order_id' => $order_id,
            'trans_id' => $trans_id,
            'trans_status' => $trans_status,
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Transaction not found'
        ], 404);

    } catch (\Exception $e) {
        Log::channel('daily')->error('[Payment] Success handler failed', [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'request' => $request->all()
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Payment processing failed: ' . $e->getMessage()
        ], 500);
    }
}

// Institute Payment Failure Handler
public function institutePaymentFail(Request $request)
{
    Log::channel('daily')->info('[Payment] institute fail CALLBACK received', $this->paymentCallbackMeta($request));
    $trans_details = sbiDecrypt($request->encData);
    $data = explode('|', $trans_details);

    $order_id = $data[0];
    $trans_id = $data[1];
    $trans_status = $data[2];
    $trans_amount = $data[3] ?? '';
    $currency = $data[4] ?? 'INR';
    $message = $data[7];
    $trans_time = $data[10] ?? date('Y-m-d H:i:s');
    $other_data = explode('_', $data[6] ?? '');
    $inst_code = $other_data[0] ?? '';
    $payment_purpose = $other_data[2] ?? 'Institute Fee';

    Log::channel('daily')->info('[Payment] institute fail parsed', [
        'order_id' => $order_id,
        'trans_id' => $trans_id,
        'trans_status' => $trans_status,
        'message' => $message,
    ]);

    $tranction = PaymentTransaction::where('order_id', $order_id)->first();

    if ($tranction) {
        Log::channel('daily')->info('[Payment] institute fail transaction found', [
            'order_id' => $order_id,
            'initiated_by' => $tranction->initiated_by,
            'existing_status' => $tranction->trans_status,
        ]);

        $tranction->update([
            'trans_id' => $trans_id,
            'trans_status' => $trans_status,
            'trans_details' => $trans_details,
            'is_verified' => 0,
        ]);

        auditTrail($tranction->initiated_by, "Institute payment failed - ORDER ID: {$order_id}, Status: {$trans_status}");
    } else {
        Log::channel('daily')->error('[Payment] institute fail transaction not found', [
            'order_id' => $order_id,
            'trans_status' => $trans_status,
        ]);
    }

    return redirect()->away($this->institutePaymentFrontendReceiptUrl([
        'trans_id' => $trans_id,
        'order_id' => $order_id,
        'trans_amount' => $trans_amount,
        'trans_status' => in_array($trans_status, ['FAIL', 'ABORT'], true) ? 'FAILED' : $trans_status,
        'currency' => $currency,
        'message' => $message,
        'trans_time' => date('d-m-Y h:i a', strtotime($trans_time)),
        'inst_code' => $inst_code ?: ($tranction->initiated_by ?? ''),
        'payment_purpose' => $payment_purpose
    ]));
}

// Download Institute Payment Receipt as PDF
public function downloadInstitutePaymentReceipt($order_id)
{
    Log::channel('daily')->info('[Payment] institute receipt download requested', ['order_id' => $order_id]);
    $transaction = PaymentTransaction::where('order_id', $order_id)
        ->where('trans_status', 'SUCCESS')
        ->first();

    if (!$transaction) {
        Log::channel('daily')->warning('[Payment] institute receipt download missing transaction', ['order_id' => $order_id]);
        abort(404, 'Transaction not found or payment not successful');
    }

    // Extract institute code from other_details
    $other_data = explode('_', explode('|', $transaction->trans_details)[6] ?? '');
    $inst_code = $other_data[0] ?? null;

    // Get institute details
    $institute = DB::table('institute_master')
        ->where('inst_code', $inst_code)
        ->first();

    $data = [
        'trans_id' => $transaction->trans_id,
        'order_id' => $transaction->order_id,
        'trans_amount' => $transaction->trans_amount,
        'trans_status' => $transaction->trans_status,
        'trans_time' => date('d-m-Y h:i a', strtotime($transaction->trans_time)),
        'inst_code' => $inst_code,
        'inst_name' => $institute->inst_name ?? 'N/A',
        'payment_purpose' => 'Institute Registration Fee'
    ];

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('institute-payment-receipt', $data);
    return $pdf->download('institute-payment-receipt-' . $order_id . '.pdf');
}

// Get Institute Payment Receipt Data (API for React)
public function getInstitutePaymentReceiptData($order_id)
{
    Log::channel('daily')->info('[Payment] institute receipt data requested', ['order_id' => $order_id]);
    $transaction = PaymentTransaction::where('order_id', $order_id)
        ->where('trans_status', 'SUCCESS')
        ->first();

    if (!$transaction) {
        Log::channel('daily')->warning('[Payment] institute receipt data missing transaction', ['order_id' => $order_id]);
        return response()->json([
            'error' => true,
            'message' => 'Transaction not found or payment not successful'
        ], 404);
    }

    // Extract institute code from other_details
    $other_data = explode('_', explode('|', $transaction->trans_details)[6] ?? '');
    $inst_code = $other_data[0] ?? null;

    // Get institute details
    $institute = DB::table('institute_master')
        ->where('inst_code', $inst_code)
        ->first();

    return response()->json([
        'error' => false,
        'data' => [
            'trans_id' => $transaction->trans_id,
            'order_id' => $transaction->order_id,
            'trans_amount' => $transaction->trans_amount,
            'trans_status' => $transaction->trans_status,
            'trans_time' => date('d-m-Y h:i a', strtotime($transaction->trans_time)),
            'inst_code' => $inst_code,
            'inst_name' => $institute->inst_name ?? 'N/A',
            'payment_purpose' => 'Institute Registration Fee'
        ]
    ], 200);
}

public function saveSbiPaymentDetails(Request $request)
{
    $amount = $request->input('amount', $request->input('p_amount'));
    $otherDetails = $request->input('other_details', $request->input('p_other_details'));
    $studentMobileNo = $request->input('student_mobile_no', $request->input('p_student_mobile_no'));
    $entryUserId = $request->input('entry_user_id', $request->input('p_entry_user_id'));

    $validator = Validator::make([
        'amount' => $amount,
        'other_details' => $otherDetails,
        'student_mobile_no' => $studentMobileNo,
        'entry_user_id' => $entryUserId,
    ], [
        'amount' => 'required|numeric|min:0',
        'other_details' => 'nullable|string',
        'student_mobile_no' => 'required|string|max:20',
        'entry_user_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => true,
            'message' => 'Validation failed.',
            'data' => $validator->errors(),
        ], 422);
    }

    Log::channel('daily')->info('[Payment] fn_savesbipaymentdtls INPUT', [
        'amount' => $amount,
        'student_mobile_no' => $studentMobileNo,
        'entry_user_id' => $entryUserId,
        'ip' => $request->ip(),
    ]);

    try {
        $result = DB::select(
            'SELECT public.fn_savesbipaymentdtls(?::double precision, ?::text, ?::varchar, ?::bigint) AS data',
            [(float) $amount, $otherDetails, $studentMobileNo, (int) $entryUserId]
        );

        return $this->dbFunctionJsonResponse($result[0]->data ?? null, 'fn_savesbipaymentdtls');
    } catch (\Exception $e) {
        Log::channel('daily')->error('[Payment] fn_savesbipaymentdtls EXCEPTION', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Failed to save SBI payment details.',
        ], 500);
    }
}

public function savePaymentDetails(Request $request)
{
    $studentId = $request->input('student_id', $request->input('p_student_id'));
    $paymentTypeId = $request->input('payment_type_id', $request->input('p_payment_type_id'));
    $transactionNo = $request->input('transaction_no', $request->input('p_transaction_no'));
    $amount = $request->input('amount', $request->input('p_amount'));
    $entryUserId = $request->input('entry_user_id', $request->input('p_entry_user_id'));

    $validator = Validator::make([
        'student_id' => $studentId,
        'payment_type_id' => $paymentTypeId,
        'transaction_no' => $transactionNo,
        'amount' => $amount,
        'entry_user_id' => $entryUserId,
    ], [
        'student_id' => 'required|integer',
        'payment_type_id' => 'required|integer',
        'transaction_no' => 'required|string|max:100',
        'amount' => 'required|numeric|min:0',
        'entry_user_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => true,
            'message' => 'Validation failed.',
            'data' => $validator->errors(),
        ], 422);
    }

    Log::channel('daily')->info('[Payment] fn_savepaymentdtls INPUT', [
        'student_id' => $studentId,
        'payment_type_id' => $paymentTypeId,
        'transaction_no' => $transactionNo,
        'amount' => $amount,
        'entry_user_id' => $entryUserId,
        'ip' => $request->ip(),
    ]);

    try {
        $result = DB::select(
            'SELECT public.fn_savepaymentdtls(?::bigint, ?::bigint, ?::varchar, ?::double precision, ?::bigint) AS data',
            [(int) $studentId, (int) $paymentTypeId, $transactionNo, (float) $amount, (int) $entryUserId]
        );

        return $this->dbFunctionJsonResponse($result[0]->data ?? null, 'fn_savepaymentdtls');
    } catch (\Exception $e) {
        Log::channel('daily')->error('[Payment] fn_savepaymentdtls EXCEPTION', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Failed to save payment details.',
        ], 500);
    }
}

public function generateStudentOrderId(Request $request)
{
    $studentId = $request->input('student_id', $request->input('p_student_id'));
    $examYear = $request->input('exam_year', $request->input('p_exam_year'));
    $paymentTypeId = $request->input('payment_type_id', $request->input('p_payment_type_id'));

    $validator = Validator::make([
        'student_id' => $studentId,
        'exam_year' => $examYear,
        'payment_type_id' => $paymentTypeId,
    ], [
        'student_id' => 'required|integer',
        'exam_year' => 'required|string|max:20',
        'payment_type_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => true,
            'message' => 'Validation failed.',
            'data' => $validator->errors(),
        ], 422);
    }

    Log::channel('daily')->info('[Payment] fn_student_generateorderid INPUT', [
        'student_id' => $studentId,
        'exam_year' => $examYear,
        'payment_type_id' => $paymentTypeId,
        'ip' => $request->ip(),
    ]);

    try {
        $result = DB::select(
            'SELECT public.fn_student_generateorderid(?::bigint, ?::varchar, ?::integer) AS data',
            [(int) $studentId, $examYear, (int) $paymentTypeId]
        );

        return $this->dbFunctionJsonResponse($result[0]->data ?? null, 'fn_student_generateorderid');
    } catch (\Exception $e) {
        Log::channel('daily')->error('[Payment] fn_student_generateorderid EXCEPTION', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Failed to generate student order id.',
        ], 500);
    }
}

public function getStudentPaymentTypeByStudentId(Request $request)
{
    $studentId = $request->input('student_id', $request->input('p_student_id'));
    $paymentType = $request->input('payment_type', $request->input('p_payment_type'));

    $validator = Validator::make([
        'student_id' => $studentId,
        'payment_type' => $paymentType,
    ], [
        'student_id' => 'required|integer',
        'payment_type' => 'required|integer|in:1,2',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => true,
            'message' => 'Validation failed.',
            'data' => $validator->errors(),
        ], 422);
    }

    Log::channel('daily')->info('[Payment] fn_get_studentpayment_type_studentid INPUT', [
        'student_id' => $studentId,
        'payment_type' => $paymentType,
        'ip' => $request->ip(),
    ]);

    try {
        $result = DB::select(
            'SELECT public.fn_get_studentpayment_type_studentid(?::bigint, ?::integer) AS data',
            [(int) $studentId, (int) $paymentType]
        );

        return $this->dbFunctionJsonResponse($result[0]->data ?? null, 'fn_get_studentpayment_type_studentid');
    } catch (\Exception $e) {
        Log::channel('daily')->error('[Payment] fn_get_studentpayment_type_studentid EXCEPTION', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Failed to get student payment type details.',
        ], 500);
    }
}

public function savePharmacyPaymentResponse(Request $request)
{
    $orderId = $request->input('order_id', $request->input('p_order_id'));
    $merchantId = $request->input('merchant_id', $request->input('p_merchant_id'));
    $transactionId = $request->input('transaction_id', $request->input('txn_id', $request->input('p_transaction_id')));
    $paymentStatus = $request->input('payment_status', $request->input('status', $request->input('p_payment_status')));
    $amount = $request->input('amount', $request->input('p_amount'));
    $paymentMsg = $request->input('payment_msg', $request->input('message', $request->input('p_payment_msg')));
    $paymentBankCode = $request->input('payment_bank_code', $request->input('bank_code', $request->input('p_payment_bank_code')));
    $trnDateTime = $request->input('trn_date_time', $request->input('txn_datetime', $request->input('p_trn_date_time')));
    $currency = $request->input('currency', $request->input('p_currency', 'INR'));
    $paymentMode = $request->input('payment_mode', $request->input('p_payment_mode'));
    $bankRef = $request->input('bank_ref', $request->input('bank_ref_no', $request->input('p_bank_ref')));
    $challanId = $request->input('challan_id', $request->input('challan_id_no', $request->input('p_challan_id')));
    $remarks = $request->input('remarks', $request->input('p_remarks'));

    $validator = Validator::make([
        'order_id' => $orderId,
        'merchant_id' => $merchantId,
        'transaction_id' => $transactionId,
        'payment_status' => $paymentStatus,
        'amount' => $amount,
        'payment_msg' => $paymentMsg,
        'payment_bank_code' => $paymentBankCode,
        'trn_date_time' => $trnDateTime,
        'currency' => $currency,
        'payment_mode' => $paymentMode,
        'bank_ref' => $bankRef,
        'challan_id' => $challanId,
        'remarks' => $remarks,
    ], [
        'order_id' => 'required|string|max:100',
        'merchant_id' => 'required|string|max:100',
        'transaction_id' => 'required|string|max:100',
        'payment_status' => 'required|string|max:50',
        'amount' => 'required|numeric|min:0',
        'payment_msg' => 'nullable|string|max:255',
        'payment_bank_code' => 'nullable|string|max:100',
        'trn_date_time' => 'required|date',
        'currency' => 'required|string|max:10',
        'payment_mode' => 'nullable|string|max:100',
        'bank_ref' => 'nullable|string|max:100',
        'challan_id' => 'nullable|string|max:100',
        'remarks' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => true,
            'message' => 'Validation failed.',
            'data' => $validator->errors(),
        ], 422);
    }

    Log::channel('daily')->info('[Payment] fn_savepharmacypaymentresponse INPUT', [
        'order_id' => $orderId,
        'merchant_id' => $merchantId,
        'transaction_id' => $transactionId,
        'payment_status' => $paymentStatus,
        'amount' => $amount,
        'currency' => $currency,
        'ip' => $request->ip(),
    ]);

    try {
        $result = DB::select(
            'SELECT public.fn_savepharmacypaymentresponse(?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::double precision, ?::varchar, ?::varchar, ?::timestamp, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::text) AS data',
            [
                $orderId,
                $merchantId,
                $transactionId,
                $paymentStatus,
                (float) $amount,
                $paymentMsg,
                $paymentBankCode,
                $trnDateTime,
                $currency,
                $paymentMode,
                $bankRef,
                $challanId,
                $remarks,
            ]
        );

        return $this->dbFunctionJsonResponse($result[0]->data ?? null, 'fn_savepharmacypaymentresponse');
    } catch (\Exception $e) {
        Log::channel('daily')->error('[Payment] fn_savepharmacypaymentresponse EXCEPTION', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Failed to save pharmacy payment response.',
        ], 500);
    }
}

public function updateSbiPaymentResponse(Request $request)
{
    $txnNo = $request->input('txn_no', $request->input('txnNo', $request->input('p_txnno')));
    $aggregatorTxnRefNo = $request->input('aggregator_txn_ref_no', $request->input('p_aggregator_txn_ref_no'));
    $paymentStatus = $request->input('payment_status', $request->input('status', $request->input('p_payment_status')));
    $paymentAmount = $request->input('payment_amount', $request->input('amount', $request->input('p_payment_amount')));
    $currency = $request->input('currency', $request->input('p_currency', 'INR'));
    $nb = $request->input('nb', $request->input('p_nb'));
    $otherDetails = $request->input('other_details', $request->input('p_other_details'));
    $paymentMsg = $request->input('payment_msg', $request->input('message', $request->input('p_payment_msg')));
    $paymentBankCode = $request->input('payment_bank_code', $request->input('p_payment_bank_code'));
    $bankRefNo = $request->input('bank_ref_no', $request->input('p_bank_ref_no'));
    $txnDateTime = $request->input('txn_datetime', $request->input('p_txn_datetime'));
    $country = $request->input('country', $request->input('p_country'));
    $challanIdNo = $request->input('challan_id_no', $request->input('p_challan_id_no'));
    $merchantId = $request->input('merchant_id', $request->input('p_merchant_id'));
    $gst = $request->input('gst', $request->input('p_gst'));
    $servicesTax = $request->input('services_tax', $request->input('p_services_tax'));

    $validator = Validator::make([
        'txn_no' => $txnNo,
        'payment_status' => $paymentStatus,
        'payment_amount' => $paymentAmount,
        'currency' => $currency,
        'merchant_id' => $merchantId,
    ], [
        'txn_no' => 'required|string|max:100',
        'payment_status' => 'required|string|max:50',
        'payment_amount' => 'required|numeric|min:0',
        'currency' => 'required|string|max:10',
        'merchant_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => true,
            'message' => 'Validation failed.',
            'data' => $validator->errors(),
        ], 422);
    }

    Log::channel('daily')->info('[Payment] fn_updatesbipaymentresponse INPUT', [
        'txn_no' => $txnNo,
        'payment_status' => $paymentStatus,
        'payment_amount' => $paymentAmount,
        'currency' => $currency,
        'merchant_id' => $merchantId,
        'ip' => $request->ip(),
    ]);

    try {
        $result = DB::select(
            'SELECT public.fn_updatesbipaymentresponse(?::varchar, ?::text, ?::varchar, ?::double precision, ?::varchar, ?::varchar, ?::text, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::double precision, ?::double precision) AS data',
            [
                $txnNo,
                $aggregatorTxnRefNo,
                $paymentStatus,
                (float) $paymentAmount,
                $currency,
                $nb,
                $otherDetails,
                $paymentMsg,
                $paymentBankCode,
                $bankRefNo,
                $txnDateTime,
                $country,
                $challanIdNo,
                (int) $merchantId,
                $gst === null ? null : (float) $gst,
                $servicesTax === null ? null : (float) $servicesTax,
            ]
        );

        return $this->dbFunctionJsonResponse($result[0]->data ?? null, 'fn_updatesbipaymentresponse');
    } catch (\Exception $e) {
        Log::channel('daily')->error('[Payment] fn_updatesbipaymentresponse EXCEPTION', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Failed to update SBI payment response.',
        ], 500);
    }
}

public function getPaymentDetailsByTransNo(Request $request, $transactionNo = null)
{
    $transactionNo = $transactionNo
        ?? $request->input('transaction_no')
        ?? $request->input('txnNo')
        ?? $request->input('transno')
        ?? $request->input('p_transaction_no');

    $validator = Validator::make([
        'transaction_no' => $transactionNo,
    ], [
        'transaction_no' => 'required|string|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => true,
            'message' => 'Validation failed.',
            'data' => $validator->errors(),
        ], 422);
    }

    Log::channel('daily')->info('[Payment] fn_getpaymentdetailsbytransno INPUT', [
        'transaction_no' => $transactionNo,
        'ip' => $request->ip(),
    ]);

    try {
        $result = DB::select(
            'SELECT public.fn_getpaymentdetailsbytransno(?::varchar) AS data',
            [$transactionNo]
        );

        return $this->dbFunctionJsonResponse($result[0]->data ?? null, 'fn_getpaymentdetailsbytransno');
    } catch (\Exception $e) {
        Log::channel('daily')->error('[Payment] fn_getpaymentdetailsbytransno EXCEPTION', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Failed to get payment details by transaction number.',
        ], 500);
    }
}

private function dbFunctionJsonResponse($raw, string $functionName)
{
    if ($raw === null) {
        return response()->json([
            'error' => true,
            'message' => "No data returned from {$functionName}.",
        ], 404);
    }

    $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        Log::channel('daily')->error("[Payment] {$functionName} invalid JSON response", [
            'raw' => $raw,
        ]);

        return response()->json([
            'error' => true,
            'message' => "Invalid response from {$functionName}.",
        ], 500);
    }

    Log::channel('daily')->info("[Payment] {$functionName} OUTPUT", $decoded);

    return response()->json($decoded, 200);
}

private function countItems($value): int
{
    if (is_array($value)) {
        return count($value);
    }

    if (is_string($value) && trim($value) !== '') {
        return count(array_filter(array_map('trim', explode(',', $value))));
    }

    return 0;
}

private function paymentCallbackMeta(Request $request): array
{
    $encData = (string) $request->input('encData', '');

    return [
        'has_encData' => $encData !== '',
        'encData_length' => strlen($encData),
        'ip' => $request->ip(),
        'user_agent' => substr((string) $request->userAgent(), 0, 255),
    ];
}

}
