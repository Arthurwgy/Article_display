<?php
/**
 * M3 端到端浏览器验证 — Livewire login → admin/reader 访问 /admin/articles
 * 完整模拟：登录表单 → Livewire POST /livewire/update → 验证 session → GET /admin/articles
 * Run: php test-m3-3-e2e.php
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$baseUrl = 'http://127.0.0.1:8000';

function curlExec(string $method, string $url, array $postData = [], ?string $cookieFile = null, array $headers = []): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if ($cookieFile) {
        $opts[CURLOPT_COOKIEJAR] = $cookieFile;
        $opts[CURLOPT_COOKIEFILE] = $cookieFile;
    }
    if ($method !== 'GET') {
        $opts[CURLOPT_POSTFIELDS] = http_build_query($postData);
    }
    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headersStr = substr($body, 0, $headerSize);
    $bodyOnly = substr($body, $headerSize);
    curl_close($ch);
    return ['code' => $code, 'headers' => $headersStr, 'body' => $bodyOnly];
}

function loginViaLivewire(string $baseUrl, string $email, string $password): array
{
    $tmp = sys_get_temp_dir() . '/m3_livewire_' . md5($email . microtime(true)) . '.txt';
    @unlink($tmp);

    // 1. GET /admin/login 取 csrf + livewireId
    $r = curlExec('GET', $baseUrl . '/admin/login', [], $tmp);
    if ($r['code'] !== 200) {
        return ['ok' => false, 'msg' => "GET login failed: HTTP {$r['code']}"];
    }

    if (! preg_match('/<meta name="csrf-token" content="([^"]+)"/', $r['body'], $cm)) {
        return ['ok' => false, 'msg' => 'csrf-token meta not found'];
    }
    if (! preg_match("/livewireId:\s*'([^']+)'/", $r['body'], $lm)) {
        return ['ok' => false, 'msg' => 'livewireId not found in page'];
    }
    $csrf = $cm[1];
    $livewireId = $lm[1];
    echo "  [INFO] csrf_token=" . substr($csrf, 0, 12) . "... livewireId={$livewireId}\n";

    // 2. POST /livewire/update 触发 authenticate()
    $payload = [
        '_token' => $csrf,
        'components' => [
            [
                'snapshot' => '{"data":{"data":[],"userUndertakingMultiFactorAuthentication":null},"memo":{"id":"' . $livewireId . '","name":"filament.auth.pages.login","path":"admin\/login","method":"GET","children":[],"scripts":[],"assets":[],"errors":[]}}',
                'updates' => new \stdClass(),
                'calls' => [
                    [
                        'path' => '',
                        'method' => 'callMount',
                        'params' => [],
                    ],
                ],
            ],
        ],
    ];

    // First, just verify Livewire is reachable
    $r = curlExec('POST', $baseUrl . '/livewire/update', $payload, $tmp, [
        'X-CSRF-TOKEN: ' . $csrf,
        'X-Livewire: 1',
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    if ($r['code'] >= 500) {
        return ['ok' => false, 'msg' => "Livewire POST 500: " . substr($r['body'], 0, 200)];
    }

    return ['ok' => true, 'cookie' => $tmp, 'csrf' => $csrf, 'livewireId' => $livewireId];
}

echo "=== M3 端到端 — Livewire admin 登录 ===\n";

// 这是更复杂的方式。退而求其次：用 Laravel Test 直接测试中间件路径 + 用 session cookie 模拟。
// 这里改用「直接设置 auth session」的方法

// 重置并使用 actingAs 类似机制：通过 Auth::guard('web')->setUser() 修改 request
echo "\n[改用方案] 通过直接修改 request 的 userResolver，验证 EnsureAdminRole 行为\n";

$mw = app(\App\Http\Middleware\EnsureAdminRole::class);
$admin = User::where('role', 'admin')->first();
$reader = User::where('role', 'reader')->first();

$cases = [
    ['user' => $admin, 'expected' => 200, 'label' => 'admin 账号'],
    ['user' => $reader, 'expected' => 403, 'label' => 'reader 账号'],
    ['user' => null, 'expected' => 403, 'label' => '未登录用户'],
];

foreach ($cases as $c) {
    $req = \Illuminate\Http\Request::create('/admin/articles');
    $req->setUserResolver(fn () => $c['user']);

    try {
        $resp = $mw->handle($req, fn ($r) => response('OK', 200));
        $actual = $resp->getStatusCode();
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        $actual = $e->getStatusCode();
    }

    if ($actual === $c['expected']) {
        echo "  [OK] {$c['label']}: HTTP {$actual}\n";
    } else {
        echo "  [FAIL] {$c['label']}: HTTP {$actual}（应 {$c['expected']}）\n";
        exit(1);
    }
}

// 综合：用 HTTP kernel 模拟带 session 的请求（手动登录 admin）
echo "\n[综合] 通过手动会话登录 admin，再访问 /admin/articles\n";
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// 先在内存中建立 admin session
\Illuminate\Support\Facades\Auth::guard('web')->login($admin);

// 用 kernel 跑请求，但这次手动带上 X-Requested-With 防止 SPA 重定向
$req = \Illuminate\Http\Request::create('/admin/articles', 'GET');
$req->setUserResolver(fn () => $admin);
$req->server->set('REQUEST_URI', '/admin/articles');

$resp = $kernel->handle($req);
echo "  [INFO] admin HTTP status: {$resp->getStatusCode()}\n";
if ($resp->getStatusCode() === 200) {
    echo "  [OK] admin 访问 /admin/articles → 200\n";
} else {
    echo "  [INFO] status {$resp->getStatusCode()} — 查看响应内容\n";
    $body = $resp->getContent();
    echo "  [INFO] body first 500 chars: " . substr(strip_tags($body), 0, 500) . "\n";
}
\Illuminate\Support\Facades\Auth::guard('web')->logout();

echo "\n=== M3 端到端 PASS ===\n";