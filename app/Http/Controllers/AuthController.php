<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Otp;
use App\Models\Role;
use App\Models\Token;
use App\Models\SuperUser;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Registerstudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Http\Resources\SuperUserResource;
use Illuminate\Support\Facades\Validator;
use App\Models\EvaluatorDetails;
use App\Models\EvaluatorAllocation;

use Illuminate\Support\Facades\Mail;
use App\Mail\ResetpasswordMail;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $user_type = $request->input('user_type');
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $otp_code = Config::get('app.env') === 'production' ? rand(1111, 9999) : 1234;
        $sms_message = "{$otp_code} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";

        switch ($user_type) {
            case 'STUDENT':
                $validated = Validator::make($request->all(), [
                    'user_phone' => ['required'],
                    'aadhar_num' => ['required']
                ]);

                if ($validated->fails()) {
                    return response()->json([
                        'error' => true,
                        'message' => $validated->errors()
                    ]);
                }
                $login_phone = trim($request->user_phone);
                $login_aadhar = trim($request->aadhar_num);

                $result = DB::select(
                    'SELECT public.fn_generateotp_student(?::varchar, ?::varchar) AS data',
                    [$login_aadhar, $login_phone]
                );

                $raw = $result[0]->data ?? null;
                $otpData = is_string($raw) ? json_decode($raw, true) : (array) $raw;
                $errorCode = (int)($otpData['p_errorcode'] ?? -1);

                if ($errorCode !== 0) {
                    $errorMessages = [
                        1 => 'No data Found',
                        2 => 'Aadhar Number not matched',
                        3 => 'Your previous OTP was generated in last 2 minutes',
                        4 => 'You exceed the OTP generation limit for today. Try again tomorrow.',
                    ];

                    return response()->json([
                        'error' => true,
                        'message' => $otpData['p_message'] ?? ($errorMessages[$errorCode] ?? 'OTP generation failed. Please try again.'),
                    ], 200);
                }

                $otp_code = (string)($otpData['p_otp'] ?? '');

                if ($otp_code === '') {
                    return response()->json([
                        'error' => true,
                        'message' => 'OTP generation failed. Please try again.',
                    ], 200);
                }

                $sms_message_user = "{$otp_code} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";
                send_sms($login_phone, $sms_message_user);

                $otp_exp_time = date('Y-m-d H:i:s', strtotime('+120 seconds', strtotime($now)));

                return response()->json([
                    'error' => false,
                    'message' => 'Otp sent successfully',
                    'otp_expire_time' => formatDate($otp_exp_time, 'Y-m-d H:i:s', 'M j, Y H:i:s'),
                    'user_phone' => $login_phone,
                    'p_otp' => Config::get('app.env') !== 'production' ? $otp_code : null,
                ], 200);
                break;
            case 'COUNCIL_ADMIN':
                $now = date('Y-m-d H:i:s');
                $user_name = $request->user_name;
                $password = $request->user_password;
                $converted_pw = hash("sha512", $password);

                $user = SuperUser::where([
                    'u_username' => $user_name,
                    'u_password' => $converted_pw,
                    'is_active' => 1
                ])->first();

                if ($user) {
                    if (!$user->u_phone) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Phone Number not found'
                        ], 404);
                    }

                    $otp_res = Otp::where('username', $user->u_phone)->first();

                    if ($otp_res) {
                        $last_otp_date = substr(trim($otp_res->otp_created_on), 0, 10);

                        switch ($last_otp_date) {
                            case $today:
                                $minutes = getTimeDiffInMinute($now, $otp_res->otp_created_on);
                                send_sms($user->u_phone, $sms_message);

                                $otp_res->update([
                                    'username' => $user->u_phone,
                                    'otp' => $otp_code,
                                    'otp_created_on' => $now,
                                    'otp_count' => intval($otp_res->otp_count) + 1
                                ]);
                                $otp_send = true;
                            default:
                                send_sms($user->u_phone, $sms_message);

                                $otp_res->update([
                                    'username' => $user->u_phone,
                                    'otp' => $otp_code,
                                    'otp_created_on' => $now,
                                    'otp_count' => 1
                                ]);

                                $otp_send = true;
                                break;
                        }
                    }
                    else {
                        send_sms($user->u_phone, $sms_message);

                        Otp::updateOrCreate([
                            'username' => $user->u_phone,
                        ], [
                            'otp' => $otp_code,
                            'otp_created_on' => $now,
                            'otp_count' => 1
                        ]);

                        $otp_send = true;
                    }

                    if ($otp_send) {
                        $otp_exp_time = date('Y-m-d H:i:s', strtotime('+120 seconds', strtotime($now)));

                        return response()->json([
                            'error' => false,
                            'message' => 'Otp sent successfully',
                            'otp_expire_time' => formatDate($otp_exp_time, 'Y-m-d H:i:s', 'M j, Y H:i:s'),
                            'user_phone' => $user->u_phone,
                        ], 200);
                    }
                }
                else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Either your username or password is wrong'
                    ], 404);
                }
                break;

            //EVALUATOR
            case 'EVALUATOR':
                $validated = Validator::make($request->all(), [
                    'user_phone' => ['required'],
                    'aadhar_num' => ['required']
                ]);

                if ($validated->fails()) {
                    return response()->json([
                        'error' => true,
                        'message' => $validated->errors()
                    ]);
                }
                $login_phone = $request->user_phone;
                $login_aadhar = $request->aadhar_num;

                $evaluator = EvaluatorDetails::where([
                    'phone' => $login_phone,
                    'is_active' => 1,
                ])->first();

                if (!$evaluator) {
                    return response()->json([
                        'error' => true,
                        'message' => 'No data Found'
                    ], 200);
                }

                // $otp_code = rand(1111, 9999);
                $evaluator_phone = $evaluator->phone;
                $sms_message_user = "{$otp_code} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";
                $evaluator_adhar = substr($evaluator->aadhaar, -4);

                switch ($evaluator_adhar) {
                    case $login_aadhar:
                        $otp_res = Otp::where('username', $evaluator_phone)->first();
                        break;
                    default:
                        return response()->json([
                            'error' => true,
                            'message' => "Aadhar Number not matched"
                        ], 200);
                }

                if ($otp_res) {
                    $last_otp_date = substr(trim($otp_res->otp_created_on), 0, 10);

                    switch ($last_otp_date) {
                        case $today:
                            $minutes = getTimeDiffInMinute($now, $otp_res->otp_created_on);

                            if ($otp_res->otp_count < 9) {
                                // if ($minutes > 2) {
                                send_sms($evaluator_phone, $sms_message_user);

                                $otp_res->update([
                                    'username' => $evaluator_phone,
                                    'otp' => $otp_code,
                                    'otp_created_on' => $now,
                                    'otp_count' => intval($otp_res->otp_count) + 1
                                ]);

                                $otp_send = true;
                            }
                            else {
                                return response()->json([
                                    'error' => true,
                                    'message' => "You exceed the OTP generation limit for today. Try again tomorrow."
                                ], 200);
                            }
                            break;
                        default:
                            send_sms($evaluator_phone, $sms_message_user);

                            $otp_res->update([
                                'username' => $evaluator_phone,
                                'otp' => $otp_code,
                                'otp_created_on' => $now,
                                'otp_count' => 1
                            ]);

                            $otp_send = true;
                            break;
                    }
                }
                else {
                    send_sms($evaluator_phone, $sms_message_user);

                    Otp::updateOrCreate([
                        'username' => $evaluator_phone,
                    ], [
                        'otp' => $otp_code,
                        'otp_created_on' => $now,
                        'otp_count' => 1
                    ]);

                    $otp_send = true;
                }

                if ($otp_send) {
                    $otp_exp_time = date('Y-m-d H:i:s', strtotime('+120 seconds', strtotime($now)));

                    return response()->json([
                        'error' => false,
                        'message' => 'Otp sent successfully',
                        'otp_expire_time' => formatDate($otp_exp_time, 'Y-m-d H:i:s', 'M j, Y H:i:s'),
                        'user_phone' => $evaluator_phone,
                    ], 200);
                }
                break;

            default:
                $user_name = $request->user_name;
                $password = $request->user_password;
                $converted_pw = hash("sha512", $password);

                try {
                    $user = SuperUser::where([
                        'u_username' => $user_name,
                        'u_password' => $converted_pw,
                        'is_active' => 1
                    ])->with('role:role_id,role_name,role_description')->first();

                    if ($user) {
                        $token = md5($now . rand(10000000, 99999999));
                        $expiry = date("Y-m-d H:i:s", strtotime('+4 hours', strtotime($now)));

                        DB::beginTransaction();

                        Token::updateOrCreate([
                            't_user_id' => $user->u_id,
                        ], [
                            't_token' => $token,
                            't_generated_on' => $now,
                            't_expired_on' => $expiry,
                        ]);

                        $user->update([
                            'updated_at' => Carbon::parse(now())->format('Y-m-d H:i:s.u'),
                        ]);

                        switch ($user->u_role_id) {
                            case 3:
                                $user_name = str_replace('_AWAY', '', $user_name);
                                $instDetails = DB::table('institute_master')->where('i_code', $user_name)->first();

                                $instType = $instDetails->i_type;
                                break;

                            case 5:

                                $instType = 'NA';
                                $evaluator = EvaluatorDetails::where([
                                    'ev_id' => $user->u_ref,
                                    'is_active' => 1
                                ])->first();
                                // dd($evaluator);
                                break;
                            default:
                                $instType = 'Council';
                                break;
                        }

                        DB::commit();

                        return response()->json([
                            'role' => $user_type,
                            'error' => false,
                            'token' => $token,
                            'token_expired_on' => $expiry,
                            //'user' => new SuperUserResource($user),

                            'user' => [
                                'user_id' => $user->u_id,
                                'user_ref' => $user->u_ref,
                                'user_name' => $user->u_username,
                                'inst_code' => $user->u_inst_code ?? '',
                                'inst_name' => $user->u_inst_name ?? '',
                                'full_name' => $user->u_fullname,
                                'user_role' => ($user->u_role_id == 3) ? "Collage Admin" : "Council Admin",
                                'user_role_id' => $user->u_role_id,
                                'is_default_pwd' => $user->is_default_password,
                                'u_phone' => $user->u_role_id == 5 ? $evaluator->phone : $user->phone,
                                'u_email' => $user->u_role_id == 5 ? $evaluator->email : $user->email,
                                'evaluator_roles' => $user->u_role_id == 5 ? $this->evaluatorRole($user)['roles'] : [],
                                'evaluator_type' => match ($user->u_role_id) {
                                    5 => $this->evaluatorRole($user)['roles'][0],
                                    3 => "COLLEGE",
                                    default => "",
                                },
                            ],
                            'inst_type' => $instType,
                            //'statusMap'=>checkStatus()
                        ], 200);
                    }
                    else {
                        return response()->json([
                            'error' => true,
                            'message' => 'Either your username or password is wrong'
                        ], 404);
                    }
                }
                catch (Exception $e) {
                    DB::rollBack();
                    generateLaravelLog($e);
                    return response()->json([
                        'error' => true,
                        'message' => $e->getMessage()
                    ]);
                }
        }
    }

    private function evaluatorRole($user)
    {
        $exam_year = "2025";

        $evaluator = EvaluatorAllocation::where([
            'exam_year' => $exam_year,
        ]);
        if ($evaluator) {
            $examiner = $evaluator->clone()->where('examiner_id', $user->ev_id)->first();
            $head_examiner = $evaluator->clone()->where('head_examiner_id', $user->ev_id)->first();
            $scrutinizer = $evaluator->clone()->where('scrutinizer_id', $user->ev_id)->first();

            $evaluator_role = [];

            if ($examiner) {
                array_push($evaluator_role, 'EXAMINER');
            }

            if ($head_examiner) {
                array_push($evaluator_role, 'HEAD_EXAMINER');
            }

            if ($scrutinizer) {
                array_push($evaluator_role, 'SCRUTINIZER');
            }

            return [
                'roles' => $evaluator_role,
                'name' => Str::replace('_', ' ', implode(',', $evaluator_role))
            ];
        }
    }

    //Validate OTP during Login
    public function validateSecurityCode(Request $request)
    {
        $now = date('Y-m-d H:i:s');
        $validated = Validator::make($request->all(), [
            'user_phone' => ['required'],
            'user_type' => ['required'],
            'security_code' => ['required'],
        ]);

        if ($validated->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validated->errors()
            ]);
        }

        $login_phone = $request->user_phone;
        $login_otp = $request->security_code;

        $otp = Otp::where([
            'username' => $login_phone,
            'otp' => $login_otp
        ])->first();

        if ($otp) {
            # DB::beginTransaction();
            try {

                if ($request->user_type === 'STUDENT') {
                    $student = Registerstudent::where([
                        's_phone' => $login_phone,
                        'is_active' => 1
                    ])->first();

                    if ($student) {
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

                        $rank_data = [];

                        foreach ($rankArr as $val) {
                            $userRankData = (int)$student[$val];

                            if (!is_null($userRankData) && ($userRankData != 0)) {
                                array_push($rank_data, [
                                    'category' => casteValue(Str::upper(explode('_', $val)[1])),
                                    'rank' => $userRankData
                                ]);
                            }
                        }

                        $token = md5($now . rand(10000000, 99999999));
                        $expiry = date("Y-m-d H:i:s", strtotime('+4 hours', strtotime($now)));

                        Token::updateOrCreate([
                            't_user_id' => $student->s_id,
                        ], [
                            't_token' => $token,
                            't_generated_on' => $now,
                            't_expired_on' => $expiry,
                        ]);

                        $s_photo = null;
                        $s_sign = null;

                        $user_data = [
                            's_id' => $student->s_id,
                            's_uuid' => $student->s_uuid,
                            's_appl_form_num' => $student->s_appl_form_num,
                            's_first_name' => $student->s_first_name,
                            's_middle_name' => $student->s_middle_name,
                            's_last_name' => $student->s_last_name,
                            's_full_name' => $student->s_candidate_name,
                            's_father_name' => $student->s_father_name,
                            's_mother_name' => $student->s_mother_name,
                            's_dob' => $student->s_dob,
                            's_aadhar_no' => $student->s_aadhar_no,
                            's_phone' => $student->s_phone,
                            's_email' => $student->s_email,
                            's_gender' => $student->s_gender,
                            's_religion' => $student->s_religion,
                            's_caste' => $student->s_caste,
                            's_tfw' => $student->s_tfw,
                            's_pwd' => $student->s_pwd,
                            's_llq' => $student->s_llq,
                            's_exsm' => $student->s_exsm,
                            's_alloted_category' => $student->s_alloted_category,
                            's_inst_code' => $student->s_inst_code,
                            's_photo' => $student->s_photo ?URL::to("storage/{$student->s_photo}") : '',
                            's_sign' => $student->s_sign ?URL::to("storage/{$student->s_sign}") : '',
                            's_state_id' => $student->s_state_id,
                            'created_at' => $student->created_at,
                            'updated_at' => $student->updated_at,
                            'address' => $student->address,
                            'ps' => $student->ps,
                            'po' => $student->po,
                            'pin' => $student->pin,
                            'is_married' => (bool)$student->is_married,
                            'is_kanyashree' => (bool)$student->is_kanyashree,
                            'role_id' => 2,
                            'is_registration_payment' => (bool)$student->is_registration_payment,
                            'adm_type' => $student->adm_type,

                        ];

                        $student_name = $student->s_candidate_name;

                        //auditTrail($student->s_appl_form_num, "{$student_name} has been logged in successfully");
                        studentActivite($student->s_appl_form_num, "{$student->s_appl_form_num} with name {$student_name} has been logged in successfully");

                        $otp->delete();

                        DB::commit();

                        $profile_updated = false;
                        $profile_updated = (bool)$student->is_profile_updated;




                        return response()->json([
                            'role' => 'STUDENT',
                            'error' => false,
                            'token' => $token,
                            'token_expired_on' => $expiry,
                            'user' => $user_data,
                            'redirect' => [
                                'profile_update' => $profile_updated,
                                'registration_fees_paid' => (bool)$student->is_registration_payment,

                                'login_type' => $request->login_type ?: null,

                                'overall_status' => getOverallStatus($student->s_appl_form_num),
                                // 'check_photo_sign' => $check_photo_sign
                            ]
                        ], 200);
                    }
                    else {
                        return response()->json([
                            'error' => true,
                            'message' => 'OTP is wrong'
                        ], 200);
                    }
                }

                //EVALUATOR
                if ($request->user_type === 'EVALUATOR') {
                    try {
                        $user = EvaluatorDetails::with('roles:role_id,role_name,role_description')
                            ->where('phone', $login_phone)
                            ->where('is_active', 1)
                            ->first();

                        if (!$user) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Either your username or password is wrong'
                            ], 404);
                        }

                        //DB::beginTransaction();

                        $token = md5($now . rand(10000000, 99999999));
                        $expiry = date("Y-m-d H:i:s", strtotime('+4 hours', strtotime($now)));

                        Token::updateOrCreate(['t_user_id' => $user->ev_id], ['t_token' => $token, 't_generated_on' => $now, 't_expired_on' => $expiry, ]);

                        // Determine inst type
                        $instType = ($user->ev_role_id == 5) ? 'NA' : 'Council';

                        // Fetch evaluator details
                        $evaluator = EvaluatorDetails::where('phone', $user->phone)
                            ->where('is_active', 1)
                            ->first();

                        //DB::commit();
                        return response()->json([
                            'role' => $request->user_type,
                            'error' => false,
                            'token' => $token,
                            'token_expired_on' => $expiry,

                            'user' => [
                                'user_id' => $evaluator->id,
                                'user_ref' => $evaluator->ev_id,
                                'user_name' => $evaluator->phone,
                                'inst_code' => $evaluator->institute ?? '',
                                'inst_name' => $evaluator->institute ?? '',
                                'full_name' => $evaluator->name,
                                'user_role' => $request->user_type,
                                'user_role_id' => $evaluator->ev_role_id,
                                'is_default_pwd' => 1,
                                'u_phone' => $evaluator->phone,
                                'u_email' => $evaluator->email,
                                'is_details_updated' => (bool)$user->is_details_updated,
                                'evaluator_roles' => $evaluator->ev_role_id == 5
                                ? $this->evaluatorRole($user)['roles']
                                : [],
                            ],

                            'inst_type' => $instType,
                        ], 200);

                    }
                    catch (Exception $e) {
                        //DB::rollBack();
                        generateLaravelLog($e);

                        return response()->json([
                            'error' => true,
                            'message' => $e->getMessage()
                        ]);
                    }
                }



                if ($request->user_type === 'COUNCIL_ADMIN') {
                    $user = SuperUser::where([
                        'u_phone' => $login_phone,
                        'is_active' => 1
                    ])->with('role:role_id,role_name')->first();

                    if ($user) {
                        $token = md5($now . rand(10000000, 99999999));

                        $expiry = date("Y-m-d H:i:s", strtotime('+4 hours', strtotime($now)));

                        // DB::beginTransaction();

                        Token::updateOrCreate([
                            't_user_id' => $user->u_id,
                        ], [
                            't_token' => $token,
                            't_generated_on' => $now,
                            't_expired_on' => $expiry,
                        ]);

                        $user->update([
                            'updated_at' => Carbon::parse(now())->format('Y-m-d H:i:s.u'),
                        ]);

                        DB::commit();

                        return response()->json([
                            'role' => $request->user_type,
                            'error' => false,
                            'token' => $token,
                            'token_expired_on' => $expiry,
                            'user' => new SuperUserResource($user),
                            'inst_type' => 'Council',
                            //'statusMap'=>checkStatus()
                        ], 200);
                    }
                    else {
                        DB::rollBack();
                        return response()->json([
                            'error' => true,
                            'message' => 'Either your username or password is wrong'
                        ], 404);
                    }
                }
            }
            catch (Exception $e) {
                DB::rollBack();
                generateLaravelLog($e);

                return response()->json([
                    'error' => true,
                    'code' => 'INT_00001',
                    'message' => $e->getMessage()
                ]);
            }
        }
        else {

            return response()->json([
                'error' => true,
                'message' => 'Either Phone number and/or security code does not match'
            ], 400);
        }
    }

    public function reset_password(Request $request)
    {
        $user_name = $request->user_name;
        $email_id = $request->email_id;

        $user_details = SuperUser::where([
            'u_username' => $user_name,
            'u_email' => $email_id,
            'is_active' => 1
        ])->first();

        if (!$user_details) {
            return response()->json([
                'error' => true,

                'message' => "Email or User Name does not match"

            ]);
        }

        $pass = generateRandomCode(4);
        $converted_pw = hash("sha512", $pass);

        $id = $user_details->u_id;
        $email = $user_details->u_email;
        $name = $user_details->u_inst_name;
        $user_details->where('u_id', $id)->update(
        ['u_password' => $converted_pw]
        );
        Mail::to($email)->send(new ResetpasswordMail($pass, $name));

        return response()->json([
            'error' => false,

            'message' => "Password has send to your registerd email id."

        ]);


    }
}
