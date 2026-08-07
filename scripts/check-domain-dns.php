<?php

/**
 * Tell whether the domain has finished moving to Cloudflare.
 *
 * Nameserver changes take anywhere from a few minutes to 24 hours, so this
 * answers "is it done yet?" without guessing.
 *
 * Run: php scripts/check-domain-dns.php
 */

$domain = $argv[1] ?? 'tech-spherewifi.xyz';

echo '=== '.$domain.' ==='.PHP_EOL.PHP_EOL;

echo 'Nameservers:'.PHP_EOL;

$nameServers = @dns_get_record($domain, DNS_NS) ?: [];

if ($nameServers === []) {
    echo '  (hakuna jibu — jaribu tena baada ya dakika chache)'.PHP_EOL;
}

$onCloudflare = false;

foreach ($nameServers as $record) {
    $host = $record['target'] ?? '-';

    echo '  '.$host.PHP_EOL;

    if (str_contains(strtolower($host), 'cloudflare')) {
        $onCloudflare = true;
    }
}

echo PHP_EOL.'Rekodi ya A (anwani ya umma):'.PHP_EOL;

foreach (@dns_get_record($domain, DNS_A) ?: [] as $record) {
    echo '  '.($record['ip'] ?? '-').PHP_EOL;
}

echo PHP_EOL.'Hali: ';

if ($onCloudflare) {
    echo 'IMEKAMILIKA — domain sasa ipo Cloudflare.'.PHP_EOL;
    echo 'Unaweza kuendelea na hatua ya 2 (API token) kwenye deploy/DOMAIN.md.'.PHP_EOL;
} else {
    echo 'BADO — nameservers hazijabadilika.'.PHP_EOL;
    echo 'Kama umeshaziweka Namecheap, subiri kisha endesha amri hii tena.'.PHP_EOL;
}

echo PHP_EOL.'=== Portal ya wateja (haiathiriwi na hapo juu) ==='.PHP_EOL;

// Customers resolve this name on the router, never on the internet, so the
// portal keeps working throughout the migration.
$portal = getenv('APP_URL') ?: 'http://192.168.20.253:8000';

echo '  Wateja wanatumia DNS ya MikroTik → '.$portal.PHP_EOL;
echo '  Kuthibitisha: php scripts/setup-domain-dns.php'.PHP_EOL.PHP_EOL;
