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

    /**
     * @OA\Post(
     *     path="/api/admin/ra-institute-list",
     *     tags={"Admin Institutes"},
     *     summary="Get list of RA (Re-Admission) institutes",
     *     description="Calls PostgreSQL stored function fn_get_ra_institute_list to retrieve all Re-Admission institutes.",
     *     security={{"token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Data fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Data fetch successfully"),
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="i_code", type="string", example="RAIP"),
     *                     @OA\Property(property="i_name", type="string", example="RISHI AUROBINDO INSTITUTE")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getRaInstituteList(Request $request)
    {
        Log::channel('daily')->info('[getRaInstituteList] INPUT', [
            'ip' => $request->ip(),
        ]);

        try {
            $result = DB::select('SELECT public.fn_get_ra_institute_list() AS data');

            if (empty($result)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => 'No institutes found.',
                    'count'   => 0,
                    'data'    => [],
                ], 200);
            }

            $institutes = [];

            foreach ($result as $row) {
                $raw = $row->data ?? null;

                if ($raw === null) {
                    continue;
                }

                $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('daily')->error('[getRaInstituteList] JSON_DECODE_ERROR', [
                        'error' => json_last_error_msg(),
                        'raw'   => $raw,
                    ]);

                    return response()->json([
                        'version' => '1.0',
                        'status'  => 3,
                        'message' => 'Failed to parse database response.',
                        'data'    => null,
                    ], 500);
                }

                if (is_array($decoded) && array_is_list($decoded)) {
                    $institutes = array_merge($institutes, $decoded);
                } elseif (is_array($decoded)) {
                    $institutes[] = $decoded;
                }
            }

            Log::channel('daily')->info('[getRaInstituteList] OUTPUT (200)', [
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
            Log::channel('daily')->error('[getRaInstituteList] EXCEPTION', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 3,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}
