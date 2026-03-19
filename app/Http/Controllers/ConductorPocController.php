<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ConductorPocService;
use Conductor\Exceptions\ConductorException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web + JSON PoC for conductor/orkes-laravel (real-time dashboard + API for polling).
 */
final class ConductorPocController extends Controller
{
    public function index(): View
    {
        return view('conductor-poc');
    }

    /**
     * DSL preview (no HTTP call to Conductor): toArray + toJson.
     */
    public function definitionPreview(ConductorPocService $poc): JsonResponse
    {
        $def = $poc->workflowDefinition((int) request()->query('version', 1));

        return response()->json([
            'name' => $def->getName(),
            'array' => $def->toArray(),
            'json' => json_decode($def->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), true),
        ]);
    }

    public function registerWorkflow(Request $request, ConductorPocService $poc): JsonResponse
    {
        $version = (int) $request->input('version', 1);

        return $this->wrap(fn () => [
            'ok' => true,
            'registered' => $poc->registerWorkflow($version),
        ]);
    }

    public function updateWorkflow(Request $request, ConductorPocService $poc): JsonResponse
    {
        $version = (int) $request->input('version', 2);

        return $this->wrap(fn () => [
            'ok' => true,
            'updated' => $poc->updateWorkflow($version),
        ]);
    }

    public function startWorkflow(Request $request, ConductorPocService $poc): JsonResponse
    {
        $input = $request->input('input', []);
        if (! is_array($input)) {
            $input = [];
        }
        $correlationId = $request->input('correlation_id');
        $correlationId = is_string($correlationId) && $correlationId !== '' ? $correlationId : null;
        $wfVersion = $request->input('wf_version');
        $wfVersion = $wfVersion !== null && $wfVersion !== '' ? (int) $wfVersion : null;

        return $this->wrap(function () use ($poc, $input, $correlationId, $wfVersion): array {
            $id = $poc->startWorkflow($input, $correlationId, $wfVersion);

            return ['ok' => true, 'workflow_id' => $id];
        });
    }

    public function showWorkflow(string $id, ConductorPocService $poc): JsonResponse
    {
        $includeTasks = filter_var(request()->query('include_tasks', '1'), FILTER_VALIDATE_BOOLEAN);

        return $this->wrap(fn () => [
            'ok' => true,
            'workflow' => $poc->getWorkflow($id, $includeTasks),
        ]);
    }

    public function workflowStatus(string $id, ConductorPocService $poc): JsonResponse
    {
        return $this->wrap(fn () => [
            'ok' => true,
            'status' => $poc->getWorkflowStatus($id),
        ]);
    }

    public function terminateWorkflow(string $id, ConductorPocService $poc): JsonResponse
    {
        return $this->wrap(function () use ($id, $poc): array {
            $poc->terminateWorkflow($id);

            return ['ok' => true];
        });
    }

    public function pauseWorkflow(string $id, ConductorPocService $poc): JsonResponse
    {
        return $this->wrap(function () use ($id, $poc): array {
            $poc->pauseWorkflow($id);

            return ['ok' => true];
        });
    }

    public function resumeWorkflow(string $id, ConductorPocService $poc): JsonResponse
    {
        return $this->wrap(function () use ($id, $poc): array {
            $poc->resumeWorkflow($id);

            return ['ok' => true];
        });
    }

    public function retryWorkflow(string $id, ConductorPocService $poc): JsonResponse
    {
        return $this->wrap(function () use ($id, $poc): array {
            $poc->retryWorkflow($id);

            return ['ok' => true];
        });
    }

    public function searchRunning(ConductorPocService $poc): JsonResponse
    {
        $size = (int) request()->query('size', 20);

        return $this->wrap(fn () => [
            'ok' => true,
            'search' => $poc->searchRunning(max(1, min(100, $size))),
        ]);
    }

    public function searchFailed(ConductorPocService $poc): JsonResponse
    {
        $size = (int) request()->query('size', 20);

        return $this->wrap(fn () => [
            'ok' => true,
            'search' => $poc->searchFailed(max(1, min(100, $size))),
        ]);
    }

    public function pollTask(Request $request, ConductorPocService $poc): JsonResponse
    {
        $taskType = (string) $request->input('task_type', 'poc_validate');

        return $this->wrap(fn () => [
            'ok' => true,
            'task' => $poc->pollTask($taskType),
        ]);
    }

    /**
     * @param  callable(): array<string, mixed>  $fn
     */
    private function wrap(callable $fn): JsonResponse
    {
        try {
            return response()->json($fn());
        } catch (ConductorException $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
