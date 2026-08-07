<?php

/**
 * Upload the custom captive portal page into flash/hotspot on the router.
 *
 * The API cannot move files between directories, so FTP is enabled just long
 * enough to place the file, then switched off again.
 *
 * Run: php scripts/upload-login-page.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = App\Models\RouterSetting::resolve();

$host = $settings->router_ip;
$user = $settings->router_username;
$pass = $settings->router_password;

$client = new RouterOS\Client([
    'host' => $host,
    'port' => $settings->router_port ?: 8728,
    'user' => $user,
    'pass' => $pass,
    'timeout' => 15,
    'attempts' => 2,
]);

$setFtp = function (string $disabled) use ($client): void {
    $rows = $client->query(
        (new RouterOS\Query('/ip/service/print'))->where('name', 'ftp')
    )->read();

    $id = $rows[0]['.id'] ?? null;

    if ($id === null) {
        return;
    }

    $client->query(
        (new RouterOS\Query('/ip/service/set'))
            ->equal('.id', $id)
            ->equal('disabled', $disabled)
            ->equal('address', '192.168.20.0/24')
    )->read();
};

$localFile = __DIR__.'/../mikrotik/login.html';

if (! is_file($localFile)) {
    echo 'mikrotik/login.html haipatikani.'.PHP_EOL;
    exit(1);
}

echo 'Inawasha FTP kwa muda mfupi...'.PHP_EOL;
$setFtp('no');
sleep(2);

$uploaded = false;

try {
    $ftp = ftp_connect($host, 21, 15);

    if ($ftp === false) {
        throw new RuntimeException('Imeshindwa kuunganisha FTP.');
    }

    if (! ftp_login($ftp, $user, $pass)) {
        throw new RuntimeException('Imeshindwa kuingia kwa FTP.');
    }

    ftp_pasv($ftp, true);

    // The FTP root is the flash disk itself, so the hotspot folder is one
    // level down. Fall back to the full path on models without a flash disk.
    if (! @ftp_chdir($ftp, 'hotspot') && ! @ftp_chdir($ftp, 'flash/hotspot')) {
        throw new RuntimeException('Folda ya hotspot haipatikani kwenye FTP.');
    }

    echo 'Folda ya FTP: '.ftp_pwd($ftp).PHP_EOL;

    if (! ftp_put($ftp, 'login.html', $localFile, FTP_BINARY)) {
        throw new RuntimeException('Imeshindwa kupakia faili.');
    }

    echo 'login.html imepakiwa kwenye folda ya hotspot.'.PHP_EOL;
    $uploaded = true;

    ftp_close($ftp);
} catch (Throwable $exception) {
    echo 'FAIL: '.$exception->getMessage().PHP_EOL;
} finally {
    echo 'Inazima FTP tena...'.PHP_EOL;
    $setFtp('yes');
}

if ($uploaded) {
    echo PHP_EOL.'Inaondoa nakala zilizopotea njia...'.PHP_EOL;

    foreach (['login.html', 'flash/login.html'] as $stray) {
        $rows = $client->query(
            (new RouterOS\Query('/file/print'))->where('name', $stray)
        )->read();

        foreach ($rows as $row) {
            if (! isset($row['.id'])) {
                continue;
            }

            $client->query(
                (new RouterOS\Query('/file/remove'))->equal('.id', $row['.id'])
            )->read();

            echo '  imeondolewa: '.$stray.PHP_EOL;
        }
    }
}

echo PHP_EOL.'=== Faili za login kwenye router ==='.PHP_EOL;

// The router needs a moment before /file/print reports the new size.
sleep(3);

$localSize = filesize($localFile);

foreach ($client->query('/file/print')->read() as $row) {
    $name = $row['name'] ?? '';

    if (! str_contains($name, 'login')) {
        continue;
    }

    $size = $row['size'] ?? '-';
    $mark = ($name === 'flash/hotspot/login.html' && (int) $size === $localSize) ? '  <== yetu' : '';

    echo sprintf("%-42s size=%-8s%s\n", $name, $size, $mark);
}

echo PHP_EOL.'Ukubwa wa faili yetu ya karibuni: '.$localSize.PHP_EOL;

echo PHP_EOL.'=== Router inafikia intaneti kwa TCP? (ICMP inaonekana imezuiliwa) ==='.PHP_EOL;

foreach ([
    'http://detectportal.firefox.com/success.txt',
    'http://1.1.1.1/',
] as $url) {
    try {
        $client->query(
            (new RouterOS\Query('/tool/fetch'))
                ->equal('url', $url)
                ->equal('dst-path', 'net-test.txt')
                ->equal('mode', 'http')
        )->read();

        echo 'OK  : '.$url.PHP_EOL;
    } catch (Throwable $exception) {
        echo 'FAIL: '.$url.' — '.$exception->getMessage().PHP_EOL;
    }
}

echo PHP_EOL;
