<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CECSaveAnswerscriptIntakeTeacherController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/save-cec-answerscript-intake-teacher",
     *     tags={"CEC - Answer Scripts"},
     *     summary="Save CEC Answer Script Intake Teacher Assignment (Single or Bulk)",
     *     description="Saves teacher subject assignment for CEC answerscript intake by calling public.fn_admin_saveteacherassignsubject_v2(p_teacher_id, p_dept_id, p_semester_id, p_subject_category_id, p_subject_id, p_entry_user_id, p_inst_id, p_examinertype_id, p_examyear, p_subject_code)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"teacher_id", "subject_id"},
     *             @OA\Property(property="teacher_id", type="integer", example=661, description="Teacher / Evaluator User ID"),
     *             @OA\Property(property="dept_id", type="integer", example=1, description="Department ID (Default: 1 for PHARM)", default=1),
     *             @OA\Property(property="semester_id", type="string", example="Part-I", description="Semester / Part Name or Number (e.g. Part-I, Part-II, 1, 2)"),
     *             @OA\Property(property="subject_category_id", type="integer", example=1, description="Subject Category ID (Default: 1)", default=1),
     *             @OA\Property(property="subject_id", type="integer", example=3, description="Subject ID"),
     *             @OA\Property(property="subject_code", type="string", example="PHCE", description="Subject Code"),
     *             @OA\Property(property="entry_user_id", type="integer", example=1, description="Entry Admin User ID"),
     *             @OA\Property(property="inst_id", type="integer", example=170, description="Institute ID (Optional if inst_code or teacher's institute is available)"),
     *             @OA\Property(property="inst_code", type="string", example="JCG", description="Institute Code (Used to look up inst_id if inst_id not passed)"),
     *             @OA\Property(property="examinertype_id", type="integer", example=1, description="Examiner Type ID (Default: 1 for External, 2 for Internal)", default=1),
     *             @OA\Property(property="examyear", type="string", example="2026", description="Exam Year (Default: 2026)")
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
     * POST /api/admin/save-cec-answerscript-intake-teacher
     *
     * Calls: public.fn_admin_saveteacherassignsubject_v2(
     *   p_teacher_id,
     *   p_dept_id,
     *   p_semester_id,
     *   p_subject_category_id,
     *   p_subject_id,
     *   p_entry_user_id,
     *   p_inst_id,
     *   p_examinertype_id,
     *   p_examyear,
     *   p_subject_code
     * )
     */
    public function saveAnswerscriptIntakeTeacher(Request $request)
    {
        Log::channel('daily')->info('🚀 === SAVE CEC ANSWERSCRIPT INTAKE TEACHER API - REQUEST START ===');
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

        // Top-level fallback parameters
        $topLevelTeacherId = $request->input('teacher_id')
            ?? $request->input('p_teacher_id')
            ?? $request->input('teacherId');

        $topLevelDeptId = $request->input('dept_id')
            ?? $request->input('p_dept_id')
            ?? $request->input('deptId')
            ?? $request->input('department_id')
            ?? $request->input('departmentId')
            ?? 1; // Default to 1 (PHARM)

        $topLevelSemesterId = $request->input('semester_id')
            ?? $request->input('p_semester_id')
            ?? $request->input('semesterId')
            ?? $request->input('semester')
            ?? $request->input('p_semester');

        $topLevelSubjectCategoryId = $request->input('subject_category_id')
            ?? $request->input('p_subject_category_id')
            ?? $request->input('subjectCategoryId')
            ?? $request->input('category_id')
            ?? $request->input('categoryId')
            ?? 1;

        $topLevelSubjectId = $request->input('subject_id')
            ?? $request->input('p_subject_id')
            ?? $request->input('subjectId');

        $topLevelSubjectCode = $request->input('subject_code')
            ?? $request->input('p_subject_code')
            ?? $request->input('subjectCode');

        $topLevelInstId = $request->input('inst_id')
            ?? $request->input('p_inst_id')
            ?? $request->input('instId')
            ?? $request->input('institute_id')
            ?? $request->input('instituteId');

        $topLevelInstCode = $request->input('inst_code')
            ?? $request->input('p_inst_code')
            ?? $request->input('instCode')
            ?? $request->input('institute_code')
            ?? $request->input('instituteCode');

        $topLevelExaminerTypeId = $request->input('examinertype_id')
            ?? $request->input('p_examinertype_id')
            ?? $request->input('examiner_type_id')
            ?? $request->input('examinerTypeId')
            ?? $request->input('examiner_type')
            ?? 1;

        $topLevelExamYear = $request->input('examyear')
            ?? $request->input('exam_year')
            ?? $request->input('p_examyear')
            ?? $request->input('p_exam_year')
            ?? $request->input('examYear')
            ?? $request->input('year')
            ?? '2026';

        $topLevelEntryUserId = $request->input('entry_user_id')
            ?? $request->input('p_entry_user_id')
            ?? $request->input('entryUserId')
            ?? $request->input('admin_user_id')
            ?? $request->input('adminUserId')
            ?? $request->input('user_id')
            ?? $authUserId
            ?? 1;

        $items = [];

        // Check format 1: subjectList / subjects array with teacher_id at top level
        $rawSubjectList = $request->input('subjectList')
            ?? $request->input('subject_list')
            ?? $request->input('subjects');

        // Check format 2: items / assignments / data array of full records
        $rawItems = $request->input('items')
            ?? $request->input('assignments')
            ?? $request->input('data');

        if (is_array($rawSubjectList) && !empty($rawSubjectList)) {
            // Teacher with multiple subjects
            foreach ($rawSubjectList as $sub) {
                if (is_array($sub)) {
                    $items[] = [
                        'teacher_id'          => $sub['teacher_id'] ?? $topLevelTeacherId,
                        'dept_id'             => $sub['dept_id'] ?? $sub['department_id'] ?? $topLevelDeptId,
                        'semester_id'         => $sub['semester_id'] ?? $sub['semester'] ?? $topLevelSemesterId,
                        'subject_category_id' => $sub['subject_category_id'] ?? $sub['category_id'] ?? $topLevelSubjectCategoryId,
                        'subject_id'          => $sub['subject_id'] ?? $sub['id'] ?? null,
                        'subject_code'        => $sub['subject_code'] ?? $sub['subjectCode'] ?? $topLevelSubjectCode,
                        'entry_user_id'       => $sub['entry_user_id'] ?? $sub['admin_user_id'] ?? $topLevelEntryUserId,
                        'inst_id'             => $sub['inst_id'] ?? $topLevelInstId,
                        'inst_code'           => $sub['inst_code'] ?? $topLevelInstCode,
                        'examinertype_id'     => $sub['examinertype_id'] ?? $sub['examiner_type_id'] ?? $topLevelExaminerTypeId,
                        'examyear'            => $sub['examyear'] ?? $sub['exam_year'] ?? $topLevelExamYear,
                    ];
                } elseif (is_numeric($sub)) {
                    // Array of subject IDs
                    $items[] = [
                        'teacher_id'          => $topLevelTeacherId,
                        'dept_id'             => $topLevelDeptId,
                        'semester_id'         => $topLevelSemesterId,
                        'subject_category_id' => $topLevelSubjectCategoryId,
                        'subject_id'          => (int) $sub,
                        'subject_code'        => $topLevelSubjectCode,
                        'entry_user_id'       => $topLevelEntryUserId,
                        'inst_id'             => $topLevelInstId,
                        'inst_code'           => $topLevelInstCode,
                        'examinertype_id'     => $topLevelExaminerTypeId,
                        'examyear'            => $topLevelExamYear,
                    ];
                }
            }
        } elseif (is_array($rawItems) && !empty($rawItems)) {
            // Bulk records array
            foreach ($rawItems as $entry) {
                if (is_array($entry)) {
                    $items[] = [
                        'teacher_id'          => $entry['teacher_id'] ?? $entry['p_teacher_id'] ?? $topLevelTeacherId,
                        'dept_id'             => $entry['dept_id'] ?? $entry['p_dept_id'] ?? $topLevelDeptId,
                        'semester_id'         => $entry['semester_id'] ?? $entry['p_semester_id'] ?? $topLevelSemesterId,
                        'subject_category_id' => $entry['subject_category_id'] ?? $entry['p_subject_category_id'] ?? $topLevelSubjectCategoryId,
                        'subject_id'          => $entry['subject_id'] ?? $entry['p_subject_id'] ?? null,
                        'subject_code'        => $entry['subject_code'] ?? $entry['p_subject_code'] ?? $entry['subjectCode'] ?? $topLevelSubjectCode,
                        'entry_user_id'       => $entry['entry_user_id'] ?? $entry['p_entry_user_id'] ?? $topLevelEntryUserId,
                        'inst_id'             => $entry['inst_id'] ?? $entry['p_inst_id'] ?? $topLevelInstId,
                        'inst_code'           => $entry['inst_code'] ?? $entry['p_inst_code'] ?? $topLevelInstCode,
                        'examinertype_id'     => $entry['examinertype_id'] ?? $entry['examiner_type_id'] ?? $topLevelExaminerTypeId,
                        'examyear'            => $entry['examyear'] ?? $entry['exam_year'] ?? $topLevelExamYear,
                    ];
                }
            }
        } elseif (is_array($request->all()) && isset($request->all()[0]) && is_array($request->all()[0])) {
            // Top-level indexed array of records
            foreach ($request->all() as $entry) {
                if (is_array($entry)) {
                    $items[] = [
                        'teacher_id'          => $entry['teacher_id'] ?? $entry['p_teacher_id'] ?? null,
                        'dept_id'             => $entry['dept_id'] ?? $entry['p_dept_id'] ?? 1,
                        'semester_id'         => $entry['semester_id'] ?? $entry['p_semester_id'] ?? null,
                        'subject_category_id' => $entry['subject_category_id'] ?? $entry['p_subject_category_id'] ?? 1,
                        'subject_id'          => $entry['subject_id'] ?? $entry['p_subject_id'] ?? null,
                        'subject_code'        => $entry['subject_code'] ?? $entry['p_subject_code'] ?? $entry['subjectCode'] ?? null,
                        'entry_user_id'       => $entry['entry_user_id'] ?? $entry['p_entry_user_id'] ?? $authUserId ?? 1,
                        'inst_id'             => $entry['inst_id'] ?? $entry['p_inst_id'] ?? null,
                        'inst_code'           => $entry['inst_code'] ?? $entry['p_inst_code'] ?? null,
                        'examinertype_id'     => $entry['examinertype_id'] ?? $entry['examiner_type_id'] ?? 1,
                        'examyear'            => $entry['examyear'] ?? $entry['exam_year'] ?? '2026',
                    ];
                }
            }
        } else {
            // Single record
            $items[] = [
                'teacher_id'          => $topLevelTeacherId,
                'dept_id'             => $topLevelDeptId,
                'semester_id'         => $topLevelSemesterId,
                'subject_category_id' => $topLevelSubjectCategoryId,
                'subject_id'          => $topLevelSubjectId,
                'subject_code'        => $topLevelSubjectCode,
                'entry_user_id'       => $topLevelEntryUserId,
                'inst_id'             => $topLevelInstId,
                'inst_code'           => $topLevelInstCode,
                'examinertype_id'     => $topLevelExaminerTypeId,
                'examyear'            => $topLevelExamYear,
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

        // Cache institute ID lookups
        $instIdCache = [];

        // Validate and resolve each item
        $isBulk = count($items) > 1;
        $rules = [
            'teacher_id'          => 'required|integer',
            'dept_id'             => 'nullable|integer',
            'semester_id'         => 'required',
            'subject_category_id' => 'nullable|integer',
            'subject_id'          => 'required|integer',
            'entry_user_id'       => 'required|integer',
            'inst_id'             => 'nullable|integer',
            'examinertype_id'     => 'nullable',
            'examyear'            => 'required',
        ];

        foreach ($items as $index => &$item) {
            // Resolve Institute ID if missing
            if (empty($item['inst_id'])) {
                if (!empty($item['inst_code'])) {
                    $code = strtoupper(trim((string) $item['inst_code']));
                    if (!isset($instIdCache[$code])) {
                        $instRow = DB::table('tbl_institute_master')
                            ->where('im_code', $code)
                            ->select('im_id')
                            ->first();
                        $instIdCache[$code] = $instRow ? (int) $instRow->im_id : null;
                    }
                    $item['inst_id'] = $instIdCache[$code];
                }

                // If still missing, try looking up from tbl_admin_users by teacher_id
                if (empty($item['inst_id']) && !empty($item['teacher_id'])) {
                    $teacherRow = DB::table('tbl_admin_users')
                        ->where('au_id', (int) $item['teacher_id'])
                        ->select('au_inst_id')
                        ->first();
                    if ($teacherRow && !empty($teacherRow->au_inst_id)) {
                        $item['inst_id'] = (int) $teacherRow->au_inst_id;
                    }
                }
            }

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

            $validator = Validator::make($item, $rules);
            if ($validator->fails()) {
                Log::channel('daily')->error("❌ VALIDATION FAILED on CEC intake teacher assignment item {$index}:", [
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

            if (empty($item['inst_id'])) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => ($isBulk ? "Item {$index}: " : '') . 'Institute ID is required and could not be determined.',
                    'data'    => ['p_errorcode' => 1],
                ], 400);
            }
        }
        unset($item);

        $sql = "SELECT public.fn_admin_saveteacherassignsubject_v2(?::bigint, ?::integer, ?::varchar, ?::integer, ?::integer, ?::bigint, ?::bigint, ?::integer, ?::varchar, ?::varchar, ?::varchar) AS result";

        DB::beginTransaction();
        try {
            $successCount = 0;
            $duplicateCount = 0;
            $failedAssignments = [];
            $results = [];
            $lastResult = null;

            foreach ($items as $index => $item) {
                $teacherId         = (int) $item['teacher_id'];
                $deptId            = (int) ($item['dept_id'] ?? 1);
                $rawSem            = trim((string) $item['semester_id']);

                // Normalize semester_id if necessary
                if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
                    $semesterId = 'Part-I';
                } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
                    $semesterId = 'Part-II';
                } else {
                    $semesterId = $rawSem;
                }

                $subjectCategoryId = (int) ($item['subject_category_id'] ?? 1);
                $subjectId         = (int) $item['subject_id'];
                $subjectCode       = trim((string) ($item['subject_code'] ?? ''));
                if ($subjectCode === '' && !empty($subjectId)) {
                    $subRow = DB::table('tbl_department_subjects_master')->where('dsm_id', $subjectId)->select('dsm_subject_id')->first();
                    $subjectCode = $subRow ? trim((string) $subRow->dsm_subject_id) : '';
                }

                $entryUserId       = (int) $item['entry_user_id'];
                $instId            = (int) $item['inst_id'];
                $instCode          = trim((string) ($item['inst_code'] ?? ''));
                if ($instCode === '' && !empty($instId)) {
                    $instRow = DB::table('tbl_institute_master')->where('im_id', $instId)->select('im_code')->first();
                    if ($instRow && !empty($instRow->im_code)) {
                        $instCode = (string) $instRow->im_code;
                    } else {
                        $instRow2 = DB::table('institute_master')->where('i_id', $instId)->select('i_code')->first();
                        if ($instRow2 && !empty($instRow2->i_code)) {
                            $instCode = (string) $instRow2->i_code;
                        }
                    }
                }
                $examinerTypeId    = (int) ($item['examinertype_id'] ?? 1);
                $examYear          = trim((string) ($item['examyear'] ?? '2026'));

                Log::channel('daily')->info("📤 Calling fn_admin_saveteacherassignsubject_v2 [Item {$index}] with parameters:", [
                    'p_teacher_id'          => $teacherId,
                    'p_dept_id'             => $deptId,
                    'p_semester_id'         => $semesterId,
                    'p_subject_category_id' => $subjectCategoryId,
                    'p_subject_id'          => $subjectId,
                    'p_entry_user_id'       => $entryUserId,
                    'p_inst_id'             => $instId,
                    'p_examinertype_id'     => $examinerTypeId,
                    'p_examyear'            => $examYear,
                    'p_subject_code'        => $subjectCode,
                    'p_inst_code'           => $instCode,
                ]);

                $spResult = DB::selectOne($sql, [
                    $teacherId,
                    $deptId,
                    $semesterId,
                    $subjectCategoryId,
                    $subjectId,
                    $entryUserId,
                    $instId,
                    $examinerTypeId,
                    $examYear,
                    $subjectCode,
                    $instCode,
                ]);

                if (!$spResult || !isset($spResult->result)) {
                    throw new \Exception("No response received from fn_admin_saveteacherassignsubject_v2 for subject ID {$subjectId}");
                }

                $raw = $spResult->result;
                $parsed = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Failed to parse database response for subject ID {$subjectId}: {$raw}");
                }

                $errorCode = isset($parsed['p_errorcode']) ? (int) $parsed['p_errorcode'] : -1;

                if ($errorCode === 0) {
                    $successCount++;
                    $lastResult = $parsed;
                    $results[] = [
                        'teacher_id' => $teacherId,
                        'subject_id' => $subjectId,
                        'status'     => 'success',
                        'result'     => $parsed,
                    ];
                    Log::channel('daily')->info("✅ Item #{$index} assigned successfully", ['subject_id' => $subjectId]);
                } else {
                    $errorMsg = $errorCode === 200
                        ? "Teacher already assigned to this subject and institute for exam year {$examYear}"
                        : ($parsed['p_errormsg'] ?? "Database function returned error code {$errorCode}");

                    if ($errorCode === 200) {
                        $duplicateCount++;
                    }

                    Log::channel('daily')->warning("⚠️ Item #{$index} assignment returned error code {$errorCode}: {$errorMsg}", [
                        'item'   => $item,
                        'parsed' => $parsed,
                    ]);

                    $failedAssignments[] = [
                        'teacher_id'    => $teacherId,
                        'subject_id'    => $subjectId,
                        'error_code'    => $errorCode,
                        'error_message' => $errorMsg,
                        'result'        => $parsed,
                    ];
                }
            }

            // If any item failed or is duplicate, rollback and return clean error
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

            $responseData = [
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Teacher assigned to subject successfully',
                'data'    => [
                    'p_errorcode' => 0,
                ],
            ];

            Log::channel('daily')->info('✅ CEC teacher assignment completed successfully:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('🔥 EXCEPTION in saveAnswerscriptIntakeTeacher:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => ['p_errorcode' => 1],
            ], 500);
        }
    }
}
