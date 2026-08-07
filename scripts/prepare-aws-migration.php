<?php

/**
 * Prepare hand-off from this PC to AWS EC2 Free Tier.
 *
 * Run: php scripts/prepare-aws-migration.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo '=== Kuandaa uhamishaji kwenda AWS ==='.PHP_EOL.PHP_EOL;

passthru('php '.escapeshellarg(__DIR__.'/export-database.php'), $exportCode);

if ($exportCode !== 0) {
    echo PHP_EOL.'Export imeshindwa.'.PHP_EOL;

    exit(1);
}

$dump = __DIR__.'/../deploy/database-dump.sql';
$keyFile = __DIR__.'/../deploy/app-key.txt';
$checklist = __DIR__.'/../deploy/AWS-MIGRATION.txt';

$clickpesa = [
    'CLICKPESA_CLIENT_ID' => config('hotspot.providers.clickpesa.client_id') ? 'imewekwa' : 'HAIJAWEKWA',
    'CLICKPESA_API_KEY' => config('hotspot.providers.clickpesa.api_key') ? 'imewekwa' : 'HAIJAWEKWA',
    'CLICKPESA_CHECKSUM_KEY' => config('hotspot.providers.clickpesa.checksum_key') ? 'imewekwa' : 'HAIJAWEKWA',
];

$router = App\Models\RouterSetting::resolve();

$lines = [
    'Tech-Sphere — orodha ya uhamishaji kwenda AWS Free Tier',
    'Tarehe: '.date('Y-m-d H:i:s'),
    '',
    'FAILI ZA KUBEBWA (scp — SI GitHub):',
    '  [x] deploy/database-dump.sql  ('.number_format(filesize($dump) / 1024, 1).' KB)',
    '  [x] deploy/app-key.txt',
    '  [ ] ClickPesa values (andika kwa mkono kwenye EC2)',
    '',
    'ClickPesa kwenye PC hii:',
];

foreach ($clickpesa as $name => $state) {
    $lines[] = "  {$name}: {$state}";
}

$lines = array_merge($lines, [
    '',
    'Router sasa: '.($router->router_ip ?? '-').'  → baada ya VPN: 10.8.0.2',
    '',
    'HATUA KWA UFUPI:',
    '  1. Sajili https://aws.amazon.com/free/',
    '  2. EC2 → Launch Ubuntu 24.04 t2.micro/t3.micro + Public IP',
    '  3. Security Group: 22, 80, 443, 1194/tcp',
    '  4. git clone https://github.com/abdulrazaqjafar/Abdul-Techwifi',
    '  5. scp database-dump.sql + app-key.txt → deploy/',
    '  6. sudo APP_PUBLIC_URL=http://PUBLIC_IP bash deploy/install-aws.sh',
    '  7. Weka ClickPesa kwenye .env',
    '  8. sudo bash deploy/openvpn/setup-server.sh',
    '  9. MikroTik: import certs + oracle-openvpn-client.rsc (badilisha PUBLIC_IP)',
    ' 10. php scripts/apply-oracle-router-ip.php',
    ' 11. php scripts/configure-oracle-portal.php PUBLIC_IP',
    ' 12. sudo bash deploy/verify-oracle.sh  kisha zima PC',
    '',
    'Soma: deploy/AWS.md',
    'Kikumbusho: Free Tier inaisha baada ya miezi 12 — weka budget alert.',
]);

file_put_contents($checklist, implode(PHP_EOL, $lines).PHP_EOL);

echo PHP_EOL.implode(PHP_EOL, $lines).PHP_EOL;
echo PHP_EOL.'Imehifadhiwa: deploy/AWS-MIGRATION.txt'.PHP_EOL;
echo 'APP_KEY: '.(is_file($keyFile) ? 'SAWA' : 'HAIKUPATIKANA').PHP_EOL;
