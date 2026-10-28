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
use Illuminate\Support\Facades\Log;
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
     * Save scrutinizer / user information and assign multiple subjects.
     *
     * POST /api/admin/save-scrutinizer-user-info
     * POST /api/admin/save-user-info
     *
     * Exactly similar to save-teacher-v2:
     * 1. Saves user information using public.fn_admin_saveuserinfo
     * 2. Loops through subjectList and assigns each subject using public.fn_admin_saveuserassignsubject_v2
     *
     * Parameters:
     * - admin_user_id: Entry / Admin user ID (bigint)
     * - userInfo: Object containing user details (or flat fields / teacherInfo)
     *     - in_user_id: 0 for new user, or user ID to update
     *     - role_id: User role ID (integer, required)
     *     - full_name: Full name (string, required)
     *     - contact_no: Mobile / Contact number (string, required)
     *     - email: Email address (string, nullable)
     *     - highest_qualification: Highest qualification (string, nullable)
     *     - aadhar_no: Aadhaar number (string, nullable)
     *     - inst_id: Institute ID (integer, required)
     *     - inst_name: Institute name (string, nullable)
     *     - designation_id: Designation ID (string/integer, nullable, default 1)
     *     - image: Profile image string / path (string, nullable)
     *     - remarks: Remarks (string, nullable)
     *     - inst_code: Institute code (string, optional, auto-resolved if empty)
     * - subjectList: Array of subject assignment objects (required, min: 1)
     *     - dept_id: Department ID (integer, required)
     *     - semester_id: Semester ID e.g. Part-I, Part-II, 1, 2 (required)
     *     - subject_category_id: Category ID (integer, required)
     *     - subject_id: Subject master ID (integer, required)
     *     - subject_code: Subject code string (optional, auto-resolved if empty)
     *     - inst_code: Institute code (optional, defaults to user institute)
     *     - examiner_type_id: Examiner type ID (1: INTERNAL, 2: EXTERNAL, 3: BOTH, default 1)
     *     - exam_year: Exam year string (default '2026')
     */
    public function saveUserInfo(Request $request)
    {
        try {
            Log::channel('daily')->info('🚀 CONTROLLER HIT - saveUserInfo() called');
            Log::channel('daily')->info('═══════════════════════════════════════════════════════════');
            Log::channel('daily')->info('🔵 SAVE USER INFO API - FUNCTION INPUT (REQUEST START)');
            Log::channel('daily')->info('REQUEST METHOD', ['method' => $request->method()]);
            Log::channel('daily')->info('REQUEST URL', ['url' => $request->fullUrl()]);
            Log::channel('daily')->info('📥 INCOMING PAYLOAD', ['payload' => $request->all()]);
            Log::channel('daily')->info('═══════════════════════════════════════════════════════════');
        } catch (\Exception $logEx) {
            Log::channel('daily')->error('❌ ERROR IN INITIAL LOGGING - saveUserInfo', ['error' => $logEx->getMessage()]);
        }

        try {
            // Normalize inputs to support both nested (userInfo/teacherInfo) and flat formats
            $adminUserId = $request->input('admin_user_id', $request->input('entry_user_id', $request->input('p_entry_user_id', auth()->id() ?? 0)));

            $rawUser = $request->input('userInfo', $request->input('teacherInfo'));
            if (!is_array($rawUser)) {
                $rawUser = [];
            }

            $topRoleId  = $request->input('role_id', $request->input('p_role_id'));
            $userRoleId = $rawUser['role_id'] ?? $rawUser['p_role_id'] ?? $topRoleId;

            $inUserId = $rawUser['in_user_id']
                ?? $rawUser['user_id']
                ?? $rawUser['in_teacher_id']
                ?? $rawUser['teacher_id']
                ?? $rawUser['p_in_user_id']
                ?? $request->input('in_user_id', $request->input('user_id', $request->input('in_teacher_id', $request->input('p_in_user_id', 0))));

            $fullName = $rawUser['full_name']
                ?? $rawUser['name']
                ?? $rawUser['p_full_name']
                ?? $request->input('full_name', $request->input('name', $request->input('p_full_name')));

            $contactNo = $rawUser['contact_no']
                ?? $rawUser['phone']
                ?? $rawUser['mobile']
                ?? $rawUser['p_contact_no']
                ?? $request->input('contact_no', $request->input('phone', $request->input('mobile', $request->input('p_contact_no'))));

            $email = $rawUser['email']
                ?? $rawUser['p_email']
                ?? $request->input('email', $request->input('p_email'));

            $highestQualification = $rawUser['highest_qualification']
                ?? $rawUser['qualification']
                ?? $rawUser['p_highest_qualification']
                ?? $request->input('highest_qualification', $request->input('qualification', $request->input('p_highest_qualification')));

            $aadharNo = $rawUser['aadhar_no']
                ?? $rawUser['aadhaar_no']
                ?? $rawUser['p_aadhar_no']
                ?? $request->input('aadhar_no', $request->input('aadhaar_no', $request->input('p_aadhar_no')));

            $instId = $rawUser['inst_id']
                ?? $rawUser['institute_id']
                ?? $rawUser['p_inst_id']
                ?? $request->input('inst_id', $request->input('institute_id', $request->input('p_inst_id')));

            $instName = $rawUser['inst_name']
                ?? $rawUser['institute_name']
                ?? $rawUser['p_inst_name']
                ?? $request->input('inst_name', $request->input('institute_name', $request->input('p_inst_name')));

            $designationId = $rawUser['designation_id']
                ?? $rawUser['p_designation_id']
                ?? $request->input('designation_id', $request->input('p_designation_id', '1'));

            $image = $rawUser['image']
                ?? $rawUser['pic']
                ?? $rawUser['p_image']
                ?? $request->input('image', $request->input('pic', $request->input('p_image')));

            $remarks = $rawUser['remarks']
                ?? $rawUser['p_remarks']
                ?? $request->input('remarks', $request->input('p_remarks', ''));

            $instCode = $rawUser['inst_code']
                ?? $rawUser['institute_code']
                ?? $request->input('inst_code', $request->input('institute_code', ''));

            $subjectList = $request->input('subjectList', $request->input('subjects', $request->input('subject_list', [])));

            $normalizedData = [
                'admin_user_id' => $adminUserId,
                'userInfo'      => [
                    'in_user_id'            => $inUserId !== null ? (int)$inUserId : 0,
                    'role_id'               => $userRoleId !== null ? (int)$userRoleId : null,
                    'full_name'             => $fullName !== null ? trim((string)$fullName) : null,
                    'contact_no'            => $contactNo !== null ? trim((string)$contactNo) : null,
                    'email'                 => $email !== null && trim((string)$email) !== '' ? trim((string)$email) : null,
                    'highest_qualification' => $highestQualification !== null && trim((string)$highestQualification) !== '' ? trim((string)$highestQualification) : null,
                    'aadhar_no'             => $aadharNo !== null && trim((string)$aadharNo) !== '' ? trim((string)$aadharNo) : null,
                    'inst_id'               => $instId !== null ? (int)$instId : null,
                    'inst_name'             => $instName !== null ? trim((string)$instName) : null,
                    'designation_id'        => $designationId !== null ? (string)$designationId : '1',
                    'image'                 => $image !== null ? (string)$image : null,
                    'remarks'               => $remarks !== null ? (string)$remarks : '',
                    'inst_code'             => $instCode !== null ? trim((string)$instCode) : '',
                ],
                'subjectList'   => is_array($subjectList) ? $subjectList : [],
            ];

            $validator = Validator::make($normalizedData, [
                'admin_user_id'                     => 'required|integer',
                'userInfo'                          => 'required|array',
                'userInfo.in_user_id'               => 'required|integer',
                'userInfo.role_id'                  => 'required|integer',
                'userInfo.full_name'                => 'required|string|max:255',
                'userInfo.contact_no'               => 'required|string|max:20',
                'userInfo.email'                    => 'nullable|email|max:255',
                'userInfo.highest_qualification'    => 'nullable|string|max:255',
                'userInfo.aadhar_no'                => 'nullable|string|max:20',
                'userInfo.inst_id'                  => 'required|integer',
                'userInfo.inst_name'                => 'nullable|string|max:255',
                'userInfo.designation_id'           => 'nullable',
                'userInfo.image'                    => 'nullable|string',
                'userInfo.remarks'                  => 'nullable|string',
                'subjectList'                       => 'required|array|min:1',
                'subjectList.*.dept_id'             => 'required|integer',
                'subjectList.*.semester_id'         => 'required',
                'subjectList.*.subject_category_id' => 'required|integer',
                'subjectList.*.subject_id'          => 'required|integer',
                'subjectList.*.subject_code'        => 'nullable|string|max:50',
                'subjectList.*.examiner_type_id'    => 'nullable|integer',
                'subjectList.*.exam_year'           => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                Log::channel('daily')->error('SAVE USER INFO API - Validation Failed:', [
                    'errors'        => $validator->errors()->all(),
                    'failed_fields' => $validator->errors()->keys()
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'error'   => true,
                    'message' => 'Validation failed: ' . $validator->errors()->first(),
                    'data'    => []
                ], 400);
            }

            Log::channel('daily')->info('✅ VALIDATION PASSED - Processing saveUserInfo request');

            $userInfoData = $normalizedData['userInfo'];
            $subjectListData = $normalizedData['subjectList'];
            $roleId = (int) $userInfoData['role_id'];

            Log::channel('daily')->info('📊 Extracted data for saveUserInfo', [
                'admin_user_id'  => $adminUserId,
                'role_id'        => $roleId,
                'user_name'      => $userInfoData['full_name'],
                'subjects_count' => count($subjectListData)
            ]);

            // Step 1: Save User Info (public.fn_admin_saveuserinfo)
            Log::channel('daily')->info('🔄 STEP 1: Calling fn_admin_saveuserinfo', [
                'p_in_user_id' => $userInfoData['in_user_id'],
                'p_role_id'    => $roleId,
                'p_full_name'  => $userInfoData['full_name'],
                'p_inst_id'    => $userInfoData['inst_id']
            ]);

            $saveUserQuery = 'SELECT public.fn_admin_saveuserinfo(?::bigint, ?::integer, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::varchar, ?::integer, ?::varchar, ?::varchar, ?::text, ?::text, ?::bigint) AS result';

            $userResult = DB::selectOne($saveUserQuery, [
                $userInfoData['in_user_id'],
                $roleId,
                $userInfoData['full_name'],
                $userInfoData['contact_no'],
                $userInfoData['email'],
                $userInfoData['highest_qualification'],
                $userInfoData['aadhar_no'],
                $userInfoData['inst_id'],
                $userInfoData['inst_name'],
                $userInfoData['designation_id'] !== null ? (string)$userInfoData['designation_id'] : '1',
                $userInfoData['image'],
                $userInfoData['remarks'],
                $adminUserId
            ]);

            Log::channel('daily')->info('✅ fn_admin_saveuserinfo executed', [
                'raw_result'  => $userResult,
                'result_json' => $userResult->result ?? null
            ]);

            if (!$userResult || !$userResult->result) {
                Log::channel('daily')->error('❌ User save failed - no result returned');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'error'   => true,
                    'message' => 'Failed to save user information',
                    'data'    => []
                ], 500);
            }

            // Parse result JSON: {"p_errorcode": 0, "p_user_id": 5} or similar
            $resultData = is_string($userResult->result)
                ? json_decode($userResult->result, true)
                : (array) $userResult->result;

            Log::channel('daily')->info('🔍 Parsed user result data', ['resultData' => $resultData]);

            $errorCode = isset($resultData['p_errorcode'])
                ? (int) $resultData['p_errorcode']
                : (isset($resultData['errorcode']) ? (int) $resultData['errorcode'] : -1);

            if (!$resultData || $errorCode !== 0) {
                Log::channel('daily')->error('❌ User save returned error', [
                    'p_errorcode' => $resultData['p_errorcode'] ?? 'not_found',
                    'p_user_id'   => $resultData['p_user_id'] ?? $resultData['p_teacher_id'] ?? 'not_found'
                ]);

                $errorMessage = $resultData['p_message'] ?? $resultData['message'] ?? null;
                if (!$errorMessage) {
                    if ($errorCode === 2) {
                        $errorMessage = 'This phone number is already registered with another institute.';
                    } else {
                        $errorMessage = 'User save returned error code: ' . $errorCode;
                    }
                }

                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'error'   => true,
                    'message' => $errorMessage,
                    'data'    => $resultData ?? []
                ], 400);
            }

            $userId = (int) (
                $resultData['p_user_id']
                ?? $resultData['user_id']
                ?? $resultData['p_teacher_id']
                ?? $resultData['teacher_id']
                ?? $userInfoData['in_user_id']
            );

            Log::channel('daily')->info('✅ User saved successfully', ['user_id' => $userId]);

            // Step 2: Loop through subjects and assign each using fn_admin_saveuserassignsubject_v2
            $assignSubjectQuery = "SELECT public.fn_admin_saveuserassignsubject_v2(?::bigint, ?::integer, ?::integer, ?::varchar, ?::integer, ?::integer, ?::bigint, ?::bigint, ?::integer, ?::varchar, ?::varchar, ?::varchar) as result";

            $successCount = 0;
            $failedSubjects = [];
            $duplicateAssignmentDetected = false;

            // Resolve user institute code
            $userInstCode = (string) ($userInfoData['inst_code'] ?? '');
            if ($userInstCode === '' && !empty($userInfoData['inst_id'])) {
                $instRow = DB::table('tbl_institute_master')->where('im_id', $userInfoData['inst_id'])->select('im_code')->first();
                if ($instRow && !empty($instRow->im_code)) {
                    $userInstCode = (string) $instRow->im_code;
                } else {
                    $instRow2 = DB::table('institute_master')->where('i_id', $userInfoData['inst_id'])->select('i_code')->first();
                    if ($instRow2 && !empty($instRow2->i_code)) {
                        $userInstCode = (string) $instRow2->i_code;
                    }
                }
            }

            Log::channel('daily')->info('🔄 STEP 2: Starting subject assignment loop for user', ['total_subjects' => count($subjectListData)]);

            foreach ($subjectListData as $index => $subject) {
                try {
                    $deptId = (int) $subject['dept_id'];
                    $rawSem = trim((string) $subject['semester_id']);
                    if ($rawSem === '1' || strcasecmp($rawSem, 'Part_I') === 0 || strcasecmp($rawSem, 'Part-1') === 0) {
                        $semesterId = 'Part-I';
                    } elseif ($rawSem === '2' || strcasecmp($rawSem, 'Part_II') === 0 || strcasecmp($rawSem, 'Part-2') === 0) {
                        $semesterId = 'Part-II';
                    } else {
                        $semesterId = $rawSem;
                    }
                    $subjectCategoryId = (int) $subject['subject_category_id'];
                    $subjectId         = (int) $subject['subject_id'];
                    $subjectCode       = trim((string) ($subject['subject_code'] ?? ''));
                    if ($subjectCode === '' && !empty($subjectId)) {
                        $subRow = DB::table('tbl_department_subjects_master')->where('dsm_id', $subjectId)->select('dsm_subject_id')->first();
                        $subjectCode = $subRow ? trim((string) $subRow->dsm_subject_id) : '';
                    }
                    $instId            = (int) ($subject['inst_id'] ?? $userInfoData['inst_id']);
                    $instCode          = trim((string) ($subject['inst_code'] ?? $userInstCode));
                    $examinerTypeId    = (int) ($subject['examiner_type_id'] ?? $subject['examinertype_id'] ?? 1);
                    $examYear          = trim((string) ($subject['exam_year'] ?? $subject['examyear'] ?? '2026'));

                    Log::channel('daily')->info("📥 Subject #{$index} - Calling fn_admin_saveuserassignsubject_v2", [
                        'p_user_id'             => $userId,
                        'p_role_id'             => $roleId,
                        'p_dept_id'             => $deptId,
                        'p_semester_id'         => $semesterId,
                        'p_subject_category_id' => $subjectCategoryId,
                        'p_subject_id'          => $subjectId,
                        'p_entry_user_id'       => $adminUserId,
                        'p_inst_id'             => $instId,
                        'p_examinertype_id'     => $examinerTypeId,
                        'p_examyear'            => $examYear,
                        'p_subject_code'        => $subjectCode,
                        'p_inst_code'           => $instCode,
                    ]);

                    $subjectResult = DB::selectOne($assignSubjectQuery, [
                        $userId,
                        $roleId,
                        $deptId,
                        $semesterId,
                        $subjectCategoryId,
                        $subjectId,
                        $adminUserId,
                        $instId,
                        $examinerTypeId,
                        $examYear,
                        $subjectCode,
                        $instCode,
                    ]);

                    Log::channel('daily')->info("📤 Subject #{$index} - fn_admin_saveuserassignsubject_v2 returned", [
                        'subject_id' => $subjectId,
                        'raw_result' => $subjectResult,
                        'result_json'=> $subjectResult->result ?? null
                    ]);

                    if ($subjectResult && $subjectResult->result) {
                        $subjectResultData = is_string($subjectResult->result)
                            ? json_decode($subjectResult->result, true)
                            : (array) $subjectResult->result;

                        $subErrorCode = isset($subjectResultData['p_errorcode'])
                            ? (int) $subjectResultData['p_errorcode']
                            : (isset($subjectResultData['errorcode']) ? (int) $subjectResultData['errorcode'] : -1);

                        if ($subErrorCode === 0) {
                            $successCount++;
                            Log::channel('daily')->info("✅ Subject #{$index} assigned successfully", ['subject_id' => $subjectId]);
                        } else {
                            if ($subErrorCode === 200) {
                                $duplicateAssignmentDetected = true;
                                $subErrorMessage = 'User already assigned to this subject and institute for this exam year';
                            } else {
                                $subErrorMessage = $subjectResultData['p_errormsg'] ?? $subjectResultData['p_message'] ?? 'Subject assignment failed';
                            }

                            Log::channel('daily')->error("❌ Subject #{$index} failed", [
                                'subject_id' => $subjectId,
                                'error_code' => $subErrorCode
                            ]);

                            $failedSubjects[] = [
                                'subject_id'          => $subjectId,
                                'dept_id'             => $deptId,
                                'semester_id'         => $semesterId,
                                'subject_category_id' => $subjectCategoryId,
                                'error_code'          => $subErrorCode,
                                'error_message'       => $subErrorMessage,
                                'error_details'       => $subjectResultData
                            ];
                        }
                    } else {
                        $failedSubjects[] = [
                            'subject_id' => $subjectId,
                            'error'      => 'No result returned from database function'
                        ];
                    }
                } catch (\Exception $subjectEx) {
                    Log::channel('daily')->error('❌ EXCEPTION during subject assignment', [
                        'subject_index' => $index,
                        'error_message' => $subjectEx->getMessage()
                    ]);

                    $failedSubjects[] = [
                        'subject_id' => $subject['subject_id'] ?? null,
                        'error'      => $subjectEx->getMessage()
                    ];
                }
            }

            Log::channel('daily')->info('AdminController::saveUserInfo - Completed', [
                'user_id'        => $userId,
                'total_subjects' => count($subjectListData),
                'success_count'  => $successCount,
                'failed_count'   => count($failedSubjects)
            ]);

            // Determine overall response
            if ($successCount === count($subjectListData)) {
                $response = [
                    'version' => '1.0',
                    'status'  => 1,
                    'error'   => false,
                    'message' => 'User and all subjects saved successfully',
                    'data'    => [
                        'user_id'           => $userId,
                        'teacher_id'        => $userId,
                        'subjects_assigned' => $successCount,
                        'total_subjects'    => count($subjectListData)
                    ]
                ];
                return response()->json($response, 200);
            } elseif ($successCount > 0) {
                $response = [
                    'version' => '1.0',
                    'status'  => 1,
                    'error'   => false,
                    'message' => 'User saved, but some subjects failed to assign',
                    'data'    => [
                        'user_id'           => $userId,
                        'teacher_id'        => $userId,
                        'subjects_assigned' => $successCount,
                        'total_subjects'    => count($subjectListData),
                        'failed_subjects'   => $failedSubjects
                    ]
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'version' => '1.0',
                    'status'  => 0,
                    'error'   => true,
                    'message' => $duplicateAssignmentDetected
                        ? 'User already assigned to this subject and institute for this exam year'
                        : 'User saved but all subjects failed to assign',
                    'data'    => [
                        'user_id'         => $userId,
                        'teacher_id'      => $userId,
                        'failed_subjects' => $failedSubjects
                    ]
                ];
                return response()->json($response, 400);
            }

        } catch (\Exception $e) {
            Log::channel('daily')->error('🔴 SAVE USER INFO API - EXCEPTION OCCURRED', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine()
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'error'   => true,
                'message' => 'Server error: ' . $e->getMessage(),
                'data'    => []
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




