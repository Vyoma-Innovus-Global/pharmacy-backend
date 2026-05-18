<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing Subjects API with Numeric Semester ID ===" . PHP_EOL . PHP_EOL;

// Test 1: semester_id = "1" (string numeric)
echo "Test 1: semester_id = \"1\" (string)" . PHP_EOL;
$result = DB::select('SELECT public.fn_admin_getdeptallsubjects_v1(?, ?, ?, ?) AS data', [1, 'PHARM', '1', 1]);
$raw = $result[0]->data ?? null;
if (is_string($raw)) {
    $data = json_decode($raw, true);
    echo "SUCCESS: Found " . count($data) . " subjects" . PHP_EOL;
    if (!empty($data)) {
        echo "Sample: " . $data[0]['subjectCode'] . " - " . $data[0]['subjectName'] . PHP_EOL;
    }
} else {
    echo "FAILED: No data" . PHP_EOL;
}

echo PHP_EOL;

// Test 2: semester_id = 2 (numeric for Part 2)
echo "Test 2: semester_id = \"2\" (string)" . PHP_EOL;
$result = DB::select('SELECT public.fn_admin_getdeptallsubjects_v1(?, ?, ?, ?) AS data', [1, 'PHARM', '2', 1]);
$raw = $result[0]->data ?? null;
if (is_string($raw)) {
    $data = json_decode($raw, true);
    echo "SUCCESS: Found " . count($data) . " subjects" . PHP_EOL;
} else {
    echo "FAILED: No data" . PHP_EOL;
}

echo PHP_EOL . "All tests completed!" . PHP_EOL;
