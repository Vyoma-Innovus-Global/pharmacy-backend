<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminGetScheduleListController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/get-schedule-list",
     *     tags={"Admin - Schedule"},
     *     summary="Get Schedule List",
     *     description="Fetches schedule list by calling public.fn_get_schedule_list(p_adminuserId)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id"},
     *             @OA\Property(property="admin_user_id", type="integer", example=15, description="Admin User ID (or p_adminuserId / p_admin_user_id / adminUserId)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Schedule list fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Schedule list fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="scheduleName", type="string", example="APPLY REVIEW"),
     *                 @OA\Property(property="scheduleStartDate", type="string", example="2026-01-05T00:00:00"),
     *                 @OA\Property(property="scheduleEndDate", type="string", example="2026-07-11T23:59:59"),
     *                 @OA\Property(property="scheduleExtendedDate", type="string", example="2026-07-11T23:59:59")
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
     * POST /api/admin/get-schedule-list
     * Calls: public.fn_get_schedule_list(p_adminuserId::bigint)
     */
    public function getScheduleList(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET SCHEDULE LIST API - REQUEST START ===');
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

        // Normalize input keys (support camelCase, snake_case, and SP param names)
        $rawAdminUserId = $request->input('admin_user_id')
            ?? $request->input('p_adminuserId')
            ?? $request->input('p_admin_user_id')
            ?? $request->input('adminUserId')
            ?? $request->input('user_id')
            ?? $request->input('p_user_id')
            ?? $request->input('userId')
            ?? $authUserId;

        $inputData = [
            'admin_user_id' => $rawAdminUserId !== null ? $rawAdminUserId : null,
        ];

        $validator = Validator::make($inputData, [
            'admin_user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getScheduleList:', [
                'errors' => $validator->errors()->all(),
                'input'  => $request->all(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'data'    => [],
            ], 400);
        }

        $adminUserId = (int) $inputData['admin_user_id'];

        Log::channel('daily')->info('📤 Calling fn_get_schedule_list with parameters:', [
            'p_adminuserId' => $adminUserId,
        ]);

        try {
            $sql = 'SELECT public.fn_get_schedule_list(?::bigint) AS data';

            $result = DB::select($sql, [$adminUserId]);

            if (empty($result) || !isset($result[0]->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_get_schedule_list');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No schedule found.',
                    'data'    => null,
                ], 200);
            }

            $raw = $result[0]->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_get_schedule_list:', [
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

            // Check if DB function returned an error code object
            if (is_array($data) && isset($data['p_errorcode']) && (int) $data['p_errorcode'] !== 0) {
                $errorMsg = $data['p_errormsg'] ?? $data['message'] ?? 'Error fetching schedule list.';
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => $errorMsg,
                    'data'    => $data,
                ], 400);
            }

            Log::channel('daily')->info('✅ Schedule list fetched successfully:', [
                'data' => $data,
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Schedule list fetched successfully',
                'data'    => $data ?? [],
            ];

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getScheduleList:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }
}
