<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Admin;
use App\Models\Package;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Route;

echo "=== DATABASE ===\n";
echo 'Admins: '.Admin::count()."\n";
echo 'Cashiers: '.Admin::where('role', 'cashier')->count()."\n";
echo 'Active packages: '.Package::where('is_active', true)->count()."\n";
echo 'Business: '.(BusinessSetting::getSettings()->business_name ?? 'N/A')."\n";

Admin::query()->select('email', 'role', 'is_active')->get()->each(function ($a): void {
    echo "  - {$a->email} | {$a->role} | active=".((int) $a->is_active)."\n";
});

Package::query()->where('is_active', true)->orderBy('price')->get(['name', 'price'])->each(function ($p): void {
    echo "  - {$p->name}: {$p->price}\n";
});

echo "\n=== CONFIG ===\n";
echo 'APP_URL: '.config('app.url')."\n";
echo 'Payment provider: '.config('hotspot.payment.default_provider')."\n";
echo 'Session driver: '.config('session.driver')."\n";

echo "\n=== ROUTES (key) ===\n";
$keyRoutes = ['hotspot.index', 'login', 'dashboard', 'cashier.index', 'staff.index', 'webhooks.clickpesa'];
foreach ($keyRoutes as $name) {
    echo $name.': '.(Route::has($name) ? route($name) : 'MISSING')."\n";
}

echo "\n=== HTTP SMOKE (127.0.0.1:8000) ===\n";
$urls = [
    'Hotspot' => 'http://127.0.0.1:8000/',
    'Login' => 'http://127.0.0.1:8000/login',
    'Dashboard (guest)' => 'http://127.0.0.1:8000/dashboard',
    'Cashier (guest)' => 'http://127.0.0.1:8000/cashier',
    'Staff (guest)' => 'http://127.0.0.1:8000/staff',
];

foreach ($urls as $label => $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    echo "{$label}: ".($err ? "ERROR {$err}" : "HTTP {$code}")."\n";
}

echo "\n=== AUTH FLOW ===\n";

function loginCheck(string $email, string $password, string $expectedRoute): void
{
    $cookieFile = tempnam(sys_get_temp_dir(), 'laravel_cookie_');

    // Get login page + CSRF
    $ch = curl_init('http://127.0.0.1:8000/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    preg_match('/name="_token" value="([^"]+)"/', $html, $m);
    $token = $m[1] ?? '';

    $ch = curl_init('http://127.0.0.1:8000/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $token,
            'email' => $email,
            'password' => $password,
        ]),
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    preg_match('/Location: (.+)/i', $response, $loc);
    $location = trim($loc[1] ?? '');
    curl_close($ch);

    $ok = $code === 302 && str_contains($location, $expectedRoute);
    echo "{$email}: HTTP {$code} -> {$location} ".($ok ? 'OK' : 'FAIL')."\n";

    if ($ok) {
        // Test protected page access
        $target = str_contains($expectedRoute, 'cashier') ? 'http://127.0.0.1:8000/cashier' : 'http://127.0.0.1:8000/dashboard';
        $ch = curl_init($target);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $pageCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "  Access {$target}: HTTP {$pageCode}\n";

        // Cashier should be blocked from staff
        if (str_contains($expectedRoute, 'cashier')) {
            $ch = curl_init('http://127.0.0.1:8000/staff');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_COOKIEJAR => $cookieFile,
                CURLOPT_COOKIEFILE => $cookieFile,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => true,
            ]);
            curl_exec($ch);
            $staffCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo "  Cashier -> /staff: HTTP {$staffCode} ".($staffCode === 403 ? 'OK (blocked)' : 'UNEXPECTED')."\n";
        }
    }

    @unlink($cookieFile);
}

loginCheck('abdulrazaqjafar66@gmail.com', 'JoaoAbdul345+?', '/dashboard');
loginCheck('cashier@techsphere.com', 'Cashier123!', '/cashier');

echo "\n=== ADMIN ACCESS ===\n";
$cookieFile = tempnam(sys_get_temp_dir(), 'admin_cookie_');
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile]);
$html = curl_exec($ch);
curl_close($ch);
preg_match('/name="_token" value="([^"]+)"/', $html, $m);
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $m[1] ?? '', 'email' => 'abdulrazaqjafar66@gmail.com', 'password' => 'JoaoAbdul345+?']),
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true,
]);
curl_exec($ch);
curl_close($ch);

foreach (['http://127.0.0.1:8000/staff', 'http://127.0.0.1:8000/cashier', 'http://127.0.0.1:8000/packages'] as $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookieFile, CURLOPT_COOKIEFILE => $cookieFile, CURLOPT_FOLLOWLOCATION => false, CURLOPT_HEADER => true, CURLOPT_NOBODY => true]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Admin {$url}: HTTP {$code}\n";
}
@unlink($cookieFile);

echo "\n=== CASHIER SELL (dry run) ===\n";
try {
    \Illuminate\Support\Facades\DB::beginTransaction();
    $cashier = Admin::where('email', 'cashier@techsphere.com')->firstOrFail();
    $package = Package::where('is_active', true)->orderBy('price')->firstOrFail();
    $sale = app(\App\Services\CashierService::class)->sellForCash([
        'package_id' => $package->id,
        'admin_id' => $cashier->id,
        'customer_name' => 'Test Customer',
        'phone' => '0712345678',
    ]);
    echo "Sell OK: {$sale->voucher->code} | {$package->name} | {$sale->amount}\n";
    \Illuminate\Support\Facades\DB::rollBack();
    echo "Rolled back (no data saved)\n";
} catch (Throwable $e) {
    \Illuminate\Support\Facades\DB::rollBack();
    echo 'FAIL: '.$e->getMessage()."\n";
}

echo "\n=== ROUTER ===\n";
$router = \App\Models\RouterSetting::first();
if ($router) {
    echo "Host: {$router->host}\n";
    echo "Port: {$router->port}\n";
    echo "Username: {$router->username}\n";
    try {
        app(\App\Services\MikrotikRouterService::class)->connect();
        echo "Router connection: OK\n";
    } catch (Throwable $e) {
        echo 'Router connection: FAIL - '.$e->getMessage()."\n";
    }
} else {
    echo "Router settings: NOT CONFIGURED\n";
}

echo "\n=== PAYMENT ===\n";
echo 'Provider: '.config('hotspot.payment.default_provider')."\n";
try {
    app(\App\Services\Payment\ClickPesa\ClickPesaClient::class)->getToken();
    echo "ClickPesa token: OK\n";
} catch (Throwable $e) {
    echo 'ClickPesa token: FAIL - '.$e->getMessage()."\n";
}

echo "\nDone.\n";
