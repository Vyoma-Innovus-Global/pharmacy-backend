<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminInstituteController extends Controller
{
    /**
     * POST /api/admin/institutes
     *
     * Get all institutes list.
     *
     * Body: {
     *   "admin_user_id": 668
     * }
     *
     * Calls: fn_admin_getallinstitutes_v1(p_admin_user_id)
     *
     * @OA\Post(
     *     path="/api/admin/institutes",
     *     tags={"Admin - Master Data"},
     *     summary="Get all institutes",
     *     description="Retrieve all institutes using fn_admin_getallinstitutes_v1",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"admin_user_id"},
     *             @OA\Property(property="admin_user_id", type="integer", example=668)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getAllInstitutes(Request $request)
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

        Log::channel('daily')->info('[getAllInstitutes] INPUT', [
            'admin_user_id' => $adminUserId,
            'ip'            => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_getallinstitutes_v1(?::bigint) AS data',
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
            $institutes = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[getAllInstitutes] JSON_DECODE_ERROR', [
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

            if (empty($institutes)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No institutes found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getAllInstitutes] OUTPUT (200)', [
                'count' => count($institutes),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $institutes,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getAllInstitutes] EXCEPTION', [
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

    public function getInstituteList(Request $request)
    {
        Log::channel('daily')->info('[getInstituteList] INPUT', [
            'ip' => $request->ip(),
        ]);

        try {
            $result = DB::select('SELECT public.fn_get_institute_list() AS data');

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No institutes found.',
                    'data'    => [],
                ], 404);
            }

            $institutes = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[getInstituteList] JSON_DECODE_ERROR', [
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

                if (array_is_list($decoded)) {
                    $institutes = array_merge($institutes, $decoded);
                } else {
                    $institutes[] = $decoded;
                }
            }

            if (empty($institutes)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No institutes found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getInstituteList] OUTPUT (200)', [
                'count' => count($institutes),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'count'   => count($institutes),
                'data'    => $institutes,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getInstituteList] EXCEPTION', [
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
