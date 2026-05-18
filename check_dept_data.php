<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Pharmacy Departments Master Table ===" . PHP_EOL . PHP_EOL;

$result = DB::select('SELECT * FROM public.tbl_pharmacy_department_mstr LIMIT 5');
foreach($result as $row) {
    echo json_encode($row) . PHP_EOL;
}
