<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Token;
use App\Models\Trade;
use App\Models\District;
use App\Models\Institute;
use App\Models\SuperUser;
use App\Models\Enrollment;
use App\Models\Registerstudent;
use App\Models\PaymentTransaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Schedule;
use DateTime;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    public function list(Request $request)
    {
      
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {  // check the token is expire or not
                $user_id = $token_check->t_user_id;
               
                $user_data = User::select('s_id', 's_candidate_name')->where('s_id', $user_id)->first();
                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', 2)->pluck('rp_url_id');
                
                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    
                    $url_data = array_column($urls, 'url_name');
                    if (in_array('enrollment-list', $url_data)) { 
                        $validated = validator::make($request->all(), [
                            'institute_code' => 'required',
                            'session_year'=>'required'
                        ]);
                        if ($validated->fails()) {
                            return response()->json([
                                'error' => true,
                                'message' => $validated->errors()
                            ]);
                        }
                        DB::beginTransaction();
                        try {
                            $institute_code = $request->institute_code;
                            $session_year = $request->session_year;
                            $enrollmentdata = Registerstudent::join('institute_master', 'pharmacy_register_student_final.s_inst_code', '=', 'institute_master.i_code')
                            ->leftJoin('pharmacy_enrollment', 'pharmacy_register_student_final.s_appl_form_num', '=', 'pharmacy_enrollment.enrl_form_num') // Changed to leftJoin
                            
                            ->where('pharmacy_register_student_final.is_registration_payment', 1)
                            ->whereNotNull('pharmacy_register_student_final.s_appl_reg_no')
                            ->where('pharmacy_register_student_final.s_appl_sess_year', $session_year)
                            ->where('pharmacy_register_student_final.s_inst_code', $institute_code)
                            ->orderBy('s_id', 'desc')
                            ->get()

                            ->map(function($RegisterStudent, $key){
                               return [
                                    'sl_no' => $key + 1,
                                    's_id' => $RegisterStudent->s_id,
                                    'fullname' => $RegisterStudent->s_candidate_name,
                                    'father_name' => $RegisterStudent->s_father_name,
                                    'mother_name' => $RegisterStudent->s_mother_name,
                                    'institute_name' => $RegisterStudent->i_name,
                                    'institute_code' => $RegisterStudent->s_inst_code,
                                    's_reg_no' => $RegisterStudent->s_appl_reg_no,
                                    'mobile_no' => $RegisterStudent->s_phone,
                                    'reg_year' => $RegisterStudent->s_appl_reg_year,
                                    'student_dob' => $RegisterStudent->s_dob,
                                    'is_applied' => (bool)$RegisterStudent->is_enrollment,
                                    'is_paid'=>(bool)$RegisterStudent->is_enrollment_payment
                                ];
                                

                            });
                           
                            DB::commit();
                            return response()->json([
                                'error'     =>  false,
                                'message'   =>  'Student list found',
                                'student_list'=> $enrollmentdata,

                            ]);
                            
                        }catch (Exception $e) {
                            DB::rollback();
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ]);
                        } 
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 401);
                    }
                } else {
                    return response()->json([
                        'error'     =>  true,
                        'message'   =>   "Oops! you don't have sufficient permission"
                    ], 401);
                }
            } else {
                return response()->json([
                    'error'     =>  true,
                    'message'   =>  'Unable to process your request due to invalid token'
                ], 401);
            }
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }
    public function enrollmentsubmit(Request $request)
    {
        $user_name=$request->user_name;
       $validated = validator::make($request->all(), [
            'student_ids' => 'required',
        ]);
        if ($validated->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validated->errors()
            ]);
        }
        try{

            $studentIds = $request->student_ids;
            if (!is_array($studentIds)) {
                $studentIds = [$studentIds]; // Ensure always array
            }
          
            $exitenroll= Enrollment::where('enrl_reg_no',$studentIds)
                        ->where('is_enrollment', 1)
                        ->count();
            if($exitenroll>0){
                return response()->json([
                    'error'   => true,
                    'message' => 'Student already enrolled.',
                ]);
            }
            $students = Registerstudent::whereIn('s_appl_reg_no', $studentIds)
            ->where('is_registration_payment', 1)
            ->whereNotNull('s_appl_reg_no')
            ->orderBy('s_id', 'asc')
            ->get();
    
            if ($students->count() == 0) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No student found for enrollment.',
                ]);
            }
            $chance=1;
            foreach ($students as $student) {
                $year=$student->s_appl_sess_year;
                $enrl_year = substr($year, -4);
                $currentYear= date("Y");
                if($currentYear==$enrl_year)
                {
                    $chance=1;
                } else {
                    $lastChance=Enrollment::where('enrl_reg_no',$student->s_appl_reg_no)->select('chance')->first();
                    $chance=$lastChance+1;
                }
                Enrollment::create([
                    'enrl_form_num'      => $student->s_appl_form_num,
                    'enrl_inst_code'     => $student->s_inst_code,
                    'enrl_reg_no'        => $student->s_appl_reg_no,
                    'enrl_reg_year'      => $student->s_appl_reg_year,
                    'is_enrollment'   => 1,
                    'enrl_date' => now(),
                    'enrl_by'      => $user_name,
                    'enrl_year'      => $enrl_year,
                    'chance'      => $chance,
                ]);
               
            }
          
            return response()->json([
                'error'   => false,
                'message' => 'Enrollment submitted successfully.',
            ]);
        
        }catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function enrollmentDownload($institute_code)
    {
        try {
            if (empty($institute_code)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Institute code is required.',
                ], 422);
            }

            // Fetch enrollment data
            $enrollmentdata = Enrollment::join('pharmacy_register_student as prs', 'pharmacy_enrollment.student_id', '=', 'prs.s_id')
                ->join('institute_master', 'prs.s_inst_code', '=', 'institute_master.i_code')
                ->where('pharmacy_enrollment.is_enrollment', 1)
                ->where('prs.is_enrollment_payment', 1)
                // ->where('prs.s_session_year', $session_year)
                ->where('prs.s_inst_code', $institute_code)
                ->select(
                    'prs.s_candidate_name',
                    'prs.s_reg_no',
                    'prs.s_reg_year',
                    'prs.s_session_year',
                    'institute_master.i_name',
                    'prs.s_id',
                    'pharmacy_enrollment.student_id',
                    'pharmacy_enrollment.is_enrollment',
                    'prs.is_enrollment_payment',
                    'prs.s_trade_code',
                    'prs.student_guardian_name',
                )
                ->get();
            // dd($enrollmentdata);

            if ($enrollmentdata->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'No enrollment data found for given filters.',
                ]);
            }

            $studentIds = $enrollmentdata->pluck('student_id');

            $paymentData = PaymentTransaction::whereIn('pmnt_stud_id', $studentIds)
                ->where('pmnt_pay_type', 'ENROLLMENT')
                ->where('trans_status', 'SUCCESS')
                ->get()
                ->keyBy('pmnt_stud_id');

            $totalAmount = $paymentData->sum('trans_amount');

            $pdf = PDF::loadView('exports.enrollment-sheet', [
                'enrollmentdata' => $enrollmentdata,
                'paymentData' => $paymentData,
                'totalAmount' => $totalAmount,
            ]);

            return $pdf->setPaper('a4', 'portrait')
                ->setOption(['defaultFont' => 'sans-serif'])
                ->download('enrollment-sheet.pdf');

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
      
}
