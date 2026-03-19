<?php

declare(strict_types=1);

namespace App\Conductor\Handlers;

use Conductor\Laravel\Workers\TaskHandler;

/**
 * PoC SIMPLE task: final notification step (worker pipeline step 3).
 */
final class PocNotifyTaskHandler implements TaskHandler
{
    public function taskType(): string
    {
        return 'poc_notify';
    }

    /**
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    public function handle(array $task): array
    {
        return [
            'step' => 'poc_notify',
            'notified' => true,
            'workflow_instance_id' => $task['workflowInstanceId'] ?? null,
        ];
    }
}
