<?php

namespace App\Http\Controllers;

use App\Mail\CECTeacherAssignmentMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CECSaveTeacherAssignSubjectController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-cec-teacher-assign-subject",
     *     tags={"CEC - Teacher Assignment"},
     *     summary="Save CEC Teacher Assign Subject (Single or Array / Bulk)",
     *     description="Saves teacher assignment for CEC answer scripts by calling public.fn_cec_saveteacherassignsubject(p_answerscript_id, p_teacher_id, p_semester_id, p_entry_user_id, p_inst_code, p_examinertype_id, p_examyear, p_subject_code)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"teacher_id", "semester_id", "subject_code"},
     *             @OA\Property(
     *                 property="answerscript_id",
     *                 description="Answer script ID (single integer or array of IDs)",
     *                 oneOf={
     *                     @OA\Schema(type="integer", example=101),
     *                     @OA\Schema(type="array", @OA\Items(type="integer", example=101))
     *                 }
     *             ),
     *             @OA\Property(property="teacher_id", type="integer", example=661, description="Teacher / Evaluator User ID"),
     *             @OA\Property(property="semester_id", type="string", example="Part-I", description="Semester / Part (e.g. Part-I, Part-II, 1, 2)"),
     *             @OA\Property(property="entry_user_id", type="integer", example=1, description="Entry Admin User ID"),
     *             @OA\Property(
     *                 property="inst_code",
     *                 description="Institute Code (single string or array of institute codes)",
     *                 oneOf={
     *                     @OA\Schema(type="string", example="JCG"),
     *                     @OA\Schema(type="array", @OA\Items(type="string", example="JCG"))
     *                 }
     *             ),
     *             @OA\Property(property="examinertype_id", type="integer", example=1, description="Examiner Type ID (1: External, 2: Internal)", default=1),
     *             @OA\Property(property="examyear", type="string", example="2026", description="Exam Year (Default: 2026)"),
     *             @OA\Property(property="subject_code", type="string", example="PHCE", description="Subject Code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher assigned to subject successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Teacher assigned to subject successfully"),
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
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Validation failed: ..."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Internal server error: ..."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     *
     * POST /api/admin/save-cec-teacher-assign-subject
     *
     * Calls: public.fn_cec_saveteacherassignsubject(
     *   p_answerscript_id,
     *   p_teacher_id,
     *   p_semester_id,
     *   p_entry_user_id,
     *   p_inst_code,
     *   p_examinertype_id,
     *   p_examyear,
     *   p_subject_code
     * )
     */
    public function saveTeacherAssignSubject(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE CEC TEACHER ASSIGN SUBJECT API - REQUEST START ===');
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

        // Extract top-level shared parameters with aliases
        $topLevelTeacherId = $request->input('teacher_id')
            ?? $request->input('p_teacher_id')
            ?? $request->input('teacherId');

        $topLevelSemesterId = $request->input('semester_id')
            ?? $request->input('p_semester_id')
            ?? $request->input('semesterId')
            ?? $request->input('semester')
            ?? $request->input('p_semester');

        $topLevelEntryUserId = $request->input('entry_user_id')
            ?? $request->input('p_entry_user_id')
            ?? $request->input('entryUserId')
            ?? $request->input('admin_user_id')
            ?? $request->input('adminUserId')
            ?? $request->input('user_id')
            ?? $authUserId
            ?? 1;

        $topLevelInstCode = $request->input('inst_code')
            ?? $request->input('p_inst_code')
            ?? $request->input('instCode')
            ?? $request->input('institute_code')
            ?? $request->input('instituteCode')
            ?? $request->input('instcode');

        $topLevelExaminerTypeId = $request->input('examinertype_id')
            ?? $request->input('p_examinertype_id')
            ?? $request->input('examiner_type_id')
            ?? $request->input('examinerTypeId')
            ?? $request->input('examiner_type')
            ?? $request->input('examinertype')
            ?? 1;

        $topLevelExamYear = $request->input('examyear')
            ?? $request->input('exam_year')
            ?? $request->input('p_examyear')
            ?? $request->input('p_exam_year')
            ?? $request->input('examYear')
            ?? $request->input('year')
            ?? '2026';

        $topLevelSubjectCode = $request->input('subject_code')
            ?? $request->input('p_subject_code')
            ?? $request->input('subjectCode')
            ?? $request->input('subjectcode');

        $topLevelSubjectId = $request->input('subject_id')
            ?? $request->input('p_subject_id')
            ?? $request->input('subjectId');

        // Look up subject_code from tbl_department_subjects_master if only subject_id is provided
        if (empty($topLevelSubjectCode) && !empty($topLevelSubjectId)) {
            $subRow = DB::table('tbl_department_subjects_master')
                ->where('dsm_id', (int) $topLevelSubjectId)
                ->select('dsm_subject_id')
                ->first();
            if ($subRow && !empty($subRow->dsm_subject_id)) {
                $topLevelSubjectCode = trim((string) $subRow->dsm_subject_id);
            }
        }

        $rawAnswerscriptId = $request->input('answerscript_id')
            ?? $request->input('p_answerscript_id')
            ?? $request->input('answerscriptId')
            ?? $request->input('answerscript_ids')
            ?? $request->input('answerscriptIds')
            ?? $request->input('answerscripts')
            ?? $request->input('id');

        $rawItems = $request->input('items')
            ?? $request->input('assignments')
            ?? $request->input('data');

        $rawBody = $request->all();

        $items = [];

        // Normalize top-level inst_code if passed as a comma-separated string
        if (is_string($topLevelInstCode) && strpos($topLevelInstCode, ',') !== false) {
            $topLevelInstCode = array_map('trim', explode(',', $topLevelInstCode));
        }

        // Normalize rawAnswerscriptId if passed as a comma-separated string
        if (is_string($rawAnswerscriptId) && strpos($rawAnswerscriptId, ',') !== false) {
            $rawAnswerscriptId = array_map('intval', array_map('trim', explode(',', $rawAnswerscriptId)));
        }

        // Scenario 1: assignments / items / data list provided
        if (is_array($rawItems) && !empty($rawItems)) {
            foreach ($rawItems as $entry) {
                if (is_array($entry)) {
                    $entryAnswerscriptId = $entry['answerscript_id']
                        ?? $entry['p_answerscript_id']
                        ?? $entry['answerscriptId']
                        ?? $entry['id']
                        ?? $rawAnswerscriptId;

                    $entryInstCode = $entry['inst_code']
                        ?? $entry['p_inst_code']
                        ?? $entry['instCode']
                        ?? $entry['institute_code']
                        ?? $topLevelInstCode;

                    if (is_string($entryInstCode) && strpos($entryInstCode, ',') !== false) {
                        $entryInstCode = array_map('trim', explode(',', $entryInstCode));
                    }

                    // If inner answerscript_id is an array
                    if (is_array($entryAnswerscriptId)) {
                        $innerInstList = is_array($entryInstCode) ? array_values($entryInstCode) : [];
                        foreach ($entryAnswerscriptId as $idx => $subAnsId) {
                            $actualSubAnsId = is_array($subAnsId) ? ($subAnsId['answerscript_id'] ?? $subAnsId['id'] ?? null) : $subAnsId;
                            $actualSubInstCode = is_array($subAnsId)
                                ? ($subAnsId['inst_code'] ?? $subAnsId['institute_code'] ?? null)
                                : ($innerInstList[$idx] ?? (is_string($entryInstCode) ? $entryInstCode : ($innerInstList[0] ?? null)));

                            $items[] = [
                                'answerscript_id'   => $actualSubAnsId,
                                'teacher_id'        => $entry['teacher_id'] ?? $entry['p_teacher_id'] ?? $topLevelTeacherId,
                                'semester_id'       => $entry['semester_id'] ?? $entry['p_semester_id'] ?? $topLevelSemesterId,
                                'entry_user_id'     => $entry['entry_user_id'] ?? $entry['p_entry_user_id'] ?? $topLevelEntryUserId,
                                'inst_code'         => $actualSubInstCode,
                                'examinertype_id'   => $entry['examinertype_id'] ?? $entry['p_examinertype_id'] ?? $topLevelExaminerTypeId,
                                'examyear'          => $entry['examyear'] ?? $entry['p_examyear'] ?? $topLevelExamYear,
                                'subject_code'      => $entry['subject_code'] ?? $entry['p_subject_code'] ?? $topLevelSubjectCode,
                            ];
                        }
                    } else {
                        $actualSubInstCode = is_array($entryInstCode) ? ($entryInstCode[0] ?? '') : $entryInstCode;
                        $items[] = [
                            'answerscript_id'   => $entryAnswerscriptId,
                            'teacher_id'        => $entry['teacher_id'] ?? $entry['p_teacher_id'] ?? $topLevelTeacherId,
                            'semester_id'       => $entry['semester_id'] ?? $entry['p_semester_id'] ?? $topLevelSemesterId,
                            'entry_user_id'     => $entry['entry_user_id'] ?? $entry['p_entry_user_id'] ?? $topLevelEntryUserId,
                            'inst_code'         => $actualSubInstCode,
                            'examinertype_id'   => $entry['examinertype_id'] ?? $entry['p_examinertype_id'] ?? $topLevelExaminerTypeId,
                            'examyear'          => $entry['examyear'] ?? $entry['p_examyear'] ?? $topLevelExamYear,
                            'subject_code'      => $entry['subject_code'] ?? $entry['p_subject_code'] ?? $topLevelSubjectCode,
                        ];
                    }
                }
            }
        }
        // Scenario 2: Root payload is a numeric array [ {...}, {...} ]
        elseif (is_array($rawBody) && isset($rawBody[0]) && is_array($rawBody[0])) {
            foreach ($rawBody as $entry) {
                if (is_array($entry)) {
                    $entryInstCode = $entry['inst_code'] ?? $entry['p_inst_code'] ?? $entry['instCode'] ?? $topLevelInstCode;
                    $items[] = [
                        'answerscript_id'   => $entry['answerscript_id'] ?? $entry['p_answerscript_id'] ?? $entry['answerscriptId'] ?? $entry['id'] ?? null,
                        'teacher_id'        => $entry['teacher_id'] ?? $entry['p_teacher_id'] ?? $topLevelTeacherId,
                        'semester_id'       => $entry['semester_id'] ?? $entry['p_semester_id'] ?? $topLevelSemesterId,
                        'entry_user_id'     => $entry['entry_user_id'] ?? $entry['p_entry_user_id'] ?? $topLevelEntryUserId,
                        'inst_code'         => is_array($entryInstCode) ? ($entryInstCode[0] ?? '') : $entryInstCode,
                        'examinertype_id'   => $entry['examinertype_id'] ?? $entry['p_examinertype_id'] ?? $topLevelExaminerTypeId,
                        'examyear'          => $entry['examyear'] ?? $entry['p_examyear'] ?? $topLevelExamYear,
                        'subject_code'      => $entry['subject_code'] ?? $entry['p_subject_code'] ?? $topLevelSubjectCode,
                    ];
                }
            }
        }
        // Scenario 3: answerscript_id is passed as an array (e.g. "answerscript_id": [101, 102])
        elseif (is_array($rawAnswerscriptId)) {
            $instCodeList = is_array($topLevelInstCode) ? array_values($topLevelInstCode) : [];

            foreach ($rawAnswerscriptId as $idx => $ansItem) {
                $actualAnsId = is_array($ansItem)
                    ? ($ansItem['answerscript_id'] ?? $ansItem['id'] ?? null)
                    : $ansItem;

                $actualInstCode = is_array($ansItem)
                    ? ($ansItem['inst_code'] ?? $ansItem['institute_code'] ?? null)
                    : null;

                if (empty($actualInstCode)) {
                    if (isset($instCodeList[$idx])) {
                        $actualInstCode = $instCodeList[$idx];
                    } elseif (is_string($topLevelInstCode)) {
                        $actualInstCode = $topLevelInstCode;
                    } elseif (!empty($instCodeList)) {
                        $actualInstCode = $instCodeList[0];
                    }
                }

                $items[] = [
                    'answerscript_id'   => $actualAnsId,
                    'teacher_id'        => is_array($ansItem) ? ($ansItem['teacher_id'] ?? $topLevelTeacherId) : $topLevelTeacherId,
                    'semester_id'       => is_array($ansItem) ? ($ansItem['semester_id'] ?? $topLevelSemesterId) : $topLevelSemesterId,
                    'entry_user_id'     => is_array($ansItem) ? ($ansItem['entry_user_id'] ?? $topLevelEntryUserId) : $topLevelEntryUserId,
                    'inst_code'         => $actualInstCode,
                    'examinertype_id'   => is_array($ansItem) ? ($ansItem['examinertype_id'] ?? $topLevelExaminerTypeId) : $topLevelExaminerTypeId,
                    'examyear'          => is_array($ansItem) ? ($ansItem['examyear'] ?? $topLevelExamYear) : $topLevelExamYear,
                    'subject_code'      => is_array($ansItem) ? ($ansItem['subject_code'] ?? $topLevelSubjectCode) : $topLevelSubjectCode,
                ];
            }
        }
        // Scenario 4: inst_code is an array while answerscript_id is single / scalar
        elseif (is_array($topLevelInstCode)) {
            foreach ($topLevelInstCode as $codeItem) {
                $codeVal = is_array($codeItem) ? ($codeItem['inst_code'] ?? $codeItem['institute_code'] ?? '') : $codeItem;
                $items[] = [
                    'answerscript_id'   => $rawAnswerscriptId,
                    'teacher_id'        => $topLevelTeacherId,
                    'semester_id'       => $topLevelSemesterId,
                    'entry_user_id'     => $topLevelEntryUserId,
                    'inst_code'         => $codeVal,
                    'examinertype_id'   => $topLevelExaminerTypeId,
                    'examyear'          => $topLevelExamYear,
                    'subject_code'      => $topLevelSubjectCode,
                ];
            }
        }
        // Scenario 5: Single record
        else {
            $items[] = [
                'answerscript_id'   => $rawAnswerscriptId,
                'teacher_id'        => $topLevelTeacherId,
                'semester_id'       => $topLevelSemesterId,
                'entry_user_id'     => $topLevelEntryUserId,
                'inst_code'         => $topLevelInstCode,
                'examinertype_id'   => $topLevelExaminerTypeId,
                'examyear'          => $topLevelExamYear,
                'subject_code'      => $topLevelSubjectCode,
            ];
        }

        if (empty($items)) {
            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'No teacher assignment data provided.',
                'data'    => ['p_errorcode' => 1],
            ], 400);
        }

        $isBulk = count($items) > 1;

        $rules = [
            'answerscript_id' => 'required|integer',
            'teacher_id'      => 'required|integer',
            'semester_id'     => 'required',
            'entry_user_id'   => 'required|integer',
            'inst_code'       => 'required|string',
            'examinertype_id' => 'nullable',
            'examyear'        => 'required',
            'subject_code'    => 'required|string',
        ];

        // Look up authoritative answer script intake details to guarantee answerscript_id is correctly mapped to its inst_code
        $ansIds = [];
        foreach ($items as $itm) {
            if (!empty($itm['answerscript_id']) && is_numeric($itm['answerscript_id'])) {
                $ansIds[] = (int) $itm['answerscript_id'];
            }
        }

        $intakeRecords = [];
        if (!empty($ansIds)) {
            try {
                $intakeRecords = DB::table('tbl_answer_script_intake')
                    ->whereIn('asi_answer_script_id', $ansIds)
                    ->select('asi_answer_script_id', 'asi_inst_code', 'asi_subject_code', 'asi_semester_id')
                    ->get()
                    ->keyBy('asi_answer_script_id');
            } catch (\Exception $e) {
                Log::channel('daily')->warning('Unable to preload tbl_answer_script_intake: ' . $e->getMessage());
            }
        }

        foreach ($items as $index => &$item) {
            $ansId = (int) ($item['answerscript_id'] ?? 0);

            // If found in tbl_answer_script_intake, enforce the exact institute code from intake
            if ($ansId && isset($intakeRecords[$ansId])) {
                $intake = $intakeRecords[$ansId];
                if (!empty($intake->asi_inst_code)) {
                    $item['inst_code'] = strtoupper(trim($intake->asi_inst_code));
                }
                if (empty($item['subject_code']) && !empty($intake->asi_subject_code)) {
                    $item['subject_code'] = trim($intake->asi_subject_code);
                }
                if (empty($item['semester_id']) && !empty($intake->asi_semester_id)) {
                    $item['semester_id'] = trim($intake->asi_semester_id);
                }
            }

            // If answerscript_id was missing or null, attempt fallback lookup by inst_code and subject_code
            if (!$ansId && !empty($item['inst_code']) && !empty($item['subject_code'])) {
                try {
                    $q = DB::table('tbl_answer_script_intake')
                        ->where('asi_inst_code', strtoupper(trim($item['inst_code'])))
                        ->where('asi_subject_code', strtoupper(trim($item['subject_code'])));
                    if (!empty($item['examyear'])) {
                        $q->where('asi_exam_year', trim((string) $item['examyear']));
                    }
                    $foundIntake = $q->first();
                    if ($foundIntake) {
                        $ansId = (int) $foundIntake->asi_answer_script_id;
                        $item['answerscript_id'] = $ansId;
                    }
                } catch (\Exception $e) {
                    Log::channel('daily')->warning('Unable to fallback lookup answerscript_id: ' . $e->getMessage());
                }
            }

            Log::channel('daily')->info("CEC teacher assignment mapped item #{$index}: answerscript_id [{$item['answerscript_id']}] -> inst_code [{$item['inst_code']}]");

            // Normalization of examiner_type
            if (is_string($item['examinertype_id'])) {
                $rawExType = strtoupper(trim($item['examinertype_id']));
                if ($rawExType === 'INTERNAL' || $rawExType === 'INT' || $rawExType === '2') {
                    $item['examinertype_id'] = 2;
                } else {
                    $item['examinertype_id'] = 1;
                }
            } else {
                $item['examinertype_id'] = (int) ($item['examinertype_id'] ?? 1);
            }

            // Normalize semester_id
            $rawSem = trim((string) ($item['semester_id'] ?? ''));
            if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
                $item['semester_id'] = 'Part-I';
            } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
                $item['semester_id'] = 'Part-II';
            } else {
                $item['semester_id'] = $rawSem;
            }

            $validator = Validator::make($item, $rules);
            if ($validator->fails()) {
                Log::channel('daily')->error("❌ VALIDATION FAILED on CEC teacher assignment item {$index}:", [
                    'errors' => $validator->errors()->all(),
                    'item'   => $item,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => ($isBulk ? "Item {$index}: " : '') . 'Validation failed: ' . $validator->errors()->first(),
                    'data'    => ['p_errorcode' => 1],
                ], 400);
            }
        }
        unset($item);

        $sql = "SELECT public.fn_cec_saveteacherassignsubject(?::bigint, ?::bigint, ?::varchar, ?::bigint, ?::varchar, ?::integer, ?::varchar, ?::varchar) AS result";

        DB::beginTransaction();
        try {
            $successCount = 0;
            $failedAssignments = [];
            $results = [];
            $lastResult = null;

            foreach ($items as $index => $item) {
                $answerscriptId    = (int) $item['answerscript_id'];
                $teacherId         = (int) $item['teacher_id'];
                $semesterId        = trim((string) $item['semester_id']);
                $entryUserId       = (int) ($item['entry_user_id'] ?? 1);
                $instCode          = strtoupper(trim((string) $item['inst_code']));
                $examinerTypeId    = (int) ($item['examinertype_id'] ?? 1);
                $examYear          = trim((string) ($item['examyear'] ?? '2026'));
                $subjectCode       = trim((string) $item['subject_code']);

                Log::channel('daily')->info("📤 Calling fn_cec_saveteacherassignsubject [Item {$index}] with parameters:", [
                    'p_answerscript_id' => $answerscriptId,
                    'p_teacher_id'      => $teacherId,
                    'p_semester_id'     => $semesterId,
                    'p_entry_user_id'   => $entryUserId,
                    'p_inst_code'       => $instCode,
                    'p_examinertype_id' => $examinerTypeId,
                    'p_examyear'        => $examYear,
                    'p_subject_code'    => $subjectCode,
                ]);

                $spResult = DB::selectOne($sql, [
                    $answerscriptId,
                    $teacherId,
                    $semesterId,
                    $entryUserId,
                    $instCode,
                    $examinerTypeId,
                    $examYear,
                    $subjectCode,
                ]);

                if (!$spResult || !isset($spResult->result)) {
                    throw new \Exception("No response received from fn_cec_saveteacherassignsubject for answerscript ID {$answerscriptId}");
                }

                $raw = $spResult->result;
                $parsed = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    // In case the DB returns a scalar error code or plain message
                    $parsed = ['result' => $raw, 'p_errorcode' => is_numeric($raw) ? (int)$raw : 0];
                }

                $errorCode = isset($parsed['p_errorcode']) ? (int) $parsed['p_errorcode'] : 0;

                if ($errorCode === 0) {
                    $successCount++;
                    $lastResult = $parsed;
                    $results[] = [
                        'answerscript_id' => $answerscriptId,
                        'teacher_id'      => $teacherId,
                        'inst_code'       => $instCode,
                        'status'          => 'success',
                        'result'          => $parsed,
                    ];
                    Log::channel('daily')->info("✅ Item #{$index} assigned successfully", [
                        'answerscript_id' => $answerscriptId,
                        'inst_code'       => $instCode,
                    ]);
                } else {
                    $errorMsg = $errorCode === 200
                        ? "Teacher already assigned to this answerscript/subject for exam year {$examYear}"
                        : ($parsed['p_errormsg'] ?? "Database function returned error code {$errorCode}");

                    Log::channel('daily')->warning("⚠️ Item #{$index} assignment returned error code {$errorCode}: {$errorMsg}", [
                        'item'   => $item,
                        'parsed' => $parsed,
                    ]);

                    $failedAssignments[] = [
                        'answerscript_id' => $answerscriptId,
                        'teacher_id'      => $teacherId,
                        'inst_code'       => $instCode,
                        'error_code'      => $errorCode,
                        'error_message'   => $errorMsg,
                        'result'          => $parsed,
                    ];
                }
            }

            // If any item failed, rollback transaction and return simple error response
            if (!empty($failedAssignments)) {
                DB::rollBack();
                $fail = $failedAssignments[0];
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => $fail['error_message'],
                    'data'    => [
                        'p_errorcode' => $fail['error_code'],
                    ],
                ], 200);
            }

            DB::commit();

            // Send notification email to assigned teacher(s)
            $mailResults = [];
            $skipMail = $request->input('send_mail') === false || $request->input('send_email') === false;

            if (!$skipMail) {
                $notifiedTeachers = [];

                foreach ($items as $assignedItem) {
                    $tId     = (int) ($assignedItem['teacher_id'] ?? 0);
                    $sem     = trim((string) ($assignedItem['semester_id'] ?? 'Part-I'));
                    $yr      = trim((string) ($assignedItem['examyear'] ?? '2026'));
                    $adminId = (int) ($assignedItem['entry_user_id'] ?? 1);

                    if ($tId <= 0) {
                        continue;
                    }

                    $uniqueKey = "{$adminId}_{$tId}_{$yr}_{$sem}";
                    if (!isset($notifiedTeachers[$uniqueKey])) {
                        $notifiedTeachers[$uniqueKey] = [
                            'admin_user_id' => $adminId,
                            'teacher_id'    => $tId,
                            'exam_year'     => $yr,
                            'semester'      => $sem,
                        ];
                    }
                }

                foreach ($notifiedTeachers as $target) {
                    try {
                        $detailsSql = 'SELECT public.fn_admin_getexternalexamineranswerscriptdetails(?::bigint, ?::bigint, ?::varchar, ?::varchar) AS data';
                        $detailsResult = DB::select($detailsSql, [
                            $target['admin_user_id'],
                            $target['teacher_id'],
                            $target['exam_year'],
                            $target['semester'],
                        ]);

                        if (empty($detailsResult) || !isset($detailsResult[0]->data)) {
                            Log::channel('daily')->warning("⚠️ [CEC Mail] No examiner script details found for teacher ID {$target['teacher_id']}");
                            $mailResults[] = [
                                'teacher_id' => $target['teacher_id'],
                                'status'     => 'no_details_found',
                            ];
                            continue;
                        }

                        $rawDetails = $detailsResult[0]->data;
                        $detailsData = is_string($rawDetails) ? json_decode($rawDetails, true) : (array) $rawDetails;

                        if (is_array($detailsData) && !empty($detailsData) && !isset($detailsData['p_errorcode'])) {
                            $first = $detailsData[0] ?? [];
                            $teacherEmail = trim((string) ($first['teacherEmail'] ?? ''));
                            $teacherName  = trim((string) ($first['teacherName'] ?? 'Examiner'));

                            // Fallback lookup from tbl_admin_users if teacherEmail is empty or invalid
                            if (empty($teacherEmail) || !filter_var($teacherEmail, FILTER_VALIDATE_EMAIL)) {
                                try {
                                    $userRow = DB::table('tbl_admin_users')
                                        ->where('au_id', $target['teacher_id'])
                                        ->first();
                                    if ($userRow) {
                                        $fallbackEmail = $userRow->au_email ?? $userRow->email ?? null;
                                        if (!empty($fallbackEmail) && filter_var($fallbackEmail, FILTER_VALIDATE_EMAIL)) {
                                            $teacherEmail = trim((string) $fallbackEmail);
                                        }
                                        if (empty($teacherName) || $teacherName === 'Examiner') {
                                            $teacherName = trim((string) ($userRow->au_fullname ?? $userRow->au_name ?? 'Examiner'));
                                        }
                                    }
                                } catch (\Throwable $userEx) {
                                    Log::channel('daily')->warning("⚠️ [CEC Mail] Fallback lookup in tbl_admin_users failed: " . $userEx->getMessage());
                                }
                            }

                            if (!empty($teacherEmail) && filter_var($teacherEmail, FILTER_VALIDATE_EMAIL)) {
                                $memoNumber = $first['memoNumber'] ?? null;
                                if (empty($memoNumber)) {
                                    foreach ($detailsData as $d) {
                                        if (!empty($d['memoNumber'])) {
                                            $memoNumber = $d['memoNumber'];
                                            break;
                                        }
                                    }
                                }

                                $assignmentsList = (isset($first['Assign']) && is_array($first['Assign']))
                                    ? $first['Assign']
                                    : $detailsData;

                                $mailPayload = [
                                    'teacher_name'        => $teacherName,
                                    'teacher_email'       => $teacherEmail,
                                    'teacher_phone'       => $first['teacherPhoneNumber'] ?? '',
                                    'teacher_designation' => $first['teacherDesignation'] ?? '',
                                    'teacher_inst_name'   => $first['teacherInstName'] ?? $first['teacherinstName'] ?? '',
                                    'teacher_inst_code'   => $first['teacherInstCode'] ?? '',
                                    'memo_number'         => $memoNumber,
                                    'exam_year'           => $target['exam_year'],
                                    'semester'            => $target['semester'],
                                    'assignments'         => $assignmentsList,
                                ];

                                Mail::to($teacherEmail)->send(new CECTeacherAssignmentMail($mailPayload));

                                $mailResults[] = [
                                    'teacher_id' => $target['teacher_id'],
                                    'email'      => $teacherEmail,
                                    'status'     => 'sent',
                                ];

                                Log::channel('daily')->info("📧 [CEC Mail] Assignment notification email successfully sent to {$teacherEmail} (Teacher ID: {$target['teacher_id']})");
                            } else {
                                Log::channel('daily')->warning("⚠️ [CEC Mail] Teacher ID {$target['teacher_id']} has no valid email ('{$teacherEmail}'). Skipping mail.");
                                $mailResults[] = [
                                    'teacher_id' => $target['teacher_id'],
                                    'email'      => $teacherEmail,
                                    'status'     => 'skipped_no_valid_email',
                                ];
                            }
                        } else {
                            Log::channel('daily')->warning("⚠️ [CEC Mail] fn_admin_getexternalexamineranswerscriptdetails returned error or empty list for teacher ID {$target['teacher_id']}:", [
                                'raw' => $rawDetails,
                            ]);
                            $mailResults[] = [
                                'teacher_id' => $target['teacher_id'],
                                'status'     => 'details_error',
                            ];
                        }
                    } catch (\Throwable $mailEx) {
                        Log::channel('daily')->error("❌ [CEC Mail] Exception sending assignment email to teacher ID {$target['teacher_id']}: " . $mailEx->getMessage(), [
                            'trace' => $mailEx->getTraceAsString(),
                        ]);
                        $mailResults[] = [
                            'teacher_id' => $target['teacher_id'],
                            'status'     => 'failed',
                            'error'      => $mailEx->getMessage(),
                        ];
                    }
                }
            }

            $hasMailSent = !empty($mailResults) && collect($mailResults)->contains('status', 'sent');

            $responseData = [
                'version' => '1.0',
                'status'  => 1,
                'message' => $isBulk
                    ? "Teacher assigned to {$successCount} answerscript(s) successfully"
                    : 'Teacher assigned to subject successfully',
                'data'    => [
                    'p_errorcode' => 0,
                    'mail_sent'   => $hasMailSent,
                    'mail_status' => $mailResults,
                ],
            ];

            Log::channel('daily')->info('✅ CEC teacher assignment completed successfully:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('🔥 EXCEPTION in saveTeacherAssignSubject (fn_cec_saveteacherassignsubject):', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => $e->getMessage(),
                'data'    => [
                    'p_errorcode' => 1,
                ],
            ], 500);
        }
    }
}
