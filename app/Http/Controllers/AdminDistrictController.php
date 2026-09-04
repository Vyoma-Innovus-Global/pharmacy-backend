<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDistrictController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/district-list",
     *     tags={"Admin - Master Data"},
     *     summary="Get district list by admin",
     *     description="Retrieves the list of all districts for admin. Calls: public.fn_getdistrictlistbyadmin()",
     *     security={{"token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="District list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Data fetch successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="districtId", type="integer", example=1),
     *                     @OA\Property(property="districtName", type="string", example="ALIPURDUAR")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No districts found",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="No districts found."),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Server error occurred."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     *
     * GET /api/admin/district-list
     * Calls: public.fn_getdistrictlistbyadmin()
     */
    public function getDistrictListByAdmin(Request $request)
    {
        Log::channel('daily')->info('[getDistrictListByAdmin] Fetching district list', [
            'ip' => $request->ip(),
        ]);

        try {
            $result = DB::select('SELECT public.fn_getdistrictlistbyadmin() AS data');

            $raw = $result[0]->data ?? null;

            if (!$raw) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No data returned from database function.',
                    'data'    => [],
                ], 404);
            }

            // DB function returns JSON string or array
            $districts = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('daily')->error('[getDistrictListByAdmin] JSON_DECODE_ERROR', [
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

            if (empty($districts)) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'No districts found.',
                    'data'    => [],
                ], 404);
            }

            Log::channel('daily')->info('[getDistrictListByAdmin] OUTPUT (200)', [
                'count' => count($districts),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Data fetch successfully',
                'data'    => $districts,
            ], 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[getDistrictListByAdmin] EXCEPTION', [
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
