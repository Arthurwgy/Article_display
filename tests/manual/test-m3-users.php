<?php

require __DIR__ . '/../../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = \App\Models\User::where('role', 'admin')->first();
$reader = \App\Models\User::where('role', 'reader')->first();
$author = \App\Models\User::where('role', 'author')->first();

echo "admin: " . ($admin ? $admin->email : 'NONE') . PHP_EOL;
echo "reader: " . ($reader ? $reader->email : 'NONE') . PHP_EOL;
echo "author: " . ($author ? $author->email : 'NONE') . PHP_EOL;