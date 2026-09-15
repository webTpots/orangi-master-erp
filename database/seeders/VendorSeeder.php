<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // Find the company
        $companyId = DB::table('companies')->where('name', 'ORANGI')->value('id');

        if (! $companyId) {
            $this->command->warn('Company ORANGI not found. Run FoundationSeeder first.');
            return;
        }

        // ── SP Fashion Vendor ──
        $vendorId = DB::table('vendors')->insertGetId([
            'company_id'     => $companyId,
            'name'           => 'SP Fashion',
            'contact_person' => 'Suresh Patel',
            'phone'          => '9876543210',
            'email'          => 'spfashion@example.com',
            'gstin'          => '24AABCS1429B1ZS',
            'pan'            => 'AABCS1429B',
            'address'        => 'Shop 45, Textile Market, Ring Road',
            'city'           => 'Surat',
            'state'          => 'Gujarat',
            'pincode'        => '395002',
            'payment_terms'  => 'Net 15',
            'lead_time_days' => 5,
            'rating'         => 4.50,
            'status'         => 'active',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // ── Link vendor_products for all active SKUs ──
        $skus = DB::table('skus')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->get(['id', 'cost_price']);

        $vendorProducts = [];
        foreach ($skus as $sku) {
            $vendorProducts[] = [
                'vendor_id'     => $vendorId,
                'sku_id'        => $sku->id,
                'vendor_sku'    => null,
                'vendor_price'  => $sku->cost_price ?? 100.00,
                'moq'           => 10,
                'lead_time_days' => 5,
                'is_preferred'  => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        if (! empty($vendorProducts)) {
            DB::table('vendor_products')->insert($vendorProducts);
        }

        $this->command->info("SP Fashion vendor created with {$skus->count()} product links.");
    }
}
