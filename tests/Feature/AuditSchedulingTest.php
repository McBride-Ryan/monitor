<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_command_is_scheduled(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $auditEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'audit:run all');
        });

        $this->assertNotNull($auditEvent, 'Audit command should be scheduled');
    }

    public function test_audit_runs_daily(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $auditEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'audit:run all');
        });

        $this->assertNotNull($auditEvent);
        $this->assertEquals('0 2 * * *', $auditEvent->expression);
    }

    public function test_audit_command_can_run_manually(): void
    {
        $this->artisan('audit:run all')
            ->assertSuccessful();
    }

    public function test_individual_audits_can_run(): void
    {
        $this->artisan('audit:run price_discrepancy')
            ->assertSuccessful();

        $this->artisan('audit:run asset_health')
            ->assertSuccessful();

        $this->artisan('audit:run categorization')
            ->assertSuccessful();
    }
}
