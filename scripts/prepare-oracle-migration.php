<?php

/**
 * Prepare a safe hand-off from this PC to Oracle Cloud: export the database,
 * write APP_KEY, and print a checklist of files that must travel (never via
 * public GitHub).
 *
 * Run: php scripts/prepare-oracle-migration.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo '=== Kuandaa uhamishaji kwenda Oracle ==='.PHP_EOL.PHP_EOL;

passthru('php '.escapeshellarg(__DIR__.'/export-database.php'), $exportCode);

if ($exportCode !== 0) {
    echo PHP_EOL.'Export imeshindwa.'.PHP_EOL;

    exit(1);
}

$dump = __DIR__.'/../deploy/database-dump.sql';
$keyFile = __DIR__.'/../deploy/app-key.txt';
$checklist = __DIR__.'/../deploy/ORACLE-MIGRATION.txt';

$clickpesa = [
    'CLICKPESA_CLIENT_ID' => config('hotspot.providers.clickpesa.client_id') ? 'imewekwa' : 'HAIJAWEKWA',
    'CLICKPESA_API_KEY' => config('hotspot.providers.clickpesa.api_key') ? 'imewekwa' : 'HAIJAWEKWA',
    'CLICKPESA_CHECKSUM_KEY' => config('hotspot.providers.clickpesa.checksum_key') ? 'imewekwa' : 'HAIJAWEKWA',
];

$router = App\Models\RouterSetting::resolve();

$lines = [];
$lines[] = 'Tech-Sphere — orodha ya uhamishaji kwenda Oracle Cloud';
$lines[] = 'Tarehe: '.date('Y-m-d H:i:s');
$lines[] = '';
$lines[] = 'FAILI ZA KUBEBWA (USB au scp — SI GitHub):';
$lines[] = '  [x] deploy/database-dump.sql  ('.number_format(filesize($dump) / 1024, 1).' KB)';
$lines[] = '  [x] deploy/app-key.txt        (APP_KEY — password ya router inategemea hii)';
$lines[] = '  [ ] .env values za ClickPesa (andika kwa mkono kwenye VM)';
$lines[] = '';
$lines[] = 'ClickPesa kwenye PC hii:';
foreach ($clickpesa as $name => $state) {
    $lines[] = "  {$name}: {$state}";
}
$lines[] = '';
$lines[] = 'Router sasa:';
$lines[] = '  IP: '.($router->router_ip ?? '-');
$lines[] = '  baada ya VPN: badilisha iwe 10.8.0.2';
$lines[] = '';
$lines[] = 'HATUA KWA UFUPI:';
$lines[] = '  1. Sajili Oracle Free Tier, unda Ubuntu 24.04 VM + Public IP';
$lines[] = '  2. Fungua ports 22, 80, 443, 1194 (TCP) kwenye Security List';
$lines[] = '  3. git clone https://github.com/abdulrazaqjafar/Abdul-Techwifi';
$lines[] = '  4. scp database-dump.sql + app-key.txt kwenda deploy/ kwenye VM';
$lines[] = '  5. sudo APP_PUBLIC_URL=http://PUBLIC_IP bash deploy/install-oracle.sh';
$lines[] = '  6. Weka ClickPesa kwenye /var/www/techsphere/.env';
$lines[] = '  7. sudo bash deploy/openvpn/setup-server.sh';
$lines[] = '  8. Unganisha MikroTik (oracle-openvpn-client.rsc)';
$lines[] = '  9. php scripts/apply-oracle-router-ip.php';
$lines[] = ' 10. php scripts/configure-oracle-portal.php PUBLIC_IP';
$lines[] = ' 11. Thibitisha, kisha zima php artisan serve kwenye PC';
$lines[] = '';
$lines[] = 'Soma: deploy/ORACLE.md';

file_put_contents($checklist, implode(PHP_EOL, $lines).PHP_EOL);

echo PHP_EOL.'=== Checklist ==='.PHP_EOL;
echo implode(PHP_EOL, $lines).PHP_EOL;
echo PHP_EOL.'Imehifadhiwa: deploy/ORACLE-MIGRATION.txt'.PHP_EOL;
echo 'APP_KEY file: '.(is_file($keyFile) ? 'SAWA' : 'HAIKUPATIKANA').PHP_EOL;
