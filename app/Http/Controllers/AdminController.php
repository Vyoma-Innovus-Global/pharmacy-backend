<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Registerstudent;
use Exception;
use App\Models\Otp;
use App\Models\Token;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use App\Models\SuperUser;
use App\Http\Resources\SuperUserResource;
use App\Models\Role;
use Illuminate\Support\Facades\URL;
use App\Models\State;
use App\Http\Resources\StateResource;
use App\Models\District;
use App\Http\Resources\DistrictResource;
use App\Models\Subdivision;
use App\Http\Resources\SubdivisionResource;
use App\Models\BlockMunicipality;
use App\Http\Resources\BlockMunicipalityResource;
use App\Models\CouncilBoard;
use App\Http\Resources\CouncilBoardResource;

class AdminController extends Controller
{
    public function studentListPone(Request $request)
    {

                    try {
                        $collegeId = $request->collegeId;
                        $i_code = $request->i_code;
                        $sess_year = $request->sess_year;
                        $part_sem = $request->part_sem;

                        $query = Registerstudent::where('is_registration_payment', 1)
                                ->where('is_active', 1)
                                ->where('s_part_sem', $part_sem)
                                ->where('s_appl_sess_year', $sess_year);

                                if ($i_code) {
                                   $query = $query->where('s_inst_code', $i_code);
                                }

                                $query = $query->select([
                                's_id',
                                's_appl_reg_no',
                                's_appl_reg_year',
                                's_candidate_name',
                                's_appl_form_num',
                                's_inst_code',
                                'admission_type',
                                's_phone',
                                's_email'
                                ])->get();

                        if (!$query) {
                            return response()->json([
                                'error' => true,
                                'message' => 'No Students Found.',
                            ], 409);
                        }

                        return response()->json([
                            'studentList' => $query,
                            'role'=> $collegeId,
                        ]);
                    } catch (\Exception $e) {
                        return response()->json(['error' => $e->getMessage()], 500);
                    }


    }

    //Syllabus Subject Master
    public function syllabusSubjectList($semester)
    {
        try {
            $paperList = Subject::query()
                        ->select('syllabus','syllabus_id','dept','semester','subject_name','subject_sn', 'general_code','subject_type','type','paper_type','q_code','exam_hours')
                        ->where('is_active', 1);
                        if($semester !=="ALL"){
                            $paperList = $paperList->where('semester', $semester);
                        }
                        $paperList = $paperList->orderBy('subject_sn', 'asc')->get();
                return response()->json([
                    'status' => 'success',
                    'data' => $paperList,
                ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save admin bank information using PostgreSQL function fn_admin_savebankinfo.
     *
     * POST /admin/save-bank-info
     */
    public function saveBankInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'          => 'required|integer',
            'bank_id'          => 'required|integer',
            'branch_id'        => 'required|integer',
            'bank_name'        => 'required|string|max:255',
            'branch_name'      => 'required|string|max:255',
            'ifsc'             => 'required|string|max:50',
            'acc_no'           => 'required|string|max:100',
            'acc_holder_name'  => 'required|string|max:255',
            'entry_user_id'    => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $params = [
                $request->input('user_id'),
                $request->input('bank_id'),
                $request->input('branch_id'),
                $request->input('bank_name'),
                $request->input('branch_name'),
                $request->input('ifsc'),
                $request->input('acc_no'),
                $request->input('acc_holder_name'),
                $request->input('entry_user_id'),
            ];

            $result = DB::select(
                'SELECT public.fn_admin_savebankinfo(?, ?, ?, ?, ?, ?, ?, ?, ?) AS data',
                $params
            );

            if (empty($result)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No result returned from function.',
                ], 500);
            }

            $raw = $result[0]->data ?? null;
            $responseData = is_string($raw) ? json_decode($raw, true) : $raw;

            return response()->json([
                'error' => false,
                'data'  => $responseData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get admin bank information using PostgreSQL function fn_admin_getbankinfo.
     *
    * POST /admin/get-bank-info
     */
    public function getBankInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $adminUserId = (int) $request->input('admin_user_id');
            $result = DB::select(
                'SELECT public.fn_admin_getbankinfo(?::bigint) AS data',
                [$adminUserId]
            );

            if (empty($result) || !isset($result[0]->data)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No bank info found for the given admin user ID.',
                ], 404);
            }

            $raw = $result[0]->data;
            $bankInfo = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Failed to parse bank info from database.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'data'  => $bankInfo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bank information by IFSC code using PostgreSQL function fn_admin_getbankinfobyifsc.
     *
     * POST /admin/get-bank-info-by-ifsc
     */
    public function getBankInfoByIfsc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ifsc' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $ifsc = $request->input('ifsc');
            $result = DB::select(
                'SELECT public.fn_admin_getbankinfobyifsc(?::text) AS data',
                [$ifsc]
            );

            if (empty($result) || !isset($result[0]->data)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No bank info found for the given IFSC.',
                ], 404);
            }

