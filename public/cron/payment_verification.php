<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$start_time = microtime(true); // Start timer
include('db.php');

 
#echo "A";exit();
// === Config ===
$merchant_id = '1001954'; // replace with actual value
#$payment_transaction_tbl	=	'payment_transaction_tbl_copy2';
#$application_table			=  'register_student_copy2';

$payment_transaction_tbl	=	'pharmacy_payment_transactions';
$student_final_data_tbl		=   'pharmacy_register_student_final';
$enrollment_tbl				=	'pharmacy_enrollment';
$payment_final				=	'pharmacy_payment_final';

$now	=	date('Y-m-d H:i:s');
#_20251124_1907

$check_table_suffix	=	'_payment_double_verification_check';
/*
$payment_transaction_tbl	.=$check_table_suffix	;
$student_final_data_tbl		.=$check_table_suffix	;
$enrollment_tbl				.=$check_table_suffix	;
$payment_final				.=$check_table_suffix	;*/

#_payment_double_verification_check
// === Fetch Unverified Transactions ===
$query = "SELECT * FROM $payment_transaction_tbl 
			WHERE  trans_id is null 
			AND initiated_at < NOW() - INTERVAL '30 MINUTE'
			
			limit 1500
			";
$result = pg_query($dbconn, $query);
 

echo "not paid candidate transaction $query <br/>"; 

 
 
 $total_payment_transaction	= pg_num_rows($result);
 $failed_transaction		=	0;
 $pending_payment			=	0;
 $success_payment			=	0;
 
 echo "<br/>total payment transaction - ".$total_payment_transaction;
# die();
#$successfull_payment_update_candidate	= array();
#$updated_candidate	=	array();
#$duplicate_candidate_details	=	array();

