<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ConductorPocService;
use Conductor\Laravel\Facades\Conductor;
use Tests\TestCase;

/**
 * Demonstrates package testing utilities: Conductor::fake() and workflow assertions.
 */
final class ConductorPocFakeTest extends TestCase
{
    public function test_conductor_poc_dashboard_renders(): void
    {
        $this->get('/conductor-poc')->assertOk();
    }

    public function test_definition_preview_does_not_require_live_conductor(): void
    {
        $this->get('/conductor-poc/definition/preview')
            ->assertOk()
            ->assertJsonPath('name', ConductorPocService::WORKFLOW_NAME);
    }

    public function test_fake_records_workflow_start_from_poc_service(): void
    {
        Conductor::fake();

        $svc = app(ConductorPocService::class);
        $id = $svc->startWorkflow([
            'message' => 'test',
            'request_id' => 'r1',
        ]);

        $this->assertSame('fake-workflow-id', $id);
        Conductor::assertWorkflowStarted(ConductorPocService::WORKFLOW_NAME);
        Conductor::assertWorkflowStartedWithInput(ConductorPocService::WORKFLOW_NAME, [
            'message' => 'test',
        ]);
    }
}