            $raw = $result[0]->data;
            $bankInfo = is_string($raw) ? json_decode($raw, true) : $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Failed to parse bank info from database.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'data'  => $bankInfo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get admin dashboard summary using PostgreSQL function fn_admin_getdashboard.
     *
     * POST /admin/dashboard
     */
    public function getDashboard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
            'semester_id' => 'required|integer',
            'exam_year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $adminUserId = (int) $request->input('admin_user_id');
            $semesterId = (int) $request->input('semester_id');
            $examYear = (int) $request->input('exam_year');

            $result = DB::select(
                'SELECT public.fn_admin_getdashboard(?::bigint, ?::integer, ?::integer) AS data',
                [$adminUserId, $semesterId, $examYear]
            );

            if (empty($result) || !isset($result[0]->data)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No dashboard data found.',
                ], 404);
            }

            $raw = $result[0]->data;
            $dashboardData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Failed to parse dashboard data from database.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'data'  => $dashboardData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get entered student marks info using PostgreSQL function fn_admin_getenteredstudentmarksinfo.
     *
     * POST /admin/get-entered-student-marks-info
     */
    public function getEnteredStudentMarksInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id'     => 'required|integer',
            'teacher_id'        => 'required|integer',
            'student_id'        => 'required|integer',
            'evaluator_type_id' => 'required|integer',
            'inst_code'         => 'required|string|max:50',
            'dept_code'         => 'required|string|max:50',
            'subject_code'      => 'required|string|max:50',
            'exam_year'         => 'required|integer',
            'semester'          => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getenteredstudentmarksinfo(?::bigint, ?::bigint, ?::bigint, ?::bigint, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::integer) AS data',
                [
                    (int) $request->input('admin_user_id'),
                    (int) $request->input('teacher_id'),
                    (int) $request->input('student_id'),
                    (int) $request->input('evaluator_type_id'),
                    $request->input('inst_code'),
                    $request->input('dept_code'),
                    $request->input('subject_code'),
                    (int) $request->input('exam_year'),
                    (int) $request->input('semester'),
                ]
            );

            if (empty($result) || !isset($result[0]->data)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No entered student marks info found.',
                ], 404);
            }

            $raw = $result[0]->data;
            $marksInfo = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Failed to parse entered student marks info from database.',
                ], 500);
            }

            return response()->json([
                'error' => false,
                'data'  => $marksInfo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function allStates(Request $request, $type = null)
    {
        try {

            if (is_null($type)) {
                $state_list = State::select('state_id_pk', 'state_name')
                                    ->where('active_status', '1')
                                    ->orderBy('state_name', 'DESC')
                                    ->get();

                if ($state_list->isNotEmpty()) {
                    return response()->json([
                        'error'   => false,
                        'message' => 'State found',
                        'count'   => $state_list->count(),
                        'states'  => StateResource::collection($state_list),
                    ], 200);
                } else {
                    return response()->json([
                        'error'   => true,
                        'message' => 'No state available',
                    ], 404);
                }
            } else {
                $state_list = State::select('state_id_pk', 'state_name')
                    ->where('active_status', '1')
                    ->orderBy('state_id_pk', 'DESC')
                    ->get();

                if ($state_list->isNotEmpty()) {
                    return response()->json([
                        'error'   => false,
                        'message' => 'State found',
                        'count'   => $state_list->count(),
                        'states'  => StateResource::collection($state_list),
                    ], 200);
                } else {
                    return response()->json([
                        'error'   => true,
                        'message' => 'No state available',
                    ], 404);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function allDistricts(Request $request, $state_code = null, $type = null)
    {
        try {
            //$state_code=$request->state_code;
            //dd($state_code);
            $query = District::with('state:state_id_pk,state_name');

            if (!is_null($state_code)) {
                $query->where('state_id_fk', $state_code);
            }

            $district_list = $query->orderBy('district_name', 'DESC')->get();

            if ($district_list->isNotEmpty()) {
                return response()->json([
                    'error'     => false,
                    'message'   => 'District found',
                    'count'     => $district_list->count(),
                    'districts' => DistrictResource::collection($district_list),
                ], 200);
            } else {
                return response()->json([
                    'error'   => true,
                    'message' => 'No district available',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function allSubdivisions(Request $request, $dist_id = null, $type = null)
    {
        try {

            if ($type === null) {
                if ($dist_id) {
                    $subdivision_list = Subdivision::with('district:district_id_pk,district_name')
                        ->where('active_status', '1')
                        ->where('district_id', $dist_id)
                        ->orderBy('name', 'DESC')
                        ->get();
                } else {
                    $subdivision_list = Subdivision::with('district:district_id_pk,district_name')
                        ->where('active_status', '1')
                        ->orderBy('name', 'DESC')
                        ->get();
                }

                if ($subdivision_list->isNotEmpty()) {
                    return response()->json([
                        'error'         => false,
                        'message'       => 'Subdivision found',
                        'count'         => $subdivision_list->count(),
                        'subdivisions'  => SubdivisionResource::collection($subdivision_list),
                    ], 200);
                } else {
                    return response()->json([
                        'error'     => true,
                        'message'   => 'No subdivision available',
                    ], 404);
                }
            }



            return response()->json([
                'error'     => true,
                'message'   => 'Invalid request type',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error'     => true,
                'message'   => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }
   function allBlockMunicipality(Request $request){
       try {
            $district_id=$request->district_id;
            //subdiv_id=district_id
            $query = BlockMunicipality::where('active_status','1')->where('district_id', $district_id)->orderBy('name', 'DESC')->get();



            if ($query->isNotEmpty()) {
                return response()->json([
                    'error'     => false,
                    'message'   => 'Block or Municipality found',
                    'count'     => $query->count(),
                    'block_municipality' => BlockMunicipalityResource::collection($query),
                ], 200);
            } else {
                return response()->json([
                    'error'   => true,
                    'message' => 'No Block or Municipality available',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function studentsBoardList()
    {
        try {
           $board_list = CouncilBoard::orderBy('name', 'ASC')->get();

            if (sizeof($board_list) > 0) {
                return response([
                    'error' => false,
                    'message' => 'State found',
                    'list' => CouncilBoardResource::collection($board_list)
                ], 200);
            } else {
                return response()->json([
                    'error'     =>  true,
                    'message'   =>  'No Data available'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save user/teacher information.
     *
     * POST /api/admin/save-user-info
     *
     * Calls: public.fn_admin_saveuserinfo(
     *   p_in_user_id BIGINT,
     *   p_role_id INTEGER,
     *   p_full_name VARCHAR,
     *   p_contact_no VARCHAR,
     *   p_email VARCHAR,
     *   p_highest_qualification VARCHAR,
     *   p_aadhar_no VARCHAR,
     *   p_inst_id INTEGER,
     *   p_inst_name VARCHAR,
     *   p_designation_id VARCHAR,
     *   p_image TEXT,
     *   p_remarks TEXT,
     *   p_entry_user_id BIGINT
     * )
     */
    public function saveUserInfo(Request $request)
    {
        $inUserId = $request->input('in_user_id', $request->input('user_id', $request->input('p_in_user_id', 0)));
        $roleId = $request->input('role_id', $request->input('p_role_id'));
        $fullName = $request->input('full_name', $request->input('p_full_name'));
        $contactNo = $request->input('contact_no', $request->input('phone', $request->input('p_contact_no')));
        $email = $request->input('email', $request->input('p_email'));
        $highestQualification = $request->input('highest_qualification', $request->input('qualification', $request->input('p_highest_qualification')));
        $aadharNo = $request->input('aadhar_no', $request->input('aadhaar_no', $request->input('p_aadhar_no')));
        $instId = $request->input('inst_id', $request->input('institute_id', $request->input('p_inst_id')));
        $instName = $request->input('inst_name', $request->input('institute_name', $request->input('p_inst_name')));
        $designationId = $request->input('designation_id', $request->input('p_designation_id'));
        $image = $request->input('image', $request->input('pic', $request->input('p_image')));
        $remarks = $request->input('remarks', $request->input('p_remarks'));
        $entryUserId = $request->input('entry_user_id', $request->input('admin_user_id', $request->input('p_entry_user_id', auth()->id() ?? 0)));

        $validator = Validator::make([
            'in_user_id'             => $inUserId,
            'role_id'                => $roleId,
            'full_name'              => $fullName,
            'contact_no'             => $contactNo,
            'email'                  => $email,
            'highest_qualification'  => $highestQualification,
            'aadhar_no'              => $aadharNo,
            'inst_id'                => $instId,
            'inst_name'              => $instName,
            'designation_id'         => $designationId,
            'entry_user_id'          => $entryUserId,
        ], [
            'in_user_id'             => 'nullable|integer',
            'role_id'                => 'required|integer',
            'full_name'              => 'required|string|max:255',
            'contact_no'             => 'required|string|max:20',
            'email'                  => 'nullable|email|max:255',
            'highest_qualification'  => 'nullable|string|max:100',
            'aadhar_no'              => 'nullable|string|max:20',
            'inst_id'                => 'required|integer',
            'inst_name'              => 'nullable|string|max:255',
            'designation_id'         => 'required',
            'entry_user_id'          => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $sql = 'SELECT public.fn_admin_saveuserinfo(?::bigint, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::varchar, ?::varchar, ?::text, ?::text, ?::bigint) AS result';

            $bindings = [
                $inUserId ? (int)$inUserId : 0,
                (int)$roleId,
                trim($fullName),
                trim($contactNo),
                $email ? trim($email) : null,
                $highestQualification ? trim($highestQualification) : null,
                $aadharNo ? trim($aadharNo) : null,
                (int)$instId,
                $instName ? trim($instName) : null,
                (string)$designationId,
                $image ? (string)$image : null,
                $remarks ? (string)$remarks : null,
                (int)$entryUserId,
            ];

            $res = DB::selectOne($sql, $bindings);

            if (!$res || !$res->result) {
                return response()->json([
                    'status'  => 0,
                    'error'   => true,
                    'message' => 'Failed to save user information: No response returned from database',
                ], 500);
            }

            $data = is_string($res->result) ? json_decode($res->result, true) : (array)$res->result;

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status'  => 0,
                    'error'   => true,
                    'message' => 'Failed to parse database response',
                ], 500);
            }

            $errorCode = $data['p_errorcode'] ?? ($data['errorcode'] ?? 0);
            $message = $data['p_message'] ?? ($data['message'] ?? 'User information saved successfully.');

            if ((int)$errorCode !== 0) {
                return response()->json([
                    'status'  => 0,
                    'error'   => true,
                    'message' => $message,
                    'data'    => $data,
                ], 400);
            }

            return response()->json([
                'status'  => 1,
                'error'   => false,
                'message' => $message,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign subject to a user using public.fn_admin_saveuserassignsubject_v2
     * Supports single assignment object or array of subjects under "subjects"
     *
     * POST /api/admin/save-user-assign-subject-v2
     *
     * Calls: public.fn_admin_saveuserassignsubject_v2(
     *   p_user_id BIGINT,
     *   p_role_id INTEGER,
     *   p_dept_id INTEGER,
     *   p_semester_id VARCHAR,
     *   p_subject_category_id INTEGER,
     *   p_subject_id INTEGER,
     *   p_entry_user_id BIGINT,
     *   p_inst_id BIGINT,
     *   p_examinertype_id INTEGER,
     *   p_examyear VARCHAR,
     *   p_subject_code VARCHAR,
     *   p_inst_code VARCHAR
     * )
     */
    public function saveUserAssignSubjectV2(Request $request)
    {
        $userId = $request->input('user_id', $request->input('p_user_id'));
        $roleId = $request->input('role_id', $request->input('p_role_id'));
        $entryUserId = $request->input('entry_user_id', $request->input('p_entry_user_id', auth()->id() ?? 0));
        $instId = $request->input('inst_id', $request->input('institute_id', $request->input('p_inst_id', 0)));
        $instCode = $request->input('inst_code', $request->input('institute_code', $request->input('p_inst_code')));
        $examYear = $request->input('exam_year', $request->input('examyear', $request->input('p_examyear')));
        $examinerTypeId = $request->input('examinertype_id', $request->input('examiner_type_id', $request->input('p_examinertype_id')));

        // Check if multiple subjects passed or single subject
        $rawSubjects = $request->input('subjects', $request->input('subject_list'));

        $items = [];
        if (!empty($rawSubjects) && is_array($rawSubjects)) {
            $items = $rawSubjects;
        } else {
            $items = [[
                'dept_id'             => $request->input('dept_id', $request->input('p_dept_id')),
                'semester_id'         => $request->input('semester_id', $request->input('semester', $request->input('p_semester_id'))),
                'subject_category_id' => $request->input('subject_category_id', $request->input('category_id', $request->input('p_subject_category_id'))),
                'subject_id'          => $request->input('subject_id', $request->input('p_subject_id')),
                'subject_code'        => $request->input('subject_code', $request->input('p_subject_code')),
                'examinertype_id'     => $examinerTypeId,
                'exam_year'           => $examYear,
                'inst_id'             => $instId,
                'inst_code'           => $instCode,
            ]];
        }

        $validator = Validator::make([
            'user_id'       => $userId,
            'role_id'       => $roleId,
            'entry_user_id' => $entryUserId,
            'items'         => $items,
        ], [
            'user_id'       => 'required|integer',
            'role_id'       => 'required|integer',
            'entry_user_id' => 'required|integer',
            'items'         => 'required|array|min:1',
            'items.*.dept_id'     => 'required|integer',
            'items.*.semester_id' => 'required|string',
            'items.*.subject_id'  => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $sql = 'SELECT public.fn_admin_saveuserassignsubject_v2(?::bigint, ?::integer, ?::integer, ?::varchar, ?::integer, ?::integer, ?::bigint, ?::bigint, ?::integer, ?::varchar, ?::varchar, ?::varchar) AS result';

            $results = [];
            $hasError = false;
            $firstErrorMessage = null;

            foreach ($items as $index => $item) {
                $curDeptId = $item['dept_id'] ?? $request->input('dept_id', $request->input('p_dept_id'));
                $curSem = $item['semester_id'] ?? $item['semester'] ?? $request->input('semester_id', $request->input('p_semester_id'));
                $curCatId = $item['subject_category_id'] ?? $item['category_id'] ?? $request->input('subject_category_id', $request->input('p_subject_category_id'));
                $curSubId = $item['subject_id'] ?? $request->input('subject_id', $request->input('p_subject_id'));
                $curSubCode = $item['subject_code'] ?? $request->input('subject_code', $request->input('p_subject_code'));
                $curExamTypeId = $item['examinertype_id'] ?? $item['examiner_type_id'] ?? $examinerTypeId;
                $curExamYear = $item['exam_year'] ?? $item['examyear'] ?? $examYear;
                $curInstId = $item['inst_id'] ?? $instId;
                $curInstCode = $item['inst_code'] ?? $instCode;

                $bindings = [
                    (int)$userId,
                    (int)$roleId,
                    (int)$curDeptId,
                    (string)$curSem,
                    $curCatId ? (int)$curCatId : null,
                    (int)$curSubId,
                    (int)$entryUserId,
                    $curInstId ? (int)$curInstId : 0,
                    $curExamTypeId ? (int)$curExamTypeId : null,
                    $curExamYear ? (string)$curExamYear : null,
                    $curSubCode ? (string)$curSubCode : null,
                    $curInstCode ? (string)$curInstCode : null,
                ];

                $dbRes = DB::selectOne($sql, $bindings);

                if (!$dbRes || !$dbRes->result) {
                    $hasError = true;
                    $firstErrorMessage = "Failed to assign subject (Item {$index})";
                    $results[] = [
                        'item_index' => $index,
                        'p_errorcode' => 1,
                        'p_message' => 'No response returned from database',
                    ];
                    continue;
                }

                $data = is_string($dbRes->result) ? json_decode($dbRes->result, true) : (array)$dbRes->result;
                $errCode = $data['p_errorcode'] ?? ($data['errorcode'] ?? 0);

                if ((int)$errCode !== 0) {
                    $hasError = true;
                    if (!$firstErrorMessage) {
                        $firstErrorMessage = $data['p_message'] ?? 'Error assigning subject';
                    }
                }

                $results[] = array_merge(['item_index' => $index], $data);
            }

            if ($hasError && count($items) === 1) {
                return response()->json([
                    'status'  => 0,
                    'error'   => true,
                    'message' => $firstErrorMessage ?? 'Subject assignment failed',
                    'data'    => $results[0] ?? [],
                ], 400);
            }

            return response()->json([
                'status'  => $hasError ? 0 : 1,
                'error'   => $hasError,
                'message' => $hasError ? ($firstErrorMessage ?? 'Some subjects could not be assigned.') : 'Subject(s) assigned successfully',
                'data'    => count($items) === 1 ? $results[0] : $results,
            ], $hasError ? 400 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user institute allocation summary using public.fn_admin_getuserinstallocationsummary
     *
     * POST /api/admin/get-user-inst-allocation-summary
     *
     * Calls: public.fn_admin_getuserinstallocationsummary(
     *   p_admin_user_id BIGINT,
     *   p_user_type_id BIGINT,
     *   p_evaluator_type_id INTEGER,
     *   p_exam_year INTEGER,
     *   p_semester INTEGER
     * )
     */
    public function getUserInstAllocationSummary(Request $request)
    {
        $adminUserId     = $request->input('admin_user_id', $request->input('user_id', $request->input('p_admin_user_id', 0)));
        $userTypeId      = $request->input('user_type_id', $request->input('role_id', $request->input('p_user_type_id', 0)));
        $evaluatorTypeId = $request->input('evaluator_type_id', $request->input('examinertype_id', $request->input('p_evaluator_type_id')));
        $examYear        = $request->input('exam_year', $request->input('examyear', $request->input('p_exam_year')));
        $semester        = $request->input('semester', $request->input('semester_id', $request->input('p_semester', 0)));

        // Handle string semester like 'Part-I' or 'Part-II'
        if (is_string($semester)) {
            $semLower = strtolower(trim($semester));
            if ($semLower === 'part-i' || $semLower === 'part 1' || $semLower === '1') {
                $semester = 1;
            } elseif ($semLower === 'part-ii' || $semLower === 'part 2' || $semLower === '2') {
                $semester = 2;
            } else {
                $semester = (int) $semester;
            }
        }

        $validator = Validator::make([
            'admin_user_id'     => $adminUserId,
            'user_type_id'      => $userTypeId,
            'evaluator_type_id' => $evaluatorTypeId,
            'exam_year'         => $examYear,
            'semester'          => $semester,
        ], [
            'admin_user_id'     => 'nullable|integer',
            'user_type_id'      => 'nullable|integer',
            'evaluator_type_id' => 'required|integer',
            'exam_year'         => 'required|integer',
            'semester'          => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $sql = 'SELECT public.fn_admin_getuserinstallocationsummary(?::bigint, ?::bigint, ?::integer, ?::integer, ?::integer) AS data';

            $bindings = [
                (int) $adminUserId,
                (int) $userTypeId,
                (int) $evaluatorTypeId,
                (int) $examYear,
                (int) $semester,
            ];

            $result = DB::selectOne($sql, $bindings);

            if (!$result || !isset($result->data)) {
                return response()->json([
                    'status'  => 1,
                    'error'   => false,
                    'message' => 'No data found.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result->data;
            $rows = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status'  => 0,
                    'error'   => true,
                    'message' => 'Failed to parse database response.',
                ], 500);
            }

            // Optional grouping similar to evaluator allocation summary
            $grouped = [
                1 => [], // internal
                2 => [], // external
                3 => [], // other
            ];

            foreach ($rows as $row) {
                $typeId = (int)($row['assignedEvaluatorTypeId'] ?? 0);
                $instId = $row['assignedInstId'] ?? null;
                $deptId = $row['assignedDepartmentId'] ?? null;

                if (!isset($grouped[$typeId])) {
                    $grouped[$typeId] = [];
                }

                if ($instId !== null && !isset($grouped[$typeId][$instId])) {
                    $grouped[$typeId][$instId] = [
                        'inst_id'            => $instId,
                        'inst_code'          => $row['assignedInstCode'] ?? null,
                        'inst_name'          => $row['assignedInstName'] ?? null,
                        'pending_department' => (int)($row['pending_department'] ?? 0),
                        'departments'        => [],
                    ];
                }

                if ($instId !== null && $deptId !== null) {
                    $grouped[$typeId][$instId]['departments'][] = [
                        'dept_id'                => $deptId,
                        'dept_code'              => $row['assignedDepartmentCode'] ?? null,
                        'dept_name'              => $row['assignedDepartmentName'] ?? null,
                        'total_subjects'         => (int)($row['totalSubjects'] ?? 0),
                        'total_pending_subjects' => (int)($row['totalPendingSubjects'] ?? 0),
                    ];
                }
            }

            return response()->json([
                'status'  => 1,
                'error'   => false,
                'message' => 'Data fetch successfully',
                'raw_list' => $rows,
                'data'    => [
                    'internal_institutes' => array_values($grouped[1] ?? []),
                    'external_institutes' => array_values($grouped[2] ?? []),
                    'other_institutes'    => array_values($grouped[3] ?? []),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user subject allocation summary using public.fn_admin_getusersubjectallocationsummary
     *
     * POST /api/admin/get-user-subject-allocation-summary
     *
     * Calls: public.fn_admin_getusersubjectallocationsummary(
     *   p_admin_user_id BIGINT,
     *   p_user_type_id BIGINT,
     *   p_inst_id BIGINT,
     *   p_evaluator_type_id INTEGER,
     *   p_department_id INTEGER,
     *   p_exam_year INTEGER,
     *   p_semester INTEGER
     * )
     */
    public function getUserSubjectAllocationSummary(Request $request)
    {
        $adminUserId     = $request->input('admin_user_id', $request->input('user_id', $request->input('p_admin_user_id', 0)));
        $userTypeId      = $request->input('user_type_id', $request->input('role_id', $request->input('p_user_type_id', 0)));
        $instId          = $request->input('inst_id', $request->input('institute_id', $request->input('p_inst_id')));
        $evaluatorTypeId = $request->input('evaluator_type_id', $request->input('examinertype_id', $request->input('p_evaluator_type_id')));
        $departmentId    = $request->input('department_id', $request->input('dept_id', $request->input('p_department_id', 0)));
        $examYear        = $request->input('exam_year', $request->input('examyear', $request->input('p_exam_year')));
        $semester        = $request->input('semester', $request->input('semester_id', $request->input('p_semester', 0)));

        // Normalize semester (handles 'Part-I', 'Part-II', etc.)
        if (is_string($semester)) {
            $semLower = strtolower(trim($semester));
            if ($semLower === 'part-i' || $semLower === 'part 1' || $semLower === '1') {
                $semester = 1;
            } elseif ($semLower === 'part-ii' || $semLower === 'part 2' || $semLower === '2') {
                $semester = 2;
            } else {
                $semester = (int) $semester;
            }
        }

        $validator = Validator::make([
            'admin_user_id'     => $adminUserId,
            'user_type_id'      => $userTypeId,
            'inst_id'           => $instId,
            'evaluator_type_id' => $evaluatorTypeId,
            'department_id'     => $departmentId,
            'exam_year'         => $examYear,
            'semester'          => $semester,
        ], [
            'admin_user_id'     => 'nullable|integer',
            'user_type_id'      => 'nullable|integer',
            'inst_id'           => 'required|integer',
            'evaluator_type_id' => 'required|integer',
            'department_id'     => 'nullable|integer',
            'exam_year'         => 'required|integer',
            'semester'          => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $sql = 'SELECT public.fn_admin_getusersubjectallocationsummary(?::bigint, ?::bigint, ?::bigint, ?::integer, ?::integer, ?::integer, ?::integer) AS data';

            $bindings = [
                (int) $adminUserId,
                (int) $userTypeId,
                (int) $instId,
                (int) $evaluatorTypeId,
                (int) $departmentId,
                (int) $examYear,
                (int) $semester,
            ];

            $result = DB::selectOne($sql, $bindings);

            if (!$result || !isset($result->data)) {
                return response()->json([
                    'status'  => 1,
                    'error'   => false,
                    'message' => 'No data found.',
                    'data'    => [],
                ], 200);
            }

            $raw = $result->data;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status'  => 0,
                    'error'   => true,
                    'message' => 'Failed to parse database response.',
                ], 500);
            }

            return response()->json([
                'status'  => 1,
                'error'   => false,
                'message' => 'Data fetch successfully',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'error'   => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

}




