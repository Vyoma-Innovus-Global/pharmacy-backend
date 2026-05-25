<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Token;
use App\Mail\OtpMail;

class GenerateOtpController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | OTP Delivery Rules by user_type_id
    |--------------------------------------------------------------------------
    | Type  9, 10, 11  → SMS only  (contact_no = phone)
    | Type  8          → Email only (contact_no = email)
    | Type  12         → SMS + Email
    |--------------------------------------------------------------------------
    */

    /**
     * @OA\Post(
     *     path="/api/generate-otp/send",
     *     tags={"Authentication"},
     *     summary="Generate and send OTP",
     *     description="Generates OTP for admin users. Calls fn_generateotp to create OTP, then sends via SMS/Email based on user_type_id. Type 8→Email, Types 9/10/11→SMS, Type 12→Both. OTP expires in 2 minutes.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "user_type_id"},
     *             @OA\Property(property="username", type="string", example="AIE", description="Admin username"),
     *             @OA\Property(property="user_type_id", type="integer", example=8, description="User type: 8=Email, 9/10/11=SMS, 12=Both")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="OTP Sent Successfully."),
     *             @OA\Property(property="otp_expire_time", type="string", example="2026-05-21 16:00:00", description="OTP valid for 2 minutes"),
     *             @OA\Property(
     *                 property="sent_via",
     *                 type="object",
     *                 @OA\Property(property="sms", type="boolean", example=false),
     *                 @OA\Property(property="email", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OTP generation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invalid username or user type")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Server error")
     * )
     *
     * POST /api/generate-otp/send
     * Body: { "username": "AIE", "user_type_id": 8 }
     *
     * 1. Calls fn_generateotp → DB generates & stores OTP
     * 2. Fetches admin contact via fn_getadmindetailsbyusername
     * 3. Delivers OTP via SMS / Email / Both
     * 4. Stores OTP in Laravel Cache (NOT returned in response)
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'     => 'required|string|max:100',
            'user_type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()], 422);
        }

        $username   = trim($request->input('username'));
        $userTypeId = (int) $request->input('user_type_id');

        Log::channel('daily')->info('[generate] INPUT', [
            'username'     => $username,
            'user_type_id' => $userTypeId,
            'ip'           => $request->ip(),
        ]);

        try {
            // Step 1: Call fn_generateotp
            $result  = DB::select('SELECT public.fn_generateotp(?::varchar, ?::integer) AS data', [$username, $userTypeId]);
            $raw     = $result[0]->data ?? null;
            $otpData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            Log::channel('daily')->info('[generate] fn_generateotp', ['raw' => $raw, 'parsed' => $otpData]);

            if (empty($result) || json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => true, 'message' => 'OTP generation failed.'], 500);
            }

            $errorCode = (int)($otpData['p_errorcode'] ?? -1);
            if ($errorCode !== 0) {
                $errorMessages = [
                    200 => 'Invalid username or user type. Please check your credentials and try again.',
                    1   => 'OTP generation failed due to a server error. Please try again.',
                ];
                $message = $errorMessages[$errorCode] ?? ($otpData['p_message'] ?? 'OTP generation failed. Please try again.');
                return response()->json(['error' => true, 'message' => $message], 400);
            }

            $otp = (string)$otpData['p_otp'];

            // Step 2: Store OTP in Laravel Cache (2 min) — NOT in response
            $cacheKey = "otp_{$username}_{$userTypeId}";
            Cache::put($cacheKey, $otp, now()->addMinutes(2));
            Log::channel('daily')->info('[generate] OTP cached', ['key' => $cacheKey]);

            // Step 3: Fetch admin contact details
            $adminResult = DB::select('SELECT public.fn_getadmindetailsbyusername(?::varchar, ?::integer) AS data', [$username, $userTypeId]);
            $adminRaw    = $adminResult[0]->data ?? null;
            $adminData   = is_string($adminRaw) ? json_decode($adminRaw, true) : (array) $adminRaw;

            $email = $adminData['email']     ?? null;
            $phone = $adminData['contactNo'] ?? null;
            $name  = $adminData['fullName']  ?? $username;

            // Step 4: Deliver OTP
            // TODO: Re-enable SMS/Email sending before production deployment
            $smsSent    = false;
            $emailSent  = false;
            $smsMessage = "{$otp} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";

            // if (in_array($userTypeId, [9, 10, 11]) && $phone) { send_sms($phone, $smsMessage); $smsSent = true; }
            if ($userTypeId === 8 && $email) {
                Log::channel('daily')->info('[generate] Email send attempt', ['email' => $email]);
                Mail::to($email)->send(new OtpMail($otp, $name));
                $emailSent = true;
                Log::channel('daily')->info('[generate] Email sent', ['email' => $email]);
            }
            if ($userTypeId === 12) {
            //     if ($phone) { send_sms($phone, $smsMessage); $smsSent = true; }
                if ($email) {
                    Log::channel('daily')->info('[generate] Email send attempt (type 12)', ['email' => $email]);
                    Mail::to($email)->send(new OtpMail($otp, $name));
                    $emailSent = true;
                    Log::channel('daily')->info('[generate] Email sent (type 12)', ['email' => $email]);
                }
            }

            Log::channel('daily')->info('[generate] OUTPUT (200)', ['sms' => $smsSent, 'email' => $emailSent]);

            return response()->json([
                'error'           => false,
                'message'         => 'OTP Sent Successfully.',
                'otp_expire_time' => now()->addMinutes(2)->format('Y-m-d H:i:s'),
                'sent_via'        => ['sms' => $smsSent, 'email' => $emailSent],
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[generate] EXCEPTION', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/generate-otp/verify",
     *     tags={"Authentication"},
     *     summary="Verify OTP and get authentication token",
     *     description="Validates OTP and generates authentication token. Calls fn_getlatestotpbyusername for validation, generates MD5 token (valid 4 hours), marks OTP as used via fn_updateuserotpbycontactno, and returns user details with token.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "user_type_id", "otp"},
     *             @OA\Property(property="username", type="string", example="AIE", description="Admin username"),
     *             @OA\Property(property="user_type_id", type="integer", example=8, description="User type ID"),
     *             @OA\Property(property="otp", type="string", example="1234", description="4-digit OTP received via SMS/Email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified successfully and token generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="OTP Used Successfully."),
     *             @OA\Property(property="token", type="string", example="a1b2c3d4e5f6g7h8i9j0", description="Authentication token - valid for 4 hours"),
     *             @OA\Property(property="token_expired_on", type="string", example="2026-05-21 20:00:00", description="Token expiry timestamp"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="adminUserId", type="integer", example=668),
     *                 @OA\Property(property="fullName", type="string", example="Admin Name"),
     *                 @OA\Property(property="email", type="string", example="admin@example.com"),
     *                 @OA\Property(property="contactNo", type="string", example="9876543210"),
     *                 @OA\Property(property="instCode", type="string", example="JCG"),
     *                 @OA\Property(property="instName", type="string", example="JNAN CHANDRA GHOSH POLYTECHNIC")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Incorrect OTP or could not resolve contact",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Incorrect OTP. Please try again.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No OTP found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="No OTP found. Please request a new OTP.")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Server error")
     * )
     *
     * POST /api/generate-otp/verify
     * Body: { "username": "AIE", "user_type_id": 8, "otp": "7777" }
     *
     * 1. Calls fn_getlatestotpbyusername → get latest OTP from DB
     * 2. Also checks Laravel Cache (double validation)
     * 3. If matched:
     *    a. Generate md5 token → save to pharmacy_tokens
     *    b. Call fn_updateuserotpbycontactno(contact_no, type, '') → mark OTP used
     *    c. Call fn_getadmindetailsbyusername → return user details + token
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'     => 'required|string|max:100',
            'user_type_id' => 'required|integer',
            'otp'          => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()], 422);
        }

        $username   = trim($request->input('username'));
        $userTypeId = (int) $request->input('user_type_id');
        $inputOtp   = trim($request->input('otp'));

        Log::channel('daily')->info('[verifyOtp] INPUT', [
            'username'     => $username,
            'user_type_id' => $userTypeId,
            'otp'          => $inputOtp,
            'ip'           => $request->ip(),
        ]);

        try {
            // Step 1: Fetch admin details FIRST — fn_generateotp stores OTP keyed by contact_no (phone/email), NOT username
            $adminResult = DB::select('SELECT public.fn_getadmindetailsbyusername(?::varchar, ?::integer) AS data', [$username, $userTypeId]);
            $adminRaw    = $adminResult[0]->data ?? null;
            $adminData   = is_string($adminRaw) ? json_decode($adminRaw, true) : (array) $adminRaw;

            $adminUserId = $adminData['adminUserId'] ?? null;

            // Resolve contact_no: phone for types 9/10/11, email for types 8/13, both for 12
            if (in_array($userTypeId, [9, 10, 11])) {
                $contactNo = $adminData['contactNo'] ?? null;
            } elseif (in_array($userTypeId, [8, 13])) {
                $contactNo = $adminData['email'] ?? null;
            } elseif ($userTypeId === 12) {
                $contactNo = $adminData['contactNo'] ?? ($adminData['email'] ?? null);
            } else {
                $contactNo = $adminData['contactNo'] ?? ($adminData['email'] ?? $username);
            }

            Log::channel('daily')->info('[verifyOtp] Admin data fetched', [
                'adminUserId' => $adminUserId,
                'contactNo'   => $contactNo,
                'user_type_id'=> $userTypeId,
            ]);

            if (!$contactNo) {
                return response()->json(['error' => true, 'message' => 'Could not resolve contact for this user.'], 400);
            }

            // Step 2: Fetch latest OTP from DB using contact_no (fn_generateotp stores uo_username = contact_no)
            $result    = DB::select('SELECT public.fn_getlatestotpbyusername(?::varchar, ?::integer) AS data', [$contactNo, $userTypeId]);
            $raw       = $result[0]->data ?? null;
            $otpData   = is_string($raw) ? json_decode($raw, true) : (array) $raw;
            $storedOtp = (string)($otpData['otp'] ?? '');

            Log::channel('daily')->info('[verifyOtp] fn_getlatestotpbyusername', [
                'contact_no' => $contactNo,
                'raw'        => $raw,
                'stored_otp' => $storedOtp,
            ]);

            if ($storedOtp === '' || $storedOtp === 'null') {
                return response()->json(['error' => true, 'message' => 'No OTP found. Please request a new OTP.'], 404);
            }

            // Step 3: Cache cross-check (secondary)
            $cacheKey  = "otp_{$username}_{$userTypeId}";
            $cachedOtp = (string)(Cache::get($cacheKey) ?? '');
            Log::channel('daily')->info('[verifyOtp] OTP check', [
                'db'    => $storedOtp,
                'cache' => $cachedOtp,
                'input' => $inputOtp,
            ]);

            if ($storedOtp !== $inputOtp) {
                Log::channel('daily')->warning('[verifyOtp] OTP mismatch');
                return response()->json(['error' => true, 'message' => 'Incorrect OTP. Please try again.'], 400);
            }

            // Step 3b: Generate token & save to pharmacy_tokens
            $now    = now()->format('Y-m-d H:i:s');
            $token  = md5($now . rand(10000000, 99999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+4 hours', strtotime($now)));

            Token::updateOrCreate(
                ['t_user_id' => $adminUserId],
                ['t_token' => $token, 't_generated_on' => $now, 't_expired_on' => $expiry]
            );
            Log::channel('daily')->info('[verifyOtp] Token saved', ['user_id' => $adminUserId, 'expiry' => $expiry]);

            // Step 3c: Mark OTP used in DB (p_encotp = '')
            $updateResult = DB::select(
                'SELECT public.fn_updateuserotpbycontactno(?::varchar, ?::integer, ?::text) AS data',
                [$contactNo, $userTypeId, '']
            );
            Log::channel('daily')->info('[verifyOtp] fn_updateuserotpbycontactno', ['result' => $updateResult]);

            // Step 3d: Clear cache
            Cache::forget($cacheKey);

            // Strip password before returning
            unset($adminData['adminUserPassword']);

            $response = [
                'error'            => false,
                'message'          => 'OTP Used Successfully.',
                'token'            => $token,
                'token_expired_on' => $expiry,
                'user'             => $adminData,
            ];

            Log::channel('daily')->info('[verifyOtp] OUTPUT (200)', ['token' => $token, 'user_id' => $adminUserId]);
            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[verifyOtp] EXCEPTION', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/generate-otp/update-otp-used
     * Body: { "contact_no": "7980544903", "user_type_id": 9 }
     */
    public function updateOtpUsed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_no'   => 'required|string|max:100',
            'user_type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()], 422);
        }

        $contactNo  = trim($request->input('contact_no'));
        $userTypeId = (int) $request->input('user_type_id');

        Log::channel('daily')->info('[updateOtpUsed] INPUT', ['contact_no' => $contactNo, 'user_type_id' => $userTypeId]);

        try {
            $otp = (string) rand(1000, 9999);

            $updateResult = DB::select(
                'SELECT public.fn_updateuserotpbycontactno(?::varchar, ?::integer, ?::text) AS data',
                [$contactNo, $userTypeId, $otp]
            );

            $updateRaw  = $updateResult[0]->data ?? null;
            $updateData = is_string($updateRaw) ? json_decode($updateRaw, true) : (array) $updateRaw;

            Log::channel('daily')->info('[updateOtpUsed] fn_updateuserotpbycontactno', ['raw' => $updateRaw, 'parsed' => $updateData]);

            if ((int)($updateData['p_errorcode'] ?? -1) !== 0) {
                return response()->json(['error' => true, 'p_errorcode' => $updateData['p_errorcode'], 'message' => $updateData['p_message'] ?? 'DB error.'], 400);
            }

            $smsSent    = false;
            $emailSent  = false;
            $smsMessage = "{$otp} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";

            if (in_array($userTypeId, [9, 10, 11])) { send_sms($contactNo, $smsMessage); $smsSent = true; }
            if ($userTypeId === 8)                   { Mail::to($contactNo)->send(new OtpMail($otp, $contactNo)); $emailSent = true; }
            if ($userTypeId === 12) {
                send_sms($contactNo, $smsMessage); $smsSent = true;
                Mail::to($contactNo)->send(new OtpMail($otp, $contactNo)); $emailSent = true;
            }

            $response = [
                'error'           => false,
                'message'         => 'OTP Sent Successfully.',
                'otp_expire_time' => now()->addSeconds(120)->format('Y-m-d H:i:s'),
                'sent_via'        => ['sms' => $smsSent, 'email' => $emailSent],
                'p_otp'           => Config::get('app.env') !== 'production' ? $otp : null,
            ];

            Log::channel('daily')->info('[updateOtpUsed] OUTPUT (200)', $response);
            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[updateOtpUsed] EXCEPTION', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }
}
