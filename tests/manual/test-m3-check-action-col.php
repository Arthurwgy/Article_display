<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$col = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM article_review_logs WHERE Field='action'");
print_r($col);