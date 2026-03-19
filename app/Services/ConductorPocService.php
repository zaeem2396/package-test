<?php

declare(strict_types=1);

namespace App\Services;

use Conductor\Client\ConductorClient;
use Conductor\Laravel\DSL\Workflow;
use Conductor\Laravel\DSL\WorkflowDefinition;

/**
 * PoC helpers: Workflow DSL + workflow client operations used by the dashboard and tests.
 */
final class ConductorPocService
{
    public const WORKFLOW_NAME = 'poc_realtime_demo';

    public function __construct(
        private readonly ConductorClient $client,
    ) {
    }

    public function workflowDefinition(int $version = 1): WorkflowDefinition
    {
        return Workflow::define(self::WORKFLOW_NAME)
            ->description('package-test PoC: poc_validate → poc_process → poc_notify (SIMPLE tasks)')
            ->version($version)
            ->ownerEmail('poc@package-test.local')
            ->inputParameters(['message', 'request_id'])
            ->outputParameters(['summary' => '${poc_notify_ref.output}'])
            ->task('poc_validate')
            ->task('poc_process')
            ->task('poc_notify');
    }

    /**
     * @return array<string, mixed>
     */
    public function registerWorkflow(int $version = 1): array
    {
        $def = $this->workflowDefinition($version);
        $def->register($this->client->workflow());

        return $def->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function updateWorkflow(int $version): array
    {
        $def = $this->workflowDefinition($version);
        $this->client->workflow()->updateWorkflowDefinition([$def->toArray()]);

        return $def->toArray();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function startWorkflow(array $input, ?string $correlationId = null, ?int $wfVersion = null): string
    {
        return $this->client->workflow()->start(
            self::WORKFLOW_NAME,
            $input,
            $correlationId,
            $wfVersion,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getWorkflow(string $workflowId, bool $includeTasks = true): array
    {
        return $this->client->workflow()->getWorkflow($workflowId, $includeTasks);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWorkflowStatus(string $workflowId): array
    {
        return $this->client->workflow()->getWorkflowStatus($workflowId);
    }

    public function terminateWorkflow(string $workflowId): void
    {
        $this->client->workflow()->terminateWorkflow($workflowId);
    }

    public function pauseWorkflow(string $workflowId): void
    {
        $this->client->workflow()->pauseWorkflow($workflowId);
    }

    public function resumeWorkflow(string $workflowId): void
    {
        $this->client->workflow()->resumeWorkflow($workflowId);
    }

    public function retryWorkflow(string $workflowId): void
    {
        $this->client->workflow()->retryWorkflow($workflowId);
    }

    /**
     * @return array{totalHits: int, results: array<int, array<string, mixed>>}
     */
    public function searchRunning(int $size = 20): array
    {
        return $this->client->workflow()->search('status = RUNNING', 0, $size);
    }

    /**
     * @return array{totalHits: int, results: array<int, array<string, mixed>>}
     */
    public function searchFailed(int $size = 20): array
    {
        return $this->client->workflow()->search('status IN (FAILED, TIMED_OUT, TERMINATED)', 0, $size);
    }

    /**
     * Demonstrates TaskClient::poll (returns null when queue is empty).
     *
     * @return array<string, mixed>|null
     */
    public function pollTask(string $taskType): ?array
    {
        return $this->client->tasks()->poll($taskType);
    }
}
