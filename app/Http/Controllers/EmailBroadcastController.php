<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\NoticeMail;
use App\Mail\RegistrationCancellationMail;
use App\Models\Registerstudent;

class EmailBroadcastController extends Controller
{
    /**
     * Send Registration Cancellation Email to student using SP data.
     *
     * SP: public.fn_getregistredstudentdetailslistbystudentid(p_studentid, p_adminuserid)
     */
    public function sendRegistrationCancellationMail(Request $request)
    {
        try {
            $studentId = $request->input('studentID', $request->input('studentId', $request->input('student_id', $request->input('p_studentid'))));
            $adminUserId = $request->input('adminuserid', $request->input('adminUserId', $request->input('admin_user_id', $request->input('p_adminuserid', $request->input('adminUserID', 0)))));

            $validator = Validator::make([
                'student_id' => $studentId,
                'admin_user_id' => $adminUserId,
            ], [
                'student_id' => 'required|integer',
                'admin_user_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors(),
                ], 422);
            }

            $studentData = [];

            // 1. Fetch student details via the Stored Procedure
            if (!empty($studentId)) {
                try {
                    $result = DB::select(
                        'SELECT public.fn_getregistredstudentdetailslistbystudentid(?::bigint, ?::bigint) AS data',
                        [(int) $studentId, (int) $adminUserId]
                    );

                    if (!empty($result) && isset($result[0]->data)) {
                        $raw = $result[0]->data;
                        $decoded = is_string($raw) ? json_decode($raw, true) : (array) $raw;
                        if (is_array($decoded) && !empty($decoded)) {
                            $studentData = $decoded;
                        }
                    }
                } catch (\Exception $e) {
                    Log::channel('daily')->warning('[sendRegistrationCancellationMail] SP fetch error: ' . $e->getMessage());
                }
            }

            // 2. Allow direct data payload if passed in request
            if (empty($studentData) && $request->has('data')) {
                $studentData = is_array($request->input('data')) ? $request->input('data') : (json_decode($request->input('data'), true) ?? []);
            }

            if (empty($studentData)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No student details found for student ID: ' . $studentId,
                ], 404);
            }

            // 3. Extract and map email
            $email = $request->input('email', $studentData['email'] ?? null);

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Valid student email address is required and could not be found for student ID: ' . $studentId,
                ], 422);
            }

            // 4. Extract and map student name
            $studentName = $request->input('student_name', $request->input('name', null));
            if (empty($studentName)) {
                $studentName = !empty($studentData['studentName']) ? trim($studentData['studentName']) : null;
            }
            if (empty($studentName)) {
                $firstName = $studentData['firstName'] ?? '';
                $middleName = $studentData['middleName'] ?? '';
                $lastName = $studentData['lastName'] ?? '';
                $studentName = trim("{$firstName} {$middleName} {$lastName}");
            }
            if (empty($studentName)) {
                $studentName = 'Student';
            }

            $formNumber = $studentData['formNumber'] ?? $studentData['form_number'] ?? $request->input('form_no', null);

            // 5. Extract and map registration number
            $registrationNumber = $request->input('registration_no', $request->input('registrationNumber', $request->input('reg_no', 
                $studentData['registrationNumber'] ?? $studentData['registration_no'] ?? null
            )));

            if (empty($registrationNumber) && (!empty($studentId) || !empty($formNumber))) {
                try {
                    $regStudent = Registerstudent::where(function ($query) use ($studentId, $formNumber) {
                        if (!empty($studentId)) {
                            $query->where('s_id', $studentId);
                        }
                        if (!empty($formNumber)) {
                            $query->orWhere('s_appl_form_num', $formNumber);
                        }
                    })->first();

                    if ($regStudent && !empty($regStudent->s_appl_reg_no)) {
                        $registrationNumber = $regStudent->s_appl_reg_no;
                    }
                } catch (\Exception $e) {
                    // Safe fallback
                }
            }
            if (empty($registrationNumber)) {
                $registrationNumber = 'N/A';
            }

            // 6. Extract and map roll number
            $rollNumber = $request->input('roll_no', $request->input('roll_number', $request->input('rollNumber', 
                $studentData['rollNumber'] ?? $studentData['roll_no'] ?? null
            )));

            if (empty($rollNumber) && !empty($formNumber)) {
                try {
                    $rollData = DB::table('pharmacy_roll_no')->where('form_num', $formNumber)->first();
                    if ($rollData && !empty($rollData->roll_no)) {
                        $rollNumber = $rollData->roll_no;
                    }
                } catch (\Exception $e) {
                    // Table or column optional check
                }
            }
            if (empty($rollNumber)) {
                $rollNumber = 'N/A';
            }

            // 7. Extract and map institute name
            $instituteName = $request->input('institute_name', $request->input('institute', 
                $studentData['instituteName'] ?? $studentData['institute_name'] ?? 'N/A'
            ));

            // 8. Extract and map course name
            $courseName = $request->input('course_name', $request->input('course', 
                $studentData['courseName'] ?? $studentData['course'] ?? 'Diploma in Pharmacy'
            ));

            // 9. Extract and map academic session
            $session = $request->input('session', $request->input('session_year', $request->input('SessionYear', 
                $studentData['SessionYear'] ?? $studentData['session_year'] ?? $studentData['session'] ?? 'N/A'
            )));

            $mailData = [
                'email' => $email,
                'studentName' => $studentName,
                'registrationNumber' => $registrationNumber,
                'rollNumber' => $rollNumber,
                'instituteName' => $instituteName,
                'courseName' => $courseName,
                'session' => $session,
            ];

            try {
                Mail::to($email)->send(new RegistrationCancellationMail($mailData));

                Log::channel('daily')->info('[sendRegistrationCancellationMail] Email sent successfully', [
                    'email' => $email,
                    'studentName' => $studentName,
                    'registrationNumber' => $registrationNumber,
                ]);

                // Safe audit trail
                try {
                    if (function_exists('auditTrail')) {
                        $userId = $adminUserId ?: ($studentId ?: 0);
                        auditTrail($userId, "Registration cancellation email sent to {$email} (Student: {$studentName})");
                    }
                } catch (\Throwable $atEx) {
                    Log::channel('daily')->warning('[sendRegistrationCancellationMail] AuditTrail error: ' . $atEx->getMessage());
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Registration cancellation email sent successfully.',
                    'data' => $mailData,
                ], 200);

            } catch (\Exception $e) {
                Log::channel('daily')->error('[sendRegistrationCancellationMail] Failed to send email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => true,
                    'message' => 'Failed to send registration cancellation email: ' . $e->getMessage(),
                ], 500);
            }
        } catch (\Throwable $th) {
            Log::channel('daily')->error('[sendRegistrationCancellationMail] Unexpected error', [
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Server Error: ' . $th->getMessage(),
            ], 500);
        }
    }

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
