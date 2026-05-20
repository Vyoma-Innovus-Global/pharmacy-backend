<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminDesignationController extends Controller
{
    /**
     * POST /api/admin/designations
     *
     * Get all designations list.
     *
     * Body: {
     *   "admin_user_id": 668
     * }
     *
     * Calls: fn_admin_getalldesignation(p_admin_user_id)
     */
    public function getAllDesignations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_id' => 'required|integer',
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

        Log::channel('daily')->info('[getAllDesignations] INPUT', [
            'admin_user_id' => $adminUserId,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getalldesignation(?::bigint) AS data',
                [$adminUserId]
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
            $designations = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[getAllDesignations] JSON_DECODE_ERROR', [
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

            if (empty($designations)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No designations found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getAllDesignations] OUTPUT (200)', [
                'count' => count($designations),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $designations,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getAllDesignations] EXCEPTION', [
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
