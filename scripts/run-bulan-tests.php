<?php

use Illuminate\Contracts\Console\Kernel;

// Run only against the dedicated database from phpunit.xml; never use the app DB.
require __DIR__.'/../vendor/autoload.php';

$root = dirname(__DIR__);
chdir($root);
$stage = $argv[1] ?? 'all';
$stages = [
    'baseline' => ['--filter', '/^(?!.*(?:AccessIntegrationTest|IdorIntegrationTest|Week6RegressionTest|JsonPrivacyAssertionTest)).*$/'],
    'new' => ['--filter', 'AccessIntegrationTest|IdorIntegrationTest|Week6RegressionTest|JsonPrivacyAssertionTest'],
    'feature' => ['--testsuite', 'Feature'],
    'regression' => ['--filter', 'AuthApiTest|CourseApiTest|MaterialApiTest|AssignmentApiTest|SubmissionApiTest|GradeApiTest|NotificationApiTest|ApiValidationFormatTest|Week6RegressionTest'],
    'all' => [],
];
if (! isset($stages[$stage]) || count($argv) > 2) {
    fwrite(STDERR, "Usage: php scripts/run-bulan-tests.php baseline|new|feature|regression|all\n");
    exit(2);
}

// PHPUnit env values normally do not override inherited production variables.
// Force the checked configuration into the parent and child process instead.
$xml = simplexml_load_file($root.'/phpunit.xml');
foreach ($xml->php->env as $entry) {
    $name = (string) $entry['name'];
    $value = (string) $entry['value'];
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
$app = require $root.'/bootstrap/app.php';
if ($app->configurationIsCached()) {
    fwrite(STDERR, "BLOCKED: cached config exists. Inspect it and clear config before testing.\n");
    exit(2);
}
$app->make(Kernel::class)->bootstrap();
$connection = $app['db']->connection();
if (! $app->environment('testing')
    || $connection->getDriverName() !== 'mysql'
    || $connection->getDatabaseName() !== 'kampuslms_testing'
    || $app['config']->get('database.connections.mysql.url')) {
    fwrite(STDERR, "BLOCKED: expected APP_ENV=testing and MySQL kampuslms_testing without DB_URL.\n");
    exit(2);
}
try {
    $actual = $connection->selectOne('SELECT DATABASE() AS name, VERSION() AS version');
    if ($actual->name !== 'kampuslms_testing') {
        throw new RuntimeException('Unexpected database');
    }
} catch (Throwable $exception) {
    // Avoid including connection credentials or exception SQL in evidence.
    fwrite(STDERR, "BLOCKED: dedicated test database cannot be verified; check MySQL and create kampuslms_testing if needed.\n");
    exit(2);
}

$startedAt = new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar'));
$runId = $startedAt->format('Ymd-Hisu').'-'.$stage.'-'.bin2hex(random_bytes(4));
$relativeDirectory = 'docs/testing/evidence/runs/'.$runId;
$directory = $root.'/'.$relativeDirectory;
if (! mkdir($directory, 0777, true)) {
    fwrite(STDERR, "BLOCKED: cannot create a new evidence directory.\n");
    exit(2);
}
$header = 'Run ID: '.$runId."\nStage: ".$stage."\n"
    .'Time (Asia/Makassar): '.$startedAt->format('Y-m-d H:i:s.u P')."\n"
    .'Evidence: '.$relativeDirectory."\n"
    .'PHP: '.PHP_VERSION.'; DB: '.$actual->version."; database: kampuslms_testing; APP_ENV: testing\n";
echo $header;
$command = array_merge([
    PHP_BINARY, $root.'/vendor/phpunit/phpunit/phpunit', '-c', $root.'/phpunit.xml',
    '--colors=never', '--do-not-cache-result', '--log-junit', $directory.'/'.$stage.'.xml',
], $stages[$stage]);
$sourceHashes = [];
foreach (['app', 'routes', 'tests', 'config', 'database'] as $sourceDirectory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$sourceDirectory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $sourceFile) {
        if ($sourceFile->isFile() && $sourceFile->getExtension() === 'php') {
            $relativePath = str_replace('\\', '/', substr($sourceFile->getPathname(), strlen($root) + 1));
            $sourceHashes[$relativePath] = hash_file('sha256', $sourceFile->getPathname());
        }
    }
}
foreach (['bootstrap/app.php', 'phpunit.xml', 'composer.lock', 'scripts/run-bulan-tests.php'] as $sourceFile) {
    $sourceHashes[$sourceFile] = hash_file('sha256', $root.'/'.$sourceFile);
}
ksort($sourceHashes);
$manifest = [
    'run_id' => $runId, 'stage' => $stage, 'started_at' => $startedAt->format(DateTimeInterface::ATOM),
    'timezone' => 'Asia/Makassar', 'php_version' => PHP_VERSION, 'mysql_version' => $actual->version,
    'app_env' => 'testing', 'database' => 'kampuslms_testing', 'command' => $command,
    'source_sha256' => $sourceHashes,
];
$manifestPath = $directory.'/manifest.json';
$manifestFile = fopen($manifestPath, 'x');
fwrite($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
fclose($manifestFile);
$log = fopen($directory.'/'.$stage.'.txt', 'x');
fwrite($log, $header);
$process = proc_open($command, [0 => STDIN, 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes);
if (! is_resource($process)) {
    fwrite($log, "BLOCKED: PHPUnit process could not start.\nExit code: 2\n");
    fclose($log);
    exit(2);
}
while (! feof($pipes[1])) {
    $line = fgets($pipes[1]);
    if ($line !== false) {
        echo $line;
        fwrite($log, $line);
    }
}
fclose($pipes[1]);
$code = proc_close($process);
fwrite($log, "\nExit code: $code\n");
fclose($log);
$manifest['exit_code'] = $code;
$manifest['finished_at'] = (new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')))->format(DateTimeInterface::ATOM);
foreach ([$stage.'.txt', $stage.'.xml'] as $artifact) {
    if (is_file($directory.'/'.$artifact)) {
        $manifest['artifact_sha256'][$artifact] = hash_file('sha256', $directory.'/'.$artifact);
    }
}
// Finalize only this run's manifest; all earlier run directories stay untouched.
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX);
echo 'Saved evidence: '.$relativeDirectory."\n";
exit($code);
