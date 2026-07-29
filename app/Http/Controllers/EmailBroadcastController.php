<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\NoticeMail;

class EmailBroadcastController extends Controller
{
    public function sendBulkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emails' => 'required|array',
            'emails.*' => 'required|email',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()], 422);
        }

        $emails = $request->input('emails');
        
        $defaultMessage = "Important Notice

As per the records of the West Bengal State Council of Technical & Vocational Education and Skill Development (WBSCTVESD), it is observed that you have successfully paid the Pharmacy Registration Fee. However, you have not yet uploaded all the required documents and information.

You are hereby requested to upload all the required documents and information in a single submission at the earliest. Kindly ensure that all documents and information submitted are complete, accurate, and clearly legible.

Please note that failure to upload the required documents and information will result in your registration being placed on hold, notwithstanding the successful payment of the registration fee.

This is for your information and necessary action.

Regards,
West Bengal State Council of Technical & Vocational Education and Skill Development";
        
        $message = $request->input('message', $defaultMessage);

        $results = [
            'total' => count($emails),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new NoticeMail($message));
                
                $results['success']++;
                $results['details'][] = ['email' => $email, 'status' => 'success'];
                
                // Logging for each attempt
                Log::channel('daily')->info('[sendBulkEmail] Email sent', [
                    'email' => $email,
                    'status' => 'success',
                ]);
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = ['email' => $email, 'status' => 'failed', 'error' => $e->getMessage()];
                
                Log::channel('daily')->error('[sendBulkEmail] Email send failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'error' => false,
            'message' => 'Bulk Email process completed.',
            'data' => $results
        ], 200);
    }
}
