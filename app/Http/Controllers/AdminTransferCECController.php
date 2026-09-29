<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminTransferCECController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/admin/transfer-cec",
     *     tags={"CEC - Answer Scripts"},
     *     summary="Transfer Answer Scripts to CEC (Single or Array)",
     *     description="Transfers answer script(s) to a Central Evaluation Center (CEC) by calling public.fn_admin_transfer_cec(p_answerscriptId, p_cec_code, p_userId)",
     *     security={{"token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="answerscript_ids", type="array", @OA\Items(type="integer", example=101), description="Array of Answer Script IDs"),
     *                     @OA\Property(property="cec_code", type="string", example="COUNCIL", description="CEC Code (Default: COUNCIL)", default="COUNCIL"),
     *                     @OA\Property(property="user_id", type="integer", example=1, description="Admin User ID")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="answerscript_id", type="integer", example=101, description="Single Answer Script ID"),
     *                     @OA\Property(property="cec_code", type="string", example="COUNCIL", description="CEC Code (Default: COUNCIL)", default="COUNCIL"),
     *                     @OA\Property(property="user_id", type="integer", example=1, description="Admin User ID")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CEC transfer completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="CEC transfer completed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Validation failed: ..."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="version", type="string", example="1.0"),
     *             @OA\Property(property="status", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Internal server error: ..."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="p_errorcode", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     *
     * POST /api/admin/transfer-cec
     *
     * Calls: public.fn_admin_transfer_cec(
     *   p_answerscriptId,
     *   p_cec_code,
     *   p_userId
     * )
     */
    public function transferCEC(Request $request)
    {
        Log::channel('daily')->info('🚀 === TRANSFER CEC API - REQUEST START ===');
        Log::channel('daily')->info('📥 REQUEST INPUT:', [
            'full_request' => $request->all(),
            'method'       => $request->method(),
            'url'          => $request->fullUrl(),
            'ip'           => $request->ip(),
        ]);

        // Extract authenticated user_id from middleware header if available
        $authUserId = null;
        $authUserData = $request->header('auth_user_data');
        if ($authUserData) {
            $decodedAuth = is_string($authUserData) ? json_decode($authUserData, true) : (array) $authUserData;
            $authUserId = $decodedAuth['user_id'] ?? null;
        }

        $topLevelUserId = $request->input('user_id')
            ?? $request->input('admin_user_id')
            ?? $request->input('entry_user_id')
            ?? $request->input('p_userId')
            ?? $request->input('p_user_id')
            ?? $authUserId
            ?? 1;

        // Hardcode 'COUNCIL' as default if not passed or empty
        $topLevelCecCode = $request->input('cec_code')
            ?? $request->input('p_cec_code')
            ?? $request->input('cecCode')
            ?? 'COUNCIL';

        if (trim((string) $topLevelCecCode) === '') {
            $topLevelCecCode = 'COUNCIL';
        }

        $items = [];

        // 1. Array of IDs in "answerscript_ids" or "answerscriptIds" or "ids"
        $rawIds = $request->input('answerscript_ids')
            ?? $request->input('answerscriptIds')
            ?? $request->input('ids');

        // 2. Array of objects in "items" or "data" or "answerscripts"
        $rawItems = $request->input('items')
            ?? $request->input('data')
            ?? $request->input('answerscripts');

        if (is_array($rawIds) && !empty($rawIds)) {
            foreach ($rawIds as $id) {
                if (is_numeric($id) || is_string($id)) {
                    $items[] = [
                        'answerscript_id' => (int) $id,
                        'cec_code'        => (string) $topLevelCecCode,
                        'user_id'         => (int) $topLevelUserId,
                    ];
                } elseif (is_array($id)) {
                    $items[] = [
                        'answerscript_id' => (int) ($id['answerscript_id'] ?? $id['answerscriptId'] ?? $id['id'] ?? 0),
                        'cec_code'        => (string) ($id['cec_code'] ?? $id['cecCode'] ?? $topLevelCecCode),
                        'user_id'         => (int) ($id['user_id'] ?? $id['admin_user_id'] ?? $topLevelUserId),
                    ];
                }
            }
        } elseif (is_array($rawItems) && !empty($rawItems)) {
            foreach ($rawItems as $entry) {
                if (is_array($entry)) {
                    $items[] = [
                        'answerscript_id' => (int) ($entry['answerscript_id'] ?? $entry['answerscriptId'] ?? $entry['id'] ?? 0),
                        'cec_code'        => (string) ($entry['cec_code'] ?? $entry['cecCode'] ?? $topLevelCecCode),
                        'user_id'         => (int) ($entry['user_id'] ?? $entry['admin_user_id'] ?? $topLevelUserId),
                    ];
                } elseif (is_numeric($entry)) {
                    $items[] = [
                        'answerscript_id' => (int) $entry,
                        'cec_code'        => (string) $topLevelCecCode,
                        'user_id'         => (int) $topLevelUserId,
                    ];
                }
            }
        } elseif (is_array($request->all()) && isset($request->all()[0]) && is_array($request->all()[0])) {
            // Raw JSON array of objects: [ { "answerscript_id": 101 }, ... ]
            foreach ($request->all() as $entry) {
                if (is_array($entry)) {
                    $items[] = [
                        'answerscript_id' => (int) ($entry['answerscript_id'] ?? $entry['answerscriptId'] ?? $entry['id'] ?? 0),
                        'cec_code'        => (string) ($entry['cec_code'] ?? $entry['cecCode'] ?? $topLevelCecCode),
                        'user_id'         => (int) ($entry['user_id'] ?? $entry['admin_user_id'] ?? $topLevelUserId),
                    ];
                }
            }
        } else {
            // Single object submission
            $singleId = $request->input('answerscript_id')
                ?? $request->input('answerscriptId')
                ?? $request->input('p_answerscriptId')
                ?? $request->input('p_answerscript_id')
                ?? $request->input('id');

            if ($singleId !== null && $singleId !== '') {
                $items[] = [
                    'answerscript_id' => (int) $singleId,
                    'cec_code'        => (string) $topLevelCecCode,
                    'user_id'         => (int) $topLevelUserId,
                ];
            }
        }

        if (empty($items)) {
            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'No answer script ID(s) provided for CEC transfer.',
                'data'    => ['p_errorcode' => 1],
            ], 400);
        }

        // Validate each item
        $isBulk = count($items) > 1;
        $rules = [
            'answerscript_id' => 'required|integer|min:1',
            'cec_code'        => 'required|string|max:50',
            'user_id'         => 'required|integer',
        ];

        foreach ($items as $index => $item) {
            $validator = Validator::make($item, $rules);
            if ($validator->fails()) {
                Log::channel('daily')->error("❌ VALIDATION FAILED on transfer CEC item {$index}:", [
                    'errors' => $validator->errors()->all(),
                    'item'   => $item,
                ]);

                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => ($isBulk ? "Item {$index}: " : '') . 'Validation failed: ' . $validator->errors()->first(),
                    'data'    => ['p_errorcode' => 1],
                ], 400);
            }
        }

        $sql = "SELECT public.fn_admin_transfer_cec(?::bigint, ?::varchar, ?::bigint) AS result";

        DB::beginTransaction();
        try {
            $successCount = 0;
            $failedTransfers = [];

            foreach ($items as $index => $item) {
                $answerscriptId = (int) $item['answerscript_id'];
                $cecCode        = trim((string) ($item['cec_code'] ?: 'COUNCIL'));
                $userId         = (int) $item['user_id'];

                Log::channel('daily')->info("📤 Calling fn_admin_transfer_cec [Item {$index}] with parameters:", [
                    'p_answerscriptId' => $answerscriptId,
                    'p_cec_code'       => $cecCode,
                    'p_userId'         => $userId,
                ]);

                $spResult = DB::selectOne($sql, [
                    $answerscriptId,
                    $cecCode,
                    $userId,
                ]);

                if (!$spResult || !isset($spResult->result)) {
                    throw new \Exception("No response received from fn_admin_transfer_cec for Answer Script ID {$answerscriptId}");
                }

                $raw = $spResult->result;
                $parsed = is_string($raw) ? json_decode($raw, true) : (array) $raw;

                if (is_string($raw) && json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Failed to parse database response for Answer Script ID {$answerscriptId}: {$raw}");
                }

                $errorCode = isset($parsed['p_errorcode']) ? (int) $parsed['p_errorcode'] : -1;

                if ($errorCode === 0) {
                    $successCount++;
                    Log::channel('daily')->info("✅ Item #{$index} (Answer Script ID: {$answerscriptId}) transferred to CEC {$cecCode} successfully");
                } else {
                    $errorMsg = $parsed['p_errormsg'] ?? "Database function returned error code {$errorCode}";
                    Log::channel('daily')->warning("⚠️ Item #{$index} (Answer Script ID: {$answerscriptId}) returned error code {$errorCode}: {$errorMsg}");

                    $failedTransfers[] = [
                        'answerscript_id' => $answerscriptId,
                        'error_code'      => $errorCode,
                        'error_message'   => $errorMsg,
                    ];
                }
            }

            if (!empty($failedTransfers)) {
                DB::rollBack();
                $fail = $failedTransfers[0];
                return response()->json([
                    'version' => '1.0',
                    'status'  => 0,
                    'message' => $fail['error_message'],
                    'data'    => [
                        'p_errorcode' => $fail['error_code'],
                    ],
                ], 200);
            }

            DB::commit();

            $responseData = [
                'version' => '1.0',
                'status'  => 1,
                'message' => count($items) > 1
                    ? "CEC transfer completed successfully for {$successCount} answer script(s)."
                    : "CEC transfer completed successfully",
                'data'    => [
                    'p_errorcode' => 0,
                ],
            ];

            Log::channel('daily')->info('✅ CEC transfer API completed successfully:', $responseData);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('daily')->error('🔥 EXCEPTION in transferCEC:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'version' => '1.0',
                'status'  => 0,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => ['p_errorcode' => 1],
            ], 500);
        }
    }
}
