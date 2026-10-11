<?php

use Database\Seeders\BulanAuditDemoSeeder;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$root = dirname(__DIR__);
chdir($root);
// Fixed destination: neither .env's application DB nor the suite's test DB.
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'kampuslms_uts_demo', 'DB_URL' => ''] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
$app = require $root.'/bootstrap/app.php';
if ($app->configurationIsCached()) {
    fwrite(STDERR, "BLOCKED: inspect and clear cached config first.\n");
    exit(2);
}
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
try {
    $connection = $app['db']->connection();
    if (! $app->environment('testing') || $connection->getDriverName() !== 'mysql'
        || $connection->getDatabaseName() !== 'kampuslms_uts_demo'
        || $app['config']->get('database.connections.mysql.url')
        || $connection->selectOne('SELECT DATABASE() AS name')->name !== 'kampuslms_uts_demo') {
        throw new RuntimeException('Unexpected database');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "BLOCKED: create and verify the dedicated MySQL database kampuslms_uts_demo first.\n");
    exit(2);
}
if (strlen(getenv('BULAN_DEMO_PASSWORD') ?: '') < 8) {
    fwrite(STDERR, "BLOCKED: set BULAN_DEMO_PASSWORD privately (at least 8 characters).\n");
    exit(2);
}
echo "Verified: APP_ENV=testing, MySQL kampuslms_uts_demo. Applying pending migrations without dropping tables.\n";
$code = $kernel->call('migrate', ['--force' => true]);
echo $kernel->output();
if ($code !== 0) {
    exit($code);
}
$code = $kernel->call('db:seed', ['--class' => BulanAuditDemoSeeder::class, '--force' => true]);
echo $kernel->output();
exit($code);
