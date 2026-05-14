<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Mail\OtpMail;

class GenerateOtpController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | OTP Delivery Rules by user_type_id
    |--------------------------------------------------------------------------
    | Type  9, 10, 11  → SMS only
    | Type  8          → Email only
    | Type  12         → SMS + Email
    |--------------------------------------------------------------------------
    */

    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'     => 'required|string|max:100',
            'user_type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        $username   = trim($request->input('username'));
        $userTypeId = (int) $request->input('user_type_id');

        try {
            // Call the PostgreSQL function — it handles OTP generation & storage internally
            $result = DB::select(
                'SELECT public.fn_generateotp(?::varchar, ?::integer) AS data',
                [$username, $userTypeId]
            );

            if (empty($result)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'OTP generation failed. No response from database.',
                ], 500);
            }

            $raw     = $result[0]->data ?? null;
            $otpData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE || empty($otpData)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Failed to parse OTP response from database.',
                ], 500);
            }

            if ((int)($otpData['p_errorcode'] ?? -1) !== 0) {
                return response()->json([
                    'error'       => true,
                    'p_errorcode' => $otpData['p_errorcode'] ?? -1,
                    'message'     => $otpData['p_message'] ?? 'OTP generation failed at DB level.',
                ], 400);
            }

            $otp = $otpData['p_otp'];

            // Fetch admin contact details to determine delivery channel
            $adminResult = DB::select(
                'SELECT public.fn_getadmindetailsbyusername(?::varchar, ?::integer) AS data',
                [$username, $userTypeId]
            );

            $adminRaw  = $adminResult[0]->data ?? null;
            $adminData = is_string($adminRaw) ? json_decode($adminRaw, true) : (array) $adminRaw;

            $email = $adminData['email']     ?? null;
            $phone = $adminData['contactNo'] ?? null;
            $name  = $adminData['fullName']  ?? $username;

            // Deliver OTP based on user_type_id
            $smsSent    = false;
            $emailSent  = false;
            $smsMessage = "{$otp} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";

            // SMS only: types 9, 10, 11
            if (in_array($userTypeId, [9, 10, 11])) {
                if ($phone) {
                    send_sms($phone, $smsMessage);
                    $smsSent = true;
                }
            }

            // Email only: type 8
            if ($userTypeId === 8) {
                if ($email) {
                    Mail::to($email)->send(new OtpMail($otp, $name));
                    $emailSent = true;
                }
            }

            // SMS + Email: type 12
            if ($userTypeId === 12) {
                if ($phone) {
                    send_sms($phone, $smsMessage);
                    $smsSent = true;
                }
                if ($email) {
                    Mail::to($email)->send(new OtpMail($otp, $name));
                    $emailSent = true;
                }
            }

            $otpExpiry = now()->addSeconds(120)->format('Y-m-d H:i:s');

            return response()->json([
                'error'           => false,
                'message'         => 'OTP generated and sent successfully.',
                'otp_expire_time' => $otpExpiry,
                'sent_via'        => [
                    'sms'   => $smsSent,
                    'email' => $emailSent,
                ],
                // Only expose OTP in non-production for debugging
                'p_otp' => Config::get('app.env') !== 'production' ? $otp : null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify an OTP for a given username.
     * Fetches the latest OTP via fn_getlatestotpbyusername and compares.
     *
     * POST /generate-otp/verify
     * Body: { "username": "AIE", "user_type_id": 8, "otp": "7777" }
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'     => 'required|string|max:100',
            'user_type_id' => 'required|integer',
            'otp'          => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        $username   = trim($request->input('username'));
        $userTypeId = (int) $request->input('user_type_id');
        $inputOtp   = trim($request->input('otp'));

        // ── LOG INPUT ────────────────────────────────────────────────────────
        Log::channel('daily')->info('[verifyOtp] INPUT', [
            'username'     => $username,
            'user_type_id' => $userTypeId,
            'otp'          => $inputOtp,
            'ip'           => $request->ip(),
            'timestamp'    => now()->toDateTimeString(),
        ]);

        try {
            // Fetch the latest stored OTP via DB function
            Log::channel('daily')->info('[verifyOtp] SP CALL', [
                'function' => 'fn_getlatestotpbyusername',
                'args'     => ['p_username' => $username, 'p_usertype' => $userTypeId],
            ]);

            $result = DB::select(
                'SELECT public.fn_getlatestotpbyusername(?::varchar, ?::integer) AS data',
                [$username, $userTypeId]
            );

            Log::channel('daily')->info('[verifyOtp] SP RAW RESPONSE', [
                'result' => $result,
            ]);

            if (empty($result)) {
                $response = ['error' => true, 'message' => 'No OTP found. Please request a new OTP.'];
                Log::channel('daily')->warning('[verifyOtp] OUTPUT (404 - empty result)', $response);
                return response()->json($response, 404);
            }

            $raw     = $result[0]->data ?? null;
            $otpData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            Log::channel('daily')->info('[verifyOtp] PARSED SP DATA', [
                'raw'    => $raw,
                'parsed' => $otpData,
            ]);

            if (json_last_error() !== JSON_ERROR_NONE || empty($otpData)) {
                $response = ['error' => true, 'message' => 'No OTP found. Please request a new OTP.'];
                Log::channel('daily')->warning('[verifyOtp] OUTPUT (404 - parse failed)', $response);
                return response()->json($response, 404);
            }

            $storedOtp = (string)($otpData['otp'] ?? '');

            if ($storedOtp === '') {
                $response = ['error' => true, 'message' => 'No OTP found. Please request a new OTP.'];
                Log::channel('daily')->warning('[verifyOtp] OUTPUT (404 - empty otp in response)', $response);
                return response()->json($response, 404);
            }

            if ($storedOtp !== $inputOtp) {
                $response = ['error' => true, 'message' => 'Incorrect OTP. Please try again.'];
                Log::channel('daily')->warning('[verifyOtp] OUTPUT (400 - OTP mismatch)', [
                    'stored_otp' => $storedOtp,
                    'input_otp'  => $inputOtp,
                ]);
                return response()->json($response, 400);
            }

            $response = ['error' => false, 'message' => 'OTP Used Successfully.'];
            Log::channel('daily')->info('[verifyOtp] OUTPUT (200 - success)', $response);
            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[verifyOtp] EXCEPTION', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate & send OTP by contact number (phone or email).
     * Calls fn_updateuserotpbycontactno then fn_getlatestotpbyusername to fetch OTP for delivery.
     *
     * POST /generate-otp/send-by-contact
     * Body: { "contact_no": "7980544903", "user_type_id": 9 }
     *  - Types 9, 10, 11 → SMS to contact_no
     *  - Type  8         → Email to contact_no
     *  - Type  12        → SMS + Email
     */
    public function updateOtpUsed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_no'   => 'required|string|max:100',
            'user_type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        $contactNo  = trim($request->input('contact_no'));
        $userTypeId = (int) $request->input('user_type_id');

        Log::channel('daily')->info('[updateOtpUsed] INPUT', [
            'contact_no'   => $contactNo,
            'user_type_id' => $userTypeId,
            'ip'           => $request->ip(),
            'timestamp'    => now()->toDateTimeString(),
        ]);

        try {
            // Generate 4-digit OTP in PHP
            $otp = (string) rand(1000, 9999);

            // Call fn_updateuserotpbycontactno — DB stores OTP, p_encotp passed from PHP
            Log::channel('daily')->info('[updateOtpUsed] SP CALL', [
                'function' => 'fn_updateuserotpbycontactno',
                'args'     => ['p_contact_no' => $contactNo, 'p_user_type' => $userTypeId, 'p_encotp' => $otp],
            ]);

            $updateResult = DB::select(
                'SELECT public.fn_updateuserotpbycontactno(?::varchar, ?::integer, ?::text) AS data',
                [$contactNo, $userTypeId, $otp]
            );

            Log::channel('daily')->info('[updateOtpUsed] SP RAW RESPONSE', [
                'result' => $updateResult,
            ]);

            $updateRaw  = $updateResult[0]->data ?? null;
            $updateData = is_string($updateRaw) ? json_decode($updateRaw, true) : (array) $updateRaw;

            Log::channel('daily')->info('[updateOtpUsed] PARSED RESPONSE', [
                'raw'    => $updateRaw,
                'parsed' => $updateData,
            ]);

            if ((int)($updateData['p_errorcode'] ?? -1) !== 0) {
                $response = [
                    'error'       => true,
                    'p_errorcode' => $updateData['p_errorcode'] ?? -1,
                    'message'     => $updateData['p_message'] ?? 'OTP update failed at DB level.',
                ];
                Log::channel('daily')->warning('[updateOtpUsed] OUTPUT (400 - DB error)', $response);
                return response()->json($response, 400);
            }

            // Deliver OTP based on user_type_id
            $smsSent    = false;
            $emailSent  = false;
            $smsMessage = "{$otp} is your One Time Password (OTP). Don't share this with anyone. - WBSCTE&VE&SD";

            // SMS only: types 9, 10, 11
            if (in_array($userTypeId, [9, 10, 11])) {
                send_sms($contactNo, $smsMessage);
                $smsSent = true;
                Log::channel('daily')->info('[updateOtpUsed] SMS sent', ['to' => $contactNo]);
            }

            // Email only: type 8
            if ($userTypeId === 8) {
                Mail::to($contactNo)->send(new OtpMail($otp, $contactNo));
                $emailSent = true;
                Log::channel('daily')->info('[updateOtpUsed] Email sent', ['to' => $contactNo]);
            }

            // SMS + Email: type 12
            if ($userTypeId === 12) {
                send_sms($contactNo, $smsMessage);
                $smsSent = true;
                Mail::to($contactNo)->send(new OtpMail($otp, $contactNo));
                $emailSent = true;
                Log::channel('daily')->info('[updateOtpUsed] SMS + Email sent', ['to' => $contactNo]);
            }

            $response = [
                'error'           => false,
                'message'         => 'OTP Sent Successfully.',
                'otp_expire_time' => now()->addSeconds(120)->format('Y-m-d H:i:s'),
                'sent_via'        => [
                    'sms'   => $smsSent,
                    'email' => $emailSent,
                ],
                'p_otp' => Config::get('app.env') !== 'production' ? $otp : null,
            ];

            Log::channel('daily')->info('[updateOtpUsed] OUTPUT (200 - success)', $response);
            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[updateOtpUsed] EXCEPTION', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
