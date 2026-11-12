<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSaveCDCController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-cdc",
     *     tags={"Admin - CDC"},
     *     summary="Save or Update CDC Details",
     *     description="Creates or updates a CDC record in public.tbl_cdc_mstr by calling public.fn_admin_savecdc(p_cdc_id, p_cdc_code, p_activestatus, p_entry_user_id)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cdc_code", "activestatus", "entry_user_id"},
     *             @OA\Property(property="cdc_id", type="integer", example=0, description="CDC ID: 0 for new record or existing ID for update"),
     *             @OA\Property(property="cdc_code", type="string", example="JCG", description="CDC Code (Institute code)"),
     *             @OA\Property(property="activestatus", type="integer", example=1, description="Status: 1 = Active, 0 = Inactive"),
     *             @OA\Property(property="entry_user_id", type="integer", example=12, description="Admin/Entry User ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CDC saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="CDC saved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or duplicate CDC code",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=2),
     *             @OA\Property(property="message", type="string", example="CDC code already exists."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Internal server error: ..."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     *
     * POST /api/admin/save-cdc
     * Calls: public.fn_admin_savecdc(
     *          p_cdc_id::bigint,
     *          p_cdc_code::varchar,
     *          p_activestatus::smallint,
     *          p_entry_user_id::bigint
     *        )
     */
    public function saveCDC(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE CDC API - REQUEST START ===');
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
            $authUserId = $decodedAuth['user_id'] ?? null;
        }

        // Normalize input keys
        $inputData = [
            'cdc_id'        => $request->input('cdc_id', $request->input('p_cdc_id', 0)),
            'cdc_code'      => $request->input('cdc_code', $request->input('p_cdc_code', $request->input('cdcCode'))),
            'activestatus'  => $request->input('activestatus', $request->input('active_status', $request->input('p_activestatus', 1))),
            'entry_user_id' => $request->input('entry_user_id', $request->input('p_entry_user_id', $request->input('admin_user_id', $request->input('user_id', $authUserId)))),
        ];

        $validator = Validator::make($inputData, [
            'cdc_id'        => 'nullable|integer',
            'cdc_code'      => 'required|string|max:100',
            'activestatus'  => 'required|integer|in:0,1',
            'entry_user_id' => 'required|integer',
        ], [
            'cdc_code.required'      => 'CDC code is required.',
            'activestatus.required'  => 'Active status is required.',
            'activestatus.in'        => 'Active status must be 1 (active) or 0 (inactive).',
            'entry_user_id.required' => 'Entry user ID is required.',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in saveCDC:', [
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

        $cdcId       = (int) $inputData['cdc_id'];
        $cdcCode     = strtoupper(trim((string) $inputData['cdc_code']));
        $activeStatus = (int) $inputData['activestatus'];
        $entryUserId = (int) $inputData['entry_user_id'];

        Log::channel('daily')->info('📤 Calling fn_admin_savecdc with parameters:', [
            'p_cdc_id'        => $cdcId,
            'p_cdc_code'      => $cdcCode,
            'p_activestatus'  => $activeStatus,
            'p_entry_user_id' => $entryUserId,
        ]);

        try {
            $sql = 'SELECT public.fn_admin_savecdc(?::bigint, ?::varchar, ?::smallint, ?::bigint) AS data';

            $result = DB::selectOne($sql, [
                $cdcId,
                $cdcCode,
                $activeStatus,
                $entryUserId,
            ]);

            if (!$result || !isset($result->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_admin_savecdc');
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
                Log::channel('daily')->error('❌ JSON parsing error in fn_admin_savecdc:', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to parse database response.',
                    'data'    => [],
                ], 500);
            }

            $errorCode = isset($data['p_errorcode']) ? (int) $data['p_errorcode'] : -1;

            Log::channel('daily')->info('📥 fn_admin_savecdc response:', [
                'p_errorcode' => $errorCode,
                'parsed'      => $data,
            ]);

            // Handling response error codes from database
            if ($errorCode === 0) {
                $message = $data['p_errormessage'] ?? ($cdcId > 0 ? 'CDC updated successfully.' : 'CDC saved successfully.');

                $response = [
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => $message,
                    'data'    => [
                        'p_errorcode' => 0,
                    ],
                ];

                Log::channel('daily')->info('✅ SAVE CDC SUCCESS:', $response);
                return response()->json($response, 200);
            }

            if ($errorCode === 2) {
                $response = [
                    'version' => '1.0',
                    'status'  => 2,
                    'message' => 'CDC already exists.',
                    'data'    => [
                        'p_errorcode' => 2,
                    ],
                ];

                Log::channel('daily')->warning('⚠️ SAVE CDC DUPLICATE (p_errorcode = 2):', $response);
                return response()->json($response, 400);
            }

            // General error code 1 or other failure
            $errorMsg = $data['p_errormessage'] ?? $data['message'] ?? 'An error occurred while saving CDC in the database.';
            $response = [
                'version' => '1.0',
                'status'  => 1,
                'message' => $errorMsg,
                'data'    => [
                    'p_errorcode' => $errorCode,
                ],
            ];

            Log::channel('daily')->warning('⚠️ SAVE CDC FAILED:', $response);
            return response()->json($response, 400);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in saveCDC:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => [
                    'p_errorcode' => 1,
                ],
            ], 500);
        }
    }
}

