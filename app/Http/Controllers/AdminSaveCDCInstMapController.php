<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSaveCDCInstMapController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-cdc-inst-map",
     *     tags={"Admin - CDC"},
     *     summary="Save or Update CDC Institute Mapping",
     *     description="Creates or updates a CDC Institute Mapping in public.tbl_cdc_institute_map by calling public.fn_save_cdc_inst_map(p_cdc_inst_id, p_cdc_code, p_inst_code, p_active_status, p_semster_id, p_examyear, p_entryuserid)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cdc_code", "inst_code", "active_status", "semester_id", "exam_year", "entry_user_id"},
     *             @OA\Property(property="cdc_inst_id", type="integer", example=0, description="CDC Institute Mapping ID: 0 for new record or existing ID for update"),
     *             @OA\Property(property="cdc_code", type="string", example="SRV", description="CDC Code"),
     *             @OA\Property(property="inst_code", type="string", example="BPPC", description="Institute / Center Code"),
     *             @OA\Property(property="active_status", type="integer", example=1, description="Status: 1 = Active, 0 = Inactive"),
     *             @OA\Property(property="semester_id", type="string", example="Part-II", description="Semester / Part ID (e.g. Part-I, Part-II)"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year"),
     *             @OA\Property(property="entry_user_id", type="integer", example=12, description="Admin/Entry User ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CDC institute mapping saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="CDC institute mapping saved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or duplicate mapping",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=2),
     *             @OA\Property(property="message", type="string", example="CDC institute mapping already exists."),
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
     * POST /api/admin/save-cdc-inst-map
     * Calls: public.fn_save_cdc_inst_map(
     *          p_cdc_inst_id::bigint,
     *          p_cdc_code::varchar,
     *          p_inst_code::varchar,
     *          p_active_status::integer,
     *          p_semster_id::varchar,
     *          p_examyear::varchar,
     *          p_entryuserid::bigint
     *        )
     */
    public function saveCDCInstMap(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE CDC INST MAP API - REQUEST START ===');
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
            'cdc_inst_id'   => $request->input('cdc_inst_id', $request->input('p_cdc_inst_id', 0)),
            'cdc_code'      => $request->input('cdc_code', $request->input('p_cdc_code', $request->input('cdcCode'))),
            'inst_code'     => $request->input('inst_code', $request->input('p_inst_code', $request->input('instCode'))),
            'active_status' => $request->input('active_status', $request->input('p_active_status', $request->input('activestatus', 1))),
            'semester_id'   => $request->input('semester_id', $request->input('p_semster_id', $request->input('p_semester_id', $request->input('semesterId')))),
            'exam_year'     => $request->input('exam_year', $request->input('p_examyear', $request->input('p_exam_year', $request->input('examYear')))),
            'entry_user_id' => $request->input('entry_user_id', $request->input('p_entryuserid', $request->input('p_entry_user_id', $request->input('admin_user_id', $request->input('user_id', $authUserId))))),
        ];

        $validator = Validator::make($inputData, [
            'cdc_inst_id'   => 'nullable|integer',
            'cdc_code'      => 'required|string|max:100',
            'inst_code'     => 'required|string|max:100',
            'active_status' => 'required|integer|in:0,1',
            'semester_id'   => 'required|string|max:50',
            'exam_year'     => 'required|string|max:20',
            'entry_user_id' => 'required|integer',
        ], [
            'cdc_code.required'      => 'CDC code is required.',
            'inst_code.required'     => 'Institute code is required.',
            'active_status.required' => 'Active status is required.',
            'active_status.in'       => 'Active status must be 1 (active) or 0 (inactive).',
            'semester_id.required'   => 'Semester ID is required.',
            'exam_year.required'     => 'Exam year is required.',
            'entry_user_id.required' => 'Entry user ID is required.',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in saveCDCInstMap:', [
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

        $cdcInstId    = (int) $inputData['cdc_inst_id'];
        $cdcCode      = strtoupper(trim((string) $inputData['cdc_code']));
        $instCode     = strtoupper(trim((string) $inputData['inst_code']));
        $activeStatus = (int) $inputData['active_status'];

        // Normalize semester_id
        $rawSem = trim((string) $inputData['semester_id']);
        if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
            $semesterId = 'Part-I';
        } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
            $semesterId = 'Part-II';
        } else {
            $semesterId = $rawSem;
        }

        $examYear    = trim((string) $inputData['exam_year']);
        $entryUserId = (int) $inputData['entry_user_id'];

        Log::channel('daily')->info('📤 Calling fn_save_cdc_inst_map with parameters:', [
            'p_cdc_inst_id'   => $cdcInstId,
            'p_cdc_code'      => $cdcCode,
            'p_inst_code'     => $instCode,
            'p_active_status' => $activeStatus,
            'p_semster_id'    => $semesterId,
            'p_examyear'      => $examYear,
            'p_entryuserid'   => $entryUserId,
        ]);

        try {
            $sql = 'SELECT public.fn_save_cdc_inst_map(?::bigint, ?::varchar, ?::varchar, ?::integer, ?::varchar, ?::varchar, ?::bigint) AS data';

            $result = DB::selectOne($sql, [
                $cdcInstId,
                $cdcCode,
                $instCode,
                $activeStatus,
                $semesterId,
                $examYear,
                $entryUserId,
            ]);

            if (!$result || !isset($result->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_save_cdc_inst_map');
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
                Log::channel('daily')->error('❌ JSON parsing error in fn_save_cdc_inst_map:', [
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

            Log::channel('daily')->info('📥 fn_save_cdc_inst_map response:', [
                'p_errorcode' => $errorCode,
                'parsed'      => $data,
            ]);

            // Handling response error codes
            if ($errorCode === 0) {
                $message = $cdcInstId > 0 ? 'CDC institute mapping updated successfully.' : 'CDC institute mapping saved successfully.';

                $response = [
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => $message,
                    'data'    => [
                        'p_errorcode' => 0,
                    ],
                ];

                Log::channel('daily')->info('✅ SAVE CDC INST MAP SUCCESS:', $response);
                return response()->json($response, 200);
            }

            if ($errorCode === 2) {
                $response = [
                    'version' => '1.0',
                    'status'  => 2,
                    'message' => 'CDC institute mapping already exists for this institute, semester and exam year.',
                    'data'    => [
                        'p_errorcode' => 2,
                    ],
                ];

                Log::channel('daily')->warning('⚠️ SAVE CDC INST MAP DUPLICATE (p_errorcode = 2):', $response);
                return response()->json($response, 400);
            }

            // General error code 1 or other failure
            $errorMsg = $data['p_msg'] ?? $data['message'] ?? 'An error occurred while saving CDC institute mapping in the database.';
            $response = [
                'version' => '1.0',
                'status'  => 1,
                'message' => $errorMsg,
                'data'    => [
                    'p_errorcode' => $errorCode,
                ],
            ];

            Log::channel('daily')->warning('⚠️ SAVE CDC INST MAP FAILED:', $response);
            return response()->json($response, 400);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in saveCDCInstMap:', [
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

    /**
     * @OA\Get(
     *     path="/api/admin/get-cdc-details",
     *     tags={"Admin - CDC"},
     *     summary="Get Active CDC Details List",
     *     description="Fetches active CDC records by calling public.fn_get_cdc_details()",
     *     security={{"token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="CDC details fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="CDC details fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="CdcInstCode", type="string", example="JCG"),
     *                     @OA\Property(property="CdcInstName", type="string", example="JNAN CHANDRA GHOSH POLYTECHNIC")
     *                 )
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
     * GET/POST /api/admin/get-cdc-details
     * Calls: public.fn_get_cdc_details()
     */
    public function getCDCDetails(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET CDC DETAILS API - REQUEST START ===');

        try {
            $sql = 'SELECT public.fn_get_cdc_details() AS data';

            $result = DB::selectOne($sql);

            if (!$result || !isset($result->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_get_cdc_details');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No CDC details found.',
                    'data'    => [],
                ], 200);
            }

            $raw  = $result->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_get_cdc_details:', [
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

            Log::channel('daily')->info('✅ GET CDC DETAILS SUCCESS, count: ' . (is_array($data) ? count($data) : 0));

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'CDC details fetched successfully',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getCDCDetails:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/get-cec-details-by-cdc",
     *     tags={"Admin - CDC"},
     *     summary="Get CEC Details Mapped to a CDC",
     *     description="Fetches mapped CEC (institute) details for a CDC by calling public.fn_get_cec_details_by_cdc(p_cdc_code, p_semester, p_examyear)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cdc_code", "semester_id", "exam_year"},
     *             @OA\Property(property="cdc_code", type="string", example="JCG", description="CDC Code"),
     *             @OA\Property(property="semester_id", type="string", example="Part-II", description="Semester (e.g. Part-I, Part-II)"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CEC details fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="CEC details fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="InstCode", type="string", example="BPPC"),
     *                     @OA\Property(property="InstName", type="string", example="BHAGAWAN PRASAD PHARMACY COLLEGE")
     *                 )
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
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Internal server error: ..."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     *
     * POST /api/admin/get-cec-details-by-cdc
     * Calls: public.fn_get_cec_details_by_cdc(p_cdc_code::varchar, p_semester::varchar, p_examyear::varchar)
     */
    public function getCECDetailsByCDC(Request $request)
    {
        Log::channel('daily')->info('🚀 === GET CEC DETAILS BY CDC API - REQUEST START ===', [
            'payload' => $request->all(),
            'url'     => $request->fullUrl(),
        ]);

        $inputData = [
            'cdc_code'    => $request->input('cdc_code', $request->input('p_cdc_code', $request->input('cdcCode'))),
            'semester_id' => $request->input('semester_id', $request->input('p_semester', $request->input('p_semester_id', $request->input('semesterId')))),
            'exam_year'   => $request->input('exam_year', $request->input('p_examyear', $request->input('p_exam_year', $request->input('examYear')))),
        ];

        $validator = Validator::make($inputData, [
            'cdc_code'    => 'required|string|max:100',
            'semester_id' => 'required|string|max:50',
            'exam_year'   => 'required|string|max:20',
        ], [
            'cdc_code.required'    => 'CDC code is required.',
            'semester_id.required' => 'Semester ID is required.',
            'exam_year.required'   => 'Exam year is required.',
        ]);

        if ($validator->fails()) {
            Log::channel('daily')->error('❌ VALIDATION FAILED in getCECDetailsByCDC:', [
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

        $cdcCode = strtoupper(trim((string) $inputData['cdc_code']));

        // Normalize semester_id
        $rawSem = trim((string) $inputData['semester_id']);
        if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
            $semesterId = 'Part-I';
        } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
            $semesterId = 'Part-II';
        } else {
            $semesterId = $rawSem;
        }

        $examYear = trim((string) $inputData['exam_year']);

        Log::channel('daily')->info('📤 Calling fn_get_cec_details_by_cdc with parameters:', [
            'p_cdc_code' => $cdcCode,
            'p_semester' => $semesterId,
            'p_examyear' => $examYear,
        ]);

        try {
            $sql = 'SELECT public.fn_get_cec_details_by_cdc(?::varchar, ?::varchar, ?::varchar) AS data';

            $result = DB::selectOne($sql, [
                $cdcCode,
                $semesterId,
                $examYear,
            ]);

            if (!$result || !isset($result->data)) {
                Log::channel('daily')->warning('⚠️ No result returned from fn_get_cec_details_by_cdc');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No CEC details found for this CDC.',
                    'data'    => [],
                ], 200);
            }

            $raw  = $result->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('❌ JSON parsing error in fn_get_cec_details_by_cdc:', [
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

            Log::channel('daily')->info('✅ GET CEC DETAILS BY CDC SUCCESS, count: ' . (is_array($data) ? count($data) : 0));

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'CEC details fetched successfully',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔥 EXCEPTION in getCECDetailsByCDC:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }
}

