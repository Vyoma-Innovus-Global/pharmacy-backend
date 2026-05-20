<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminDetailsController extends Controller
{
    /**
     * Get Admin Details by Username using PostgreSQL function.
     *
     * Calls: public.fn_getadmindetailsbyusername(p_username, p_user_type_id)
     *
     * GET  /admin-details/by-username?username=AIE&user_type_id=8
     * POST /admin-details/by-username  { "username": "AIE", "user_type_id": 8 }
     */
    public function getByUsername(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'     => 'required|string|max:100',
            'user_type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        $username     = $request->input('username');
        $userTypeId   = (int) $request->input('user_type_id');

        try {
            // Call the PostgreSQL function
            $result = DB::select(
                'SELECT public.fn_getadmindetailsbyusername(?, ?) AS data',
                [$username, $userTypeId]
            );

            if (empty($result)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No data found for the given username and user type.',
                ], 404);
            }

            // The function returns a JSON object — decode it
            $raw = $result[0]->data ?? null;

            if (is_null($raw)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No admin record found.',
                ], 404);
            }

            // If the DB driver returns a string, decode it
            $adminData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE || empty($adminData)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Failed to parse admin data from database.',
                ], 500);
            }

            // Strip sensitive fields before returning
            unset($adminData['adminUserPassword']);

            return response()->json([
                'error' => false,
                'data'  => $adminData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Admin Details with password included (internal/privileged use only).
     * Should be called only from authenticated + authorized admin context.
     *
     * POST /admin-details/raw
     */
    public function getRaw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'     => 'required|string|max:100',
            'user_type_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => true,
                'message' => $validator->errors(),
            ], 422);
        }

        $username   = $request->input('username');
        $userTypeId = (int) $request->input('user_type_id');

        try {
            $result = DB::select(
                'SELECT public.fn_getadmindetailsbyusername(?, ?) AS data',
                [$username, $userTypeId]
            );

            if (empty($result)) {
                return response()->json([
                    'error'   => true,
                    'message' => 'No data found.',
                ], 404);
            }

            $raw = $result[0]->data ?? null;
            $adminData = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            return response()->json([
                'error' => false,
                'data'  => $adminData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
