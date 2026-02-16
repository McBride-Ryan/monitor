<?php

namespace App\Services\Audits;

use App\Models\DataAuditLog;
use App\Models\ProductAsset;

class AssetHealthAudit
{
    private int $staleDays;

    public function __construct(int $staleDays = 30)
    {
        $this->staleDays = $staleDays;
    }

    public function run(): int
    {
        $count = 0;

        // Check for invalid URL format
        $invalidUrls = ProductAsset::where('is_active', true)
            ->get()
            ->filter(function ($asset) {
                return ! $this->isValidUrl($asset->url);
            });

        foreach ($invalidUrls as $asset) {
            DataAuditLog::create([
                'audit_type' => 'broken_asset',
                'severity' => 'critical',
                'entity_type' => 'ProductAsset',
                'entity_id' => $asset->id,
                'details' => [
                    'message' => 'Invalid URL format',
                    'url' => $asset->url,
                    'asset_type' => $asset->asset_type,
                    'product_id' => $asset->product_id,
                ],
            ]);
            $count++;
        }

        // Check for missing alt text
        $missingAlt = ProductAsset::where('is_active', true)
            ->where('asset_type', 'image')
            ->where(function ($query) {
                $query->whereNull('alt_text')
                    ->orWhere('alt_text', '');
            })
            ->get();

        foreach ($missingAlt as $asset) {
            DataAuditLog::create([
                'audit_type' => 'broken_asset',
                'severity' => 'warning',
                'entity_type' => 'ProductAsset',
                'entity_id' => $asset->id,
                'details' => [
                    'message' => 'Missing alt text for image',
                    'url' => $asset->url,
                    'product_id' => $asset->product_id,
                ],
            ]);
            $count++;
        }

        // Check for stale last_checked_at
        $staleThreshold = now()->subDays($this->staleDays);
        $staleAssets = ProductAsset::where('is_active', true)
            ->where(function ($query) use ($staleThreshold) {
                $query->whereNull('last_checked_at')
                    ->orWhere('last_checked_at', '<', $staleThreshold);
            })
            ->get();

        foreach ($staleAssets as $asset) {
            $daysSinceCheck = $asset->last_checked_at
                ? now()->diffInDays($asset->last_checked_at)
                : null;

            DataAuditLog::create([
                'audit_type' => 'broken_asset',
                'severity' => 'info',
                'entity_type' => 'ProductAsset',
                'entity_id' => $asset->id,
                'details' => [
                    'message' => 'Asset not checked recently',
                    'url' => $asset->url,
                    'last_checked_at' => $asset->last_checked_at?->toDateTimeString(),
                    'days_since_check' => $daysSinceCheck,
                    'threshold_days' => $this->staleDays,
                    'product_id' => $asset->product_id,
                ],
            ]);
            $count++;
        }

        return $count;
    }

    private function isValidUrl(string $url): bool
    {
        // Check if URL starts with http:// or https://
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return false;
        }

        // Basic URL validation
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
