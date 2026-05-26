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

}
