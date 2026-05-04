<?php

namespace App\Http\Controllers;
use Exception;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Models\Answersheetmasking;
use App\Models\Attendancepone;

class AnswersheetController extends Controller
{
    //Answersheet Masking Setup
    public function updateMaskSetup(Request $request)
    {
        try {
            $data = [
                'pams_subject_id'       => $request->input('subject_id'),
                'pams_subject_code'     => $request->input('subject_code'),
                'pams_part_sem'         => $request->input('part_sem'),
                'pams_exam_year'        => $request->input('exam_year'),
                'pams_masking_active'   => (bool) $request->input('mask_is_active') == true ? 1 : 0,
                'pams_is_active'        => (bool) $request->input('is_active') == true ? 1 : 0,
            ];

            $maskId = $request->input('pams_id');

            if ($maskId) {
                Answersheetmasking::updateOrCreate(['pams_id' => $maskId], $data);
            } else {
                Answersheetmasking::create($data);
            }

            return response()->json([
                'error' => false,
                'message' => $maskId ? 'Answersheet Mask Setup updated successfully.' : 'Answersheet Mask Setup created successfully.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error storing Answersheet Mask Setup: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Something went wrong while Answersheet Mask Setup.'
            ], 500);
        }
    }
    //Get Mask List
    public function getMaskList(Request $request)
    {
        $part_sem = $request->part_sem;
        $subject = $request->subject_code;
        $exam_year = $request->exam_year;
        try {
            $masks = Answersheetmasking::query()
                    ->where([
                        ['pams_exam_year', '=', $exam_year],
                        ['pams_part_sem', '=', $part_sem],
                        ['pams_is_active', '=', 1],
                    ])
                    ->when(!empty($subject), fn($query) =>
                        $query->where('pams_subject_code', $subject)
                    )
                    ->with('subjectDetails', function ($query) use ($subject, $part_sem) {
                        $query->where('semester', $part_sem)
                        ->when(!empty($subject), function ($q) use ($subject) {
                            $q->where('general_code', $subject);
                        });
                    })
                    ->get();
            $result = [];
            foreach ($masks as $mask_val) {
                $result[] = [
                    'subject_name' => $mask_val->subjectDetails->subject_name,
                    'pams_id' => $mask_val->pams_id,
                    'pams_subject_id' => $mask_val->pams_subject_id,
                    'pams_subject_code' => $mask_val->pams_subject_code,
                    'pams_part_sem' => $mask_val->pams_part_sem,
                    'pams_exam_year' => $mask_val->pams_exam_year,
                    'pams_masking_active' => $mask_val->pams_masking_active,
                    'pams_is_active' => $mask_val->pams_is_active
                ];
            }
            return response()->json([
                'error' => false,
                'message' => 'Mask found',
                'maskList' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    function subjectList(Request $request)
    {
        try {
            $part_sem = $request->input('part_sem');
            $subjectList = DB::table('pharmacy_subjects_master')
                ->where('semester', $part_sem)
                ->where('dept', 'PHARM')
                ->where('subject_type', 'Theoretical')
                ->where('type', 'Main')
                ->select('subject_id', 'general_code', 'q_code', 'subject_name')
                ->orderBy('subject_sn', 'ASC')
                ->get();

            return response()->json([
                'error' => false,
                'subjectList' => $subjectList
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listAnswersheetSerialEntry(Request $request)
    {
        $type = $request->student_type;
        $part_sem = $request->part_sem;
        $exam_year = $request->exam_year;
        $subject_code = $request->subject_code;
        $mark_type = $request->mark_type;
        $center_code= $request->inst_code;

        $masking_status = Answersheetmasking::query()
                    ->where([
                        ['pams_exam_year', '=', $exam_year],
                        ['pams_part_sem', '=', $part_sem],
                        ['pams_is_active', '=', 1],
                        ['pams_masking_active','=', 1],
                        ['pams_subject_code', '=', $subject_code]
                    ])->count() ;
        /* return response()->json([
                'error' => true,
                'message' => 'Masking is not available',
                'data'      =>  $masking_status 
            ]); */


        if($masking_status ==0){
            return response()->json([
                'error' => true,
                'message' => 'Masking is not available'
            ]);
        }   

        if($part_sem == 'Part_I'){
            $attendances = Attendancepone::where([
            'ea_student_type' => $type,
            'ea_exam_year' => $exam_year,
            'ea_part_sem' => $part_sem,
            'ea_subject_code' => $subject_code,
            'ea_center_code'=> $center_code,
            ]);
        }else{
            $attendances = Attendanceptwo::where([
            'ea_student_type' => $type,
            'exam_year' => $exam_year,
            'ea_part_sem' => $part_sem,
            'ea_subject_code' => $subject_code,
            'ea_center_code'=> $center_code,
            ]);
        }

        if (!$attendances->count()) {
            return response()->json([
                'error' => true,
                'message' => 'No Student Found'
            ]);
        }

        $present_count = $absent_count = $ra_count = 0;
       # $mark_type = strtolower(explode('_', $mark_type)[1]);

        $present_count = $attendances->clone()->where("ea_is_present_{$mark_type}", 1)->count();
        $absent_count = $attendances->clone()->where("ea_is_absent_{$mark_type}", 1)->count();

        $ra_count = $attendances->clone()->where("ea_is_ra_{$mark_type}", 1)->count();

        $attendances = $attendances->clone()
            ->with('student')
            ->orderBy('ea_roll_number', 'ASC')
            ->get();

        return response()->json([
            'error' => false,
            'message' => 'Data found',
            'student' => $this->StudentAttendenceData($attendances, $subject_code , $mark_type),
            'enrolled_count' => sizeof($attendances),
            'present_count' => $present_count,
            'absent_count' => $absent_count,
            'ra_count' => $ra_count
        ]);
    }
    private function StudentAttendenceData($attendances, $subject_code, $mark_type)
    {

      #  $mark_type = request()->mark_type;
        $type = match ($mark_type) {
            'mark_internal' => 'internal',
            'mark_external' => 'external',
            default => null,
        };

        return $attendances->map(function ($item) use ($type, $subject_code) {
            $student = $item->student;
            return [
                'id' => $student->s_id,
                'student_form_no' => $student->s_appl_form_num,
                'student_reg_no' => $student->s_appl_reg_no,
                'roll_no'        => $item->ea_roll_number,
                'center_code'   => $item->ea_center_code,
                'inst_code'     => $item->ea_inst_code,
                'paper_code'    =>  $subject_code,
                'mask_code'     =>$item->ea_masking_code,
                
                'student_name' => $student->s_candidate_name,
                'is_mask_locked'  => $item->ea_masking_code !='' ? true:false,
            ];
        });
    }

    public function getInstituteList(Request $request)
    {
        $request->validate([
            'exam_year' => 'required',
            'part_sem' => 'required',
            'subject' => 'required',
            'student_type' => 'required',
            'u_role_id' => 'required'
        ]);

        $exam_year = $request->exam_year;
        $part_sem = $request->part_sem;
        $subject = $request->subject;
        $student_type = $request->student_type;
        $u_role_id = $request->u_role_id;
        $table_name = $part_sem == 'Part_I' ? 'exam_attendance_pone' : 'exam_attendance_ptwo';

        $data = DB::table($table_name)
            ->select('ea_inst_code', 'i_name')
            ->where('ea_exam_year', $exam_year)
            ->where('ea_part_sem', $part_sem)
            ->where('ea_subject_code', $subject)
            ->where('ea_student_type' ,$student_type )
            ->join('institute_master', 'i_code', '=', 'ea_inst_code')
            ->groupBy('ea_inst_code', 'i_name')
            ->get();

        $institute_data = $data->map(function ($item) {
            return [
                'institute_code' => $item->ea_inst_code,
                'institute_name' => $item->i_name
            ];
        });


        return response()->json([
            'error' => false,
            'data' => $institute_data,
            'message' => 'No Data available'
        ], 200);

    }

    public function finalSubmitMasking(Request $request)
    {

        /* return response()->json([
                'error' => true,
                'student_data'   =>  $request->all()['student_data'],
                'other'         => $request->all()['other_data'],
                'exam_year'     =>  $request->all()['other_data']['exam_year'],
                'message' => 'Attendance Updated Finally',
            ]); */
        try {
            DB::beginTransaction();
             
            $post_data = $request->all();
            $other_data = $post_data['other_data'];
            $student_data   =   $post_data['student_data']; 

            $exam_year    = $other_data['exam_year'] ?? null;
            $student_type = $other_data['student_type'] ?? null;
            $part_sem     = $other_data['part_sem'] ?? null;
            $subject_code = $other_data['subject_code'] ?? null;
            $inst_code    = $other_data['inst_code'] ?? null;
           # $mark_type    = $other_data['mark_type'] ?? null;
                   
 

            
            foreach ($student_data as $student) { 
               
                if($part_sem == 'Part_I'){
                    $att = Attendancepone::where([
                    'ea_student_type' =>    $student_type,
                    'ea_exam_year' => $exam_year,
                    'ea_part_sem' => $part_sem,
                    'ea_subject_code' => $subject_code,
                    'ea_form_number' => $student['form_no'],
                    'ea_center_code' => $inst_code 
                    ]);
                }else{
                    $att = Attendanceptwo::where([
                    'ea_student_type' =>    $student_type,
                    'ea_exam_year' => $exam_year,
                    'ea_part_sem' => $part_sem,
                    'ea_subject_code' => $subject_code,
                    'ea_form_number' => $student['form_no'],
                    'ea_center_code' => $inst_code 
                    ]);
                }
                          

                $att->update([
                    'ea_masking_code' => $student['mask_code'],
                    //'ea_final_submit_by' => 
                ]);

                auditTrail($student['form_no'], 'Student Attendance Updated Finally');
            }

            

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Attendance Updated Finally',
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }



}