while ($transaction = pg_fetch_assoc($result)) {
	
    $amount = $transaction['trans_amount'];
    $order_id = $transaction['order_id'];
	$application_id	=	$transaction['form_no'];
    // === Prepare cURL to check transaction status ===
    $url = "https://www.sbiepay.sbi/payagg/statusQuery/getStatusQuery";
    $queryRequest = "|$merchant_id|$order_id|$amount";

    $queryData = http_build_query([
        'queryRequest' => $queryRequest,
        'aggregatorId' => 'SBIEPAY',
        'merchantId' => $merchant_id
    ]);
	echo "<br/>query " . $queryRequest. "<br/>";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSLVERSION, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $queryData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    #curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        curl_close($ch);
        continue;
    }
    curl_close($ch);

    // === Parse response ===
    $data = explode('|', $response);
    if (count($data) < 13) {
        echo "Invalid response for order_id: $order_id\n";
        continue;
    }
	
	$payment_data	= [];
    $payment_data['order_id'] = $order_id;
    $payment_data['trans_id'] = $data[1];
    $payment_data['trans_status'] = $data[2];
    $payment_data['country_code'] = $data[3];
    $payment_data['currency'] = $data[4];
    $payment_data['trans_amount'] = $data[7];
    $payment_data['bank_code'] = $data[9];
    $payment_data['bank_ref'] = $data[10];
    $payment_data['trans_time'] = $data[11] !== 'NA' ? $data[11] : null;
    $payment_data['trans_mode'] = $data[12];

    $payment_data['other_data'] = explode('_', $data[5]);
    $payment_data['type'] = $payment_data['other_data'];

	#print_r($payment_data);
	#exit();
    // === If transaction is not pending, update record ===
			$trans_status	=	$payment_data['trans_status'];
	   if ( $trans_status  !== 'PENDING') {
			echo "<br/> transaction status $trans_status <br/>";
			
			
			$trans_id_esc = pg_escape_literal($dbconn, $payment_data['trans_id']);
			$trans_status_esc = pg_escape_literal($dbconn, $payment_data['trans_status']);
			$trans_amount_esc = pg_escape_literal($dbconn, $payment_data['trans_amount']);
			$trans_mode_esc = pg_escape_literal($dbconn, $payment_data['trans_mode']);
			$trans_time_esc = $payment_data['trans_time'] ? pg_escape_literal($dbconn, $payment_data['trans_time']) : "NULL";
			$country_code_esc = pg_escape_literal($dbconn, $payment_data['country_code']);
			$merchant_id_esc = pg_escape_literal($dbconn, $merchant_id);
			$bank_code_esc = pg_escape_literal($dbconn, $payment_data['bank_code']);
			$bank_ref_esc = pg_escape_literal($dbconn, $payment_data['bank_ref']);
			$response_esc = pg_escape_literal($dbconn, $response);
			$order_id_esc = pg_escape_literal($dbconn, $payment_data['order_id']);
        
		
		
			$update_sql = "
            UPDATE $payment_transaction_tbl SET
                trans_id = $trans_id_esc,
                trans_status = $trans_status_esc,
                trans_amount = $trans_amount_esc,
                trans_mode = $trans_mode_esc,
                trans_time = $trans_time_esc,
                country_code = $country_code_esc,
                marchnt_id = $merchant_id_esc,
                bank_code = $bank_code_esc,
                bank_ref = $bank_ref_esc,
                trans_details = $response_esc ,
				is_verified	=2			
                 
            WHERE order_id = $order_id_esc
			";
			echo "<br/>$update_sql";	
			#exit();
			if($trans_status=='SUCCESS'){
				
				#echo "order id".$application_id;
				
				$application_ids	=	explode( ",", $application_id);
				 
				if(count($application_ids) >1){
					foreach($application_ids as $aapid){
						echo "<br/><br/>appplication id ". $aapid;
					}
					die('a'); 
				}else{
					
					$form_number	=	$application_id;
					 //check student already update or not
					 
					$check_student_data	= "select * from $student_final_data_tbl 
					where s_appl_form_num='$form_number' ";
					
					echo "<br/><br/><br/>check student data : $check_student_data";
					$result_check_student_data = pg_query($dbconn, $check_student_data);
					while ($row_check_student_data = pg_fetch_assoc($result_check_student_data)) { 
						#if()
							
						$payment_status	=	$row_check_student_data['s_part1_status'];
						##echo "part sem status". $row_check_student_data['s_part1_status'];
						echo "<br/><br/>part sem status". $row_check_student_data['s_part1_status'];
						
						if($payment_status	!= 4){
							
						//payment transaction table UPDATE
						#pg_query($dbconn, $update_sql);
						$other_data	=	$payment_data['other_data'];
						$exam_year = $other_data[0];
						$paying_for = $other_data[1];
						$s_inst_code = $other_data[2];
						$s_appl_form_num = explode(',', $other_data[3]);
						$form_num = $other_data[3];
						$part_sem =  str_replace("-","_",$other_data[4]) ;
						$type = $other_data[5];
						
						$final_payment_transaction = "INSERT INTO $payment_final (
							order_id,trans_id, form_id, inst_code, paid_type, paid_amount,
							paid_at, payment_mode, detail, exam_year
						) VALUES (
							'$order_id',
							$trans_id_esc,
							'$form_number',
							'$s_inst_code',
							'$paying_for',
							$trans_amount_esc,
							$trans_time_esc,
							$trans_mode_esc,
							$response_esc,
							'$exam_year'
						)";
						
						 
						$student_final_data_tbl_query	="UPDATE $student_final_data_tbl
													SET s_part1_status = '4'
													WHERE s_appl_form_num IN ('$form_number')";
				
						$enroll_table_update	=	"UPDATE $enrollment_tbl
													SET enrl_is_paid = 1,
													enrl_type    = '$type'
													WHERE enrl_form_num IN ('$form_number')";
						
						echo "<br/><br/><br/><br/>";
						
						
						 
						pg_query($dbconn, "BEGIN");
						
						try {
						 
							// --- 1. Update payment_transaction table ---
							echo "<br/><br/>payment transaction table update";
							echo "<br/>$update_sql";

							$res1 = pg_query($dbconn, $update_sql);
							if (!$res1) { throw new Exception(pg_last_error($dbconn)); }

							// --- 2. Update final payment transaction table ---
							echo "<br/><br/>payment transaction final table update";
							echo "<br/>$final_payment_transaction";

							$res2 = pg_query($dbconn, $final_payment_transaction);
							if (!$res2) { throw new Exception(pg_last_error($dbconn)); }

							// --- 3. Update student final table ---
							echo "<br/><br/>Student final table update";
							echo "<br/>$student_final_data_tbl_query";

							$res3 = pg_query($dbconn, $student_final_data_tbl_query);
							if (!$res3) { throw new Exception(pg_last_error($dbconn)); }

							// --- 4. Update enroll table ---
							echo "<br/><br/>Enroll final table update";
							echo "<br/>$enroll_table_update";

							$res4 = pg_query($dbconn, $enroll_table_update);
							if (!$res4) { throw new Exception(pg_last_error($dbconn)); }

							// Everything succeeded → commit
							pg_query($dbconn, "COMMIT");
							echo "<br/><br/><b style='color:green'>Transaction committed successfully</b>";

						} catch (Exception $e) {

							// Something failed → rollback
							pg_query($dbconn, "ROLLBACK");
							echo "<br/><br/><b style='color:red'>Transaction failed: " . $e->getMessage() . "</b>";
							 
						}
						 
						 
						}
						 
						
					}
				}
				
				 
				$success_payment++;
				 
				
				
			}else{
				pg_query($dbconn, $update_sql);
				$failed_transaction++;
			}
		}else{
			 
			$pending_payment++;
		}
		
	#	die('x');
}
 
 
 //
 
 echo "<h1> :: Status :: </h1>";
 
 echo "<br/><br/> total payment process". $total_payment_transaction;
 
 echo "<br/><br/> total pending transaction ". $pending_payment;
 
 echo "<br/><br/> total Failed transaction ". $failed_transaction;

  

$end_time = microtime(true);   // End timer
$execution_time = $end_time - $start_time;
 

echo "<br/>Execution time: " . round($execution_time, 4) . " seconds\n";