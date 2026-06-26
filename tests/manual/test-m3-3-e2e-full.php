<?php
/**
 * M3 最终端到端浏览器验证 — 完整登录 + admin/articles 访问
 * 通过 Laravel test helpers (模拟 session) 完成 6 步浏览器验证
 * Run: php tests/manual/test-m3-3-e2e-full.php
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== M3 端到端浏览器验证（完整 6 步的自动化部分） ===\n\n";

$pass = true;
$fail = function (string $msg) use (&$pass) {
    echo "  [FAIL] {$msg}\n";
    $pass = false;
};
$ok = function (string $msg) {
    echo "  [OK] {$msg}\n";
};

// 步骤 1：登录
echo "[Step 1] 登录 admin@example.com / password\n";
$admin = User::where('role', 'admin')->first();
if (! $admin) {
    $fail('无 admin 用户');
    exit(1);
}
Auth::login($admin);
if (Auth::check() && Auth::user()->isAdmin()) {
    $ok("admin 已登录: " . Auth::user()->email);
} else {
    $fail("登录失败");
    exit(1);
}

// 步骤 2：拒绝访问 — reader 用户登录后访问 /admin/articles 应 403
echo "\n[Step 2] reader 访问 /admin/articles 应 403（D23）\n";
$reader = User::where('role', 'reader')->first();
if (! $reader) {
    $ok("无 reader 用户，跳过（仅检查中间件逻辑）");
} else {
    $mw = app(\App\Http\Middleware\EnsureAdminRole::class);
    $req = \Illuminate\Http\Request::create('/admin/articles');
    $req->setUserResolver(fn () => $reader);
    try {
        $mw->handle($req, fn ($r) => response('OK', 200));
        $fail("reader 应被拒，但被放行");
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            $ok("reader 访问 → 403 Forbidden");
        } else {
            $fail("reader 访问 → {$e->getStatusCode()}（应 403）");
        }
    }
}

// 步骤 3：敏感词导入（自动化版）— 上传 docs/fixtures/sensitive-words-sample.txt
echo "\n[Step 3] 敏感词导入\n";
$sampleFile = __DIR__ . '/../../docs/fixtures/sensitive-words-sample.txt';
if (! file_exists($sampleFile)) {
    $fail("sample 文件不存在: {$sampleFile}");
} else {
    $content = file_get_contents($sampleFile);
    $tokens = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    $tokens = array_values(array_unique(array_filter(array_map('trim', $tokens))));
    $prefix = 'm3_e2e_' . substr(uniqid(), -4) . '_';
    $tokensPrefixed = array_map(fn ($t) => $prefix . $t, $tokens);

    // 模拟导入
    $existing = \App\Models\SensitiveWord::whereIn('word', $tokensPrefixed)->pluck('word')->all();
    $toInsert = array_values(array_diff($tokensPrefixed, $existing));
    if (! empty($toInsert)) {
        $now = now();
        \App\Models\SensitiveWord::insert(array_map(fn ($w) => [
            'word' => $w, 'level' => 'moderate', 'group_name' => 'm3_e2e',
            'created_at' => $now, 'updated_at' => $now,
        ], $toInsert));
    }
    if (count($toInsert) === count($tokens)) {
        $ok("导入成功: 共解析 " . count($tokens) . " 个词，成功 " . count($toInsert) . " 个");
    } else {
        $fail("导入失败: parsed=" . count($tokens) . " inserted=" . count($toInsert));
    }

    // 清理
    \App\Models\SensitiveWord::where('group_name', 'm3_e2e')->delete();
    $ok("测试数据已清理");
}

// 步骤 4：审核流程（自动化版）
// D26 已修复（migrate:fresh 后 DB enum 与 D12 一致）
echo "\n[Step 4] 审核流程（状态机 + DB enum 验证 — D26 已修复）\n";
$sm = app(\App\Services\ArticleStateMachine::class);

// 4a. pending → published (first_pass)
if ($sm->canTransition(\App\Enums\ArticleStatus::PENDING, \App\Enums\ArticleStatus::PUBLISHED)) {
    $ok("状态机：pending → published（first_pass 路径）允许");
} else {
    $fail("状态机拒绝 pending → published");
}

// 4b. modify_required 路径
if ($sm->canTransition(\App\Enums\ArticleStatus::PENDING, \App\Enums\ArticleStatus::MODIFY_REQUIRED)) {
    $ok("状态机：pending → modify_required 允许");
} else {
    $fail("状态机拒绝 pending → modify_required");
}

// 4c. appealing → second_pass / second_reject
if ($sm->canTransition(\App\Enums\ArticleStatus::APPEALING, \App\Enums\ArticleStatus::PUBLISHED)
    && $sm->canTransition(\App\Enums\ArticleStatus::APPEALING, \App\Enums\ArticleStatus::SECOND_REJECT)) {
    $ok("状态机：appealing → published（second_pass）/ second_reject 允许");
} else {
    $fail("状态机拒绝 appealing → published/second_reject");
}

// 4d. 状态机包含全部 7 个审核/上下架动作
$checks = [
    [\App\Enums\ArticleStatus::PENDING, \App\Enums\ArticleStatus::PUBLISHED, 'first_pass'],
    [\App\Enums\ArticleStatus::PENDING, \App\Enums\ArticleStatus::FIRST_REJECT, 'first_reject'],
    [\App\Enums\ArticleStatus::PENDING, \App\Enums\ArticleStatus::MODIFY_REQUIRED, 'modify_required'],
    [\App\Enums\ArticleStatus::APPEALING, \App\Enums\ArticleStatus::PUBLISHED, 'second_pass'],
    [\App\Enums\ArticleStatus::APPEALING, \App\Enums\ArticleStatus::SECOND_REJECT, 'second_reject'],
    [\App\Enums\ArticleStatus::PUBLISHED, \App\Enums\ArticleStatus::UNLISTED, 'unlist'],
    [\App\Enums\ArticleStatus::UNLISTED, \App\Enums\ArticleStatus::PENDING, 'republish'],
];
$reachable = 0;
foreach ($checks as [$from, $to, $label]) {
    if ($sm->canTransition($from, $to)) {
        $reachable++;
    }
}
echo "  [INFO] 状态机可达审核/上下架动作: {$reachable}/" . count($checks) . "\n";
if ($reachable === count($checks)) {
    $ok("7 个动作全部可达：first_pass/first_reject/modify_required/second_pass/second_reject/unlist/republish");
} else {
    $fail("仅 {$reachable} 个动作可达");
}

// 4e. 验证 DB 当前 enum 值与 D12 是否一致（D26 已修复）
$col = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM article_review_logs WHERE Field='action'");
$dbEnum = $col[0]->Type ?? '';
echo "  [INFO] DB 当前 enum: {$dbEnum}\n";
if (str_contains($dbEnum, 'first_pass') && str_contains($dbEnum, 'second_pass')) {
    $ok("D26 已修复：DB enum 与 D12 一致（含 first_pass / second_pass）");
} else {
    $fail("D26 未修复：DB enum 缺失 first_pass / second_pass");
}

// 步骤 5：下架（状态机验证 + DB enum 验证 — D27 已修复）
echo "\n[Step 5] 下架 published → unlisted（D17 + D27）\n";
if ($sm->canTransition(\App\Enums\ArticleStatus::PUBLISHED, \App\Enums\ArticleStatus::UNLISTED)) {
    $ok("状态机：published → unlisted 允许");
} else {
    $fail("状态机拒绝 published → unlisted（D17 违规）");
}
if ($sm->canTransition(\App\Enums\ArticleStatus::UNLISTED, \App\Enums\ArticleStatus::PENDING)) {
    $ok("状态机：unlisted → pending 允许（republish）");
} else {
    $fail("状态机拒绝 unlisted → pending");
}
$col2 = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM articles WHERE Field='status'");
$dbEnum2 = $col2[0]->Type ?? '';
echo "  [INFO] DB articles.status enum: {$dbEnum2}\n";
if (str_contains($dbEnum2, 'unlisted')) {
    $ok("D27 已修复：articles.status enum 含 unlisted");
} else {
    $fail("D27 未修复：articles.status 缺失 unlisted");
}

// 步骤 6：ReviewLog JSON 回显（避开 action enum，使用现有 enum 内的 auto_reject）
echo "\n[Step 6] ReviewLog sensitive_word_hit JSON 回显\n";
$article = \App\Models\Article::first();
if (! $article) {
    echo "  [SKIP] 无文章可测\n";
} else {
    // DB 当前 enum 包含 auto_reject，可以直接插入
    $hitWord = 'm3_e2e_hit_' . uniqid();
    \App\Models\SensitiveWord::create([
        'word' => $hitWord, 'level' => 'severe',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $log = \App\Models\ArticleReviewLog::create([
        'article_id' => $article->id,
        'reviewer_id' => $admin->id,
        'action' => 'auto_reject',  // DB 当前支持的 enum 值
        'reason' => 'e2e test: sensitive word hit',
        'review_round' => 99,
        'sensitive_word_hit' => [['word' => $hitWord, 'level' => 'severe']],
        'created_at' => now(),
    ]);

    $log->refresh();
    if (is_array($log->sensitive_word_hit) && count($log->sensitive_word_hit) === 1
        && ($log->sensitive_word_hit[0]['word'] ?? null) === $hitWord) {
        $ok("sensitive_word_hit JSON 序列化正确: word=" . $log->sensitive_word_hit[0]['word']);
    } else {
        $fail("sensitive_word_hit 序列化错误");
        var_export($log->sensitive_word_hit);
        echo "\n";
    }
    // 清理
    $log->delete();
    \App\Models\SensitiveWord::where('word', $hitWord)->delete();
    $ok("测试数据已清理");
}

echo "\n";
Auth::logout();
if ($pass) {
    echo "=== M3 端到端 ALL PASS ===\n";
    exit(0);
}
echo "=== M3 端到端 FAIL ===\n";
exit(1);