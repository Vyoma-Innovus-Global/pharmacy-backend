<?php
/**
 * Test script for fn_admin_saveteacherinfo and fn_admin_saveteacherassignsubject_v1
 *
 * This script tests the complete teacher save flow:
 * 1. Save teacher info
 * 2. Get returned teacher_id
 * 3. Loop through subjects and assign each one
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=" . str_repeat("=", 80) . "\n";
echo "Testing Teacher Save Functions\n";
echo "=" . str_repeat("=", 80) . "\n\n";

try {
    // Test Data
    $teacherData = [
        'in_teacher_id' => 0,
        'full_name' => 'Test Teacher ' . date('His'),
        'contact_no' => '9876543210',
        'email' => 'test.teacher.' . time() . '@example.com',
        'highest_qualification' => 'M.Pharm',
        'aadhar_no' => '123456789012',
        'inst_id' => 1,
        'inst_name' => 'Test Pharmacy College',
        'designation_id' => '1',
        'image' => null,
        'remarks' => 'Test teacher entry via script',
        'entry_user_id' => 1001
    ];

    $subjects = [
        ['dept_id' => 1, 'semester_id' => 1, 'subject_category_id' => 1, 'subject_id' => 1],
        ['dept_id' => 1, 'semester_id' => 1, 'subject_category_id' => 2, 'subject_id' => 2],
    ];

    echo "Step 1: Saving Teacher Info\n";
    echo str_repeat("-", 80) . "\n";
    echo "Teacher Name: {$teacherData['full_name']}\n";
    echo "Email: {$teacherData['email']}\n";
    echo "Contact: {$teacherData['contact_no']}\n\n";

    // Call fn_admin_saveteacherinfo
    $teacherResult = DB::selectOne(
        "SELECT public.fn_admin_saveteacherinfo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) as result",
        [
            $teacherData['in_teacher_id'],
            $teacherData['full_name'],
            $teacherData['contact_no'],
            $teacherData['email'],
            $teacherData['highest_qualification'],
            $teacherData['aadhar_no'],
            $teacherData['inst_id'],
            $teacherData['inst_name'],
            $teacherData['designation_id'],
            $teacherData['image'],
            $teacherData['remarks'],
            $teacherData['entry_user_id']
        ]
    );

    if (!$teacherResult || !$teacherResult->result) {
        echo "❌ FAILED: No result returned from teacher save function\n";
        exit(1);
    }

    $resultData = json_decode($teacherResult->result, true);
    echo "Function Result: " . json_encode($resultData, JSON_PRETTY_PRINT) . "\n\n";

    if (!$resultData || $resultData['p_errorcode'] != 0) {
        echo "❌ FAILED: Teacher save returned error code: " . ($resultData['p_errorcode'] ?? 'unknown') . "\n";
        exit(1);
    }

    $teacherId = $resultData['p_teacher_id'];
    echo "✅ SUCCESS: Teacher saved with ID: {$teacherId}\n\n";

    echo "Step 2: Assigning Subjects\n";
    echo str_repeat("-", 80) . "\n";

    $successCount = 0;
    $failedSubjects = [];

    foreach ($subjects as $index => $subject) {
        echo "\nAssigning Subject " . ($index + 1) . ":\n";
        echo "  - Dept ID: {$subject['dept_id']}\n";
        echo "  - Semester ID: {$subject['semester_id']}\n";
        echo "  - Subject Category ID: {$subject['subject_category_id']}\n";
        echo "  - Subject ID: {$subject['subject_id']}\n";

        $subjectResult = DB::selectOne(
            "SELECT public.fn_admin_saveteacherassignsubject_v1(?, ?, ?, ?, ?, ?, ?) as result",
            [
                $teacherId,
                $subject['dept_id'],
                $subject['semester_id'],
                $subject['subject_category_id'],
                $subject['subject_id'],
                $teacherData['entry_user_id'],
                $teacherData['inst_id']
            ]
        );

        if (!$subjectResult || !$subjectResult->result) {
            echo "  ❌ FAILED: No result returned\n";
            $failedSubjects[] = $subject['subject_id'];
            continue;
        }

        $subjectResultData = json_decode($subjectResult->result, true);
        echo "  Result: " . json_encode($subjectResultData, JSON_PRETTY_PRINT) . "\n";

        if ($subjectResultData && isset($subjectResultData['p_errorcode']) && $subjectResultData['p_errorcode'] == 0) {
            echo "  ✅ SUCCESS: Subject assigned\n";
            $successCount++;
        } else {
            echo "  ❌ FAILED: Error code " . ($subjectResultData['p_errorcode'] ?? 'unknown') . "\n";
            $failedSubjects[] = $subject['subject_id'];
        }
    }

    echo "\n" . str_repeat("=", 80) . "\n";
    echo "SUMMARY\n";
    echo str_repeat("=", 80) . "\n";
    echo "Teacher ID: {$teacherId}\n";
    echo "Total Subjects: " . count($subjects) . "\n";
    echo "Successfully Assigned: {$successCount}\n";
    echo "Failed: " . count($failedSubjects) . "\n";

    if (count($failedSubjects) > 0) {
        echo "Failed Subject IDs: " . implode(', ', $failedSubjects) . "\n";
    }

    echo "\n";
    if ($successCount === count($subjects)) {
        echo "🎉 ALL TESTS PASSED!\n";
    } else {
        echo "⚠️  SOME TESTS FAILED\n";
    }

} catch (Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo str_repeat("=", 80) . "\n";
