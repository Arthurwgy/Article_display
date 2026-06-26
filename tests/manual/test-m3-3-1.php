<?php
/**
 * M3 3.1 verification — EnsureAdminRole middleware existence + AdminPanelProvider wiring (D23).
 * Run with: php test-m3-3-1.php
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== M3 3.1 — EnsureAdminRole middleware check ===\n";

$mwClass = App\Http\Middleware\EnsureAdminRole::class;
echo "Class name: {$mwClass}\n";

if (class_exists($mwClass)) {
    echo "  [OK] Class autoloadable\n";
    $mw = new App\Http\Middleware\EnsureAdminRole();
    echo "  [OK] Instance created\n";

    $reflect = new ReflectionClass($mw);
    if ($reflect->hasMethod('handle')) {
        echo "  [OK] handle() method exists\n";
        $handle = $reflect->getMethod('handle');
        echo "  [OK] handle() params count: " . $handle->getNumberOfParameters() . "\n";
    } else {
        echo "  [FAIL] handle() method missing\n";
        exit(1);
    }
} else {
    echo "  [FAIL] class not autoloadable\n";
    exit(1);
}

// Verify AdminPanelProvider registers EnsureAdminRole
$provider = new App\Providers\Filament\AdminPanelProvider($app);
$panel = $provider->panel(new Filament\Panel());
$authMiddleware = $panel->getAuthMiddleware();
echo "AdminPanelProvider authMiddleware:\n";
$found = false;
foreach ($authMiddleware as $m) {
    $name = is_string($m) ? $m : get_class($m);
    echo "  - {$name}\n";
    if ($name === App\Http\Middleware\EnsureAdminRole::class) {
        $found = true;
    }
}

if ($found) {
    echo "  [OK] EnsureAdminRole is registered in authMiddleware\n";
} else {
    echo "  [FAIL] EnsureAdminRole NOT in authMiddleware\n";
    exit(1);
}

echo "=== M3 3.1 PASS ===\n";