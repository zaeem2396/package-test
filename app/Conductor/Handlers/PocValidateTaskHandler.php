<?php

declare(strict_types=1);

namespace App\Conductor\Handlers;

use Conductor\Laravel\Workers\TaskHandler;

/**
 * PoC SIMPLE task: validates input (demonstrates conductor:work TaskHandler).
 */
final class PocValidateTaskHandler implements TaskHandler
{
    public function taskType(): string
    {
        return 'poc_validate';
    }

    /**
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    public function handle(array $task): array
    {
        $input = $task['inputData'] ?? [];
        $message = isset($input['message']) ? (string) $input['message'] : '';

        return [
            'step' => 'poc_validate',
            'valid' => $message !== '',
            'message_length' => strlen($message),
        ];
    }
}
