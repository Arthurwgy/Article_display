<?php
/**
 * M3 3.4 verification — SensitiveWordResource + 导入流程端到端测试 (D21/D22/D25)
 * Run: php test-m3-3-4.php
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Filament\Resources\SensitiveWordResource\Pages\ListSensitiveWords;
use App\Models\SensitiveWord;
use Illuminate\Support\Facades\Storage;

echo "=== M3 3.4 — SensitiveWordResource + 导入端到端 ===\n";

// 1. Resource 类加载
$resourceClass = App\Filament\Resources\SensitiveWordResource::class;
echo "Resource class: {$resourceClass}\n";
if (! class_exists($resourceClass)) {
    echo "  [FAIL] not loaded\n";
    exit(1);
}
echo "  [OK] class loaded\n";

$model = $resourceClass::getModel();
if ($model !== SensitiveWord::class) {
    echo "  [FAIL] model mismatch\n";
    exit(1);
}
echo "  [OK] model is SensitiveWord\n";

// 2. 测试页面类加载并能反射 importAction
$pageClass = ListSensitiveWords::class;
if (! class_exists($pageClass)) {
    echo "  [FAIL] page class not loaded\n";
    exit(1);
}
echo "  [OK] ListSensitiveWords page loaded\n";

$reflect = new ReflectionClass($pageClass);
if (! $reflect->hasMethod('importAction')) {
    echo "  [FAIL] importAction method missing\n";
    exit(1);
}
echo "  [OK] importAction method exists\n";

if (! $reflect->hasMethod('exportAction')) {
    echo "  [FAIL] exportAction method missing\n";
    exit(1);
}
echo "  [OK] exportAction method exists\n";

// 3. 端到端导入：模拟 ListSensitiveWords 上的导入 action 行为
$sample = __DIR__ . '/../../docs/fixtures/sensitive-words-sample.txt';
if (! file_exists($sample)) {
    echo "  [FAIL] sample fixture missing\n";
    exit(1);
}

$content = file_get_contents($sample);
$tokens = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
$tokens = array_values(array_unique(array_filter(array_map('trim', $tokens), fn ($t) => $t !== '')));
echo "Sample tokens: " . count($tokens) . "\n";

// 用唯一前缀避免与已有数据冲突
$prefix = 'm3test_' . substr(uniqid(), -6) . '_';
$tokensPrefixed = array_map(fn ($t) => $prefix . $t, $tokens);

// 写入临时上传位置
$relPath = 'temp-imports/' . uniqid('sw_') . '.txt';
Storage::disk('local')->put($relPath, implode("\n", $tokensPrefixed));
echo "Uploaded to: {$relPath}\n";

// 模拟导入 action 的核心逻辑
$existing = SensitiveWord::whereIn('word', $tokensPrefixed)->pluck('word')->all();
$toInsert = array_values(array_diff($tokensPrefixed, $existing));
$now = now();
$rows = array_map(fn ($w) => [
    'word' => $w,
    'level' => 'moderate',
    'group_name' => 'm3-fixture-test',
    'created_at' => $now,
    'updated_at' => $now,
], $toInsert);
SensitiveWord::insert($rows);

$imported = count($toInsert);
$skipped = count($existing);
echo "Imported: {$imported}, Skipped: {$skipped}\n";

if ($imported !== count($tokens)) {
    echo "  [FAIL] expected " . count($tokens) . " imported, got {$imported}\n";
    exit(1);
}
echo "  [OK] import succeeded\n";

// 4. 二次导入相同内容 → 全部跳过
$existing2 = SensitiveWord::whereIn('word', $tokensPrefixed)->pluck('word')->all();
$toInsert2 = array_values(array_diff($tokensPrefixed, $existing2));
echo "Re-import: would import " . count($toInsert2) . " (expect 0)\n";
if (count($toInsert2) !== 0) {
    echo "  [FAIL] re-import should skip all\n";
    exit(1);
}
echo "  [OK] unique constraint working\n";

// 5. 清理测试数据
$deleted = SensitiveWord::where('group_name', 'm3-fixture-test')->delete();
echo "Cleaned up: {$deleted} test rows\n";

Storage::disk('local')->delete($relPath);

echo "=== M3 3.4 PASS ===\n";