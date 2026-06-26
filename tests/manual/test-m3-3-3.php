<?php
/**
 * M3 3.3 verification — ArticleResource 可加载 + 关联可用 (D19/D20/D24)
 * Run: php test-m3-3-3.php
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== M3 3.3 — ArticleResource + 关联 + 状态机检查 ===\n";

// 1. Resource 类可加载
$resourceClass = App\Filament\Resources\ArticleResource::class;
echo "Resource class: {$resourceClass}\n";
if (! class_exists($resourceClass)) {
    echo "  [FAIL] class not loaded\n";
    exit(1);
}
echo "  [OK] class loaded\n";

// 2. model 指向 Article
$model = $resourceClass::getModel();
echo "Model: {$model}\n";
if ($model !== App\Models\Article::class) {
    echo "  [FAIL] model mismatch\n";
    exit(1);
}
echo "  [OK] model is Article\n";

// 3. ReviewLogsRelationManager 注册
$relations = $resourceClass::getRelations();
echo "Relations: " . implode(', ', $relations) . "\n";
$found = false;
foreach ($relations as $rel) {
    if (is_string($rel) && $rel === App\Filament\Resources\ArticleResource\RelationManagers\ReviewLogsRelationManager::class) {
        $found = true;
    } elseif ($rel instanceof \Filament\Resources\RelationManagers\RelationManagerConfiguration) {
        if ($rel->relationManager === App\Filament\Resources\ArticleResource\RelationManagers\ReviewLogsRelationManager::class) {
            $found = true;
        }
    }
}
if (! $found) {
    echo "  [FAIL] ReviewLogsRelationManager not registered\n";
    exit(1);
}
echo "  [OK] ReviewLogsRelationManager registered\n";

// 4. Article::reviewLogs() 关联可用 (D19)
$a = App\Models\Article::query()->withCount('reviewLogs')->first();
if ($a) {
    echo "Sample article id={$a->id}, status={$a->status}, review_logs_count={$a->review_logs_count}\n";
    $logs = $a->reviewLogs()->limit(5)->get();
    echo "reviewLogs() returned " . $logs->count() . " log(s)\n";
    echo "  [OK] reviewLogs relation works\n";
} else {
    echo "  [WARN] no articles in DB yet, relation check skipped (semantic only)\n";
    // 检查关联定义存在
    $reflect = new ReflectionClass(App\Models\Article::class);
    if (! $reflect->hasMethod('reviewLogs')) {
        echo "  [FAIL] Article::reviewLogs() method missing\n";
        exit(1);
    }
    echo "  [OK] Article::reviewLogs() method exists (D19)\n";
}

// 5. 状态机 checks (D17)
$sm = app(App\Services\ArticleStateMachine::class);
$checks = [
    [App\Enums\ArticleStatus::PUBLISHED, App\Enums\ArticleStatus::UNLISTED, true, 'published → unlisted'],
    [App\Enums\ArticleStatus::UNLISTED, App\Enums\ArticleStatus::PENDING, true, 'unlisted → pending'],
    [App\Enums\ArticleStatus::PENDING, App\Enums\ArticleStatus::MODIFY_REQUIRED, true, 'pending → modify_required (D14)'],
    [App\Enums\ArticleStatus::PENDING, App\Enums\ArticleStatus::PUBLISHED, true, 'pending → published (first_pass)'],
];
foreach ($checks as [$from, $to, $expected, $label]) {
    $actual = $sm->canTransition($from, $to);
    $mark = $actual === $expected ? '[OK]' : '[FAIL]';
    echo "  {$mark} {$label}: " . ($actual ? 'true' : 'false') . "\n";
    if ($actual !== $expected) {
        exit(1);
    }
}

// 6. Article::first_pass / second_pass 恒为 0 (D20 回归)
$transitionalCount = App\Models\Article::whereIn('status', ['first_pass', 'second_pass'])->count();
echo "Articles in first_pass/second_pass: {$transitionalCount}\n";
if ($transitionalCount !== 0) {
    echo "  [FAIL] D20 violated: transition states should never persist\n";
    exit(1);
}
echo "  [OK] D20 verified: no transition-state articles in DB\n";

// 7. ReviewLog 列敏感词 JSON 序列化（验证 cast 正常）
$lastLog = App\Models\ArticleReviewLog::query()->latest('created_at')->first();
if ($lastLog) {
    echo "Last review log id={$lastLog->id}, action={$lastLog->action}, hits=";
    var_export($lastLog->sensitive_word_hit);
    echo "\n";
} else {
    echo "  [INFO] no review logs yet\n";
}

echo "=== M3 3.3 PASS ===\n";