<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminDepartmentController extends Controller
{
    /**
     * POST /api/admin/departments
     *
     * Get all departments by institute and semester.
     *
     * Body: {
     *   "admin_user_id": 1,
     *   "inst_code": "AMNA",
     *   "semester_id": 1
     * }
     *
     * Calls: fn_admin_getdepartmentsbyinst(p_inst_code, p_semester_id)
     */
    public function getDepartmentsByInst(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
            'inst_code'     => 'required|string',
            'semester_id'   => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $adminUserId = (int) $request->input('admin_user_id');
        $instCode    = $request->input('inst_code');
        $semesterId  = (int) $request->input('semester_id');

        Log::channel('daily')->info('[getDepartmentsByInst] INPUT', [
            'admin_user_id' => $adminUserId,
            'inst_code'     => $instCode,
            'semester_id'   => $semesterId,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getdepartmentsbyinst(?, ?) AS data',
                [$instCode, $semesterId]
            );

            if (empty($result)) {
                Log::channel('daily')->warning('[getDepartmentsByInst] No result from DB function');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No data returned from database function.',
                    'data'    => [],
                ]);
            }

            $rawData = $result[0]->data ?? null;

            if (is_null($rawData)) {
                Log::channel('daily')->warning('[getDepartmentsByInst] Null data from DB function');
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No departments found.',
                    'data'    => [],
                ]);
            }

            // DB function returns JSON string
            $departments = is_string($rawData) ? json_decode($rawData, true) : (array) $rawData;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[getDepartmentsByInst] JSON_DECODE_ERROR', [
                    'error' => json_last_error_msg(),
                    'raw'   => $rawData,
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to parse database response.',
                    'data'    => null,
                ], 500);
            }

            if (empty($departments)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No departments found for this institute and semester.',
                    'data'    => [],
                ]);
            }

            Log::channel('daily')->info('[getDepartmentsByInst] OUTPUT (200)', [
                'count' => count($departments),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $departments,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getDepartmentsByInst] EXCEPTION', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Server error occurred.',
                'data'    => null,
            ], 500);
        }
    }
}
