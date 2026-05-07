<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Illuminate\Support\Facades\DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'pharmacy_schedule_master'");
print_r(array_column($columns, 'column_name'));
