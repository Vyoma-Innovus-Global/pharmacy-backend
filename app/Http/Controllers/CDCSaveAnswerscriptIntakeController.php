<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CDCSaveAnswerscriptIntakeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-answerscript-intake",
     *     tags={"CDC - Answer Scripts"},
     *     summary="Save Answer Script Intake (Single or Bulk)",
     *     description="Saves answer script intake record by calling public.fn_cdc_saveanswerscriptintake(p_answerscript_id, p_exam_year, p_semester_id, p_inst_code, p_dept_code, p_subject_code, p_total_scripts, p_remarks, p_entry_user_id)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"exam_year", "semester_id", "inst_code", "dept_code", "subject_code", "total_scripts", "entry_user_id"},
     *             @OA\Property(property="answerscript_id", type="integer", example=0, description="Answer script ID (0 for new record)", nullable=true),
     *             @OA\Property(property="exam_year", type="string", example="2026", description="Exam Year"),
     *             @OA\Property(property="semester_id", type="string", example="Part-II", description="Semester / Part Name (e.g. Part-I, Part-II)"),
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute Code"),
     *             @OA\Property(property="dept_code", type="string", example="PHARM", description="Department Code"),
     *             @OA\Property(property="subject_code", type="string", example="PHCE", description="Subject Code"),
     *             @OA\Property(property="total_scripts", type="integer", example=40, description="Total Answer Scripts Count"),
     *             @OA\Property(property="remarks", type="string", example="test", description="Remarks / Notes", nullable=true),
     *             @OA\Property(property="entry_user_id", type="integer", example=1, description="Entry User ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer script intake saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Answer script intake saved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or function failure",
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
     * POST /api/admin/save-answerscript-intake
     * POST /api/cdc/save-answerscript-intake
     *
     * Calls: public.fn_cdc_saveanswerscriptintake(
     *   p_answerscript_id,
     *   p_exam_year,
     *   p_semester_id,
     *   p_inst_code,
     *   p_dept_code,
     *   p_subject_code,
     *   p_total_scripts,
     *   p_remarks,
     *   p_entry_user_id
     * )
     */
    public function saveAnswerscriptIntake(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE ANSWER SCRIPT INTAKE API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Attempt to extract authenticated user_id from middleware header if available
        $authUserId = null;
        $authUserData = $request->header('auth_user_data');
        if ($authUserData) {
            $decodedAuth = is_string($authUserData) ? json_decode($authUserData, true) : (array) $authUserData;
            $authUserId = $decodedAuth['user_id'] ?? null;
        }

        // Shared top-level fallback fields
        $topLevelExamYear    = $request->input('exam_year') ?? $request->input('p_exam_year') ?? $request->input('examYear') ?? $request->input('year');
        $topLevelSemesterId  = $request->input('semester_id') ?? $request->input('p_semester_id') ?? $request->input('semesterId') ?? $request->input('semester') ?? $request->input('p_semester');
        $topLevelInstCode    = $request->input('inst_code') ?? $request->input('p_inst_code') ?? $request->input('instCode') ?? $request->input('institute_code') ?? $request->input('instituteCode');
        $topLevelDeptCode    = $request->input('dept_code') ?? $request->input('p_dept_code') ?? $request->input('deptCode') ?? $request->input('department_code') ?? $request->input('departmentCode') ?? $request->input('department');
        $topLevelEntryUserId = $request->input('entry_user_id') ?? $request->input('p_entry_user_id') ?? $request->input('entryUserId') ?? $request->input('admin_user_id') ?? $request->input('adminUserId') ?? $request->input('user_id') ?? $authUserId;
        $topLevelRemarks     = $request->input('remarks') ?? $request->input('p_remarks') ?? $request->input('remark') ?? $request->input('comment') ?? '';

        $items = [];
        $rawItems = $request->input('items') ?? $request->input('data') ?? $request->input('scripts') ?? $request->input('answerscripts');

        if (is_array($rawItems)) {
            // Bulk items passed
            foreach ($rawItems as $entry) {
                if (is_array($entry)) {
                    $items[] = [
                        'answerscript_id' => $entry['answerscript_id'] ?? $entry['p_answerscript_id'] ?? $entry['answerscriptId'] ?? $entry['id'] ?? 0,
                        'exam_year'       => $entry['exam_year'] ?? $entry['p_exam_year'] ?? $entry['examYear'] ?? $topLevelExamYear,
                        'semester_id'     => $entry['semester_id'] ?? $entry['p_semester_id'] ?? $entry['semesterId'] ?? $entry['semester'] ?? $topLevelSemesterId,
                        'inst_code'       => $entry['inst_code'] ?? $entry['p_inst_code'] ?? $entry['instCode'] ?? $entry['institute_code'] ?? $topLevelInstCode,
                        'dept_code'       => $entry['dept_code'] ?? $entry['p_dept_code'] ?? $entry['deptCode'] ?? $entry['department_code'] ?? $topLevelDeptCode,
                        'subject_code'    => $entry['subject_code'] ?? $entry['p_subject_code'] ?? $entry['subjectCode'] ?? $entry['subject'] ?? null,
                        'total_scripts'   => $entry['total_scripts'] ?? $entry['p_total_scripts'] ?? $entry['totalScripts'] ?? $entry['scripts_count'] ?? $entry['script_count'] ?? null,
                        'remarks'         => $entry['remarks'] ?? $entry['p_remarks'] ?? $entry['remark'] ?? $topLevelRemarks ?? '',
                        'entry_user_id'   => $entry['entry_user_id'] ?? $entry['p_entry_user_id'] ?? $entry['entryUserId'] ?? $topLevelEntryUserId,
                    ];
                }
            }
        } elseif (is_array($request->all()) && isset($request->all()[0]) && is_array($request->all()[0])) {
            // Top-level indexed array of records passed
            foreach ($request->all() as $entry) {
                if (is_array($entry)) {
                    $items[] = [
                        'answerscript_id' => $entry['answerscript_id'] ?? $entry['p_answerscript_id'] ?? $entry['answerscriptId'] ?? $entry['id'] ?? 0,
                        'exam_year'       => $entry['exam_year'] ?? $entry['p_exam_year'] ?? $entry['examYear'] ?? null,
                        'semester_id'     => $entry['semester_id'] ?? $entry['p_semester_id'] ?? $entry['semesterId'] ?? $entry['semester'] ?? null,
                        'inst_code'       => $entry['inst_code'] ?? $entry['p_inst_code'] ?? $entry['instCode'] ?? $entry['institute_code'] ?? null,
                        'dept_code'       => $entry['dept_code'] ?? $entry['p_dept_code'] ?? $entry['deptCode'] ?? $entry['department_code'] ?? null,
                        'subject_code'    => $entry['subject_code'] ?? $entry['p_subject_code'] ?? $entry['subjectCode'] ?? $entry['subject'] ?? null,
                        'total_scripts'   => $entry['total_scripts'] ?? $entry['p_total_scripts'] ?? $entry['totalScripts'] ?? $entry['scripts_count'] ?? $entry['script_count'] ?? null,
                        'remarks'         => $entry['remarks'] ?? $entry['p_remarks'] ?? $entry['remark'] ?? '',
                        'entry_user_id'   => $entry['entry_user_id'] ?? $entry['p_entry_user_id'] ?? $entry['entryUserId'] ?? $authUserId,
                    ];
                }
            }
        } else {
            // Single record submission
            $items[] = [
                'answerscript_id' => $request->input('answerscript_id') ?? $request->input('p_answerscript_id') ?? $request->input('answerscriptId') ?? $request->input('id') ?? 0,
                'exam_year'       => $topLevelExamYear,
                'semester_id'     => $topLevelSemesterId,
                'inst_code'       => $topLevelInstCode,
                'dept_code'       => $topLevelDeptCode,
                'subject_code'    => $request->input('subject_code') ?? $request->input('p_subject_code') ?? $request->input('subjectCode') ?? $request->input('subject'),
                'total_scripts'   => $request->input('total_scripts') ?? $request->input('p_total_scripts') ?? $request->input('totalScripts') ?? $request->input('scripts_count') ?? $request->input('script_count'),
                'remarks'         => $topLevelRemarks,
                'entry_user_id'   => $topLevelEntryUserId,
            ];
        }

        if (empty($items)) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'No answer script intake data provided.',
                'data'    => [],
            ], 400);
        }

        // Validate each item
        $isBulk = count($items) > 1;
        $rules = [
            'answerscript_id' => 'nullable|integer|min:0',
            'exam_year'       => 'required|string|max:20',
            'semester_id'     => 'required',
            'inst_code'       => 'required|string|max:50',
            'dept_code'       => 'required|string|max:50',
            'subject_code'    => 'required|string|max:50',
            'total_scripts'   => 'required|integer|min:0',
            'remarks'         => 'nullable|string',
            'entry_user_id'   => 'required|integer',
        ];

        foreach ($items as $index => $item) {
            $validator = Validator::make($item, $rules);
            if ($validator->fails()) {
                Log::channel('daily')->error("❌ VALIDATION FAILED on answer script intake item {$index}:", [
                    'errors' => $validator->errors()->all(),
                    'item'   => $item,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => ($isBulk ? "Item {$index}: " : '') . 'Validation failed: ' . $validator->errors()->first(),
                    'data'    => [],
                ], 400);
            }
        }

        $sql = 'SELECT public.fn_cdc_saveanswerscriptintake(?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::text, ?::bigint) AS data';

        DB::beginTransaction();
        try {
            $lastResult = null;
            $results = [];

            foreach ($items as $index => $item) {
                $answerscriptId = (int) ($item['answerscript_id'] ?? 0);
                $examYear       = trim((string) $item['exam_year']);

                // Normalize semester_id to handle Part-I, Part-II, 1, 2, Part_I, Part_II
                $rawSem = trim((string) $item['semester_id']);
                if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
                    $semesterId = 'Part-I';
                } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
                    $semesterId = 'Part-II';
                } else {
                    $semesterId = $rawSem;
                }

                $instCode     = strtoupper(trim((string) $item['inst_code']));
                $deptCode     = strtoupper(trim((string) $item['dept_code']));
                $subjectCode  = strtoupper(trim((string) $item['subject_code']));
                $totalScripts = (int) $item['total_scripts'];
                $remarks      = (string) ($item['remarks'] ?? '');
                $entryUserId  = (int) $item['entry_user_id'];

                Log::channel('daily')->info("📤 Calling fn_cdc_saveanswerscriptintake [Item {$index}] with parameters:", [
                    'p_answerscript_id' => $answerscriptId,
                    'p_exam_year'       => $examYear,
                    'p_semester_id'     => $semesterId,
                    'p_inst_code'       => $instCode,
                    'p_dept_code'       => $deptCode,
                    'p_subject_code'    => $subjectCode,
                    'p_total_scripts'   => $totalScripts,
                    'p_remarks'         => $remarks,
                    'p_entry_user_id'   => $entryUserId,
                ]);

                $spResult = DB::select($sql, [
                    $answerscriptId,
                    $examYear,
                    $semesterId,
                    $instCode,
                    $deptCode,
                    $subjectCode,
                    $totalScripts,
                    $remarks,
                    $entryUserId,
                ]);

                if (empty($spResult) || !isset($spResult[0]->data)) {
                    throw new \Exception("No response received from fn_cdc_saveanswerscriptintake for subject {$subjectCode}");
                }

                $raw = $spResult[0]->data;
                $parsed = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Failed to parse database response for subject {$subjectCode}: {$raw}");
                }

                $errorCode = isset($parsed['p_errorcode']) ? (int) $parsed['p_errorcode'] : 0;
                if ($errorCode !== 0) {
                    $errorMsg = $parsed['p_errormsg'] ?? $parsed['message'] ?? "Database function returned error code {$errorCode}";
                    throw new \Exception("Subject {$subjectCode}: {$errorMsg}");
                }

                $lastResult = $parsed;
                $results[] = [
                    'subject_code' => $subjectCode,
                    'result'       => $parsed,
                ];
            }

            DB::commit();

            Log::channel('daily')->info('✅ fn_cdc_saveanswerscriptintake executed successfully:', [
                'total_processed' => count($items),
                'results'         => $results,
            ]);

            $responseData = [
                'version' => '1.0',
                'status'  => 0,
                'message' => count($items) > 1
                    ? 'Answer script intake saved successfully for ' . count($items) . ' record(s).'
                    : 'Answer script intake saved successfully',
                'data'    => $lastResult ?? ['p_errorcode' => 0],
            ];

            if (count($items) > 1) {
                $responseData['items'] = $results;
            }

            Log::channel('daily')->info('📤 FINAL RESPONSE:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('🔥 EXCEPTION in saveAnswerscriptIntake:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 400);
        }
    }
}
