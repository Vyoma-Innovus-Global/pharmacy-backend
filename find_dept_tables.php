<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Finding Department Tables ===" . PHP_EOL . PHP_EOL;

$result = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND (table_name LIKE '%dept%' OR table_name LIKE '%department%')");
foreach($result as $row) {
    echo $row->table_name . PHP_EOL;
}

if (empty($result)) {
    echo "No department tables found. Let's check for master tables:" . PHP_EOL;
    $result = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%mstr%' ORDER BY table_name");
    foreach($result as $row) {
        echo $row->table_name . PHP_EOL;
    }
}
