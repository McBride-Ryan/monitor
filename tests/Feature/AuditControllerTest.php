<?php

namespace Tests\Feature;

use App\Models\DataAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_inertia_page(): void
    {
        $response = $this->get('/audits');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('AuditDashboard'));
    }

    public function test_index_shows_unresolved_by_default(): void
    {
        DataAuditLog::factory()->create(['resolved_at' => null]);
        DataAuditLog::factory()->create(['resolved_at' => now()]);

        $response = $this->get('/audits');

        $response->assertInertia(fn ($page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.resolved_at', null)
        );
    }

    public function test_index_filters_by_severity(): void
    {
        DataAuditLog::factory()->create(['severity' => 'critical']);
        DataAuditLog::factory()->create(['severity' => 'warning']);

        $response = $this->get('/audits?severity=critical');

        $response->assertInertia(fn ($page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.severity', 'critical')
        );
    }

    public function test_index_filters_by_type(): void
    {
        DataAuditLog::factory()->create(['audit_type' => 'price_discrepancy']);
        DataAuditLog::factory()->create(['audit_type' => 'broken_asset']);

        $response = $this->get('/audits?type=price_discrepancy');

        $response->assertInertia(fn ($page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.audit_type', 'price_discrepancy')
        );
    }

    public function test_index_shows_resolved_when_requested(): void
    {
        DataAuditLog::factory()->create(['resolved_at' => null]);
        DataAuditLog::factory()->create(['resolved_at' => now()]);

        $response = $this->get('/audits?resolved=resolved');

        $response->assertInertia(fn ($page) => $page
            ->has('logs.data', 1)
            ->whereNot('logs.data.0.resolved_at', null)
        );
    }

    public function test_index_shows_all_when_requested(): void
    {
        DataAuditLog::factory()->create(['resolved_at' => null]);
        DataAuditLog::factory()->create(['resolved_at' => now()]);

        $response = $this->get('/audits?resolved=all');

        $response->assertInertia(fn ($page) => $page->has('logs.data', 2));
    }

    public function test_index_provides_summary_stats(): void
    {
        DataAuditLog::factory()->create([
            'severity' => 'critical',
            'audit_type' => 'price_discrepancy',
            'resolved_at' => null,
        ]);
        DataAuditLog::factory()->create([
            'severity' => 'warning',
            'audit_type' => 'broken_asset',
            'resolved_at' => null,
        ]);

        $response = $this->get('/audits');

        $response->assertInertia(fn ($page) => $page
            ->has('summary')
            ->where('summary.total_unresolved', 2)
            ->where('summary.by_severity.critical', 1)
            ->where('summary.by_severity.warning', 1)
            ->where('summary.by_type.price_discrepancy', 1)
            ->where('summary.by_type.broken_asset', 1)
        );
    }

    public function test_index_paginates_results(): void
    {
        DataAuditLog::factory()->count(30)->create(['resolved_at' => null]);

        $response = $this->get('/audits');

        $response->assertInertia(fn ($page) => $page
            ->has('logs.data', 25)
            ->where('logs.total', 30)
            ->where('logs.per_page', 25)
        );
    }

    public function test_resolve_marks_log_as_resolved(): void
    {
        $log = DataAuditLog::factory()->create(['resolved_at' => null]);

        $response = $this->post("/audits/{$log->id}/resolve");

        $response->assertRedirect();
        $this->assertNotNull($log->fresh()->resolved_at);
    }

    public function test_resolve_redirects_back(): void
    {
        $log = DataAuditLog::factory()->create();

        $response = $this->post("/audits/{$log->id}/resolve");

        $response->assertSessionHas('success');
        $response->assertRedirect();
    }

    public function test_combined_filters_work_together(): void
    {
        DataAuditLog::factory()->create([
            'severity' => 'critical',
            'audit_type' => 'price_discrepancy',
            'resolved_at' => null,
        ]);
        DataAuditLog::factory()->create([
            'severity' => 'warning',
            'audit_type' => 'price_discrepancy',
            'resolved_at' => null,
        ]);
        DataAuditLog::factory()->create([
            'severity' => 'critical',
            'audit_type' => 'broken_asset',
            'resolved_at' => null,
        ]);

        $response = $this->get('/audits?severity=critical&type=price_discrepancy');

        $response->assertInertia(fn ($page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.severity', 'critical')
            ->where('logs.data.0.audit_type', 'price_discrepancy')
        );
    }
}
