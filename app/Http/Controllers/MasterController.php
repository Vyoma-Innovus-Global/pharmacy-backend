<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\CDC;
use App\Models\Institute;
use App\Models\CDCInstituteTag;
use App\Models\User;
use App\Models\Token;
use App\Models\Holiday;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\ExamSchedule;
use Illuminate\Http\Request;
use App\Imports\HolidayImport;
use Illuminate\Support\Carbon;
use App\Models\CenterAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Config;
use App\Http\Resources\ScheduleResource;
use Illuminate\Support\Facades\Validator;

class MasterController extends Controller
{
    public function instituteAdd(Request $request)
    {
        if ($request->header('token')) {
            $now = date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user_id = $token_check->t_user_id;

                $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();

                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', $user_data->u_role_id)->pluck('rp_url_id');

                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');

                    if (in_array('student/institute-add', $url_data)) {
                        $validator = Validator::make($request->all(), [
                            'i_code' => 'required|unique:institute_master,i_code',
                            'i_name' => 'required|max:255',
                        ]);

                        if ($validator->fails()) {
                            return response()->json(['errors' => true, 'message' => $validator->errors()], 422);
                        }

                        try {
                            DB::table('institute_master')->insert([
                                'i_code' => $request->input('i_code'),
                                'i_name' => $request->input('i_name'),
                                'i_dist_code' => $request->input('i_dist_code'),
                                'i_type' => $request->input('i_type'),
                                'is_active' => 1,
                                'i_contact_person_name_1' => $request->input('i_contact_person_name_1'),
                                'i_contact_person_phone_1' => $request->input('i_contact_person_phone_1'),
                                'i_contact_person_name_2' => $request->input('i_contact_person_name_2'),
                                'i_contact_person_phone_2' => $request->input('i_contact_person_phone_2'),
                                'i_contact_person_name_3' => $request->input('i_contact_person_name_3'),
                                'i_contact_person_phone_3' => $request->input('i_contact_person_phone_3'),
                                'i_contact_email' => $request->input('i_contact_email'),
                            ]);
                            return response()->json(['message' => 'Institute added successfully.'], 201);
                        }
                        catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 500);
                        }
                    }
                    else {
                        return response()->json([
                            'error' => true,
                            'message' => "Oops! you don't have sufficient permission"
                        ], 403);
                    }
                }
                else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Unable to process your request due to invalid token'
                    ], 401);
                }
            }
            else {
                return response()->json([
                    'error' => true,
                    'message' => 'Unable to process your request due to non availability of token'
                ], 401);
            }
        }
        else {
            return response()->json([
                'error' => true,
                'message' => 'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    public function branchAdd(Request $request)
    {
        if ($request->header('token')) {
            $now = date('Y-m-d H:i:s');
            $token_check = Token::where('t_token', '=', $request->header('token'))->where('t_expired_on', '>=', $now)->first();
            if ($token_check) {
                $user_id = $token_check->t_user_id;
                $user_data = User::select('u_id', 'u_ref', 'u_role_id')->where('u_id', $user_id)->first();
                $role_url_access_id = DB::table('pharmacy_auth_roles_permissions')->where('rp_role_id', 1)->pluck('rp_url_id');
                if (sizeof($role_url_access_id) > 0) {
                    $urls = DB::table('pharmacy_auth_urls')->where('url_visible', 1)->whereIn('url_id', $role_url_access_id)->get()->toArray();
                    $url_data = array_column($urls, 'url_name');
                    if (in_array('student/branch-add', $url_data)) {
                        $validator = Validator::make($request->all(), [
                            'branch_id' => 'required|string|unique:pharmacy_branch_master,branch_id',
                            'br_name' => 'required|max:255',
                        ]);
                        if ($validator->fails()) {
                            return response()->json(['errors' => true, 'message' => $validator->errors()], 422);
                        }
                        try {
                            DB::table('pharmacy_branch_master')->insert([
                                'br_name' => $request->input('br_name'),
                                'br_gen_code' => $request->input('br_gen_code'),
                                'branch_id' => $request->input('branch_id'),
                                'br_industry_training' => $request->input('br_industry_training'),
                                'is_active' => 1,
                            ]);
                            return response()->json(['error' => false, 'message' => 'Branch added successfully.'], 201);
                        }
                        catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 500);
                        }
                    }
                    else {
                        return response()->json([
                            'error' => true,
                            'message' => "Oops! you don't have sufficient permission"
                        ], 403);
                    }
                }
                else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Unable to process your request due to invalid token'
                    ], 401);
                }
            }
            else {
                return response()->json([
                    'error' => true,
                    'message' => 'Unable to process your request due to non availability of token'
                ], 401);
            }
        }
        else {
            return response()->json([
                'error' => true,
                'message' => 'Unable to process your request due to non availability of token'
            ], 401);
        }
    }

    // csv or Excel import Holiday list
    public function importHolidayList(Request $request)
    {
        try {
            $request->validate([
                'holiday_csv_file' => 'required|file|mimes:csv,txt,xlsx'
            ]);

            Excel::import(new HolidayImport, $request->file('holiday_csv_file'));

            return response()->json([
                'error' => false,
                'message' => 'Holidays imported successfully.'
            ], 201);

        }
        catch (\Exception $e) {
            Log::error('Import Error: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //Insert update Holiday Master
    public function updateHolidayList(Request $request)
    {
        try {
            $data = [
                'hol_name' => $request->input('hol_name'),
                'hol_date' => $request->input('hol_date'),
                'hol_year' => $request->input('hol_year'),
                'hol_description' => $request->input('hol_description'),
                'hol_is_active' => (bool)$request->input('hol_is_active') == true ? 1 : 0,
            ];

            $holId = $request->input('hol_id');

            if ($holId) {
                Holiday::updateOrCreate(['hol_id' => $holId], $data);
            }
            else {
                Holiday::create($data);
            }

            return response()->json([
                'error' => false,
                'message' => $holId ? 'Holiday updated successfully.' : 'Holiday created successfully.'
            ], 201);

        }
        catch (\Exception $e) {
            Log::error('Error storing holiday: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Something went wrong while saving the holiday.'
            ], 500);
        }
    }
    //Get Holiday List
    public function getHolidayList(Request $request)
    {
        $year = $request->current_year;

        try {
            $holidays = Holiday::select('hol_id', 'hol_name', 'hol_date', 'hol_year', 'hol_description', 'hol_is_active')
                ->where('hol_year', $year)
                ->where('hol_is_active', 1)
                ->orderBy('hol_date', 'asc')
                ->get();

            $result = [];
            foreach ($holidays as $holiday) {
                $result[] = [
                    'hol_id' => $holiday->hol_id,
                    'hol_name' => $holiday->hol_name,
                    'hol_date' => $holiday->hol_date,
                    'hol_description' => $holiday->hol_description,
                    'hol_year' => $holiday->hol_year,
                    'hol_is_active' => $holiday->hol_is_active
                ];
            }
            return response()->json([
                'error' => false,
                'message' => 'Holiday found',
                'holidayList' => $result,
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    //Away Center Master List
    public function allAwayCenter(Request $request)
    {
        try {
            $query = CenterAllocation::where('acm_active', 1);
            $away_center_list = $query->orderBy('acm_id', 'ASC')->get();

            if ($away_center_list->isNotEmpty()) {
                return response()->json([
                    'error' => false,
                    'message' => 'Away center found',
                    'count' => $away_center_list->count(),
                    'away_center' => $away_center_list,
                ], 200);
            }
            else {
                return response()->json([
                    'error' => true,
                    'message' => 'No Away Center available',
                ], 404);
            }
        }
        catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    //Get Exam Schedule List
    public function getExamSchedule(Request $request)
    {
        $part_sem = $request->part_sem;
        $exam_yr = $request->exam_yr;
        try {
            $subjects = Subject::select('subject_name', 'subject_id', 'general_code', 'subject_type', 'type', 'q_code')
                ->where('semester', $part_sem)
                ->orderBy('subject_sn', 'asc')
                ->get();
            $schedules = ExamSchedule::select(
                'exam_subject_id',
                'exam_subject_general_code',
                'exam_q_code',
                'exam_date',
                'exam_time'
            )
                ->where('exam_part_sem', $part_sem)
                ->where('exam_year', $exam_yr)
                ->orderBy('exam_date', 'asc')
                ->get()
                ->keyBy('exam_subject_general_code');
            $result = [];
            foreach ($subjects as $subject) {
                $schedule = $schedules[$subject->general_code] ?? null;
                $exam_date = $schedule ?Carbon::parse($schedule->exam_date)->format('d-m-Y') : null;
                $exam_weekday = $schedule ?Carbon::parse($schedule->exam_date)->format('l') : null;
                $exam_time = $schedule ? $schedule->exam_time : null;
                $result[] = [
                    'subject_name' => $subject->subject_name,
                    'subject_id' => $subject->subject_id,
                    'subject_code' => $subject->general_code,
                    'subject_type' => $subject->subject_type . ' - ' . $subject->type,
                    'subject_q_code' => $subject->q_code,
                    'exam_time' => isset($exam_time) ? $exam_time : '',
                    'exam_date' => isset($exam_date) ? $exam_date : '',
                    'exam_weekday' => isset($exam_weekday) ? $exam_weekday : ''
                ];
            }
            return response()->json([
                'error' => false,
                'message' => 'Away center found',
                'scheduleList' => $result,
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    //Save Exam Schedule
    public function saveExamSchedule(Request $request)
    {
        try {
            ExamSchedule::create([
                'exam_subject_id' => $request->input('subject_id'),
                'exam_subject_general_code' => $request->input('subject_code'),
                'exam_q_code' => $request->input('subject_q_code'),
                'exam_part_sem' => $request->input('part_sem'),
                'exam_year' => $request->input('exam_yr'),
                'exam_date' => $request->input('exam_date'),
                'exam_time' => $request->input('exam_time')
            ]);

            return response()->json(['error' => false, 'message' => 'Schedule added successfully.'], 201);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    function subjectList(Request $request)
    {
        try {
            $part_sem = $request->input('part_sem');
            $subjectList = DB::table('pharmacy_subjects_master')
                ->where('semester', $part_sem)
                ->where('dept', 'PHARM')
                ->select('subject_id', 'general_code', 'q_code', 'subject_name', 'subject_type')
                ->orderBy('subject_sn', 'ASC')
                ->get();

            return response()->json([
                'error' => false,
                'subjectList' => $subjectList
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    function subjectListTheory(Request $request)
    {
        try {
            $part_sem = $request->input('part_sem');
            $subjectList = DB::table('pharmacy_subjects_master')
                ->where('semester', $part_sem)
                ->where('dept', 'PHARM')
                ->where('subject_type', 'Theoretical')
                ->select('subject_id', 'general_code', 'q_code', 'subject_name')
                ->orderBy('subject_sn', 'ASC')
                ->get();

            return response()->json([
                'error' => false,
                'subjectList' => $subjectList
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // schedule master
    public function scheduleList(Request $request)
    {
        $part = $request->part;
        $inst_code = $request->inst_code;
        $schedule_type = $request->type;

        $schedule = Schedule::query();

        if ($part) {
            $schedule = $schedule->where([
                'part' => $part,
            ]);
        }

        if ($inst_code) {
            $schedule = $schedule->where([
                'inst_code' => $inst_code,
            ]);
        }

        if ($schedule_type) {
            $schedule = $schedule->where([
                'schedule_type' => $schedule_type,
            ]);
        }

        $schedule = $schedule
            ->orderBy('inst_code', 'DESC')
            ->orderBy('schedule_type', 'ASC')
            ->orderBy('mark_type', 'DESC')
            ->get();

        if (sizeof($schedule) > 0) {
            return response()->json([
                'error' => false,
                'message' => 'Data found',
                'data' => ScheduleResource::collection($schedule)
            ], 200);
        }
        else {
            return response()->json([
                'error' => false,
                'message' => 'No Data available'
            ], 200);
        }
    }

    public function scheduleCreate(Request $request)
    {
        $request->validate([
            'part' => ['required'],
            'schedule_type' => ['required'],
            'start_date' => ['required'],
            'end_date' => ['required'],
            'update_for' => ['required'], // all, individual
            'mark_type' => ['nullable'], // MARK_INTERNAL, MARK_EXTERNAL
            'is_active' => ['required'],
            'is_active_always' => ['required'],
        ]);

        try {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $lateDate = $request->late_date;
            $part = $request->part;
            $schedule_type = $request->schedule_type;
            $mark_type = $request->mark_type;
            $inst_code = $request->inst_code;
            $exam_year = $request->exam_year;
            $academic_session = $request->academic_session;
            $update_for = $request->update_for;
            $is_active = $request->is_active;
            $is_active_always = $request->is_active_always;
            $current_date_time = date('Y-m-d H:i:s');

            DB::beginTransaction();

            $old_count = Schedule::where([
                'part' => $part,
                'schedule_type' => $schedule_type
            ]);

            if ($inst_code) {
                $old_count = $old_count->where('inst_code', $inst_code);
            }

            switch ($update_for) {
                case 'all':
                    $old_count = $old_count->count();

                    if ($old_count) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Schedule already exists'
                        ]);
                    }

                    Schedule::insert([
                        [
                            'part' => $part,
                            'schedule_type' => $schedule_type,
                            'inst_code' => $inst_code,
                            'mark_type' => 'mark_internal',
                            'exam_year' => $exam_year,
                            'academic_session' => $academic_session,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'active_status' => $is_active,
                            'is_active_always' => $is_active_always,
                            'created_at' => now()
                        ],
                        [
                            'part' => $part,
                            'schedule_type' => $schedule_type,
                            'inst_code' => $inst_code,
                            'mark_type' => 'mark_external',
                            'exam_year' => $exam_year,
                            'academic_session' => $academic_session,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'active_status' => $is_active,
                            'is_active_always' => $is_active_always,
                            'created_at' => now()
                        ]
                    ]);
                default:
                    if (!is_null($mark_type)) {
                        $old_count = $old_count->where('mark_type', $mark_type);
                    }

                    $old_count = $old_count->count();

                    if ($old_count) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Schedule Already Exists'
                        ]);
                    }

                    $old_data = Schedule::where([
                        'part' => $part,
                        'schedule_type' => $schedule_type,
                    ]);

                    if (!is_null($inst_code)) {
                        $old_data = $old_data->where('inst_code', $inst_code);
                    }

                    if (!is_null($mark_type)) {
                        $old_data = $old_data->where('mark_type', $mark_type);
                    }

                    $is_date_exist = false;
                    foreach ($old_data->clone()->get() as $old_schedule) {
                        if ($current_date_time >= $old_schedule->start_date && $current_date_time <= $old_schedule->end_date) {
                            $is_date_exist = true;
                            break;
                        }
                    }

                    if ($is_date_exist) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Schedule Already Exists',
                        ]);
                    }

                    if (!is_null($inst_code)) {
                        $vtc_old_data = $old_data->where([
                            'inst_code' => $inst_code,
                        ]);

                        $is_exist = $vtc_old_data->clone()->where([
                            'start_date' => $start_date,
                            'end_date' => $end_date
                        ])->first();

                        if ($is_exist) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Schedule Already Exists',
                            ]);
                        }
                    }
                    else {
                        $all_old_data = $old_data;

                        $is_exist = $all_old_data->clone()->where([
                            'start_date' => $start_date,
                            'end_date' => $end_date
                        ])->first();

                        if ($is_exist) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Schedule Already Exists',
                            ]);
                        }
                    }

                    Schedule::create([
                        'part' => $part,
                        'schedule_type' => $schedule_type,
                        'inst_code' => $inst_code,
                        'mark_type' => $mark_type,
                        'exam_year' => $exam_year,
                        'academic_session' => $academic_session,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'late_start_at' => $lateDate,
                        'active_status' => $is_active,
                        'is_active_always' => $is_active_always,
                        'created_at' => now(),
                    ]);
                    break;
            }

            // auditTrail(authUserId(), "Schedule for {$schedule_type} Created");

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Schedule Created Successfully',
            ]);
        }
        catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function scheduleUpdate(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
                'part' => 'required',
                'schedule_type' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'update_for' => 'required',
            ]);

            DB::beginTransaction();

            $data = [
                'inst_code' => $request->inst_code,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'exam_year' => $request->exam_year,
                'academic_session' => $request->academic_session,
                'late_start_at' => $request->late_date,
                'active_status' => $request->is_active,
                'is_active_always' => $request->is_active_always,
            ];

            if ($request->update_for === 'all') {
                $query = Schedule::where([
                    'part' => $request->part,
                    'schedule_type' => $request->schedule_type
                ]);

                if (!is_null($request->inst_code)) {
                    $query->whereNotNull('inst_code')->where('inst_code', $request->inst_code);
                }
                else {
                    $query->whereNull('inst_code');
                }

                $updated = $query->update($data);
            }
            elseif ($request->update_for === 'individual') {
                $schedule = Schedule::findOrFail($request->id);
                $updated = $schedule->update($data);
            }
            else {
                return response()->json(['error' => true, 'message' => 'Invalid update_for value']);
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => $updated ? 'Schedule Updated Successfully' : 'No records were updated',
            ]);
        }
        catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function scheduleDelete($id)
    {
        $current_date_time = date('Y-m-d H:i:s');

        try {
            $schedule = Schedule::findOrFail($id);

            if ($schedule) {
                if ($current_date_time <= $schedule->end_date && $schedule->active_status == 1) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Schedule Already Started',
                    ]);
                }
                else {
                    DB::beginTransaction();

                    $schedule->delete();

                    // auditTrail(authUserId(), "Schedule deleted");

                    DB::commit();

                    return response()->json([
                        'error' => false,
                        'message' => 'Schedule Deleted Successfully',
                    ]);
                }
            }
        }
        catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function scheduleCheck(Request $request)
    {
        $request->validate([
            'part_sem' => ['required'],
            'schedule_type' => ['required'],
            'inst_code' => ['nullable'],
            'mark_type' => ['nullable'],
        ]);

        $part = $request->part_sem;
        $schedule_type = $request->schedule_type;
        $inst_code = $request->inst_code;
        $mark_type = $request->mark_type;
        $current_date = date('Y-m-d H:i:s');

        $schedule = Schedule::where([
            'part' => $part,
            'active_status' => 1,
            'schedule_type' => $schedule_type,
        ]);

        if ($mark_type) {
            $schedule = $schedule->where('mark_type', $mark_type);
        }

        $is_active_always = $schedule->clone()
            ->where('is_active_always', 1)
            ->first();

        if ($is_active_always) {
            return response()->json([
                'error' => false,
                'schedule' => true,
                'message' => 'Data found',
            ]);
        }

        $notAlwaysActive = $schedule->clone()
            ->where('start_date', '<=', $current_date)
            ->where('end_date', '>=', $current_date);

        $inst_schdule = $notAlwaysActive->clone()
            ->where([
            'inst_code' => $inst_code
        ])->first();
        // for late fine
        $data = $schedule->first();
        $lateFine = '';

        if (!empty($data->late_start_at)) {
            if (strtotime($data->late_start_at) < strtotime($current_date)) {
                $lateFine = true;
            }
            else {
                $lateFine = false;
            }
        }
        else {
            $lateFine = '';
        }


        if ($inst_schdule) {
            return response()->json([
                'error' => false,
                'schedule' => true,
                'latefine' => $lateFine,
                'message' => 'Schedule found',
            ]);
        }

        $all_schedule = $notAlwaysActive->clone()->whereNull('inst_code')->first();

        if ($all_schedule) {
            return response()->json([
                'error' => false,
                'schedule' => true,
                'message' => 'Schedule found',
                'latefine' => $lateFine,
            ]);
        }
        else {
            $message = match ($schedule_type) {
                    'ENROLLMENT' => 'You cannot enroll at this time, please contact admin for details',
                    'ADMIT_CARD' => 'You cannot download admit at this time, please contact admin for details',
                    'ENROLLMENT_PAYMENT' => 'You cannot pay at this time, please contact admin for details',
                    'MARKS_ENTRY' => 'You cannot entry marks at this time, please contact admin for details',
                    'ATTENDANCE_ENTRY' => 'You cannot entry Attendance at this time, please contact admin for details',
                };

            return response()->json([
                'error' => true,
                'schedule' => false,
                'message' => $message,

            ]);
        }
    }

    //CDC Add
    public function createCdc(Request $request)
    {
        try {
            $data = [
                'CDC_name' => $request->input('cdc_name'),
                'cdc_code' => $request->input('cdc_code'),
                'is_active' => (bool)$request->input('is_active') == true ? 1 : 0,
            ];

            $cdcId = $request->input('id');

            if ($cdcId) {
                CDC::updateOrCreate(['id' => $cdcId], $data);
            }
            else {
                CDC::create($data);
            }

            return response()->json([
                'error' => false,
                'message' => $cdcId ? 'CDC updated successfully.' : 'CDC created successfully.'
            ], 201);

        }
        catch (\Exception $e) {
            Log::error('Error storing cdc: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    //CDC List
    public function cdcList(Request $request)
    {
        try {
            $data = CDC::where('is_active', 1)->get();
            return response()->json(['error' => false, 'data' => $data], 200);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }
    //CDC institute Tag
    public function cdcInstTagg(Request $request)
    {
        $cdc_code = $request->cdc_code;
        $all_institutes = $request->all_institutes;
        $exam_year = $request->exam_year;
        $cdc = CDC::where('cdc_code', $cdc_code)->first();
        if (!$cdc) {
            return response()->json(['error' => true, 'message' => 'Invalid CDC Code'], 404);
        }

        if ($all_institutes) {
            $institutes = Institute::select('i_code')->where('is_active', 1)->get();

            DB::beginTransaction();
            try {
                if ($all_institutes === 'ALL') {
                    $institutes = Institute::select('i_code')->where('is_active', 1)->get();
                }
                // Case 2: Specific institutes
                else if (is_array($all_institutes) && count($all_institutes) > 0) {
                    $institutes = Institute::select('i_code')
                        ->whereIn('i_code', $all_institutes)
                        ->where('is_active', 1)
                        ->get();
                }
                else {
                    return response()->json(['error' => true, 'message' => 'Invalid institute input'], 400);
                }
                foreach ($institutes as $institute) {
                    CDCInstituteTag::updateOrInsert(
                    [
                        'cdc_code' => $cdc_code,
                        'inst_code' => $institute->i_code,
                        'exam_year' => $exam_year,
                    ]

                    );
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'All active institutes tagged successfully',
                    'tagged_institutes' => $institutes->pluck('i_code') // Optional return
                ]);
            }
            catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
            }
        }
    }


}
