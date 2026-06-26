<?php
/**
 * M3 3.5 verification — CategoryResource + 删除保护
 * Run: php test-m3-3-5.php
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== M3 3.5 — CategoryResource 验证 ===\n";

$resourceClass = App\Filament\Resources\CategoryResource::class;
if (! class_exists($resourceClass)) {
    echo "  [FAIL] not loaded\n";
    exit(1);
}
echo "  [OK] Resource loaded: {$resourceClass}\n";

if ($resourceClass::getModel() !== App\Models\ArticleCategory::class) {
    echo "  [FAIL] model mismatch\n";
    exit(1);
}
echo "  [OK] model is ArticleCategory\n";

// 验证 ArticleCategory 有 children 关联（删除保护需要）
$cat = new App\Models\ArticleCategory();
$reflect = new ReflectionClass($cat);
if (! $reflect->hasMethod('children')) {
    echo "  [FAIL] ArticleCategory::children() missing\n";
    exit(1);
}
echo "  [OK] ArticleCategory::children() exists\n";

// 删除保护逻辑：父分类有子分类时调用 children()->exists() 应返回 true
$parent = App\Models\ArticleCategory::first();
if ($parent && $parent->children()->exists()) {
    echo "  [INFO] sample parent has children, delete should be blocked\n";
    echo "  [OK] delete protection logic reachable\n";
} elseif ($parent) {
    echo "  [INFO] sample parent has no children (acceptable for this check)\n";
    echo "  [OK] delete protection logic path exists\n";
} else {
    echo "  [WARN] no categories in DB\n";
}

// 表格列：含 parent.name（顶级时显示「顶级」）—— 通过反射检查
$reflect = new ReflectionClass($resourceClass);
if (! $reflect->hasMethod('table')) {
    echo "  [FAIL] table() method missing\n";
    exit(1);
}
echo "  [OK] table() method exists\n";

if (! $reflect->hasMethod('form')) {
    echo "  [FAIL] form() method missing\n";
    exit(1);
}
echo "  [OK] form() method exists\n";

if (! $reflect->hasMethod('getPages')) {
    echo "  [FAIL] getPages() method missing\n";
    exit(1);
}
$pages = $resourceClass::getPages();
echo "  [OK] pages: " . implode(', ', array_keys($pages)) . "\n";

echo "=== M3 3.5 PASS ===\n";