<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminInstituteController extends Controller
{
    /**
     * POST /api/admin/institutes
     *
     * Get all institutes list.
     *
     * Body: {
     *   "admin_user_id": 668
     * }
     *
     * Calls: fn_admin_getallinstitutes_v1(p_admin_user_id)
     *
     * @OA\Post(
     *     path="/api/admin/institutes",
     *     tags={"Admin - Master Data"},
     *     summary="Get all institutes",
     *     description="Retrieve all institutes using fn_admin_getallinstitutes_v1",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id"},
     *             @OA\Property(property="admin_user_id", type="integer", example=668)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getAllInstitutes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $adminUserId = (int) $request->input('admin_user_id');

        Log::channel('daily')->info('[getAllInstitutes] INPUT', [
            'admin_user_id' => $adminUserId,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getallinstitutes_v1(?::bigint) AS data',
                [$adminUserId]
            );

            $raw = $result[0]->data ?? null;

            if (!$raw) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No data returned from database function.',
                    'data'    => [],
                ], 404);
            }

            // DB function returns JSON string
            $institutes = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[getAllInstitutes] JSON_DECODE_ERROR', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to parse database response.',
                    'data'    => null,
                ], 500);
            }

            if (empty($institutes)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No institutes found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getAllInstitutes] OUTPUT (200)', [
                'count' => count($institutes),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $institutes,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getAllInstitutes] EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    public function getInstituteList(Request $request)
    {
        Log::channel('daily')->info('[getInstituteList] INPUT', [
            'ip' => $request->ip(),
        ]);

        try {
            $result = DB::select('SELECT public.fn_get_institute_list() AS data');

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No institutes found.',
                    'data'    => [],
                ], 404);
            }

            $institutes = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[getInstituteList] JSON_DECODE_ERROR', [
                        'error' => json_last_error_msg(),
                        'raw'   => $raw,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 1,
                        'message' => 'Failed to parse database response.',
                        'data'    => null,
                    ], 500);
                }

                if (array_is_list($decoded)) {
                    $institutes = array_merge($institutes, $decoded);
                } else {
                    $institutes[] = $decoded;
                }
            }

            if (empty($institutes)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No institutes found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getInstituteList] OUTPUT (200)', [
                'count' => count($institutes),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'count'   => count($institutes),
                'data'    => $institutes,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getInstituteList] EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/ra-institute-list",
     *     tags={"Admin Institutes"},
     *     summary="Get list of RA (Re-Admission) institutes",
     *     description="Calls PostgreSQL stored function fn_get_ra_institute_list to retrieve all Re-Admission institutes.",
     *     security={{"token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Data fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Data fetch successfully"),
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="i_code", type="string", example="RAIP"),
     *                     @OA\Property(property="i_name", type="string", example="RISHI AUROBINDO INSTITUTE")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getRaInstituteList(Request $request)
    {
        Log::channel('daily')->info('[getRaInstituteList] INPUT', [
            'ip' => $request->ip(),
        ]);

        try {
            $result = DB::select('SELECT public.fn_get_ra_institute_list() AS data');

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No institutes found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $institutes = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[getRaInstituteList] JSON_DECODE_ERROR', [
                        'error' => json_last_error_msg(),
                        'raw'   => $raw,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 3,
                        'message' => 'Failed to parse database response.',
                        'data'    => null,
                    ], 500);
                }

                if (is_array($decoded) && array_is_list($decoded)) {
                    $institutes = array_merge($institutes, $decoded);
                } elseif (is_array($decoded)) {
                    $institutes[] = $decoded;
                }
            }

            Log::channel('daily')->info('[getRaInstituteList] OUTPUT (200)', [
                'count' => count($institutes),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'count'   => count($institutes),
                'data'    => $institutes,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getRaInstituteList] EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/all-examination-institutes",
     *     tags={"Admin - Master Data", "Examinations"},
     *     summary="Get all examination institutes by institute/center code",
     *     description="Calls PostgreSQL stored function fn_admin_getallexaminationinstitutes to retrieve all examination institutes for a given institute/center code and admin user ID.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"inst_code"},
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute / Center Code (p_inst_code)"),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (p_examyear)"),
     *             @OA\Property(property="semester", type="string", example="Part-II", description="Semester / Part (p_semester)"),
     *             @OA\Property(property="admin_user_id", type="integer", format="int64", example=5447, description="Admin User ID (p_admin_user_id, defaults to auth user)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Examination institutes fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Examination institutes fetched successfully"),
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="instituteId", type="integer", example=187),
     *                     @OA\Property(property="instituteCode", type="string", example="AMNA"),
     *                     @OA\Property(property="instituteName", type="string", example="AAMNA COLLEGE OF PHARMACEUTICAL SCIENCE & RESEARCH"),
     *                     @OA\Property(property="instituteType", type="string", example="Private")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getAllExaminationInstitutes(Request $request)
    {
        $instCode    = $request->input('inst_code', $request->input('p_inst_code', $request->input('institute_code', $request->input('instituteCode', $request->input('i_code', $request->input('center_code'))))));
        $examYear    = $request->input('exam_year', $request->input('p_examyear', $request->input('p_exam_year', $request->input('examYear', $request->input('year')))));
        $semester    = $request->input('semester', $request->input('p_semester', $request->input('semester_id', $request->input('semesterId', $request->input('part_sem', $request->input('part_id', $request->input('part')))))));
        $adminUserId = $request->input('admin_user_id', $request->input('p_admin_user_id', $request->input('adminUserId', $request->input('user_id', $request->input('p_userid')))));

        if (empty($adminUserId)) {
            try {
                $adminUserId = authUserId();
            } catch (\Exception $e) {
                $adminUserId = null;
            }
        }
        if (empty($adminUserId)) {
            $adminUserId = 1;
        }

        $validator = Validator::make([
            'inst_code'     => $instCode,
            'exam_year'     => $examYear,
            'semester'      => $semester,
            'admin_user_id' => $adminUserId,
        ], [
            'inst_code'     => 'required|string|max:100',
            'exam_year'     => 'nullable|string|max:20',
            'semester'      => 'nullable|string|max:50',
            'admin_user_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $instCode    = (string) trim($instCode);
        $examYear    = !empty($examYear) ? (string) trim($examYear) : '';
        $semester    = !empty($semester) ? (string) trim($semester) : '';
        $adminUserId = (int) $adminUserId;

        Log::channel('daily')->info('[getAllExaminationInstitutes] INPUT', [
            'inst_code'     => $instCode,
            'exam_year'     => $examYear,
            'semester'      => $semester,
            'admin_user_id' => $adminUserId,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getallexaminationinstitutes(?::varchar, ?::varchar, ?::varchar, ?::bigint) AS data',
                [$instCode, $examYear, $semester, $adminUserId]
            );

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No examination institutes found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $institutes = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[getAllExaminationInstitutes] JSON decode error', [
                        'error' => json_last_error_msg(),
                        'raw'   => $raw,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 3,
                        'message' => 'Failed to parse database response.',
                        'data'    => null,
                    ], 500);
                }

                if (is_array($decoded) && array_is_list($decoded)) {
                    $institutes = array_merge($institutes, $decoded);
                } elseif (is_array($decoded)) {
                    $institutes[] = $decoded;
                }
            }

            Log::channel('daily')->info('[getAllExaminationInstitutes] OUTPUT', [
                'inst_code' => $instCode,
                'count'     => count($institutes),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Examination institutes fetched successfully',
                'count'   => count($institutes),
                'data'    => $institutes,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getAllExaminationInstitutes] EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while fetching examination institutes: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/get-examination-center",
     *     tags={"Admin - Master Data", "Examinations"},
     *     summary="Get examination center allocation mapping",
     *     description="Calls PostgreSQL stored function fn_admin_getexaminationcenter to retrieve examination center mappings for a given exam year and semester.",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"exam_year", "semester"},
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year (p_exam_year)"),
     *             @OA\Property(property="semester", type="string", example="Part-II", description="Semester / Part (p_semester)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Examination centers fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Examination centers fetched successfully"),
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="destination_inst_code", type="string", example="JCG"),
     *                     @OA\Property(property="destination_inst_name", type="string", nullable=true, example="JNAN CHANDRA GHOSH POLYTECHNIC"),
     *                     @OA\Property(
     *                         property="source_institutes",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="source_inst_code", type="string", example="ARCP"),
     *                             @OA\Property(property="source_inst_name", type="string", nullable=true, example="ABDUR RAHIM COLLEGE OF PHARMACY")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getExaminationCenter(Request $request)
    {
        $examYear = $request->input('exam_year', $request->input('p_exam_year', $request->input('p_examyear', $request->input('examyear', $request->input('year')))));
        $semester = $request->input('semester', $request->input('p_semester', $request->input('part_sem', $request->input('part_id', $request->input('part')))));

        $validator = Validator::make([
            'exam_year' => $examYear,
            'semester'  => $semester,
        ], [
            'exam_year' => 'required|string|max:20',
            'semester'  => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
                'data'    => null,
            ], 422);
        }

        $examYear = trim((string) $examYear);
        $semester = trim((string) $semester);

        Log::channel('daily')->info('[AdminInstitute] fn_admin_getexaminationcenter INPUT', [
            'exam_year' => $examYear,
            'semester'  => $semester,
            'ip'        => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getexaminationcenter(?::varchar, ?::varchar) AS data',
                [$examYear, $semester]
            );

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No examination centers found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $centers = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[AdminInstitute] fn_admin_getexaminationcenter JSON decode error', [
                        'error' => json_last_error_msg(),
                        'raw'   => $raw,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 3,
                        'message' => 'Failed to parse database response.',
                        'data'    => null,
                    ], 500);
                }

                if (is_array($decoded) && array_is_list($decoded)) {
                    $centers = array_merge($centers, $decoded);
                } elseif (is_array($decoded)) {
                    $centers[] = $decoded;
                }
            }

            // Group by Destination Institute (Snake Case Only)
            $grouped = [];

            foreach ($centers as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $destCode = $item['destination_inst_code'] ?? ($item['DestinationInstCode'] ?? ($item['destinationInstCode'] ?? null));
                $destName = $item['destination_inst_name'] ?? ($item['DestinationInstName'] ?? ($item['destinationInstName'] ?? null));
                $srcCode  = $item['source_inst_code'] ?? ($item['SoucrceInstCode'] ?? ($item['SourceInstCode'] ?? ($item['sourceInstCode'] ?? null)));
                $srcName  = $item['source_inst_name'] ?? ($item['SourceInstName'] ?? ($item['sourceInstName'] ?? null));

                $groupKey = ($destCode !== null && trim((string) $destCode) !== '') ? trim((string) $destCode) : '__UNASSIGNED__';

                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'destination_inst_code' => ($destCode !== null && trim((string) $destCode) !== '') ? trim((string) $destCode) : null,
                        'destination_inst_name' => $destName,
                        'source_institutes'     => [],
                    ];
                }

                if (!empty($destName) && empty($grouped[$groupKey]['destination_inst_name'])) {
                    $grouped[$groupKey]['destination_inst_name'] = $destName;
                }

                if (!empty($srcCode)) {
                    $cleanSrcCode = trim((string) $srcCode);

                    // Check if this source institute is already added for this destination center
                    $exists = false;
                    foreach ($grouped[$groupKey]['source_institutes'] as $idx => $existingSrc) {
                        if (strcasecmp($existingSrc['source_inst_code'] ?? '', $cleanSrcCode) === 0) {
                            $exists = true;
                            if (empty($existingSrc['source_inst_name']) && !empty($srcName)) {
                                $grouped[$groupKey]['source_institutes'][$idx]['source_inst_name'] = $srcName;
                            }
                            break;
                        }
                    }

                    if (!$exists) {
                        $grouped[$groupKey]['source_institutes'][] = [
                            'source_inst_code' => $cleanSrcCode,
                            'source_inst_name' => $srcName,
                        ];
                    }
                }
            }

            $groupedData = array_values($grouped);

            Log::channel('daily')->info('[AdminInstitute] fn_admin_getexaminationcenter OUTPUT', [
                'exam_year' => $examYear,
                'semester'  => $semester,
                'count'     => count($groupedData),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Examination centers fetched successfully',
                'count'   => count($groupedData),
                'data'    => $groupedData,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[AdminInstitute] fn_admin_getexaminationcenter EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => 'An error occurred while fetching examination centers: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/save-institute",
     *     tags={"Admin - Master Data"},
     *     summary="Save Institute Details",
     *     description="Saves or updates institute information. Calls PostgreSQL function: fn_save_instritute",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "name", "type", "district_id", "contactperson_name", "contactperson_email", "instemail", "address"},
     *             @OA\Property(property="code", type="string", example="INST001", description="Institute Code (p_code)"),
     *             @OA\Property(property="name", type="string", example="ABC Pharmacy College", description="Institute Name (p_name)"),
     *             @OA\Property(property="type", type="string", example="Govt", description="Institute Type (p_type)"),
     *             @OA\Property(property="district_id", type="integer", example=1, description="District ID (p_districtId)"),
     *             @OA\Property(property="contactperson_name", type="string", example="John Doe", description="Contact Person Name (p_contactperson_name)"),
     *             @OA\Property(property="contactperson_email", type="string", example="contact@abc.edu", description="Contact Person Email (p_contactperson_email)"),
     *             @OA\Property(property="instemail", type="string", example="info@abc.edu", description="Institute Email (p_instemail)"),
     *             @OA\Property(property="address", type="string", example="123 College Road", description="Address (p_address)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Institute saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Institute saved successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function saveInstitute(Request $request)
    {
        $code               = $request->input('code', $request->input('p_code', $request->input('inst_code', $request->input('i_code'))));
        $name               = $request->input('name', $request->input('p_name', $request->input('inst_name', $request->input('i_name'))));
        $type               = $request->input('type', $request->input('p_type', $request->input('inst_type', $request->input('institute_type'))));
        $districtId         = $request->input('district_id', $request->input('districtId', $request->input('p_districtId', $request->input('p_district_id', $request->input('districtid', $request->input('district', $request->input('districtname', $request->input('district_name', $request->input('p_districtname')))))))));
        $contactPersonName  = $request->input('contactperson_name', $request->input('contact_person_name', $request->input('p_contactperson_name', $request->input('contact_name'))));
        $contactPersonEmail = $request->input('contactperson_email', $request->input('contact_person_email', $request->input('p_contactperson_email', $request->input('contact_email'))));
        $instEmail          = $request->input('instemail', $request->input('inst_email', $request->input('institute_email', $request->input('p_instemail', $request->input('email')))));
        $address            = $request->input('address', $request->input('inst_address', $request->input('p_address', $request->input('i_address'))));

        $validator = Validator::make([
            'code'                => $code,
            'name'                => $name,
            'type'                => $type,
            'district_id'         => $districtId,
            'contactperson_name'  => $contactPersonName,
            'contactperson_email' => $contactPersonEmail,
            'instemail'           => $instEmail,
            'address'             => $address,
        ], [
            'code'                => 'required|string|max:50',
            'name'                => 'required|string|max:255',
            'type'                => 'required|string|max:50',
            'district_id'         => 'required|integer',
            'contactperson_name'  => 'required|string|max:255',
            'contactperson_email' => 'required|string|max:255',
            'instemail'           => 'required|string|max:255',
            'address'             => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
                'data'    => $validator->errors(),
            ], 422);
        }

        $code               = trim((string) $code);
        $name               = trim((string) $name);
        $type               = trim((string) $type);
        $districtId         = (int) $districtId;
        $contactPersonName  = trim((string) $contactPersonName);
        $contactPersonEmail = trim((string) $contactPersonEmail);
        $instEmail          = trim((string) $instEmail);
        $address            = trim((string) $address);

        Log::channel('daily')->info('[AdminInstitute] fn_save_instritute INPUT', [
            'code'                => $code,
            'name'                => $name,
            'type'                => $type,
            'district_id'         => $districtId,
            'contactperson_name'  => $contactPersonName,
            'contactperson_email' => $contactPersonEmail,
            'instemail'           => $instEmail,
            'address'             => $address,
            'ip'                  => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_save_instritute(?::varchar, ?::varchar, ?::varchar, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::varchar) AS data',
                [
                    $code,
                    $name,
                    $type,
                    $districtId,
                    $contactPersonName,
                    $contactPersonEmail,
                    $instEmail,
                    $address,
                ]
            );

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No data returned from database function.',
                    'data'    => null,
                ], 500);
            }

            $raw = $result[0]->data ?? null;
            $responseData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                $responseData = $raw;
            }

            Log::channel('daily')->info('[AdminInstitute] fn_save_instritute OUTPUT', [
                'result' => $responseData,
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Institute saved successfully.',
                'data'    => $responseData,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[AdminInstitute] fn_save_instritute EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'An error occurred while saving institute: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}


