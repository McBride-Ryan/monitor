<?php

namespace App\Console\Commands;

use App\Services\Audits\PriceDiscrepancyAudit;
use Illuminate\Console\Command;

class RunAuditCommand extends Command
{
    protected $signature = 'audit:run {type : The audit type to run (price_discrepancy, asset_health, categorization, all)}';

    protected $description = 'Run data quality audits';

    public function handle(): int
    {
        $type = $this->argument('type');

        $this->info("Running audit: {$type}");

        $totalIssues = 0;

        match ($type) {
            'price_discrepancy' => $totalIssues = $this->runPriceDiscrepancyAudit(),
            'all' => $totalIssues = $this->runAllAudits(),
            default => $this->error("Unknown audit type: {$type}"),
        };

        if ($totalIssues > 0) {
            $this->warn("Found {$totalIssues} issue(s)");
        } else {
            $this->info('No issues found');
        }

        return self::SUCCESS;
    }

    private function runPriceDiscrepancyAudit(): int
    {
        $audit = new PriceDiscrepancyAudit();
        $count = $audit->run();
        $this->line("  - Price discrepancy: {$count} issues");

        return $count;
    }

    private function runAllAudits(): int
    {
        $total = 0;
        $total += $this->runPriceDiscrepancyAudit();

        return $total;
    }
}
