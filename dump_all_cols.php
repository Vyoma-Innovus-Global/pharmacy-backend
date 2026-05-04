<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Illuminate\Support\Facades\DB::select("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = 'public'");
foreach($columns as $c) {
    echo $c->table_name . "." . $c->column_name . "\n";
}
