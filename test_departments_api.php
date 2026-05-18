<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing Departments API ===" . PHP_EOL . PHP_EOL;

// Test with AMNA institute and semester 1
echo "Test: fn_admin_getdepartmentsbyinst('AMNA', 1)" . PHP_EOL;
$result = DB::select('SELECT public.fn_admin_getdepartmentsbyinst(?, ?) AS data', ['AMNA', 1]);
$raw = $result[0]->data ?? null;

if (is_string($raw)) {
    $data = json_decode($raw, true);
    echo "SUCCESS: Found " . count($data) . " department(s)" . PHP_EOL;
    foreach($data as $dept) {
        echo "  - deptCode: " . $dept['deptCode'] . ", deptName: " . $dept['deptName'] . ", instCode: " . $dept['instCode'] . PHP_EOL;
    }
} else {
    echo "FAILED: No data or invalid format" . PHP_EOL;
    var_dump($raw);
}

echo PHP_EOL . "All tests completed!" . PHP_EOL;
