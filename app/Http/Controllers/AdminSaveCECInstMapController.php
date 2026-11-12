<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSaveCECInstMapController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-cec-inst-map",
     *     tags={"Admin - CEC"},
     *     summary="Save or Update CEC Institute Mapping",
     *     description="Creates or updates a CEC record in public.tbl_cec_inst_map by calling public.fn_admin_savecec_inst_map(p_cec_id, p_cec_code, p_cec_inst_code, p_exam_year, p_semester_id, p_activestatus, p_entry_user_id)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cec_code", "cec_inst_code", "exam_year", "semester_id", "activestatus", "entry_user_id"},
     *             @OA\Property(property="cec_id", type="integer", example=0, description="CEC Mapping ID: 0 for new record or existing ID for update"),
     *             @OA\Property(property="cec_code", type="string", example="JCG", description="CEC Code"),
     *             @OA\Property(property="cec_inst_code", type="string", example="COB", description="CEC Institute Code"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year"),
     *             @OA\Property(property="semester_id", type="string", example="Part-II", description="Semester / Part Name (e.g. Part-I, Part-II)"),
     *             @OA\Property(property="activestatus", type="integer", example=1, description="Status: 1 = Active, 0 = Inactive"),
     *             @OA\Property(property="entry_user_id", type="integer", example=12, description="Admin/Entry User ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CEC institute mapping saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="CEC institute mapping saved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error, duplicate record, or institute not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=2),
     *             @OA\Property(property="message", type="string", example="CEC institute mapping already exists."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=2)
     *             )
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
     * POST /api/admin/save-cec-inst-map
     * Calls: public.fn_admin_savecec_inst_map(
     *          p_cec_id::bigint,
     *          p_cec_code::varchar,
     *          p_cec_inst_code::varchar,
     *          p_exam_year::varchar,
     *          p_semester_id::varchar,
     *          p_activestatus::smallint,
     *          p_entry_user_id::bigint
     *        )
     */
    public function saveCECInstMap(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE CEC INST MAP API - REQUEST START ===');
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
            'cec_id'        => $request->input('cec_id', $request->input('p_cec_id', 0)),
            'cec_code'      => $request->input('cec_code', $request->input('p_cec_code', $request->input('cecCode'))),
            'cec_inst_code' => $request->input('cec_inst_code', $request->input('p_cec_inst_code', $request->input('cecInstCode', $request->input('inst_code')))),
            'exam_year'     => $request->input('exam_year', $request->input('p_exam_year', $request->input('p_examyear', $request->input('examYear')))),
            'semester_id'   => $request->input('semester_id', $request->input('p_semester_id', $request->input('p_semester', $request->input('semesterId')))),
            'activestatus'  => $request->input('activestatus', $request->input('active_status', $request->input('p_activestatus', 1))),
            'entry_user_id' => $request->input('entry_user_id', $request->input('p_entry_user_id', $request->input('p_entryuserid', $request->input('admin_user_id', $request->input('user_id', $authUserId))))),
        ];

        $validator = Validator::make($inputData, [
            'cec_id'        => 'nullable|integer',
            'cec_code'      => 'required|string|max:100',
            'cec_inst_code' => 'required|string|max:100',
            'exam_year'     => 'required|string|max:20',
            'semester_id'   => 'required|string|max:50',
            'activestatus'  => 'required|integer|in:0,1',
            'entry_user_id' => 'required|integer',
        ], [
            'cec_code.required'      => 'CEC code is required.',
            'cec_inst_code.required' => 'CEC institute code is required.',
            'exam_year.required'     => 'Exam year is required.',
            'semester_id.required'   => 'Semester ID is required.',
            'activestatus.required'  => 'Active status is required.',
            'activestatus.in'        => 'Active status must be 1 (active) or 0 (inactive).',
            'entry_user_id.required' => 'Entry user ID is required.',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in saveCECInstMap:', [
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

        $cecId       = (int) $inputData['cec_id'];
        $cecCode     = strtoupper(trim((string) $inputData['cec_code']));
        $cecInstCode = strtoupper(trim((string) $inputData['cec_inst_code']));
        $examYear    = trim((string) $inputData['exam_year']);

        // Normalize semester_id format
        $rawSem = trim((string) $inputData['semester_id']);
        if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
            $semesterId = 'Part-I';
        } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
            $semesterId = 'Part-II';
        } else {
            $semesterId = $rawSem;
        }

        $activeStatus = (int) $inputData['activestatus'];
        $entryUserId  = (int) $inputData['entry_user_id'];

        Log::channel('daily')->info('📤 Calling fn_admin_savecec_inst_map with parameters:', [
            'p_cec_id'        => $cecId,
            'p_cec_code'      => $cecCode,
            'p_cec_inst_code' => $cecInstCode,
            'p_exam_year'     => $examYear,
            'p_semester_id'   => $semesterId,
            'p_activestatus'  => $activeStatus,
            'p_entry_user_id' => $entryUserId,
        ]);

        try {
            $sql = 'SELECT public.fn_admin_savecec_inst_map(?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::smallint, ?::bigint) AS data';

            $result = DB::selectOne($sql, [
                $cecId,
                $cecCode,
                $cecInstCode,
                $examYear,
                $semesterId,
                $activeStatus,
                $entryUserId,
            ]);

            if (!$result || !isset($result->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_admin_savecec_inst_map');
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
                Log::channel('daily')->error('❌ JSON parsing error in fn_admin_savecec_inst_map:', [
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

            Log::channel('daily')->info('📥 fn_admin_savecec_inst_map response:', [
                'p_errorcode' => $errorCode,
                'parsed'      => $data,
            ]);

            // Handling response error codes
            if ($errorCode === 0) {
                $message = $cecId > 0 ? 'CEC institute mapping updated successfully.' : 'CEC institute mapping saved successfully.';

                $response = [
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => $message,
                    'data'    => [
                        'p_errorcode' => 0,
                    ],
                ];

                Log::channel('daily')->info('✅ SAVE CEC INST MAP SUCCESS:', $response);
                return response()->json($response, 200);
            }

            if ($errorCode === 2) {
                $response = [
                    'version' => '1.0',
                    'status'  => 2,
                    'message' => 'CEC institute mapping already exists for this institute.',
                    'data'    => [
                        'p_errorcode' => 2,
                    ],
                ];

                Log::channel('daily')->warning('⚠️ SAVE CEC INST MAP DUPLICATE (p_errorcode = 2):', $response);
                return response()->json($response, 400);
            }

            if ($errorCode === 3) {
                $response = [
                    'version' => '1.0',
                    'status'  => 3,
                    'message' => $data['p_errormessage'] ?? 'Institute code not found.',
                    'data'    => [
                        'p_errorcode' => 3,
                    ],
                ];

                Log::channel('daily')->warning('⚠️ SAVE CEC INST MAP NOT FOUND (p_errorcode = 3):', $response);
                return response()->json($response, 400);
            }

            // General error code 1 or other failure
            $errorMsg = $data['p_errormessage'] ?? $data['message'] ?? 'An error occurred while saving CEC institute mapping in the database.';
            $response = [
                'version' => '1.0',
                'status'  => 1,
                'message' => $errorMsg,
                'data'    => [
                    'p_errorcode' => $errorCode,
                ],
            ];

            Log::channel('daily')->warning('⚠️ SAVE CEC INST MAP FAILED:', $response);
            return response()->json($response, 400);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in saveCECInstMap:', [
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

