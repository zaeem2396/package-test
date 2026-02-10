<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Wrapper around runtime:explain that uses the default Laravel log path.
 * The package's runtime:explain only reads from a log file when --log is passed.
 */
class InsightExplainCommand extends Command
{
    protected $signature = 'insight:explain
                            {--log= : Path to log file (default: storage/logs/laravel.log)}
                            {--line= : Line number in log file}
                            {--format=text : Output format (text, json, markdown, html, ide)}';

    protected $description = 'Explain the last runtime error (uses laravel.log by default)';

    public function handle(): int
    {
        $logPath = $this->option('log') ?? storage_path('logs/laravel.log');

        $this->info('Using log: ' . $logPath);

        return $this->call('runtime:explain', [
            '--log' => $logPath,
            '--line' => $this->option('line'),
            '--format' => $this->option('format'),
        ]);
    }
}
