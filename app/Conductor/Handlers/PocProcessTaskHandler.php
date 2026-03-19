<?php

declare(strict_types=1);

namespace App\Conductor\Handlers;

use Conductor\Laravel\Workers\TaskHandler;

/**
 * PoC SIMPLE task: pretend processing (worker pipeline step 2).
 */
final class PocProcessTaskHandler implements TaskHandler
{
    public function taskType(): string
    {
        return 'poc_process';
    }

    /**
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    public function handle(array $task): array
    {
        $input = $task['inputData'] ?? [];

        return [
            'step' => 'poc_process',
            'processed_at' => date(\DateTimeInterface::ATOM),
            'request_id' => $input['request_id'] ?? null,
        ];
    }
}
