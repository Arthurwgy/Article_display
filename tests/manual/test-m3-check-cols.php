<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== articles.status ===\n";
$cols = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM articles WHERE Field='status'");
foreach ($cols as $c) {
    print_r($c);
}

echo "\n=== article_review_logs.action ===\n";
$cols = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM article_review_logs WHERE Field='action'");
foreach ($cols as $c) {
    print_r($c);
}