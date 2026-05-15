<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminScheduleController extends Controller
{
    /**
     * POST /api/admin/check-schedule
     *
     * Calls: public.fn_admin_check_schedule(
     *          p_admin_user_type_id::integer,
     *          p_schedule_type::varchar,
     *          p_date::timestamp,
     *          p_semester::integer
     *        )
     *
     * Body: {
     *   "admin_user_type_id": 9,
     *   "schedule_type":      "MARKS_ENTRY_INTERNAL",
     *   "date":               "2026-05-14 15:56:08",   // optional, defaults to now
     *   "semester":           1
     * }
     *
     * DB returns: {"p_errorcode": 300, "p_last_date": "...", "p_extended_date": "..."}
     *
     * Error codes:
     *   0   → Schedule active (within date range)
     *   100 → Schedule not found
     *   200 → Schedule not yet started
     *   300 → Schedule expired (past last date)
     *   400 → Schedule in extended period
     */
    public function checkSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_user_type_id' => 'required|integer',
            'schedule_type'      => 'required|string|max:100',
            'date'               => 'nullable|date',
            'semester'           => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'version' => '1.0',
                'status'  => 1,
                'message' => 'Validation failed.',
                'data'    => $validator->errors(),
            ], 422);
        }

        $userTypeId    = (int) $request->input('admin_user_type_id');
        $scheduleType  = trim($request->input('schedule_type'));
        $date          = $request->input('date') ?? now()->format('Y-m-d H:i:s');
        $semester      = (int) $request->input('semester');

        Log::channel('daily')->info('[checkSchedule] INPUT', [
            'admin_user_type_id' => $userTypeId,
            'schedule_type'      => $scheduleType,
            'date'               => $date,
            'semester'           => $semester,
            'ip'                 => $request->ip(),
        ]);

        try {
            $result = DB::select(
                'SELECT public.fn_admin_check_schedule(?::integer, ?::varchar, ?::timestamp, ?::integer) AS data',
                [$userTypeId, $scheduleType, $date, $semester]
            );

            $raw  = $result[0]->data ?? null;
            $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

            Log::channel('daily')->info('[checkSchedule] fn_admin_check_schedule', [
                'raw'    => $raw,
                'parsed' => $data,
            ]);

            if (empty($result) || json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'version' => '1.0',
                    'status'  => 1,
                    'message' => 'Failed to retrieve schedule.',
                    'data'    => null,
                ], 500);
            }

            $errorCode = (int)($data['p_errorcode'] ?? -1);

            $statusMessages = [
                0   => 'Schedule is active.',
                100 => 'Schedule not found for the given parameters.',
                200 => 'Schedule has not started yet.',
                300 => 'Schedule is Active.',
                301 => 'Schedule has expired.',
                400 => 'Schedule is in extended period.',
            ];

            $message = $statusMessages[$errorCode] ?? 'Schedule check completed.';
            $httpStatus = ($errorCode === 0 || $errorCode === 300 || $errorCode === 400) ? 200 : 200;

            $response = [
                'version' => '1.0',
                'status'  => ($errorCode === 0) ? 0 : $errorCode,
                'message' => $message,
                'data'    => [
                    'p_errorcode'    => $data['p_errorcode']    ?? null,
                    'p_last_date'    => $data['p_last_date']    ?? null,
                    'p_extended_date'=> $data['p_extended_date']?? null,
                ],
            ];

            Log::channel('daily')->info('[checkSchedule] OUTPUT (200)', $response);
            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::channel('daily')->error('[checkSchedule] EXCEPTION', [
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
