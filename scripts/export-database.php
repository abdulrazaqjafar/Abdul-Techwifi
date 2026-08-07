<?php

/**
 * Write the whole billing database to deploy/database-dump.sql so it can be
 * carried to the new server. Done in pure PHP rather than with mysqldump,
 * because mysqldump lives in a different place on every XAMPP/Laragon install.
 *
 * Run: php scripts/export-database.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$database = config('database.connections.'.config('database.default').'.database');
$target = __DIR__.'/../deploy/database-dump.sql';

$handle = fopen($target, 'w');

if ($handle === false) {
    echo 'Imeshindwa kuandika '.$target.PHP_EOL;

    exit(1);
}

fwrite($handle, "-- Tech-Sphere Wi-Fi Billing\n");
fwrite($handle, '-- Database: '.$database."\n");
fwrite($handle, '-- Tarehe: '.date('Y-m-d H:i:s')."\n\n");
fwrite($handle, "SET NAMES utf8mb4;\n");
fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

$tables = array_map(
    static fn (object $row): string => array_values((array) $row)[0],
    DB::select('SHOW TABLES')
);

$totalRows = 0;

foreach ($tables as $table) {
    $create = (array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
    $createSql = $create['Create Table'] ?? $create['Create View'] ?? null;

    if ($createSql === null) {
        continue;
    }

    fwrite($handle, "\n-- ----------------------------\n");
    fwrite($handle, '-- '.$table."\n");
    fwrite($handle, "-- ----------------------------\n");
    fwrite($handle, 'DROP TABLE IF EXISTS `'.$table."`;\n");
    fwrite($handle, $createSql.";\n\n");

    $rows = DB::table($table)->get();

    if ($rows->isEmpty()) {
        echo sprintf("  %-40s tupu\n", $table);

        continue;
    }

    $columns = array_keys((array) $rows->first());
    $columnList = '`'.implode('`, `', $columns).'`';

    foreach ($rows->chunk(200) as $chunk) {
        $values = [];

        foreach ($chunk as $row) {
            $cells = [];

            foreach ((array) $row as $value) {
                if ($value === null) {
                    $cells[] = 'NULL';

                    continue;
                }

                if (is_int($value) || is_float($value)) {
                    $cells[] = (string) $value;

                    continue;
                }

                $cells[] = DB::getPdo()->quote((string) $value);
            }

            $values[] = '('.implode(', ', $cells).')';
        }

        fwrite($handle, 'INSERT INTO `'.$table.'` ('.$columnList.") VALUES\n");
        fwrite($handle, implode(",\n", $values).";\n");
    }

    $totalRows += $rows->count();

    echo sprintf("  %-40s rows=%d\n", $table, $rows->count());
}

fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($handle);

echo PHP_EOL.'Imehifadhiwa: deploy/database-dump.sql'.PHP_EOL;
echo 'Majedwali: '.count($tables).'   Rows: '.$totalRows.PHP_EOL;
echo 'Ukubwa: '.number_format(filesize($target) / 1024, 1).' KB'.PHP_EOL;

// router_settings.router_password imehifadhiwa kwa encryption inayotegemea
// APP_KEY. Seva mpya ikitengeneza key nyingine, password hiyo haitasomeka na
// mfumo utashindwa kuunganishwa na MikroTik. Hivyo key inasafiri na data.
$keyFile = __DIR__.'/../deploy/app-key.txt';
file_put_contents($keyFile, (string) config('app.key'));

echo PHP_EOL.'APP_KEY imehifadhiwa: deploy/app-key.txt'.PHP_EOL;
echo 'Usiifute — bila yake seva mpya haitaweza kusoma password ya router.'.PHP_EOL;
echo PHP_EOL.'Kwa Oracle Cloud: php scripts/prepare-oracle-migration.php'.PHP_EOL;
echo 'Usipush database-dump.sql wala app-key.txt kwenye GitHub.'.PHP_EOL;
