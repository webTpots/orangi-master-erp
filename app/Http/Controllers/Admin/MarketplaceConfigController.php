<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Services\MarketplaceConfigService;
use Illuminate\Http\Request;

class MarketplaceConfigController extends Controller
{
    public function __construct(
        private MarketplaceConfigService $configService
    ) {}

    public function index()
    {
        $companyId = auth()->user()->company_id;

        $marketplaces = Marketplace::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->map(function ($marketplace) use ($companyId) {
                $marketplace->configs = $this->configService->getAllConfigs($marketplace->id, $companyId);
                return $marketplace;
            });

        return view('admin.marketplace-config.index', compact('marketplaces'));
    }

    public function edit(Marketplace $marketplace)
    {
        $companyId = auth()->user()->company_id;

        if ($marketplace->company_id !== $companyId) {
            abort(403);
        }

        $configs = $this->configService->getAllConfigs($marketplace->id, $companyId);

        return view('admin.marketplace-config.edit', compact('marketplace', 'configs'));
    }

    public function update(Request $request, Marketplace $marketplace)
    {
        $companyId = auth()->user()->company_id;

        if ($marketplace->company_id !== $companyId) {
            abort(403);
        }

        $configKeys = [
            'cutoff_time',
            'commission_rate',
            'return_window_days',
            'sla_days',
            'avg_delivery_days',
            'payment_cycle_days',
        ];

        foreach ($configKeys as $key) {
            if ($request->has($key) && $request->$key !== null) {
                $this->configService->setConfig($marketplace->id, $companyId, $key, $request->$key);
            }
        }

        return redirect()->route('admin.marketplace-config.index')
            ->with('success', "Settings updated for {$marketplace->name}.");
    }

    public function comparison()
    {
        $companyId = auth()->user()->company_id;

        $comparison = $this->configService->getMarketplaceComparison($companyId);

        $marketplaces = Marketplace::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        return view('admin.marketplace-config.comparison', compact('comparison', 'marketplaces'));
    }
}
