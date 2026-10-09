<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSaveScheduleDetailsController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-schedule-details",
     *     tags={"Admin - Schedule"},
     *     summary="Save or Update Schedule Details",
     *     description="Saves or updates schedule details in public.tbl_admin_schedule_master by calling public.fn_save_schedule_details(p_scheduleid, p_usertypeid, p_semesterid, p_scheduletype, p_startdate, p_enddate, p_extnddate, p_activestaus, p_adminuserid)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_type_id", "semester_id", "schedule_type", "start_date", "end_date"},
     *             @OA\Property(property="schedule_id", type="integer", example=0, description="Schedule ID (0 for new insert, or existing asm_id for update)"),
     *             @OA\Property(property="user_type_id", type="integer", example=9, description="Target User Type ID (e.g., 9 for Council, 3 for Institute)"),
     *             @OA\Property(property="semester_id", type="integer", example=1, description="Semester ID (1: Part-I, 2: Part-II, etc.)"),
     *             @OA\Property(property="schedule_type", type="string", example="MARKS_ENTRY_INTERNAL", description="Schedule Type Name / Code"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-05-14", description="Start date (YYYY-MM-DD)"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-06-14", description="End date (YYYY-MM-DD)"),
     *             @OA\Property(property="extnd_date", type="string", format="date", nullable=true, example="2026-06-20", description="Extended date (YYYY-MM-DD, optional)"),
     *             @OA\Property(property="active_status", type="integer", example=1, default=1, description="Active status (1: Active, 0: Inactive)"),
     *             @OA\Property(property="admin_user_id", type="integer", example=15, description="Admin User ID making this change")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Schedule details saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Schedule details saved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Validation failed: ..."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=3),
     *             @OA\Property(property="message", type="string", example="Internal server error: ..."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     *
     * POST /api/admin/save-schedule-details
     * Calls: public.fn_save_schedule_details(
     *          p_scheduleid::bigint,
     *          p_usertypeid::bigint,
     *          p_semesterid::bigint,
     *          p_scheduletype::varchar,
     *          p_startdate::date,
     *          p_enddate::date,
     *          p_extnddate::date,
     *          p_activestaus::smallint,
     *          p_adminuserid::bigint
     *        )
     */
    public function saveScheduleDetails(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE SCHEDULE DETAILS API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Extract authenticated user_id from middleware header if available as fallback
        $authUserId = null;
        $authUserData = $request->header('auth_user_data');
        if ($authUserData) {
            $decodedAuth = is_string($authUserData) ? json_decode($authUserData, true) : (array) $authUserData;
            $authUserId = $decodedAuth['user_id'] ?? $decodedAuth['id'] ?? null;
        }

        // Normalize parameter aliases (supports camelCase, snake_case, and SP param names)
        $rawScheduleId = $request->input('schedule_id')
            ?? $request->input('p_scheduleid')
            ?? $request->input('p_schedule_id')
            ?? $request->input('scheduleId')
            ?? $request->input('id')
            ?? 0;

        $rawUserTypeId = $request->input('user_type_id')
            ?? $request->input('p_usertypeid')
            ?? $request->input('p_user_type_id')
            ?? $request->input('userTypeId')
            ?? $request->input('usertype_id')
            ?? $request->input('usertypeid')
            ?? $request->input('admin_user_type_id');

        $rawSemesterId = $request->input('semester_id')
            ?? $request->input('p_semesterid')
            ?? $request->input('p_semester_id')
            ?? $request->input('semesterId')
            ?? $request->input('semester')
            ?? $request->input('p_semester');

        $rawScheduleType = $request->input('schedule_type')
            ?? $request->input('p_scheduletype')
            ?? $request->input('p_schedule_type')
            ?? $request->input('scheduleType')
            ?? $request->input('type');

        $rawStartDate = $request->input('start_date')
            ?? $request->input('p_startdate')
            ?? $request->input('p_start_date')
            ?? $request->input('startDate')
            ?? $request->input('schedule_start_date')
            ?? $request->input('scheduleStartDate');

        $rawEndDate = $request->input('end_date')
            ?? $request->input('p_enddate')
            ?? $request->input('p_end_date')
            ?? $request->input('endDate')
            ?? $request->input('schedule_end_date')
            ?? $request->input('scheduleEndDate');

        $rawExtndDate = $request->input('extnd_date')
            ?? $request->input('p_extnddate')
            ?? $request->input('p_extnd_date')
            ?? $request->input('extndDate')
            ?? $request->input('extended_date')
            ?? $request->input('p_extended_date')
            ?? $request->input('extendedDate')
            ?? $request->input('schedule_extended_date')
            ?? $request->input('scheduleExtendedDate');

        $rawActiveStatus = $request->input('active_status')
            ?? $request->input('activestaus')
            ?? $request->input('active_staus')
            ?? $request->input('p_activestaus')
            ?? $request->input('p_active_status')
            ?? $request->input('activeStatus')
            ?? $request->input('is_active')
            ?? $request->input('asm_isactive')
            ?? $request->input('status');

        $activeStatus = 1;
        if ($rawActiveStatus !== null && $rawActiveStatus !== '') {
            if ($rawActiveStatus === false || $rawActiveStatus === 'false' || $rawActiveStatus === 0 || $rawActiveStatus === '0') {
                $activeStatus = 0;
            } else {
                $activeStatus = (int) $rawActiveStatus;
            }
        }

        $rawAdminUserId = $request->input('admin_user_id')
            ?? $request->input('p_adminuserid')
            ?? $request->input('p_admin_user_id')
            ?? $request->input('adminUserId')
            ?? $request->input('user_id')
            ?? $request->input('p_user_id')
            ?? $request->input('userId')
            ?? $authUserId
            ?? 1;

        $inputData = [
            'schedule_id'   => $rawScheduleId !== null && $rawScheduleId !== '' ? (int) $rawScheduleId : 0,
            'user_type_id'  => $rawUserTypeId !== null && $rawUserTypeId !== '' ? (int) $rawUserTypeId : null,
            'semester_id'   => $rawSemesterId !== null && $rawSemesterId !== '' ? (int) $rawSemesterId : null,
            'schedule_type' => is_string($rawScheduleType) ? trim($rawScheduleType) : null,
            'start_date'    => $rawStartDate,
            'end_date'      => $rawEndDate,
            'extnd_date'    => $rawExtndDate,
            'active_status' => $activeStatus,
            'admin_user_id' => $rawAdminUserId !== null && $rawAdminUserId !== '' ? (int) $rawAdminUserId : null,
        ];

        $validator = Validator::make($inputData, [
            'schedule_id'   => 'nullable|integer|min:0',
            'user_type_id'  => 'required|integer',
            'semester_id'   => 'required|integer',
            'schedule_type' => 'required|string|max:100',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'extnd_date'    => 'nullable|date',
            'active_status' => 'nullable|integer|in:0,1',
            'admin_user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in saveScheduleDetails:', [
                'errors' => $validator->errors()->all(),
                'input'  => $request->all(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'data'    => $validator->errors(),
            ], 400);
        }

        $scheduleId   = (int) $inputData['schedule_id'];
        $userTypeId   = (int) $inputData['user_type_id'];
        $semesterId   = (int) $inputData['semester_id'];
        $scheduleType = (string) $inputData['schedule_type'];

        // Normalize dates to 'Y-m-d' format for PostgreSQL date type
        $startDate = Carbon::parse($inputData['start_date'])->format('Y-m-d');
        $endDate   = Carbon::parse($inputData['end_date'])->format('Y-m-d');
        $extndDate = !empty($inputData['extnd_date']) ? Carbon::parse($inputData['extnd_date'])->format('Y-m-d') : null;

        $adminUserId  = (int) $inputData['admin_user_id'];

        Log::channel('daily')->info('📤 Calling fn_save_schedule_details with parameters:', [
            'p_scheduleid'   => $scheduleId,
            'p_usertypeid'   => $userTypeId,
            'p_semesterid'   => $semesterId,
            'p_scheduletype' => $scheduleType,
            'p_startdate'    => $startDate,
            'p_enddate'      => $endDate,
            'p_extnddate'    => $extndDate,
            'p_activestaus'  => $activeStatus,
            'p_adminuserid'  => $adminUserId,
        ]);

        try {
            $sql = 'SELECT public.fn_save_schedule_details(?::bigint, ?::bigint, ?::bigint, ?::varchar, ?::date, ?::date, ?::date, ?::smallint, ?::bigint) AS data';

            $result = DB::selectOne($sql, [
                $scheduleId,
                $userTypeId,
                $semesterId,
                $scheduleType,
                $startDate,
                $endDate,
                $extndDate,
                $activeStatus,
                $adminUserId,
            ]);

            if (!$result || !isset($result->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_save_schedule_details');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to execute database function.',
                    'data'    => null,
                ], 500);
            }

            $raw  = $result->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_save_schedule_details:', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 3,
                    'message' => 'Failed to parse database response.',
                    'data'    => [],
                ], 500);
            }

            $errorCode = isset($data['p_errorcode']) ? (int) $data['p_errorcode'] : -1;

            Log::channel('daily')->info('📥 fn_save_schedule_details response:', [
                'p_errorcode' => $errorCode,
                'parsed'      => $data,
            ]);

            $messages = [
                0 => $scheduleId > 0 ? 'Schedule details updated successfully.' : 'Schedule details saved successfully.',
                1 => 'An error occurred while saving schedule details in the database.',
                2 => 'A schedule for this user type, semester, and schedule type already exists.',
                3 => 'Schedule record not found for update.',
            ];

            $message = $messages[$errorCode] ?? ($data['message'] ?? 'Schedule details operation completed.');

            if ($errorCode === 0) {
                $response = [
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => $message,
                    'data'    => [
                        'p_errorcode' => 0,
                        'schedule_id' => $scheduleId,
                    ],
                ];
                Log::channel('daily')->info('✅ SAVE SCHEDULE DETAILS SUCCESS:', $response);
                return response()->json($response, 200);
            }

            // Error responses (duplicate, not found, or exception)
            $response = [
                'version' => '1.0',
                'status'  => ($errorCode === 2 || $errorCode === 3) ? $errorCode : 1,
                'message' => $message,
                'data'    => [
                    'p_errorcode' => $errorCode,
                ],
            ];

            Log::channel('daily')->warning('⚠️ SAVE SCHEDULE DETAILS RETURNED ERROR CODE:', $response);

            // Return 200 with error details so frontend UI can render error message gracefully
            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in saveScheduleDetails:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => [
                    'p_errorcode' => 1,
                ],
            ], 500);
        }
    }
}
