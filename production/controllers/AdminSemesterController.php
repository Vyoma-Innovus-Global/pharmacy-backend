<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminSemesterController extends Controller
{
    /**
     * POST /api/admin/semesters
     *
     * Get all semesters list filtered by semester type.
     *
     * Body: {
     *   "admin_user_id": 668,
     *   "semester_type": 1
     * }
     *
     * Calls: fn_admin_getallsemesters(p_admin_user_id, p_semester_type)
     */
    public function getAllSemesters(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
            'semester_type' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $adminUserId  = (int) $request->input('admin_user_id');
        $semesterType = (int) $request->input('semester_type');

        Log::channel('daily')->info('[getAllSemesters] INPUT', [
            'admin_user_id' => $adminUserId,
            'semester_type' => $semesterType,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getallsemesters(?::bigint, ?::integer) AS data',
                [$adminUserId, $semesterType]
            );

            $raw = $result[0]->data ?? null;

            if (!$raw) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No data returned from database function.',
                    'data'    => [],
                ], 404);
            }

            // DB function returns JSON string
            $semesters = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[getAllSemesters] JSON_DECODE_ERROR', [
                    'error' => json_last_error_msg(),
                    'raw'   => $raw,
                ]);
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to parse database response.',
                    'data'    => null,
                ], 500);
            }

            if (empty($semesters)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No semesters found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getAllSemesters] OUTPUT (200)', [
                'count' => count($semesters),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $semesters,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getAllSemesters] EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
