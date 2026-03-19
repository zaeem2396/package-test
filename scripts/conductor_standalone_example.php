<?php

declare(strict_types=1);

/**
 * Standalone SDK demo (no Laravel): ConductorClient::fromArray + Workflow DSL export.
 *
 * Run from package-test root:
 *   php scripts/conductor_standalone_example.php
 *
 * Requires CONDUCTOR_SERVER in env or edit $baseUrl below. Does not start workflows unless you uncomment.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Conductor\Client\ConductorClient;
use Conductor\Laravel\DSL\Workflow;

$baseUrl = rtrim((string) (getenv('CONDUCTOR_SERVER') ?: 'http://127.0.0.1:8080/api'), '/');
$token = getenv('CONDUCTOR_TOKEN') ?: null;
$token = is_string($token) && $token !== '' ? $token : null;

$client = ConductorClient::fromArray([
    'base_url' => $baseUrl,
    'token' => $token,
    'timeout' => 10,
]);

$def = Workflow::define('poc_realtime_demo')
    ->description('Standalone script: DSL only (register commented out)')
    ->task('poc_validate')
    ->task('poc_process')
    ->task('poc_notify');

echo "=== Workflow DSL (toJson) ===\n";
echo $def->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// Uncomment to register against a live Conductor:
// $def->register($client->workflow());
// echo "Registered metadata/workflow.\n";

// Uncomment to start a run:
// $id = $client->workflow()->start('poc_realtime_demo', ['message' => 'standalone', 'request_id' => 's1']);
// echo "Started workflow: {$id}\n";

echo "=== SDK entrypoints used: ConductorClient::fromArray, workflow(), (optional) register/start ===\n";
