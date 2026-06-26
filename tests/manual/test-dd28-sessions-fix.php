<?php
/**
 * DD28 — sessions.user_id truncation regression test.
 * Run: php tests/manual/test-dd28-sessions-fix.php
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$pass = true;
$fail = function (string $msg) use (&$pass) {
    echo "  [FAIL] {$msg}\n";
    $pass = false;
};
$ok = function (string $msg) {
    echo "  [OK] {$msg}\n";
};

echo "=== DD28 — sessions.user_id ULID 兼容性回归测试 ===\n\n";

// 1. Schema 检查
$col = DB::selectOne("SHOW COLUMNS FROM sessions WHERE Field='user_id'");
$type = $col->Type ?? '';
echo "[1] sessions.user_id 列类型: {$type}\n";
if (str_contains($type, 'char(26)') || str_contains($type, 'varchar(26)')) {
    $ok("DD28 已修复：sessions.user_id 改为 CHAR(26)，可容纳 ULID");
} else {
    $fail("DD28 未修复：sessions.user_id 类型仍为 {$type}");
}

// 2. admin 账号存在
$admin = User::where('email', 'admin@example.com')->first();
if (! $admin) {
    $fail("admin 用户不存在");
    exit(1);
}
echo "\n[2] admin 账号: id={$admin->id} (length=" . strlen($admin->id) . ")\n";
if (strlen($admin->id) === 26) {
    $ok("admin.id 是 26 字符 ULID");
} else {
    $fail("admin.id 长度异常: " . strlen($admin->id));
}

// 3. 模拟 Filament 登录后的 session 保存
Auth::login($admin);
session()->put('_dd28_test', 'regression-check');
session()->save();
echo "\n[3] session()->save() 执行成功（修复前会抛 1265）\n";
$ok("session 写入无 SQLSTATE 1265 错误");

// 4. 验证落库的 user_id 等于 admin.id
$row = DB::selectOne('SELECT user_id FROM sessions ORDER BY last_activity DESC LIMIT 1');
$savedId = $row->user_id ?? null;
echo "[4] saved session.user_id: {$savedId}\n";
if ($savedId === $admin->id) {
    $ok("session.user_id 与 admin.id 完全一致，无截断");
} else {
    $fail("user_id 不一致：admin.id={$admin->id}, session.user_id={$savedId}");
}

// 5. 模拟 admin 通过 HTTP 访问 /admin
echo "\n[5] 模拟 HTTP GET /admin\n";
try {
    $req = \Illuminate\Http\Request::create('/admin', 'GET');
    $req->setUserResolver(fn () => $admin);
    $response = $app->handle($req);
    $status = $response->getStatusCode();
    echo "    HTTP status: {$status}\n";
    if ($status === 200 || $status === 302) {
        $ok("GET /admin 返回 {$status}（不再是 500）");
    } else {
        $fail("GET /admin 返回 {$status}");
    }
} catch (\Throwable $e) {
    $fail("GET /admin 抛出: " . get_class($e) . " — " . $e->getMessage());
}

// 6. 端到端：admin 登录后访问 /admin（验证 dashboard 路由可解析）
echo "\n[6] 端到端：dashboard 路由可达性\n";
try {
    $admin2 = User::where('email', 'admin@example.com')->first();

    // 用 admin 身份手工构建请求（不模拟 cookie/redirect，专注验证
    // Filament 中间件链不抛 1265 异常）
    $req = \Illuminate\Http\Request::create('/admin', 'GET');
    $req->setUserResolver(fn () => $admin2);

    // 触发 session 中间件
    $sessionMgr = $app->make(\Illuminate\Session\SessionManager::class);
    $session = $sessionMgr->driver();
    $session->start();
    $req->setLaravelSession($session);

    // 让 Auth middleware 看到 admin
    \Illuminate\Support\Facades\Auth::setUser($admin2);

    // 跑 Filament 中间件链
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($req);
    $status = $response->getStatusCode();
    echo "    GET /admin (admin resolver + 已认证) → {$status}\n";

    if ($status >= 200 && $status < 500) {
        $ok("端到端：admin 访问 /admin 返回 {$status}（不再 500）");
    } else {
        $fail("端到端：admin 访问 /admin 返回 {$status}");
    }

    // 检查响应 body 里没有 1265 异常信息
    $body = $response->getContent();
    if (str_contains($body, 'Data truncated') || str_contains($body, 'SQLSTATE')) {
        $fail("响应 body 含 SQL 错误: " . substr($body, 0, 200));
    } else {
        $ok("响应 body 不含 SQL 错误");
    }
} catch (\Throwable $e) {
    $fail("端到端测试抛出: " . get_class($e) . " — " . $e->getMessage());
}

echo "\n" . ($pass ? "=== DD28 ALL PASS ===" : "=== DD28 FAILED ===") . "\n";
exit($pass ? 0 : 1);
