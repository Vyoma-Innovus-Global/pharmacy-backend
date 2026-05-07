<?php
namespace App\Http\Controllers;
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
    public function evaluatorSubmit(Request $request)
    {
        try {
            $data = $request->all();
            
            $data['subject_part_I'] = implode(",", $data['subject_part_I']);
            $data['subject_part_II'] = implode(",", $data['subject_part_II']);
            $data['institute'] = implode(",", $data['institute']);
            $data['role_type'] = implode(",", $data['role_type']);
            $condition = ['id' => $data['id'] ?? null];
            $exists = DB::table('pharmacy_evaluator')->where($condition)->exists();

            if ($exists) {
                // If exists, update the record
                DB::table('pharmacy_evaluator')->where($condition)->update($data);
            } else {
                // Get the current max ev_id
                $maxEvId = DB::table('pharmacy_evaluator')->max('ev_id');

                // Set next ev_id (start from 100001 if table is empty)
                $nextEvId = $maxEvId ? $maxEvId + 1 : 100001;

                // Add ev_id to the data array
                $data['ev_id'] = $nextEvId;
                unset($data['id']);

                
                // Insert the new record
                DB::table('pharmacy_evaluator')->insert($data);
            }
            /* $condition = ['ev_id' => $data['id'] ?? null];
            $exists = DB::table('pharmacy_evaluator')->where($condition)->exists();
            DB::table('pharmacy_evaluator')->updateOrInsert($condition, $data); */
            $message = $exists ? 'Evaluator data updated successfully' : 'Evaluator data inserted successfully';

            return response()->json([
                'error' => false,
                'message' => $message
            ]);

        } catch (Exception $e) {
            DB::rollback();
            generateLaravelLog($e);
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
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
                'old_head_examiner_id' => ['nullable'],
                'old_scrutinizer_id' => ['nullable'],

                'new_examiner_id' => ['required'],
                'new_head_examiner_id' => ['required'],
                'new_scrutinizer_id' => ['required'],
            ], [
                'new_examiner_id.required' => 'Examiner not selected',
                'new_head_examiner_id.required' => 'Head Examiner not selected',
                'new_scrutinizer_id.required' => 'Scrutinizer not selected',
            ]);

            if ($request->id) {
                $validated = Validator::make($request->all(), [
                    'old_examiner_id' => ['required'],
                    'old_head_examiner_id' => ['required'],
                    'old_scrutinizer_id' => ['required'],
                ], [
                    'old_examiner_id.required' => 'Old Examiner not selected',
                    'old_head_examiner_id.required' => 'Old Head Examiner not selected',
                    'old_scrutinizer_id.required' => 'Old Scrutinizer not selected',
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
                EvaluatorAllocation::updateOrCreate([
                    'exam_year' => $exam_year,
                    'cdc_code' => $cdc_code,
                    'subject_code' => $subject_code,
                    'inst_code' => $inst_code,
                    'part_sem' => $part_sem,
                ], [
                    'examiner_id' => $new_examiner_id,
                    'head_examiner_id' => $new_head_examiner_id,
                    'scrutinizer_id' => $new_scrutinizer_id,
                ]);

                foreach ($evaluators as $evaluator_type => $evaluator_data) {
                    $old_evaluator_id = $evaluator_data['old_id'];
                    $new_evaluator_id = $evaluator_data['new_id'];

                    // evaluator credentials
                    $year = Str::substr($exam_year, 2, 2);
                    $exid = str_pad($new_evaluator_id, 6, '0', STR_PAD_LEFT);
                    $subject = $subject_code;

                    $username = "{$year}E{$exid}{$subject}";
                    $password = "{$exid}Y{$year}{$subject}";

                    $old_credential = EvaluatorCredential::where([
                        'exam_year' => $exam_year,
                        'inst_code' => $inst_code,
                        'subject_code' => $subject_code,
                        'part_sem' => $part_sem,
                        'evaluator_id' => $old_evaluator_id,
                    ])->first();

                    $old_username = null;

                    if ($old_credential) {
                        $old_username = $old_credential->username;
                        $old_credential->delete();
                    }

                    EvaluatorCredential::updateOrCreate([
                        'exam_year' => $exam_year,
                        'inst_code' => $inst_code,
                        'subject_code' => $subject_code,
                        'part_sem' => $part_sem,
                        'evaluator_id' => $new_evaluator_id,
                    ], [
                        'username' => $username,
                        'password' => $password,
                    ]);

                    // check if old credential exists
                    $credentialExists = EvaluatorCredential::where([
                        'exam_year' => $exam_year,
                        'inst_code' => $inst_code,
                        'subject_code' => $subject_code,
                        'part_sem' => $part_sem,
                        'evaluator_id' => $old_evaluator_id,
                    ])->first();

                    // user login data
                    $role_data = Role::where('role_name', 'EVALUATOR')->first();
                    
                    if ($role_data) {
                        $evaluator_detail = DB::table('pharmacy_evaluator')->where('ev_id', $new_evaluator_id)->first();
                       // dd($role_data->role_id,$new_evaluator_id,$subject_code,$username);
                       
                        User::updateOrCreate([
                            'u_role_id' => $role_data->role_id,
                            'u_ref' => $new_evaluator_id,
                            'subject_code' => $subject_code,
                        ], [
                            'u_username' => $username,
                            'u_password' => hash("sha512", $password),
                            'is_active' => 1,
                            'u_phone' => $evaluator_detail->phone,
                            'u_email' => $evaluator_detail->email,
                            'u_fullname' => $evaluator_detail->name,
                            'u_designation' => $evaluator_detail->designation,
                        ]);
                        $old_user = User::where([
                            'u_role_id' => $role_data->role_id,
                            'u_ref' => $old_evaluator_id,
                            'subject_code' => $subject_code,
                        ])->first();

                        if ($old_user && !$credentialExists) {
                            $old_user->delete();
                        }
                    }

                    auditTrail($cdc_code, "examiner id: {$new_examiner_id}, head examiner id: {$new_head_examiner_id}, scrutinizer id: {$new_scrutinizer_id}, allocated by CDC {$cdc_code} for subject {$subject_code} at vtc {$inst_code}");

                    // end loop
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
            json:
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
            $evaluator_data = DB::table('pharmacy_evaluator')
                ->where('id', $id)
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
            $inst_code = $request->inst_code;
            $part_sem = $request->part_sem;
            $exam_year = $request->exam_year;
            $get_id = $request->id ? $request->id : '';
            $subject_column = ($part_sem === 'Part_I') ? 'subject_part_I' : 'subject_part_II';


            $subject_data = DB::table("pharmacy_subjects_master")
                ->where('semester', $part_sem)
                ->where('subject_type', 'Theoretical')
                ->where('type', 'Main')
                ->where('is_active', 1)
                ->pluck('subject_name', 'general_code')
                ->toArray();

            $subject_data2 = DB::table("pharmacy_subjects_master")
                        ->where('semester', $part_sem)
                        ->where('subject_type', 'Theoretical')
                        ->where('type', 'Main')
                        ->where('is_active', 1)
                        ->whereNotIn('general_code', function ($query) use ($exam_year, $inst_code, $part_sem) {
                                    $query->select(DB::raw('subject_code::integer'))
                                        ->from('pharmacy_evaluator_allocations')
                                        ->where('exam_year', $exam_year)
                                        ->where('inst_code', $inst_code)
                                        ->where('part_sem', $part_sem);
                                })
                        ->pluck('subject_name', 'general_code')
                        ->toArray();
            if($get_id){
                $subject_codes = array_keys($subject_data);
            }
            else{
                $subject_codes = array_keys($subject_data2);
            }
            return response()->json(
                collect($subject_codes)->map(function ($code) use ($subject_data) {
                    return [
                        'subject_code' => $code,
                        'subject_name' => $subject_data[$code] ?? null
                    ];
                })
            );

            // $subject_codes = DB::table('pharmacy_evaluator')
            //     ->where('evaluator_yr',$exam_year)
            //     ->whereJsonContains('institute', $inst_code)
            //     ->pluck($subject_column)
            //     ->flatMap(function ($item) {
            //         return json_decode($item, true) ?: [];
            //     })
            //     ->unique()
            //     ->values();

            // $subject_data = DB::table('pharmacy_subjects_master')
            //     ->whereIn('general_code', $subject_codes)
            //     ->pluck('subject_name', 'general_code');

            // return response()->json(
            //     $subject_codes->map(function ($code) use ($subject_data) {
            //         return [
            //             'subject_code' => $code,
            //             'subject_name' => $subject_data[$code] ?? null
            //         ];
            //     })
            // );


        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 400);
        }

    }
    public function evaluatorRollList(Request $request)
    {

        try {
            $inst_code = $request->inst_code;

            $exam_year = $request->exam_year;
            $part_sem = $request->part_sem;

            $subject_code = (int) $request->subject_code;

            $subject_column = ($part_sem === 'Part_I') ? 'subject_part_I' : 'subject_part_II';

            $roles = [
                'scrutinizer' => 'scrutinizers',
                'head_examiner' => 'head_examiners',
                'examiner' => 'examiners'
            ];

            $data = DB::table('pharmacy_evaluator')
                ->where('evaluator_yr', $exam_year)
                ->where('institute', 'NOT LIKE', "%" . $inst_code . "%")
                ->where($subject_column, "like", "%" . $subject_code . "%")
                ->select('ev_id', 'name', 'role_type')
                ->get()
                /*  ->map(function ($item) {
                     $item->role_type = json_decode($item->role_type);
                     return $item;
                 }) */ ;

            $result = [];

            foreach ($roles as $singular => $plural) {
                $result[$plural] = $data->filter(function ($item) use ($singular) {
                    $role_type = explode(',', $item->role_type);
                    return in_array($singular, $role_type);
                })->map(function ($item) {
                    return [
                        'id' => $item->ev_id,
                        'name' => $item->name,
                    ];
                })->values();
            }

            return response()->json([
                'error' => false,
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
    
    public function evaluatorAllocationCDCList(Request $request)
    {
        try {

            $cdc_list = DB::table("pharmacy_CDC_master")
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


    public function allocationList(Request $request)
    {
        try {

            $exam_year = $request->exam_year;
            $cdc_code = $request->cdc_code;
            $part_sem = $request->part_sem;

            $allocation_list = DB::table("pharmacy_evaluator_allocations")
                ->join('pharmacy_CDC_master', 'pharmacy_evaluator_allocations.cdc_code', '=', 'pharmacy_CDC_master.cdc_code')
                ->join('institute_master', 'pharmacy_evaluator_allocations.inst_code', '=', 'institute_master.i_code')
                ->join('pharmacy_subjects_master', DB::raw("CAST(pharmacy_evaluator_allocations.subject_code AS INTEGER)"), '=', 'pharmacy_subjects_master.general_code')
                ->where('pharmacy_evaluator_allocations.exam_year', $exam_year)
                ->where('pharmacy_evaluator_allocations.cdc_code', $cdc_code)
                ->where('pharmacy_evaluator_allocations.part_sem', $part_sem)

                ->select(
                    'pharmacy_CDC_master.CDC_name',
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
                    "subject_code" => $data->subject_code,
                    "inst_code" => $data->inst_code,
                    "inst_name" => $data->i_name,
                    "scrutinizer_id" => $data->scrutinizer_id,
                    "scrutinizer_name" => $this->evaluatorsName($data->scrutinizer_id),
                    "head_examiner_id" => $data->head_examiner_id,
                    "head_examiner_name" => $this->evaluatorsName($data->head_examiner_id),
                    "examiner_id" => $data->examiner_id,
                    "examiner_name" => $this->evaluatorsName($data->examiner_id) ?? null
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
                ->select('ev_id', 'name', 'role_type')
                ->get()
                /*  ->map(function ($item) {
                     $item->role_type = json_decode($item->role_type);
                     return $item;
             }) */ ;

            $result = [];
            foreach ($roles as $role => $key) {
                $result[$key] = $evaluators->filter(function ($item) use ($role) {
                    return in_array($role, explode(",", $item->role_type) ?? []);
                })->map(function ($item) {
                    return [
                        'id' => $item->ev_id,
                        'name' => $item->name
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
