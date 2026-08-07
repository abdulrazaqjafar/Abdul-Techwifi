<?php

/**
 * Cut captive portal over to the Oracle public IP: rewrite login.html hosts,
 * apply DNS + walled garden on the router, upload the page.
 *
 * Prerequisites: OpenVPN up, router_ip already 10.8.0.2 (or still LAN if you
 * run this once from the PC before switching).
 *
 * Run:
 *   php scripts/configure-oracle-portal.php 132.145.x.x
 *   php scripts/configure-oracle-portal.php 132.145.x.x --skip-upload
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$publicIp = $argv[1] ?? null;
$skipUpload = in_array('--skip-upload', $argv ?? [], true);

if ($publicIp === null || ! filter_var($publicIp, FILTER_VALIDATE_IP)) {
    echo 'Matumizi: php scripts/configure-oracle-portal.php <PUBLIC_IP> [--skip-upload]'.PHP_EOL;

    exit(1);
}

$loginPath = __DIR__.'/../mikrotik/login.html';
$html = file_get_contents($loginPath);

if ($html === false) {
    echo 'login.html haipatikani.'.PHP_EOL;

    exit(1);
}

// Prefer domain (MikroTik static DNS → Oracle), then bare public IP on port 80.
$hostsBlock = "        var hosts = [\n"
    ."            'http://tech-spherewifi.xyz',\n"
    ."            'http://{$publicIp}',\n"
    ."            'http://{$publicIp}:8000'\n"
    .'        ];';

$updated = preg_replace(
    '/var hosts = \[[\s\S]*?\];/',
    $hostsBlock,
    $html,
    1,
    $count
);

if ($count !== 1) {
    echo 'Imeshindwa kupata orodha ya hosts ndani ya login.html'.PHP_EOL;

    exit(1);
}

file_put_contents($loginPath, $updated);
echo 'login.html imesasishwa → '.$publicIp.PHP_EOL;

// Fill PUBLIC_IP into cutover / vpn rsc copies under deploy/
$outDir = __DIR__.'/../deploy/openvpn/generated';
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

foreach ([
    'oracle-portal-cutover.rsc' => __DIR__.'/../mikrotik/oracle-portal-cutover.rsc',
    'oracle-openvpn-client.rsc' => __DIR__.'/../mikrotik/oracle-openvpn-client.rsc',
] as $name => $source) {
    $body = file_get_contents($source);
    $body = str_replace('PUBLIC_IP', $publicIp, $body);
    file_put_contents($outDir.'/'.$name, $body);
    echo '  imeandikwa: deploy/openvpn/generated/'.$name.PHP_EOL;
}

// Apply portal cutover via API (DNS + walled garden + API allow list)
echo PHP_EOL.'=== Kusanidi router (DNS + walled garden) ==='.PHP_EOL;

try {
    $settings = App\Models\RouterSetting::resolve();
    $client = new RouterOS\Client([
        'host' => $settings->router_ip,
        'port' => $settings->router_port ?: 8728,
        'user' => $settings->router_username,
        'pass' => $settings->router_password,
        'timeout' => 20,
        'attempts' => 2,
    ]);
} catch (Throwable $exception) {
    echo 'Router haikufikika: '.trim($exception->getMessage()).PHP_EOL;
    echo 'Endesha cutover .rsc kwa mkono baada ya VPN kuungana.'.PHP_EOL;

    exit(0);
}

$ensureWalledIp = function (string $ip) use ($client): void {
    $existing = $client->query(
        (new RouterOS\Query('/ip/hotspot/walled-garden/ip/print'))
            ->where('dst-address', $ip)
    )->read();

    if ($existing !== []) {
        echo "  walled-garden ip {$ip} tayari ipo".PHP_EOL;

        return;
    }

    $client->query(
        (new RouterOS\Query('/ip/hotspot/walled-garden/ip/add'))
            ->equal('dst-address', $ip)
            ->equal('action', 'accept')
            ->equal('comment', 'Tech-Sphere Oracle billing')
    )->read();

    echo "  + walled-garden ip {$ip}".PHP_EOL;
};

$ensureWalledHost = function (string $host) use ($client): void {
    $existing = $client->query(
        (new RouterOS\Query('/ip/hotspot/walled-garden/print'))
            ->where('dst-host', $host)
    )->read();

    if ($existing !== []) {
        echo "  walled-garden host {$host} tayari ipo".PHP_EOL;

        return;
    }

    $client->query(
        (new RouterOS\Query('/ip/hotspot/walled-garden/add'))
            ->equal('dst-host', $host)
            ->equal('action', 'allow')
            ->equal('comment', 'Tech-Sphere Oracle portal')
    )->read();

    echo "  + walled-garden host {$host}".PHP_EOL;
};

$ensureWalledIp($publicIp);
$ensureWalledHost($publicIp);
$ensureWalledHost('tech-spherewifi.xyz');
$ensureWalledHost('www.tech-spherewifi.xyz');

// Replace static DNS
foreach ($client->query(
    (new RouterOS\Query('/ip/dns/static/print'))->where('comment', 'Tech-Sphere portal')
)->read() as $row) {
    if (isset($row['.id'])) {
        $client->query((new RouterOS\Query('/ip/dns/static/remove'))->equal('.id', $row['.id']))->read();
    }
}

foreach (['tech-spherewifi.xyz', 'www.tech-spherewifi.xyz'] as $name) {
    $client->query(
        (new RouterOS\Query('/ip/dns/static/add'))
            ->equal('name', $name)
            ->equal('address', $publicIp)
            ->equal('ttl', '1d')
            ->equal('comment', 'Tech-Sphere portal')
    )->read();
    echo "  + dns {$name} → {$publicIp}".PHP_EOL;
}

// API allow list
$api = $client->query((new RouterOS\Query('/ip/service/print'))->where('name', 'api'))->read()[0] ?? null;
if ($api && isset($api['.id'])) {
    $client->query(
        (new RouterOS\Query('/ip/service/set'))
            ->equal('.id', $api['.id'])
            ->equal('disabled', 'false')
            ->equal('address', '192.168.20.0/24,10.8.0.0/24')
    )->read();
    echo '  + api address=192.168.20.0/24,10.8.0.0/24'.PHP_EOL;
}

// APP_URL on this machine if we are already on Oracle
$envPath = __DIR__.'/../.env';
if (is_file($envPath)) {
    $env = file_get_contents($envPath);
    $env = preg_replace('/^APP_URL=.*/m', 'APP_URL=http://'.$publicIp, $env);
    file_put_contents($envPath, $env);
    echo '  APP_URL=.env → http://'.$publicIp.PHP_EOL;
    passthru('php '.escapeshellarg(__DIR__.'/../artisan').' config:clear');
}

if (! $skipUpload) {
    echo PHP_EOL.'=== Kupakia login.html ==='.PHP_EOL;
    passthru('php '.escapeshellarg(__DIR__.'/upload-login-page.php'), $code);
    if ($code !== 0) {
        echo 'Upload imeshindwa — jaribu tena PC ikiwa bado kwenye LAN,'.PHP_EOL;
        echo 'au pakia faili kwa FTP/Winbox.'.PHP_EOL;
    }
}

echo PHP_EOL.'Cutover ya portal imekamilika.'.PHP_EOL;
echo 'Jaribu kutoka simu: Unganisha Abdul-Tech → Sign in → nunua kifurushi.'.PHP_EOL;
