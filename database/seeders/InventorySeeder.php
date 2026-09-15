<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Sku;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'ORANGI')->first();
        if (!$company) {
            $this->command->error('Company ORANGI not found. Run FoundationSeeder first.');
            return;
        }

        $warehouse = Warehouse::where('company_id', $company->id)->where('is_default', true)->first();
        if (!$warehouse) {
            $warehouse = Warehouse::where('company_id', $company->id)->first();
        }
        if (!$warehouse) {
            $this->command->error('No warehouse found. Run FoundationSeeder first.');
            return;
        }

        $inventoryService = app(InventoryService::class);
        $userId = 1; // Admin user

        // ── Opening Stock Data (from Google Sheet, Total: 209 pieces) ──
        // Format: 'DESIGN_CODE' => ['Color' => ['Size' => qty]]
        $stockData = [
            'SKD-MOON' => [
                'Black' => ['M' => 5, 'L' => 7, 'XL' => 7, '2XL' => 10, '3XL' => 7],
                'Blue'  => ['M' => 5, 'L' => 6, 'XL' => 6, '2XL' => 6, '3XL' => 4],
                'Green' => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Red'   => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Pink'  => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Beige' => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
            ],
            'SKD-DHOLKI' => [
                'Black' => ['M' => 3, 'L' => 5, 'XL' => 5, '2XL' => 7, '3XL' => 5],
                'Blue'  => ['M' => 3, 'L' => 4, 'XL' => 4, '2XL' => 5, '3XL' => 3],
                'Green' => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Red'   => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Pink'  => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Beige' => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
            ],
            'SKD-BANSARI' => [
                'Black' => ['M' => 3, 'L' => 4, 'XL' => 4, '2XL' => 5, '3XL' => 3],
                'Blue'  => ['M' => 2, 'L' => 3, 'XL' => 3, '2XL' => 4, '3XL' => 2],
                'Green' => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Red'   => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Pink'  => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Beige' => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
            ],
            'CORD-SUNFLOWER' => [
                'Black' => ['M' => 3, 'L' => 5, 'XL' => 5, '2XL' => 7, '3XL' => 4],
                'Blue'  => ['M' => 3, 'L' => 4, 'XL' => 4, '2XL' => 5, '3XL' => 3],
                'Green' => ['M' => 2, 'L' => 3, 'XL' => 3, '2XL' => 4, '3XL' => 2],
                'Red'   => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Pink'  => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
                'Beige' => ['M' => 0, 'L' => 0, 'XL' => 0, '2XL' => 0, '3XL' => 0],
            ],
        ];

        $totalImported = 0;
        $totalSkipped = 0;

        foreach ($stockData as $designCode => $colors) {
            foreach ($colors as $color => $sizes) {
                foreach ($sizes as $size => $qty) {
                    if ($qty <= 0) {
                        $totalSkipped++;
                        continue;
                    }

                    $skuCode = strtoupper("{$designCode}-{$color}-{$size}");
                    $skuCode = str_replace(' ', '-', $skuCode);

                    $sku = Sku::where('sku_code', $skuCode)
                        ->where('company_id', $company->id)
                        ->first();

                    if (!$sku) {
                        $this->command->warn("SKU not found: {$skuCode}, skipping.");
                        $totalSkipped++;
                        continue;
                    }

                    try {
                        $inventoryService->addStock(
                            $sku,
                            $warehouse,
                            $qty,
                            $sku->cost_price ?? 0,
                            InventoryService::TYPE_OPENING_BALANCE,
                            'OPENING-STOCK-SEED',
                            $userId
                        );

                        $totalImported++;
                        $this->command->info("  {$skuCode}: {$qty} units @ {$sku->cost_price}");
                    } catch (\Throwable $e) {
                        $this->command->error("  Failed: {$skuCode} - {$e->getMessage()}");
                        $totalSkipped++;
                    }
                }
            }
        }

        $this->command->info('');
        $this->command->info('Inventory seeding complete:');
        $this->command->info("  SKUs stocked: {$totalImported}");
        $this->command->info("  SKUs skipped (zero qty or not found): {$totalSkipped}");
        $this->command->info("  Total pieces: " . \App\Models\InventoryItem::where('company_id', $company->id)->sum('physical_stock'));
    }
}
