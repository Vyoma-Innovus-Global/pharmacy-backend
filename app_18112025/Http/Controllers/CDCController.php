<?php

namespace App\Http\Controllers;

use DateTime;
use Exception;
use App\Models\CDC;
use App\Models\User;
use App\Models\Token;

use App\Models\Trade;
use App\Models\District;
use App\Models\Schedule;
use App\Models\Institute;
use App\Models\SuperUser;
use App\Models\Enrollment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CDCInstituteTag;
use App\Models\Registerstudent;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;


class CDCController extends Controller
{
    public function  CDCList(Request $request)
    {
        
                        try {
                            $data = CDC::where('is_active', 1)->get();
                            return response()->json(['error' => false, 'data' => $data], 200);
                        } catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 500);
                        }
                   
    }
    public function CDCInstTagg(Request $request)
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
                                } else {
                                    return response()->json(['error' => true, 'message' => 'Invalid institute input'], 400);
                                }
                                foreach ($institutes as $institute) {
                                    CDCInstituteTag::updateOrInsert(
                                        [
                                            'cdc_code'   => $cdc_code,
                                            'inst_code'  => $institute->i_code,
                                            'exam_year'  => $exam_year,
                                        ]

                                    );
                                }

                                DB::commit();
                                return response()->json([
                                    'success' => true,
                                    'message' => 'All active institutes tagged successfully',
                                    'tagged_institutes' => $institutes->pluck('i_code') // Optional return
                                ]);
                            } catch (\Exception $e) {
                                DB::rollback();
                                return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
                            }
                        }
    }
}
