<?php
namespace App\Http\Controllers;
use App\Models\Enrollment;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\EvaluatorAllocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use App\Models\User;
use App\Models\EvaluatorCredential;
use App\Models\EvaluatorDetails;
use App\Mail\EvaluatorAppointmentMail;

class EvaluatorController extends Controller
{
    public function subjectList(Request $request)
    {
        try {
            $part_sem = $request->part_sem;
            $data = DB::table('pharmacy_subjects_master')
            ->where('semester', $part_sem)
            ->where('subject_type', 'Theoretical')
            ->where('type', 'Main')
            ->select('general_code', 'subject_name')->get();
            return response()->json([
                'error' => false,
                'subjectList' => $data
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function examYearList()
    {
        $years = Enrollment::distinct()->pluck('enrl_year');

        $exam_year = $years->map(function ($year) {
            return $year ?? null;
        })->filter()->values();

        return response()->json([
            'exam_year' => $exam_year
        ]);
    }

    public function evaluatorSubjectList(Request $request)
    {
        try {
            $exam_year = $request->exam_year;
            $part_sem  = $request->part_sem;
            $ev_id     = $request->ev_id;
            $subject_type = $request->subject_type; 
            $subject_data = DB::table('pharmacy_evaluator_allocations as pea')
                ->join(
                    'pharmacy_subjects_master as psm',
                    DB::raw('CAST(pea.subject_code AS INTEGER)'),
                    '=',
                    'psm.general_code'
                )
                ->where('pea.part_sem', $part_sem)
                ->where('pea.exam_year', $exam_year)
                ->where('pea.examiner_id', $ev_id)
                ->where('psm.subject_type', $subject_type)
                ->select(
                    'psm.general_code as general_code',
                    'psm.subject_name',
                    'psm.subject_id'
                )
                ->distinct()
                ->orderBy('psm.subject_name')
                ->get();

            return response()->json([
                'status' => 'success',
                'subjectList'   => $subject_data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch subject list: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function evaluatorInstituteList(Request $request)
    {
        try {
            $exam_year = $request->exam_year;
            $part_sem  = $request->part_sem;
            $ev_id     = $request->ev_id;
            $subject_code = $request->subject; 
            $ins_data = DB::table('pharmacy_evaluator_allocations as pea')
                        ->join('institute_master as im', 'pea.inst_code', '=', 'im.i_code')
                        ->where('pea.part_sem', $part_sem)
                        ->where('pea.exam_year', $exam_year)
                        ->where('pea.examiner_id', $ev_id)
                        ->where('pea.subject_code', $subject_code)
                        ->select(
                            'pea.inst_code as institute_code',
                            'im.i_name as institute_name'
                        )
                        ->distinct()
                        ->orderBy('pea.inst_code')
                        ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $ins_data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch subject list: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function evaluatorSubmit(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
            $data['subject_part_I']  = isset($data['subject_part_I']) && is_array($data['subject_part_I'])
                ? implode(',', $data['subject_part_I']) : null;
            $data['subject_part_II'] = isset($data['subject_part_II']) && is_array($data['subject_part_II'])
                ? implode(',', $data['subject_part_II']) : null;
            $data['institute'] = isset($data['institute']) && is_array($data['institute'])
                ? implode(',', $data['institute']) : null;
            $data['role_type'] = isset($data['role_type']) && is_array($data['role_type'])
                ? implode(',', $data['role_type']) : null;
            $updated_flag = false;
            if (!empty($data['id'])) {
                $updateData = $request->only([
                                'account_no',
                                'pan',
                                'bank_name',
                                'branch_name',
                                'ifsc_code',
                                'email',
                                'is_details_updated'
                            ]);
                $updated = DB::table('pharmacy_evaluator')
                    ->where('id', $data['id'])
                    ->where('ev_id', $data['ev_id'])
                    ->update($updateData);
                if (!$updated) {
                    throw new \Exception('No record updated. Invalid ID.');
                }
                $message = 'Evaluator data updated successfully';
                $updated_flag = true;
            } 
            else {

                $maxEvId = DB::table('pharmacy_evaluator')->max('ev_id');
                $data['ev_id'] = $maxEvId ? $maxEvId + 1 : 100001;
                unset($data['id']);
                DB::table('pharmacy_evaluator')->insert($data);
                $message = 'Evaluator data inserted successfully';
                $updated_flag = false;
            }
            DB::commit();
            return response()->json([
                'error' => false,
                'is_details_updated' => $updated_flag,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Evaluator Submit Error: ' . $e->getMessage());
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    //Allocation Submit
    public function allocationSubmit(Request $request)
    {
            $validated = Validator::make($request->all(), [
                'id' => ['nullable'],
                'exam_year' => ['required'],
                'cdc_code' => ['required'],
                'subject_code' => ['required'],
                'inst_code' => ['required'],
                'part_sem' => ['required'],

                'old_examiner_id' => ['nullable'],
                // 'old_head_examiner_id' => ['nullable'],
                // 'old_scrutinizer_id' => ['nullable'],

                'new_examiner_id' => ['required'],
                // 'new_head_examiner_id' => ['required'],
                // 'new_scrutinizer_id' => ['required'],
            ], [
                'new_examiner_id.required' => 'Examiner not selected',
                // 'new_head_examiner_id.required' => 'Head Examiner not selected',
                // 'new_scrutinizer_id.required' => 'Scrutinizer not selected',
            ]);

            if ($request->id) {
                $validated = Validator::make($request->all(), [
                    'old_examiner_id' => ['required'],
                    // 'old_head_examiner_id' => ['required'],
                    // 'old_scrutinizer_id' => ['required'],
                ], [
                    'old_examiner_id.required' => 'Old Examiner not selected',
                    // 'old_head_examiner_id.required' => 'Old Head Examiner not selected',
                    // 'old_scrutinizer_id.required' => 'Old Scrutinizer not selected',
                ]);
            }

            if ($validated->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validated->errors()->first(),
                ], 422);
            }

            $id = $request->id;
            $exam_year = $request->exam_year;
            $cdc_code = $request->cdc_code;
            $subject_code = $request->subject_code;
            $inst_code = $request->inst_code;
            $inst_code_list = is_array($inst_code) ? $inst_code : [$inst_code];
            $part_sem = $request->part_sem;

            $old_examiner_id = $request->old_examiner_id;
            $old_head_examiner_id = $request->old_head_examiner_id;
            $old_scrutinizer_id = $request->old_scrutinizer_id;

            $new_examiner_id = $request->new_examiner_id;
            $new_head_examiner_id = $request->new_head_examiner_id;
            $new_scrutinizer_id = $request->new_scrutinizer_id;

            try {
                DB::beginTransaction();

                // other data
                $evaluators = [
                    'EXAMINER' => [
                        'old_id' => $old_examiner_id,
                        'new_id' => $new_examiner_id,
                    ],
                    'HEAD_EXAMINER' => [
                        'old_id' => $old_head_examiner_id,
                        'new_id' => $new_head_examiner_id,
                    ],
                    'SCRUTINIZER' => [
                        'old_id' => $old_scrutinizer_id,
                        'new_id' => $new_scrutinizer_id,
                    ]
                ];

                // create or update allocation
                foreach ($inst_code_list as $code) {
                    EvaluatorAllocation::updateOrCreate(
                        [
                            'exam_year'    => $exam_year,
                            'cdc_code'     => $cdc_code,
                            'subject_code' => $subject_code,
                            'inst_code'    => $code,      // Insert one row per inst_code
                            'part_sem'     => $part_sem,
                        ],
                        [
                            'examiner_id'       => $new_examiner_id,
                            'head_examiner_id'  => $new_head_examiner_id,
                            'scrutinizer_id'    => $new_scrutinizer_id,
                        ]
                    );
                }

                DB::commit();

                return response()->json([
                    'error' => false,
                    'message' => 'Allocated Successfully'
                ], 200);
            } catch (Exception $e) {
                DB::rollback();
                return response()->json([
                    'error'     =>  true,
                    'message'   =>  $e->getMessage()
                ], 400);
            }
        
    }

    //Download PDF
    public function downloadPdf(Request $request)
    {
        $request->validate([
            'cdc_code' => ['required'],
            'subject_code' => ['required'],
            'exam_year' => ['required'],
            'inst_code' => ['required'],
            'part_sem' => ['required'],
            'evaluator_id' => ['required'],
        ]);

        $exam_year = $request->exam_year;
        $subject_code = $request->subject_code;
        $inst_code = $request->inst_code;
        $cdc_code = $request->cdc_code;
        $part_sem = $request->part_sem;
        $evaluator_id = $request->evaluator_id;

        $data = EvaluatorDetails::where([
            'is_active' => 1,
            'ev_id' => $evaluator_id,
        ])->with([
            'examinerAllocation' => function ($query) use ($subject_code, $exam_year, $inst_code) {
                $query->where([
                    'subject_code' => $subject_code,
                    'exam_year' => $exam_year,
                    'inst_code' => $inst_code,
                ]);
            },
            'headExaminerAllocation' => function ($query) use ($subject_code, $exam_year, $inst_code) {
                $query->where([
                    'subject_code' => $subject_code,
                    'exam_year' => $exam_year,
                    'inst_code' => $inst_code,
                ]);
            },
            'scrutinizerAllocation' => function ($query) use ($subject_code, $exam_year, $inst_code) {
                $query->where([
                    'subject_code' => $subject_code,
                    'inst_code' => $inst_code,
                    'exam_year' => $exam_year,
                ]);
            },
            'credential' => function ($query) use ($subject_code, $exam_year, $inst_code) {
                $query->where([
                    'subject_code' => $subject_code,
                    'exam_year' => $exam_year,
                    'inst_code' => $inst_code,
                ]);
            },
        ])->first();
        if ($data) {
            try {
                //$current_session = "2024-2025";
                $username = optional($data->credential)->username;

                $evaluator_type = $data->examinerAllocation ? 'EXAMINER' : ($data->headExaminerAllocation ? 'HEAD_EXAMINER' : ($data->scrutinizerAllocation ? 'SCRUTINIZER' : null));

                $pdf = Pdf::loadView('exports.evaluator-allocation-pdf', [
                    'name' => $data->name,
                    'exid' => $data->ev_id,
                    'email' => $data->email,
                    'phone' => $data->phone,
                    'designation' => $data->designation,
                    'exam_year' => $request->exam_year,
                    'evaluator_type' => Str::replace('_', ' ', $evaluator_type),
                    'paper_code' => $subject_code,
                    'paper_name' => $subject_code,
                    'nodal_name' => $subject_code,
                    'inst_code' => $inst_code,
                    'inst_name' => $inst_code,
                    'institute' => $data->vtc ?: json_decode($data->attached_vtc, true),
                    'memo_id' => $username,
                    'username' => $username,
                    'password' => optional($data->credential)->password,
                    'url' => env('MARKS_ENTRY_XII_URL'),
                    'session' => '',
                ]);

                return $pdf->setPaper('a4', 'portrait')
                    ->setOption(['defaultFont' => 'sans-serif'])
                    ->stream("Appointment-Letter_{$data->name}({$data->exid}).pdf");
            } catch (Exception $e) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage(),
                    // 'message' => 'Sorry! please try again later'
                ]);
            }
        }
    }

    //Send Mail
    public function sendMail(Request $request)
    {

        $request->validate([
            'cdc_code' => ['required'],
            'exam_year' => ['required'],
            'inst_code' => ['required'],
            'part_sem' => ['required'],
            'subject_code' => ['required'],
            'evaluator_id' => ['required'],
            'evaluator_type' => ['required'],
        ]);

        $data = EvaluatorDetails::where([
            'is_active' => 1,
            'ev_id' => $request->evaluator_id,
        ])->first();

        if ($data) {
            try {
                $mail_data = [
                    'exam_year' => $request->exam_year,
                    'evaluator_type' => $request->evaluator_type,
                    'url' => url('/api/evaluator/evaluator-download-pdf') . '?' . http_build_query([
                        'cdc_code' => $request->cdc_code,
                        'exam_year' => $request->exam_year,
                        'inst_code' => $request->inst_code,
                        'subject_code' => $request->subject_code,
                        'evaluator_id' => $request->evaluator_id,
                        'part_sem' => $request->part_sem,
                    ]),
                ];

                Mail::to($data->email)->send(new EvaluatorAppointmentMail($mail_data));

                $data->credential->update([
                    'is_mail_sent' => 1
                ]);

                return response()->json([
                    'error' => false,
                    'message' => 'Mail Sent Successfully'
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage(),
                    // 'message' => 'Sorry! please try again later'
                ]);
            }
        }
    }


    public function evaluatorList(Request $request)
    {
        try {
            $inst_code = $request->inst_code;

            $evaluator_data = DB::table('pharmacy_evaluator')
                ->where('institute', "like", "%" . $inst_code . "%")
                ->get();

            $subjectMap = DB::table('pharmacy_subjects_master')
                ->pluck('subject_name', 'general_code')
                ->toArray();

            $instituteMap = DB::table('institute_master')
                ->pluck('i_name', 'i_code')
                ->toArray();

            $result = [];
            foreach ($evaluator_data as $data) {

                $subjectPartI = explode(",", $data->subject_part_I);
                $subjectPartII = explode(",", $data->subject_part_II);
                $instituteCodes = explode(",", $data->institute);


                $subjectPartINames = array_map(fn($code) => $subjectMap[$code] ?? $code, $subjectPartI);
                $subjectPartIINames = array_map(fn($code) => $subjectMap[$code] ?? $code, $subjectPartII);


                $instituteNames = array_map(fn($code) => $instituteMap[$code] ?? $code, $instituteCodes);
                $role_type = explode(",", $data->role_type);
                $result[] = [
                    'id' => $data->id,
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'pan' => $data->pan,
                    'aadhaar' => $data->aadhaar,
                    'designation' => $data->designation,
                    'qualification' => $data->qualification,
                    'experiance_yr' => $data->experiance_yr,
                    'designation_type' => $data->designation_type,
                    'subject_part_I' => $subjectPartINames,
                    'subject_part_II' => $subjectPartIINames,
                    'institute' => $instituteNames,
                    'role_type' => $role_type,
                    'evaluator_yr' => $data->evaluator_yr,
                    'account_no' => $data->account_no,
                    'bank_name' => $data->bank_name,
                    'branch_name' => $data->branch_name,
                    'ifsc_code' => $data->ifsc_code
                ];
            }

            return response()->json([
                'error' => false,
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function evaluatorDetail(Request $request)
    {
        try {
            $id = $request->id;
            $evaluator_data = DB::table('pharmacy_evaluator as e')
                ->leftJoin('pharmacy_evaluator_allocations as a', 'a.examiner_id', '=', 'e.ev_id')
                ->where('e.id', $id)
                ->select('e.*', 'a.*')
                ->first();

            $subjectPartI = collect(explode(',', $evaluator_data->subject_part_I))
                ->map(fn($item) => (int) $item)
                ->toArray();

            $subject_part_II = collect(explode(',', $evaluator_data->subject_part_II))
                ->map(fn($item) => (int) $item)
                ->toArray();


            $result = [
                'id' => $evaluator_data->ev_id,
                'name' => $evaluator_data->name,
                'phone' => $evaluator_data->phone,
                'email' => $evaluator_data->email,
                'pan' => $evaluator_data->pan,
                'aadhaar' => $evaluator_data->aadhaar,
                'designation' => $evaluator_data->designation,
                'qualification' => $evaluator_data->qualification,
                'experiance_yr' => $evaluator_data->experiance_yr,
                'designation_type' => $evaluator_data->designation_type,
                'subject_part_I' => $subjectPartI,
                'subject_part_II' => $subject_part_II,
                'institute' => explode(",", $evaluator_data->institute),
                'role_type' => explode(",", $evaluator_data->role_type),
                'evaluator_yr' => $evaluator_data->evaluator_yr,
                'account_no' => $evaluator_data->account_no,
                'bank_name' => $evaluator_data->bank_name,
                'branch_name' => $evaluator_data->branch_name,
                'is_details_updated' => (bool)$evaluator_data->is_details_updated,
                'ifsc_code' => $evaluator_data->ifsc_code
            ];
            return response()->json([
                'error' => false,
                'data' => $result
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function evaluatorProfileInfo(Request $request)
    {
        try {
            $id = (int) $request->id;
            $evaluator = DB::table('pharmacy_evaluator as pe')
                        ->leftJoin('institute_master as im', function ($join) {
                            $join->on(
                                DB::raw("im.i_code::text = ANY (string_to_array(pe.institute, ','))"),
                                DB::raw('true')
                            );
                        })
                        ->select(
                            'pe.*',
                            DB::raw("STRING_AGG(im.i_name, ', ' ORDER BY im.i_name) as institute")
                        )
                        ->where('pe.ev_id', $id)
                        ->groupBy('pe.id')
                        ->first();

            if (!$evaluator) {
                return response()->json([
                    'error' => true,
                    'message' => 'Evaluator not found'
                ], 404);
            }
            $allocations = DB::table('pharmacy_evaluator_allocations as pea')
                        ->join('institute_master as im', 'im.i_code', '=', 'pea.inst_code')
                        ->select(
                            'pea.*',
                            'im.i_name'
                        )
                        ->where('pea.examiner_id', $id)
                        ->get();


            $inst_codes = $allocations->pluck('inst_code')->toArray();
            $inst_names = $allocations->pluck('i_name')->toArray();
            $subject_codes = $allocations->pluck('subject_code')->toArray();

            $inst_codes = array_values(array_unique(array_filter($inst_codes)));
            $inst_names = array_values(array_unique(array_filter($inst_names)));
            $inst_code_string = implode(',', $inst_codes);
            $subject_code_string = implode(',', $subject_codes);

            $result = [
                'id'               => $evaluator->ev_id,
                'name'             => $evaluator->name,
                'phone'            => $evaluator->phone,
                'email'            => $evaluator->email,
                'pan'              => $evaluator->pan,
                'aadhaar'          => $evaluator->aadhaar,
                'designation'      => $evaluator->designation,
                'qualification'    => $evaluator->qualification,
                'experiance_yr'    => $evaluator->experiance_yr,
                'designation_type' => $evaluator->designation_type,
                'institute'        => explode(",", $evaluator->institute),
                'allocated_inst_code' => $inst_codes, 
                'allocated_inst_name' => $inst_names, 
                'allocated_subj_code' => $subject_codes, 
                'allocated_inst_code_string' => $inst_code_string,
                'allocated_subj_code_string' => $subject_code_string,
                'role_type'        => explode(",", $evaluator->role_type),
                'evaluator_yr'     => $evaluator->evaluator_yr,
                'account_no'       => $evaluator->account_no,
                'bank_name'        => $evaluator->bank_name,
                'branch_name'      => $evaluator->branch_name,
                'is_details_updated' => (bool)$evaluator->is_details_updated,
                'ifsc_code'        => $evaluator->ifsc_code
            ];

            return response()->json([
                'error' => false,
                'data'  => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function evaluatorAllocationInstList(Request $request)
    {
        try {
            $cdc = $request->cdc_id;
            $exam_year = $request->exam_year;
            $allocatedInstitutes = DB::table('pharmacy_evaluator_allocations')->pluck('inst_code');

            $institute_list = DB::table("pharmacy_CDC_ins_tagging")
                ->join('institute_master', 'pharmacy_CDC_ins_tagging.inst_code', '=', 'institute_master.i_code')
                ->where('pharmacy_CDC_ins_tagging.cdc_code', $cdc)
                ->where('exam_year', $exam_year)
                // ->whereNotIn('pharmacy_CDC_ins_tagging.inst_code', $allocatedInstitutes)
                ->select('pharmacy_CDC_ins_tagging.inst_code', 'institute_master.i_name')
                ->get();


            return response()->json([
                'error' => false,
                'institute_list' => $institute_list
            ], 200);


        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function alocationSubjectList(Request $request)
{
    try {
        // Convert inst_code input to array
        $inst_code = $request->inst_code;
        $inst_code_list = is_array($inst_code) ? $inst_code : explode(',', $inst_code);
        $inst_code_list = array_map('trim', $inst_code_list);

        $part_sem = $request->part_sem;
        $exam_year = $request->exam_year;
        $get_id = $request->id ? $request->id : '';

        // Fetch all subjects
        $subject_data = DB::table("pharmacy_subjects_master")
            ->where('semester', $part_sem)
            ->where('subject_type', 'Theoretical')
            ->where('type', 'Main')
            ->where('is_active', 1)
            ->pluck('subject_name', 'general_code')
            ->toArray();

        // Find which institutes exist in allocation table
        $found_institutes = DB::table('pharmacy_evaluator_allocations')
            ->where('exam_year', $exam_year)
            ->where('part_sem', $part_sem)
            ->whereIn('inst_code', $inst_code_list)
            ->distinct()
            ->pluck('inst_code')
            ->toArray();

        // If any institute NOT found ? return all subjects
        if (count($found_institutes) !== count($inst_code_list)) {
            $subject_codes = array_keys($subject_data);
        } else {
            // If all institutes exist ? exclude allocated subjects
            $allocated_subjects = DB::table('pharmacy_evaluator_allocations')
                ->where('exam_year', $exam_year)
                ->where('part_sem', $part_sem)
                ->whereIn('inst_code', $inst_code_list)
                ->pluck('subject_code')
                ->map(fn($v) => (int)$v)
                ->unique()
                ->toArray();
        if($get_id){
                $subject_codes = array_keys($subject_data);
            }
            else{
                $subject_codes = array_diff(array_keys($subject_data), $allocated_subjects);
            }
            
        }

        //  Format the final array
        $result = [];
        foreach ($subject_codes as $code) {
            $result[] = [
                'subject_code' => (int)$code,
                'subject_name' => $subject_data[$code] ?? null
            ];
        }

        return $result; // Plain array of objects as requested

    } catch (\Exception $e) {
        return [
            'error' => true,
            'message' => $e->getMessage()
        ];
    }
    }



    public function evaluatorRollList(Request $request)
    {
        try {

            // Normalize inst_code (string or array)
            $inst_code = $request->inst_code;
            $inst_code_list = is_array($inst_code) ? $inst_code : [$inst_code];

            $exam_year  = $request->exam_year;
            $part_sem   = $request->part_sem;
            $subject_code = (int) $request->subject_code;

            $subject_column = ($part_sem === 'Part_I') ? 'subject_part_I' : 'subject_part_II';

            // Define roles
            $roles = [
                'scrutinizer'    => 'scrutinizers',
                'head_examiner'  => 'head_examiners',
                'examiner'       => 'examiners'
            ];

            // Start query
            $data = DB::table('pharmacy_evaluator')
                ->where('evaluator_yr', $exam_year)
                ->where($subject_column, "LIKE", "%{$subject_code}%")
                ->where(function ($query) use ($inst_code_list) {
                    foreach ($inst_code_list as $code) {
                        if (!empty($code)) {
                            $query->where('institute', 'NOT LIKE', "%{$code}%");
                        }
                    }
                })
                ->select('ev_id', 'name', 'role_type','phone')
                ->get();

            // Prepare result grouped by role
            $result = [];

            foreach ($roles as $single => $groupName) {
                $result[$groupName] = $data->filter(function ($item) use ($single) {
                    $roleTypes = explode(',', $item->role_type);
                    return in_array($single, $roleTypes);
                })->map(function ($item) {
                    return [
                        'id'   => $item->ev_id,
                        'name' => $item->name,
                        'role_type' => $item->role_type,
                        'phone' => $item->phone,
                    ];
                })->values();
            }

            return response()->json([
                'error' => false,
                'list'  => $result
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error'   => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    
    public function evaluatorAllocationCDCList(Request $request)
    {
        try {

            $cdc_list = DB::table("pharmacy_cdc_master")
                ->select("CDC_name", "cdc_code")
                ->get()
                ->map(function ($item) {
                    return [
                        'cdc_name' => $item->CDC_name,
                        'cdc_code' => $item->cdc_code,
                    ];
                });

            return response()->json([
                'error' => false,
                'cdc_list' => $cdc_list
            ], 200);


        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    private function evaluatorsName($id)
    {
        try {

            if (!is_numeric($id)) {
                \Log::warning("Evaluator ID is not numeric: " . $id);
                return null;
            }

            return DB::table("pharmacy_evaluator")
                ->where("ev_id", (int) $id)
                ->value("name");

        } catch (Exception $e) {
            \Log::error("Error fetching evaluator name: " . $e->getMessage());
            return null;
        }
    }

    private function evaluatorsInstitute($id)
    {
        try {

            if (!is_numeric($id)) {
                \Log::warning("Evaluator ID is not numeric: " . $id);
                return null;
            }

            return DB::table("pharmacy_evaluator")
                ->where("ev_id", (int) $id)
                ->value("institute");

        } catch (Exception $e) {
            \Log::error("Error fetching evaluator institute: " . $e->getMessage());
            return null;
        }
    }

    private function evaluatorsPhone($id)
    {
        try {

            if (!is_numeric($id)) {
                \Log::warning("Evaluator ID is not numeric: " . $id);
                return null;
            }

            return DB::table("pharmacy_evaluator")
                ->where("ev_id", (int) $id)
                ->value("phone");

        } catch (Exception $e) {
            \Log::error("Error fetching evaluator phone: " . $e->getMessage());
            return null;
        }
    }


    public function allocationList(Request $request)
    {
        try {

            $exam_year = $request->exam_year;
            $cdc_code = $request->cdc_code;
            $part_sem = $request->part_sem;

            $allocation_list = DB::table("pharmacy_evaluator_allocations")
                ->join('pharmacy_cdc_master', 'pharmacy_evaluator_allocations.cdc_code', '=', 'pharmacy_cdc_master.cdc_code')
                ->join('institute_master', 'pharmacy_evaluator_allocations.inst_code', '=', 'institute_master.i_code')
                ->join('pharmacy_subjects_master', DB::raw("CAST(pharmacy_evaluator_allocations.subject_code AS INTEGER)"), '=', 'pharmacy_subjects_master.general_code')
                ->where('pharmacy_evaluator_allocations.exam_year', $exam_year)
                ->where('pharmacy_evaluator_allocations.cdc_code', $cdc_code)
                ->where('pharmacy_evaluator_allocations.part_sem', $part_sem)

                ->select(
                    'pharmacy_cdc_master.CDC_name',
                    'institute_master.i_name',
                    'pharmacy_subjects_master.subject_name',
                    'pharmacy_evaluator_allocations.*'
                )
                ->get();



            $allocation_data = [];

            foreach ($allocation_list as $data) {
                $allocation_data[] = [
                    "id" => $data->id,
                    "cdc_name" => $data->CDC_name,
                    "cdc_code" => $data->cdc_code,
                    "subject_name" => $data->subject_name,
                    "own_institute" => $this->evaluatorsInstitute($data->examiner_id) ?? null,
                    "subject_code" => $data->subject_code,
                    "inst_code" => $data->inst_code,
                    "inst_name" => $data->i_name,
                    "scrutinizer_id" => $data->scrutinizer_id,
                    "scrutinizer_name" => $this->evaluatorsName($data->scrutinizer_id),
                    "scrutinizer_phone" => $this->evaluatorsPhone($data->scrutinizer_id),
                    "head_examiner_id" => $data->head_examiner_id,
                    "head_examiner_name" => $this->evaluatorsName($data->head_examiner_id),
                    "head_examiner_phone" => $this->evaluatorsPhone($data->head_examiner_id),
                    "examiner_id" => $data->examiner_id,
                    "examiner_name" => $this->evaluatorsName($data->examiner_id) ?? null,
                    "examiner_phone" => $this->evaluatorsPhone($data->examiner_id) ?? null
                ];
            }

            return response()->json([
                'error' => false,
                'list' => $allocation_data
            ], 200);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function allocationDetail(Request $request)
    {
        try {
            $id = $request->id;
            $allocation = DB::table("pharmacy_evaluator_allocations")
                ->where('id', $id)
                ->select(
                    'id',
                    'exam_year',
                    'part_sem',
                    'inst_code',
                    'subject_code',
                    'examiner_id',
                    'head_examiner_id',
                    'scrutinizer_id'
                )
                ->first();

            if (!$allocation) {
                return response()->json([
                    'error' => true,
                    'message' => 'Allocation not found'
                ], 404);
            }

            $part_sem = $allocation->part_sem;
            $exam_year = $allocation->exam_year;
            $inst_code = $allocation->inst_code;
            $subject_code = (int) $allocation->subject_code;
            $subject_column = $part_sem === 'Part_I' ? 'subject_part_I' : 'subject_part_II';

            $roles = [
                'scrutinizer' => 'scrutinizers',
                'head_examiner' => 'head_examiners',
                'examiner' => 'examiners'
            ];

            $evaluators = DB::table('pharmacy_evaluator')
                ->where('evaluator_yr', $exam_year)
                ->whereRaw('institute NOT LIKE ?', ['%' . $inst_code . '%'])
                ->where($subject_column, "like", "%" . $subject_code . "%")
                ->select('ev_id', 'name', 'role_type','phone')
                ->get();

            $result = [];
            foreach ($roles as $role => $key) {
                $result[$key] = $evaluators->filter(function ($item) use ($role) {
                    return in_array($role, explode(",", $item->role_type) ?? []);
                })->map(function ($item) {
                    return [
                        'id' => $item->ev_id,
                        'name' => $item->name,
                        'role_type' => $item->role_type,
                        'phone' => $item->phone
                    ];
                })->values();
            }


            $responseData = [
                "id" => $allocation->id,
                "exam_year" => $allocation->exam_year,
                "subject_code" => $allocation->subject_code,
                "inst_code" => $allocation->inst_code,
                "part_sem" => $allocation->part_sem,
                "examiner_value" => $allocation->examiner_id,
                "head_examiner_value" => $allocation->head_examiner_id,
                "scrutinizer_value" => $allocation->scrutinizer_id
            ];

            return response()->json([
                'error' => false,
                'info' => $responseData,
                'list' => $result
            ], 200);


        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }

    }

}
