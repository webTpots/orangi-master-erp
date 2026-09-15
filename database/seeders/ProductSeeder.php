<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Design;
use App\Models\MarketplaceAccount;
use App\Models\Product;
use App\Models\Sku;
use App\Models\SkuMapping;
use App\Models\Variant;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'ORANGI')->first();
        if (!$company) {
            $this->command->error('Company ORANGI not found. Run FoundationSeeder first.');
            return;
        }

        $companyId = $company->id;

        // ── Vendor: SP Fashion ──
        $vendor = Vendor::firstOrCreate(
            ['name' => 'SP Fashion', 'company_id' => $companyId],
            [
                'contact_person' => 'SP Fashion',
                'city'           => 'Surat',
                'state'          => 'Gujarat',
                'status'         => 'active',
            ]
        );

        // ── Meesho marketplace account ──
        $meeshoAccount = MarketplaceAccount::whereHas('marketplace', fn ($q) => $q->where('code', 'meesho'))
            ->where('company_id', $companyId)
            ->first();

        if (!$meeshoAccount) {
            $marketplace = \App\Models\Marketplace::where('code', 'meesho')->first();
            if ($marketplace) {
                $meeshoAccount = MarketplaceAccount::firstOrCreate(
                    ['marketplace_id' => $marketplace->id, 'company_id' => $companyId],
                    [
                        'account_name' => 'Orangi Meesho',
                        'account_id'   => 'orangi-meesho',
                        'is_connected' => true,
                        'status'       => 'active',
                    ]
                );
            }
        }

        // ── Master Designs ──
        $designSpecs = [
            [
                'code'     => 'SKD-MOON',
                'name'     => 'SKD Moon',
                'category' => 'Kurti',
                'hsn_code' => '6104',
                'gst_rate' => 5.00,
                'colors'   => ['Black', 'Blue', 'Green', 'Red', 'Pink', 'Beige'],
                'sizes'    => ['M', 'L', 'XL', '2XL', '3XL'],
                'selling_price' => 399,
                'cost_price'    => 220,
                'mrp'           => 999,
                'meesho_mappings' => [
                    // External Meesho SKU => color (maps to all sizes of that color)
                    'SKD-2pocket-Black' => 'Black',
                    'SKD-2pocket-Blue'  => 'Blue',
                    'SKD-2pocket-Green' => 'Green',
                    'SKD-2pocket-Red'   => 'Red',
                    'SKD-2pocket-Pink'  => 'Pink',
                    'SKD-2pocket-Beige' => 'Beige',
                ],
            ],
            [
                'code'     => 'SKD-DHOLKI',
                'name'     => 'SKD Dholki',
                'category' => 'Kurti',
                'hsn_code' => '6104',
                'gst_rate' => 5.00,
                'colors'   => ['Black', 'Blue', 'Green', 'Red', 'Pink', 'Beige'],
                'sizes'    => ['M', 'L', 'XL', '2XL', '3XL'],
                'selling_price' => 429,
                'cost_price'    => 240,
                'mrp'           => 1099,
                'meesho_mappings' => [
                    'SKD-Dholki-Black' => 'Black',
                    'SKD-Dholki-Blue'  => 'Blue',
                    'SKD-Dholki-Green' => 'Green',
                    'SKD-Dholki-Red'   => 'Red',
                    'SKD-Dholki-Pink'  => 'Pink',
                    'SKD-Dholki-Beige' => 'Beige',
                ],
            ],
            [
                'code'     => 'SKD-BANSARI',
                'name'     => 'SKD Bansari',
                'category' => 'Kurti',
                'hsn_code' => '6104',
                'gst_rate' => 5.00,
                'colors'   => ['Black', 'Blue', 'Green', 'Red', 'Pink', 'Beige'],
                'sizes'    => ['M', 'L', 'XL', '2XL', '3XL'],
                'selling_price' => 449,
                'cost_price'    => 250,
                'mrp'           => 1199,
                'meesho_mappings' => [
                    'SKD-Bansari-Black' => 'Black',
                    'SKD-Bansari-Blue'  => 'Blue',
                    'SKD-Bansari-Green' => 'Green',
                    'SKD-Bansari-Red'   => 'Red',
                    'SKD-Bansari-Pink'  => 'Pink',
                    'SKD-Bansari-Beige' => 'Beige',
                ],
            ],
            [
                'code'     => 'CORD-SUNFLOWER',
                'name'     => 'CORD Sunflower',
                'category' => 'Cord Set',
                'hsn_code' => '620412',
                'gst_rate' => 5.00,
                'colors'   => ['Black', 'Blue', 'Green', 'Red', 'Pink', 'Beige'],
                'sizes'    => ['M', 'L', 'XL', '2XL', '3XL'],
                'selling_price' => 549,
                'cost_price'    => 320,
                'mrp'           => 1499,
                'meesho_mappings' => [
                    'CORD-Sunflower-Black' => 'Black',
                    'CORD-Sunflower-Blue'  => 'Blue',
                    'CORD-Sunflower-Green' => 'Green',
                    'CORD-Sunflower-Red'   => 'Red',
                    'CORD-Sunflower-Pink'  => 'Pink',
                    'CORD-Sunflower-Beige' => 'Beige',
                ],
            ],
        ];

        foreach ($designSpecs as $spec) {
            $this->command->info("Creating design: {$spec['name']}");

            // Create Design
            $design = Design::firstOrCreate(
                ['code' => $spec['code'], 'company_id' => $companyId],
                [
                    'name'        => $spec['name'],
                    'category'    => $spec['category'],
                    'hsn_code'    => $spec['hsn_code'],
                    'gst_rate'    => $spec['gst_rate'],
                    'status'      => 'active',
                ]
            );

            // Create Product
            $product = Product::firstOrCreate(
                ['design_id' => $design->id, 'company_id' => $companyId],
                [
                    'name'        => $spec['name'],
                    'description' => "{$spec['name']} - {$spec['category']}",
                    'status'      => 'active',
                ]
            );

            // Build a lookup for Meesho mappings: color => [external_skus]
            $colorMappings = [];
            foreach ($spec['meesho_mappings'] as $externalSku => $color) {
                $colorMappings[$color][] = $externalSku;
            }

            // Create Variants & SKUs
            foreach ($spec['colors'] as $color) {
                foreach ($spec['sizes'] as $size) {
                    $variant = Variant::firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'company_id' => $companyId,
                            'color'      => $color,
                            'size'       => $size,
                        ],
                        [
                            'status' => 'active',
                        ]
                    );

                    $skuCode = strtoupper("{$spec['code']}-{$color}-{$size}");
                    $skuCode = str_replace(' ', '-', $skuCode);

                    $sku = Sku::firstOrCreate(
                        ['sku_code' => $skuCode, 'company_id' => $companyId],
                        [
                            'variant_id'          => $variant->id,
                            'master_sku_code'     => $skuCode,
                            'selling_price'       => $spec['selling_price'],
                            'cost_price'          => $spec['cost_price'],
                            'mrp'                 => $spec['mrp'],
                            'minimum_stock_level' => 5,
                            'status'              => 'active',
                        ]
                    );

                    // Create Meesho SKU mappings
                    // Map each external color SKU + size combination
                    if ($meeshoAccount && isset($colorMappings[$color])) {
                        foreach ($colorMappings[$color] as $externalSku) {
                            // Create child SKU mapping with size suffix
                            $externalSkuWithSize = "{$externalSku}_{$size}";

                            SkuMapping::firstOrCreate(
                                [
                                    'external_identifier'        => $externalSkuWithSize,
                                    'source_type' => 'marketplace', 'source_id' => $meeshoAccount->id, 'company_id' => $companyId,
                                ],
                                [
                                    'sku_id'           => $sku->id,
                                    'confidence_score' => 1.00,
                                    'mapped_by'        => null,
                                    'mapping_status'           => 'active',
                                ]
                            );
                        }
                    }

                    // Create Vendor Product link
                    VendorProduct::firstOrCreate(
                        ['vendor_id' => $vendor->id, 'sku_id' => $sku->id],
                        [
                            'vendor_sku'    => $skuCode,
                            // vendor_product_name => "{$spec['name']} {$color} {$size}",
                            'vendor_price'       => $spec['cost_price'],
                            'lead_time_days'     => 3,
                            'moq'  => 6,
                            'is_preferred'       => true,
                        ]
                    );
                }
            }
        }

        // Summary
        $this->command->info('Product seeding complete:');
        $this->command->info('  Designs: ' . Design::where('company_id', $companyId)->count());
        $this->command->info('  Products: ' . Product::where('company_id', $companyId)->count());
        $this->command->info('  Variants: ' . Variant::where('company_id', $companyId)->count());
        $this->command->info('  SKUs: ' . Sku::where('company_id', $companyId)->count());
        $this->command->info('  SKU Mappings: ' . SkuMapping::count());
        $this->command->info('  Vendor Products: ' . VendorProduct::where('vendor_id', $vendor->id)->count());
    }
}
