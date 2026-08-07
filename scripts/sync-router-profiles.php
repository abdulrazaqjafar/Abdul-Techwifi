<?php

/**
 * Make sure every package has a matching hotspot user profile on the router,
 * and clear leftover files out of the captive portal folder.
 * Run: php scripts/sync-router-profiles.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$defaultRateLimit = '5M/5M';

$settings = App\Models\RouterSetting::resolve();

$client = new RouterOS\Client([
    'host' => $settings->router_ip,
    'port' => $settings->router_port ?: 8728,
    'user' => $settings->router_username,
    'pass' => $settings->router_password,
    'timeout' => 15,
    'attempts' => 2,
]);

$existing = [];

foreach ($client->query('/ip/hotspot/user/profile/print')->read() as $row) {
    if (isset($row['name'])) {
        $existing[] = $row['name'];
    }
}

echo '=== Profiles ==='.PHP_EOL;

foreach (App\Models\Package::query()->get() as $package) {
    $profile = (string) $package->profile_name;

    if ($profile === '') {
        echo sprintf("%-28s HARUKA: hakina profile_name\n", $package->name);

        continue;
    }

    if (in_array($profile, $existing, true)) {
        echo sprintf("%-28s %-24s ipo tayari\n", $package->name, $profile);

        continue;
    }

    $client->query(
        (new RouterOS\Query('/ip/hotspot/user/profile/add'))
            ->equal('name', $profile)
            ->equal('rate-limit', $defaultRateLimit)
            ->equal('shared-users', '1')
    )->read();

    $existing[] = $profile;

    echo sprintf("%-28s %-24s IMETENGENEZWA (%s)\n", $package->name, $profile, $defaultRateLimit);
}

echo PHP_EOL.'=== Kusafisha folda ya captive portal ==='.PHP_EOL;

// A .rsc or test file inside html-directory is served to clients, so it does
// not belong there. Filtering happens here rather than in the query because
// the router-side "where" filter is unreliable for file names.
$strays = [
    'flash/hotspot/00-full-setup.rsc',
    'flash/billing-test.txt',
    'flash/net-test.txt',
    'flash/hotspot/net-test.txt',
];

$removed = 0;

foreach ($client->query('/file/print')->read() as $row) {
    if (! in_array($row['name'] ?? '', $strays, true) || ! isset($row['.id'])) {
        continue;
    }

    $client->query(
        (new RouterOS\Query('/file/remove'))->equal('.id', $row['.id'])
    )->read();

    echo 'imeondolewa: '.$row['name'].PHP_EOL;
    $removed++;
}

if ($removed === 0) {
    echo '(hakuna faili ya kuondoa)'.PHP_EOL;
}

echo PHP_EOL.'=== Profiles zilizopo sasa ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/user/profile/print')->read() as $row) {
    echo sprintf(
        "%-24s rate-limit=%-12s shared-users=%s\n",
        $row['name'] ?? '-',
        $row['rate-limit'] ?? '-',
        $row['shared-users'] ?? '-'
    );
}

echo PHP_EOL;
