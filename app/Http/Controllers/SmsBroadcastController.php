<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SmsBroadcastController extends Controller
{
    public function sendBulkSms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_numbers' => 'required|array',
            'phone_numbers.*' => 'required|string',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()], 422);
        }

        $phoneNumbers = $request->input('phone_numbers');
        
        $defaultMessage = "*Notice*\n\nOur records indicate that you have successfully paid the *Pharmacy Registration Fee, but you have **not uploaded the required documents*.\n\nYou are requested to upload all the necessary documents correctly *in a single submission*. Please ensure that all documents are complete and clearly legible before uploading.\n\n*If you do not upload the required documents, your registration will be cancelled, even if you have already paid the registration fee.*";
        
        $message = $request->input('message', $defaultMessage);

        $results = [
            'total' => count($phoneNumbers),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($phoneNumbers as $phone) {
            try {
                $response = send_sms($phone, $message);
                
                // Extract response body if it's a Laravel HTTP client response
                $responseBody = (is_object($response) && method_exists($response, 'body')) ? $response->body() : $response;

                $results['success']++;
                $results['details'][] = ['phone' => $phone, 'status' => 'success', 'provider_response' => $responseBody];
                
                // Logging for each attempt
                Log::channel('daily')->info('[sendBulkSms] SMS sent', [
                    'phone' => $phone,
                    'status' => 'success',
                ]);
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = ['phone' => $phone, 'status' => 'failed', 'error' => $e->getMessage()];
                
                Log::channel('daily')->error('[sendBulkSms] SMS send failed', [
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'Bulk SMS process completed.',
            'data' => $results
        ], 200);
    }
}
