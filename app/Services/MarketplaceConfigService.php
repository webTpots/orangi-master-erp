<?php

namespace App\Services;

use App\Models\Marketplace;
use App\Models\MarketplaceConfig;
use Illuminate\Support\Collection;

class MarketplaceConfigService
{
    /**
     * Get a single config value.
     */
    public function getConfig(int $marketplaceId, int $companyId, string $key, $default = null): mixed
    {
        $config = MarketplaceConfig::where('marketplace_id', $marketplaceId)
            ->where('company_id', $companyId)
            ->where('config_key', $key)
            ->first();

        return $config ? $config->config_value : $default;
    }

    /**
     * Set a config value.
     */
    public function setConfig(int $marketplaceId, int $companyId, string $key, string $value): MarketplaceConfig
    {
        return MarketplaceConfig::updateOrCreate(
            [
                'marketplace_id' => $marketplaceId,
                'company_id'     => $companyId,
                'config_key'     => $key,
            ],
            [
                'config_value' => $value,
            ]
        );
    }

    /**
     * Get all configs for a marketplace.
     */
    public function getAllConfigs(int $marketplaceId, int $companyId): Collection
    {
        return MarketplaceConfig::where('marketplace_id', $marketplaceId)
            ->where('company_id', $companyId)
            ->get()
            ->pluck('config_value', 'config_key');
    }

    /**
     * Compare all marketplace metrics side by side.
     */
    public function getMarketplaceComparison(int $companyId): array
    {
        $marketplaces = Marketplace::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $comparison = [];

        $configKeys = [
            'commission_rate',
            'return_window_days',
            'cutoff_time',
            'avg_delivery_days',
            'sla_days',
            'payment_cycle_days',
        ];

        foreach ($marketplaces as $marketplace) {
            $configs = $this->getAllConfigs($marketplace->id, $companyId);

            $row = [
                'marketplace_id'   => $marketplace->id,
                'marketplace_name' => $marketplace->name,
                'marketplace_code' => $marketplace->code,
            ];

            foreach ($configKeys as $key) {
                $row[$key] = $configs->get($key, '-');
            }

            $comparison[] = $row;
        }

        return $comparison;
    }
}
