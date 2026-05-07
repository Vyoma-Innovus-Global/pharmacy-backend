<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DateTime;
use Exception;
use App\Models\User;
use App\Models\Token;
use App\Models\Schedule;
use App\Models\Institute;
use App\Models\Subject;
use App\Models\Elective;
use Illuminate\Support\Str;
use App\Models\StudentChoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use App\Models\Registerstudent;
use App\Models\StudentActivity;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PharmacyPhotoSign;
use App\Models\PaymentTransaction;
use App\Models\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Models\PharmacyAppl_ElgbExam;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\StudentChoiceResource;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\StudentActivityResource;
use Illuminate\Support\Facades\Artisan;


class StudentController extends Controller
{
    //Student Data update
    public function studentInfoUpdate(Request $request)
    {

        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user_id = $token_check->t_user_id;

                $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();

                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('student/student-info-update', $url_data)) {
                        $validator = Validator::make($request->all(), [
                            'form_no' => 'required|max:255',
                            //'i_code' => 'required|max:255',
                        ]);

                        if ($validator->fails()) {
                            return response()->json(['errors' => true, 'message' => $validator->errors()], 422);
                        }


                        try {
                            $student = Registerstudent::where('s_appl_form_num', $request->form_no)->first();
                            if (!$student) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'Student record not found!'
                                ], 404);
                            }
                            $phoneExists = Registerstudent::whereNotNull('s_phone')->where('s_phone', $request->s_phone)
                                ->whereNot('s_appl_form_num', $request->form_no)
                                ->count();

                            $emailExists = Registerstudent::whereNotNull('s_email')->where('s_email', $request->s_email)
                                ->whereNot('s_appl_form_num', $request->form_no)
                                ->count();

                            // dd( $emailExists);

                            //dd($phoneExists);
                            if ($phoneExists) {
                                return response()->json([
                                    'error' => true,
                                    'message' => "This phone number already exists"
                                ]);
                            }

                            if ($emailExists) {
                                return response()->json([
                                    'error' => true,
                                    'message' => "This email address already exists"
                                ]);
                            }
                            if ($request->user_role != "COUNCIL_ADMIN") {
                                $validated = Validator::make($request->all(), [
                                    'first_name' => ['required'],
                                    'middle_name' => ['nullable'],
                                    'last_name' => ['required'],
                                    'father_name' => ['required'],
                                    'mother_name' => ['required'],
                                    'dob' => ['required'],
                                    'email' => ['required'],
                                    's_phone' => ['required'],
                                    's_aadhar_no' => ['required'],
                                    'gender' => ['required'],
                                    'address' => ['required'],
                                    'ps' => ['required'],
                                    'po' => ['required'],
                                    'pin' => ['required'],
                                    'is_married' => ['required'],
                                    'is_kanyashree' => ['required'],
                                    'is_pwd' => ['required']
                                ], [
                                    'first_name.required'    => 'First name is required',
                                    'last_name.required'     => 'Last name is required',
                                    'father_name.required'   => 'Father\'s name is required',
                                    'mother_name.required'   => 'Mother\'s name is required',
                                    'dob.required'           => 'Date of birth is required',
                                    'email.required'         => 'Email is required',
                                    's_phone.required'       => 'Student phone number is required',
                                    's_aadhar_no.required'   => 'Aadhar number is required',
                                    'gender.required'        => 'Gender is required',
                                    'address.required'       => 'Address is required',
                                    'ps.required'            => 'Police Station is required',
                                    'po.required'            => 'Post Office is required',
                                    'pin.required'           => 'PIN code is required',
                                    'is_married.required'    => 'Marital status is required',
                                    'is_kanyashree.required' => 'Kanyashree status is required',
                                    'is_pwd.required'        => 'Disability status is required',
                                ]);

                                if ($validated->fails()) {
                                    return response()->json([
                                        'error' => true,
                                        'message' => $validated->errors()->first()
                                    ]);
                                }
                            }

                            Artisan::call('storage:link');
                            //$aadharNo = $student->s_aadhar_original;
                            $aadharbyInput = $request->s_aadhar_no;
                            $lastFourDigits = substr($aadharbyInput, -4);
                            $encryptAadhar = encryptHEXFormat($lastFourDigits);

                            $photoPath = null;
                            $signPath = null;
                            $time = time();
                            if ($request->hasFile('s_photo')) {
                                $image = $request->file('s_photo');
                                $imageName = $request->form_no . '_image' . $time . '.' . $image->getClientOriginalExtension();
                                $photoPath = 'uploads/' . $imageName;
                                $image->storeAs('uploads/', $imageName, 'public');
                            } else {
                                $photoPath = $student->s_photo;
                            }
                            if ($request->hasFile('s_sign')) {
                                $signature = $request->file('s_sign');
                                $signatureName = $request->form_no . '_sign' . $time . '.' . $signature->getClientOriginalExtension();
                                $signPath = 'uploads/' . $signatureName;
                                $signature->storeAs('uploads/', $signatureName, 'public');
                            } else {
                                $signPath = $student->s_sign;
                            }
                            // 
                            if (($request->file('s_photo')) && ($request->file('s_sign'))) {
                                $is_profile_updated = 1;
                            } else {
                                $is_profile_updated = 0;
                            }
                            //dd($is_profile_updated);
                            $student->update([
                                's_first_name' => $request->first_name,
                                's_middle_name' => $request->middle_name,
                                's_last_name' => $request->last_name,
                                's_candidate_name' => Str::replace('  ', ' ', "{$request->first_name} {$request->middle_name} {$request->last_name}"),
                                's_father_name' => $request->father_name,
                                's_mother_name' => $request->mother_name,
                                's_dob' => $request->dob,
                                's_email' => $request->email,
                                's_phone' => $request->s_phone,
                                's_aadhar_no' => $encryptAadhar,
                                's_aadhar_original' => $aadharbyInput,
                                's_gender' => $request->gender,
                                'address' => $request->address,
                                'ps' => $request->ps,
                                'po' => $request->po,
                                'pin' => $request->pin,
                                'is_married' => $request->is_married ? 1 : 0,
                                'is_kanyashree' => $request->is_kanyashree ? 1 : 0,
                                's_pwd' => $request->is_pwd ? 1 : 0,
                                'is_profile_updated' => $is_profile_updated,
                                's_photo' => $photoPath,
                                's_sign' => $signPath,
                            ]);

                            auditTrail($student->s_appl_form_num, "{$student->s_candidate_name} updated details");
                            studentActivite($student->s_appl_form_num, "{$student->s_candidate_name} updated details");

                            $studentData = Registerstudent::where('s_appl_form_num', $request->form_no)->first();

                            $rank_data = [];
                            $userRank = $studentData;

                            $rankArr = [
                                's_gen_rank',
                                's_sc_rank',
                                's_st_rank',
                                's_obca_rank',
                                's_obcb_rank',
                                's_pwd_rank',
                                's_tfw_rank',
                                's_ews_rank',
                                's_llq_rank',
                                's_exsm_rank'
                            ];

                            foreach ($rankArr as $val) {
                                $userRankData = (int)$userRank[$val];
                                if (!is_null($userRankData) && ($userRankData != 0)) {
                                    array_push($rank_data, [
                                        'category' => casteValue(Str::upper(explode('_', $val)[1])),
                                        'rank' => $userRankData
                                    ]);
                                }
                            }

                            $check_choice_fillup = config_schedule('CHOICE_FILLUP');
                            $choice_sehedule = $check_choice_fillup['status'];

                            $check_accept = config_schedule('ACCEPT');
                            $allotment_schedule = $check_accept['status'];

                            return response()->json([
                                'error' => false,
                                'message' => 'Updated Successfully',
                                'profile_update'   => (bool)$studentData->is_profile_updated,
                                'choice_sehedule' => ($studentData->is_profile_updated == 1) && $choice_sehedule,
                                'allotment_schedule' => ($studentData->is_choice_fill_up == 1) && ($studentData->is_lock_manual == 1) && $allotment_schedule,
                                'user' => [
                                    's_id' => $studentData->s_id,
                                    's_uuid' => $studentData->s_uuid,
                                    's_appl_form_num' => $studentData->s_appl_form_num,
                                    's_first_name' => $studentData->s_first_name,
                                    's_middle_name' => $studentData->s_middle_name,
                                    's_last_name' => $studentData->s_last_name,
                                    's_full_name' => $studentData->s_candidate_name,
                                    's_father_name' => $studentData->s_father_name,
                                    's_mother_name' => $studentData->s_mother_name,
                                    's_dob' => $studentData->s_dob,
                                    's_aadhar_no' => $encryptAadhar,
                                    's_aadhar_original' => $aadharbyInput,
                                    's_phone' => $studentData->s_phone,
                                    's_email' => $studentData->s_email,
                                    's_gender' => $studentData->s_gender,
                                    's_religion' => $studentData->s_religion,
                                    's_caste' => $studentData->s_caste,
                                    's_tfw' => $studentData->s_tfw,
                                    's_pwd' => $studentData->s_pwd,
                                    's_llq' => $studentData->s_llq,
                                    's_exsm' => $studentData->s_exsm,
                                    's_inst_code' => $studentData->s_inst_code,
                                    's_photo' => URL::to("storage/{$studentData->s_photo}"),
                                    's_sign' => URL::to("storage/{$studentData->s_sign}"),
                                    's_home_district' => !is_null($studentData->s_home_district) ? $studentData->s_home_district : "",
                                    's_schooling_district' => !is_null($studentData->s_schooling_district) ? $studentData->s_schooling_district : "",
                                    's_state_id' => $studentData->s_state_id,
                                    'created_at' => $studentData->created_at,
                                    'updated_at' => $studentData->updated_at,
                                    'address' => $studentData->address,
                                    'ps' => $studentData->ps,
                                    'po' => $studentData->po,
                                    'pin' => $studentData->pin,
                                    'is_married' => (bool)$studentData->is_married,
                                    'is_kanyashree' => (bool)$studentData->is_kanyashree,
                                    'role_id' => 2,
                                ],
                            ]);
                        } catch (Exception $e) {
                            return response()->json([
                                'error' => true,
                                'message' => $e->getMessage()
                            ]);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
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
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //Download Form after Photo Sign upload
    public function downloadForm($from_num)
    {
        try {
            $registerstudent = DB::table('pharmacy_register_student_final')
                ->where(['s_appl_form_num' => $from_num])
                ->leftJoin('institute_master', 'i_code', '=', 's_inst_code')
                ->select(
                    'pharmacy_register_student_final.*',
                    'institute_master.i_id',
                    'institute_master.i_name',
                    'institute_master.i_code'
                )
                ->first();

            $pdf = PDF::loadView('exports.applicationform', [
                'registerstudent' => $registerstudent,
            ]);

            return $pdf->setPaper('a4', 'portrait')
                ->setOption(['defaultFont' => 'sans-serif',])
                ->stream('applicationform_{$from_num}.pdf');
        } catch (Exception $e) {
            generateLaravelLog($e);
            return response()->json([
                'error' =>  true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function checkRedirect($user_id)
    {
        $newuser = Registerstudent::where('s_id', $user_id)->first();
        // dd($newuser);

        if ($newuser) {
            $profile_updated =  false;


            $profile_updated = (bool)$newuser->is_profile_updated;




            $redirect = [
                'profile_update' => $profile_updated,
                'registration_fees_paid' => (bool)$newuser->is_registration_payment,
                'overall_status' => getOverallStatus($newuser->s_id),

            ];

            return response()->json([
                'error'     =>  false,
                'message'   =>  'Data found',
                'redirect' => $redirect
            ]);
        }
    }

    public function studentdetails($from_num)
    {
        $student = Registerstudent::where('s_appl_form_num', $from_num)->first();

        if (!$student) {
            return response()->json([
                'error' => true,
                'message' => 'Student not found',
            ], 404);
        }

        $studentData = [
            's_id' => $student->s_id,
            's_uuid' => $student->s_uuid,
            's_appl_form_num' => $student->s_appl_form_num,
            // 's_appl_sess_year' => $student->s_appl_sess_year,
            // 's_appl_reg_no' => $student->s_appl_reg_no,
            // 's_appl_reg_year' => $student->s_appl_reg_year,
            's_first_name' => $student->s_first_name,
            's_middle_name' => $student->s_middle_name,
            's_last_name' => $student->s_last_name,
            's_full_name' => $student->s_candidate_name,
            's_father_name' => $student->s_father_name,
            's_mother_name' => $student->s_mother_name,
            's_dob' => $student->s_dob,
            's_aadhar_no' => $student->s_aadhar_original,
            's_phone' => $student->s_phone,
            's_email' => $student->s_email,
            's_gender' => $student->s_gender,
            's_religion' => $student->s_religion,
            's_caste' => $student->s_caste,
            'address' => $student->address,
            's_tfw' => $student->s_tfw,
            's_pwd' => $student->s_pwd,
            's_llq' => $student->s_llq,
            's_exsm' => $student->s_exsm,
            'ps' => $student->ps,
            'po' => $student->po,
            'pin' => $student->pin,
            'institute_code' => $student->s_inst_code,
            's_alloted_category' => $student->s_alloted_category,
            's_photo' => $student->s_photo ? URL::to("storage/{$student->s_photo}") : '',
            's_sign'  => $student->s_sign ? URL::to("storage/{$student->s_sign}") : '',
            'is_married' => (bool)$student->is_married,
            'is_kanyashree' => (bool)$student->is_kanyashree,
            'role_id' => 2,
            'is_registration_payment' => (bool)$student->is_registration_payment,
            'adm_type' => $student->admission_type,
        ];

        return response()->json([
            'error' => false,
            'message' => 'Data found',
            'data' => $studentData,
        ]);
    }

    //Session List
    public function sessionList()
    {
        $years = Registerstudent::distinct()->pluck('s_appl_sess_year');
        return response()->json([
            'years' => $years,
        ]);
    }

    //Institute list
    public function instituteList(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user_id = $token_check->t_user_id;

                $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();

                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');
                //dd($role_url_access_id);
                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('student/institute-list', $url_data)) {
                        try {
                            $userId = $request->userId;
                            $role = $request->role;

                            $istitute = Institute::select('i_code', 'i_name');
                            if ($request->inst_code) {
                                $istitute = $istitute->where('i_code', $request->inst_code);
                            }
                            $istitute = $istitute->get();

                            return response()->json([
                                'institute' => $istitute,

                            ]);
                        } catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 500);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
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
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }


    public function registrationList(Request $request)
    {
        $i_code = $request->i_code;
        $sess_year = $request->sess_year;
        $students = Registerstudent::where('s_inst_code', $i_code)
            ->where('is_registration_payment', 1)
            ->where('s_appl_sess_year', $sess_year)
            ->select('s_id', 's_appl_reg_no', 's_appl_reg_year', 's_candidate_name', 's_appl_form_num', 's_inst_code', 'admission_type')
            ->get();

        if ($students->isEmpty()) {
            return response()->json([
                'message' => 'No eligible students found or Registration IDs already generated.',
            ], 409);
        }

        return response()->json([
            'list' => $students,
        ]);
    }

    //Generate Registration Number
    public function generateRegNo(Request $request)
    {
        $form_id = $request->form_id;


        $student = Registerstudent::whereIn('s_appl_form_num', $form_id)
            ->get();
        $counter = 1;
        foreach ($student as $formid) {
            $lastRegistration = Registerstudent::where('s_appl_sess_year', $formid->s_appl_sess_year)
                ->whereNotNull('s_appl_reg_no')
                ->orderBy('s_appl_reg_no', 'desc')
                ->first();

            // If records exist, extract the counter from the last reg_no
            if ($lastRegistration && $lastRegistration->s_appl_reg_no) {
                $lastNumber = substr($lastRegistration->s_appl_reg_no, -5);
                $counter = (int)$lastNumber + 1;
            }
            $sessionParts = explode('-', $formid->s_appl_sess_year);
            $sessionCode = substr($sessionParts[0], -2) . substr($sessionParts[1], -2);
            $prefix = 'PHARM' . $sessionCode;
            $registrationId = $prefix . str_pad($counter, 5, '0', STR_PAD_LEFT);
            if ($formid->s_appl_reg_no) {
                return response()->json([
                    'message' => 'Registration number already generated!',
                ]);
            } else {
                Registerstudent::where('s_appl_form_num', $formid->s_appl_form_num)
                    ->update([
                        's_appl_reg_no' => $registrationId,
                        's_appl_reg_year' => $formid->s_appl_sess_year,
                        's_part1_status' => 1
                    ]);
                auditTrail($formid->s_appl_form_num, "{$formid->s_candidate_name} Registration Number Generated.");
            }
            $counter++;
        }
        return response()->json([
            'message' => 'Registration number generated successfully!',
        ]);
    }

    //Get data for Syllabus tag
    public function getDataforsyllabus(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user_id = $token_check->t_user_id;

                $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();

                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('student/get-data-for-syllabus', $url_data)) {
                        $validator = Validator::make($request->all(), [
                            'session_year' => 'required|max:255',
                        ]);

                        if ($validator->fails()) {
                            return response()->json(['errors' => true, 'message' => $validator->errors()], 422);
                        }

                        try {
                            $institute_code = $request->institute_code;
                            $session_year = $request->session_year;
                            
                            
                            $data = Registerstudent::leftJoin('pharmacy_users_master', 'pharmacy_register_student_final.s_inst_code', '=', 'pharmacy_users_master.u_inst_code')
                            ->where('pharmacy_register_student_final.s_appl_sess_year', $session_year)
                            ->when($institute_code, function ($query, $institute_code) {
                                return $query->where('pharmacy_register_student_final.s_inst_code', $institute_code);
                            })
                            ->whereNotNull('pharmacy_register_student_final.s_appl_reg_no')
                            ->select(
                                DB::raw("CONCAT_WS(' ', pharmacy_register_student_final.s_first_name, pharmacy_register_student_final.s_middle_name, pharmacy_register_student_final.s_last_name) as full_name"),
                                'pharmacy_register_student_final.s_appl_form_num',
                                'pharmacy_register_student_final.s_appl_reg_no',
                                'pharmacy_register_student_final.s_inst_code',
                                'pharmacy_users_master.u_inst_name as s_inst_name',
                                'pharmacy_register_student_final.syllabus_id'
                            )
                            ->get();    

                            return response()->json([
                                'error'     =>  false,
                                'data' => $data
                            ]);
                        } catch (\Exception $e) {
                            return response()->json([
                                'error'     =>  true,
                                'message' => 'Error fetching data: ' . $e->getMessage()
                            ], 500);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
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
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //Syllabus List
    public function syllabus_idList(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user_id = $token_check->t_user_id;

                $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();

                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('student/syllabus-id-list', $url_data)) {
                       
                       try {
                        $data = DB::table('pharmacy_subjects_master')
                                ->select('syllabus','syllabus_id',
                                    DB::raw("CONCAT(syllabus, ' (', syllabus_id, ')') as syllabus_name")
                                )
                                ->groupBy('syllabus', 'syllabus_id')
                                ->get();

                            return response()->json([
                                'status' => 'success',
                                'syllabusData' => $data
                            ]);
                        } catch (\Exception $e) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Error fetching data: ' . $e->getMessage()
                            ], 500);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
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
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //Syllabus tag with registered Students
    public function syllabusTagSubmit(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user_id = $token_check->t_user_id;
                $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('student/syllabus-tag', $url_data)) {
                       try {
                            $syllabus_id    = $request->syllabus_id;
                            $session_year   = $request->sess_year;

                            $data = Registerstudent::where('s_appl_sess_year', $session_year)
                                ->whereNotNull('s_appl_reg_no')
                                ->whereNull('syllabus_id')
                                ->select('s_appl_form_num')
                                ->get();
                            if($data->isNotEmpty()){
                                foreach ($data as $dataUpdate) {
                                Registerstudent::where('s_appl_form_num', $dataUpdate->s_appl_form_num)
                                    ->update([
                                        'syllabus_id' => $syllabus_id
                                    ]);
                            }
                            return response()->json(['status' => 'success', 'message' => 'Syllabus Tagged successfully.']);
                            }
                            else{
                                return response()->json([
                                    'error'     =>  true,
                                    'message'   =>   "Syllabus Already Tagged"
                                ], 200);
                            }
                            
                        } catch (\Exception $e) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Error fetching data: ' . $e->getMessage()
                            ], 500);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
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
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    //Subject List for elective selection
    public function subjectList(Request $request)
    {
        try {
            $session = $request->session_year;
            $semester = $request->part_sem;
            $institute = $request->inst_code;

            $avlData = Registerstudent::select('s_appl_form_num','part_I_elective')
                        ->where('s_appl_sess_year', $session)
                        ->whereNotNull('s_appl_reg_no')
                        ->whereNotNull('syllabus_id')
                        ->where('s_part_sem', $semester)
                        ->where('s_inst_code', $institute)
                        ->get();
            if($avlData){
                $paperList = Subject::where('semester', $semester)
                            ->where('is_active', 1)
                            ->orderBy('subject_sn', 'asc')
                            ->get();

                $electiveList = Elective::where('elect_part_sem', $semester)
                            ->where('elect_session_year', $session)
                            ->where('elect_inst_code', $institute)
                            ->get();
                $electiveSet = false;
                $electPapers = [];       
                $output = [];       
                if ($electiveList->count() > 0) {
                    $electiveSet = true;
                    $electPapers = $electiveList->pluck('elect_paper');
                    $output = explode(',', $electPapers[0]);
                }        

                return response()->json([
                    'status' => 'success',
                    'data' => $paperList,
                    'electiveSet' => $electiveSet,
                    'electPapers' => $output,
                ]);
            }else{
                return response()->json([
                                    'error'     =>  true,
                                    'message'   =>   "No data available or Syllabus not tagged till now."
                                ], 200);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching data: ' . $e->getMessage()
            ], 500);
        }
    }

    //Submit Elective Paper
    public function submitElectivePaper(Request $request)
    {
        if ($request->header('token')) {
            $now    =   date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user_id = $token_check->t_user_id;
                $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('student/submit-elective-subject', $url_data)) {
                       try {
                            $session = $request->session_year;
                            $semester = $request->part_sem;
                            $institute = $request->inst_code;
                            $electives = $request->subject_ids;
                           $is_updated = false;
                            DB::beginTransaction();
                           Registerstudent::where('s_appl_sess_year', $session)
                                    ->where('s_part_sem', $semester)
                                    ->where('s_inst_code', $institute)
                                    ->update([
                                        'part_I_elective' => is_array($electives) ? implode(',',$electives) : $electives
                                    ]);
                            Elective::updateOrCreate([
                                'elect_session_year' => $session,
                                'elect_inst_code' => $institute,
                                'elect_part_sem' => $semester,
                                
                            ],[
                                'elect_paper' => is_array($electives) ? implode(',',$electives) : $electives
                            ]);
                            $is_updated = true;
                            DB::commit();
                            if($is_updated ){
                                return response()->json(['status' => 'success', 'message' => 'Elective Paper saved successfully.']);
                            }else{
                                return response()->json(['status' => 'error', 'message' => 'Something went wrong.']); 
                            }
                              
                        } catch (\Exception $e) {
                            DB::rollback();
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Error fetching data: ' . $e->getMessage()
                            ], 500);
                        }
                    } else {
                        return response()->json([
                            'error'     =>  true,
                            'message'   =>   "Oops! you don't have sufficient permission"
                        ], 403);
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
        } else {
            return response()->json([
                'error'     =>  true,
                'message'   =>  'Unable to process your request due to non availability of token'
            ], 401);
        }
    }


}
