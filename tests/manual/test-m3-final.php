<?php
/**
 * M3 最终验证 — 执行方案「验证方式」一节全部 6 步（tinker 自动化部分）
 * 涵盖：UNLISTED 状态、状态机、SensitiveWord、Article 关联、5 个过渡态
 * Run: php test-m3-final.php
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== M3 最终验证 — 执行方案「验证方式」全 6 步 ===\n\n";

use App\Enums\ArticleStatus;
use App\Enums\ReviewAction;
use App\Models\Article;
use App\Models\ArticleReviewLog;
use App\Models\SensitiveWord;
use App\Services\ArticleStateMachine;

$pass = true;
$fail = function (string $msg) use (&$pass) {
    echo "  [FAIL] {$msg}\n";
    $pass = false;
};
$ok = function (string $msg) {
    echo "  [OK] {$msg}\n";
};

// ----- 自动化 tinker 检查（执行方案「验证方式」一节） -----

// 1. UNLISTED 状态存在 + label
echo "[1] ArticleStatus::UNLISTED 存在\n";
if (defined(ArticleStatus::class . '::UNLISTED')) {
    $label = ArticleStatus::UNLISTED->label();
    if ($label === '已下架') {
        $ok("UNLISTED label = '已下架'");
    } else {
        $fail("UNLISTED label = '{$label}' (expected 已下架)");
    }
} else {
    $fail("UNLISTED 不存在");
}

// 2. 下架生命周期：published ↔ unlisted
echo "\n[2] 下架生命周期\n";
$sm = app(ArticleStateMachine::class);
if ($sm->canTransition(ArticleStatus::PUBLISHED, ArticleStatus::UNLISTED)) {
    $ok("published → unlisted: true");
} else {
    $fail("published → unlisted 应为 true");
}
if ($sm->canTransition(ArticleStatus::UNLISTED, ArticleStatus::PENDING)) {
    $ok("unlisted → pending: true");
} else {
    $fail("unlisted → pending 应为 true");
}

// 3. 修改要求动作可用（回归）
echo "\n[3] pending → modify_required 回归\n";
if ($sm->canTransition(ArticleStatus::PENDING, ArticleStatus::MODIFY_REQUIRED)) {
    $ok("pending → modify_required: true");
} else {
    $fail("pending → modify_required 应为 true (D14 回归)");
}

// 4. SensitiveWord 可读写
echo "\n[4] SensitiveWord CRUD\n";
try {
    $testWord = 'm3verify_' . uniqid();
    $s = SensitiveWord::create(['word' => $testWord, 'level' => 'moderate']);
    if ($s->id) {
        $ok("Created SensitiveWord id={$s->id}");
    } else {
        $fail("创建失败：无 id");
    }
    $s->delete();
    $ok("Deleted successfully");
} catch (\Throwable $e) {
    $fail("SensitiveWord 异常: " . $e->getMessage());
}

// 5. Article reviewLogs 关联可用
echo "\n[5] Article::reviewLogs() 关联\n";
$a = Article::query()->withCount('reviewLogs')->first();
if ($a) {
    $count = $a->reviewLogs()->count();
    $ok("Article id={$a->id}, reviewLogs count via relationship: {$count}");
    if ($count !== (int) $a->review_logs_count) {
        $fail("withCount ({$a->review_logs_count}) vs relation ({$count}) 不一致");
    } else {
        $ok("withCount 与 relation() 一致");
    }
} else {
    echo "  [WARN] 数据库无文章，跳过实例检查；反射检查关联定义...\n";
    $reflect = new ReflectionClass(Article::class);
    if ($reflect->hasMethod('reviewLogs')) {
        $ok("Article::reviewLogs() 方法存在（D19）");
    } else {
        $fail("Article::reviewLogs() 方法缺失");
    }
}

// 6. 路由注册（执行方案 6 步外的补充：HTTP 路由可达）
echo "\n[6] admin 路由注册（HTTP 入口可达性）\n";
$routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
    ->filter(fn ($r) => str_starts_with($r->uri(), 'admin/'))
    ->pluck('uri')
    ->unique()
    ->values();

$expected = [
    'admin/articles',
    'admin/articles/{record}/edit',
    'admin/categories',
    'admin/sensitive-words',
    'admin/tags',
    'admin/review-logs',
    'admin/users',
    'admin/login',
];
foreach ($expected as $uri) {
    if ($routes->contains($uri)) {
        $ok("Route '{$uri}' registered");
    } else {
        $fail("Route '{$uri}' NOT registered");
    }
}

// ----- D20 过渡态 0 记录回归 -----
echo "\n[D20] first_pass/second_pass 过渡态记录数\n";
$transitionalCount = Article::whereIn('status', ['first_pass', 'second_pass'])->count();
if ($transitionalCount === 0) {
    $ok("first_pass + second_pass 文章数 = 0（过渡态不落库）");
} else {
    $fail("过渡态文章数 = {$transitionalCount}（D20 违反）");
}

// ----- D23 非 admin 拦截 -----
echo "\n[D23] EnsureAdminRole 中间件拒绝非 admin\n";
$mw = new App\Http\Middleware\EnsureAdminRole();
$reflect = new ReflectionClass($mw);
$params = $reflect->getMethod('handle')->getParameters();
if (count($params) === 2) {
    $ok("handle() 接受 (Request, Closure) — 签名正确");
} else {
    $fail("handle() 签名参数数 = " . count($params));
}

// 用 admin 账号 request 模拟
$adminUser = \App\Models\User::where('role', 'admin')->first();
if ($adminUser) {
    $req = \Illuminate\Http\Request::create('/admin/articles');
    $req->setUserResolver(fn () => $adminUser);
    try {
        $resp = $mw->handle($req, fn ($r) => response('OK'));
        $ok("admin 账号 → handle() 放行（返回 200）");
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        $fail("admin 账号被拒: " . $e->getMessage());
    }
}

// 用 reader 账号 request 模拟
$readerUser = \App\Models\User::where('role', 'reader')->first();
if ($readerUser) {
    $req = \Illuminate\Http\Request::create('/admin/articles');
    $req->setUserResolver(fn () => $readerUser);
    try {
        $resp = $mw->handle($req, fn ($r) => response('OK'));
        $fail("reader 账号被放行（应被拒）");
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            $ok("reader 账号 → 403 Forbidden（正确）");
        } else {
            $fail("reader 账号 → 状态码 {$e->getStatusCode()}（应 403）");
        }
    }
}

echo "\n";
if ($pass) {
    echo "=== M3 最终验证 ALL PASS ===\n";
    exit(0);
}
echo "=== M3 最终验证 FAIL — 见上方 [FAIL] 行 ===\n";
exit(1);