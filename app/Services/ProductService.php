<?php

namespace App\Services;

use App\Models\Design;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Sku;
use App\Models\SkuMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Create a new design with optional products/variants/SKUs.
     */
    public function createDesign(array $data): Design
    {
        return DB::transaction(function () use ($data) {
            $design = Design::create([
                'company_id'         => $data['company_id'],
                'code'               => $data['code'],
                'name'               => $data['name'],
                'description'        => $data['description'] ?? null,
                'category'           => $data['category'] ?? null,
                'hsn_code'           => $data['hsn_code'] ?? null,
                'gst_rate'           => $data['gst_rate'] ?? 5.00,
                'primary_image_path' => $data['primary_image_path'] ?? null,
                'status'             => $data['status'] ?? 'active',
            ]);

            // If variants data provided, create full hierarchy
            if (!empty($data['variants'])) {
                $this->createVariantsFromMatrix($design, $data);
            }

            return $design;
        });
    }

    /**
     * Create a product under a design.
     */
    public function createProduct(Design $design, array $data): Product
    {
        return Product::create([
            'design_id'   => $design->id,
            'company_id'  => $data['company_id'] ?? $design->company_id,
            'name'        => $data['name'] ?? $design->name,
            'slug'        => $data['slug'] ?? Str::slug($data['name'] ?? $design->name),
            'description' => $data['description'] ?? $design->description,
            'status'      => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Create a variant under a product.
     */
    public function createVariant(Product $product, array $data): Variant
    {
        return Variant::create([
            'product_id' => $product->id,
            'company_id' => $data['company_id'] ?? $product->company_id,
            'color'      => $data['color'] ?? null,
            'size'       => $data['size'] ?? null,
            'weight'     => $data['weight'] ?? null,
            'material'   => $data['material'] ?? null,
            'status'     => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Create a SKU under a variant.
     * Auto-generates sku_code if not provided: DESIGN-COLOR-SIZE
     */
    public function createSku(Variant $variant, array $data): Sku
    {
        $skuCode = $data['sku_code'] ?? $this->generateSkuCode($variant);

        return Sku::create([
            'variant_id'          => $variant->id,
            'company_id'          => $data['company_id'] ?? $variant->company_id,
            'sku_code'            => $skuCode,
            'barcode'             => $data['barcode'] ?? null,
            'master_sku_code'     => $data['master_sku_code'] ?? $skuCode,
            'selling_price'       => $data['selling_price'] ?? 0,
            'cost_price'          => $data['cost_price'] ?? 0,
            'mrp'                 => $data['mrp'] ?? 0,
            'minimum_stock_level' => $data['minimum_stock_level'] ?? 5,
            'status'              => $data['status'] ?? 'active',
        ]);
    }

    /**
     * Generate SKU code from variant hierarchy: DESIGN-COLOR-SIZE
     */
    protected function generateSkuCode(Variant $variant): string
    {
        $variant->load('product.design');

        $design = $variant->product->design;
        $parts  = array_filter([
            $design->code,
            $variant->color ? Str::slug($variant->color, '-') : null,
            $variant->size,
        ]);

        $base = strtoupper(implode('-', $parts));

        // Ensure uniqueness
        $existing = Sku::where('sku_code', $base)->exists();
        if ($existing) {
            $count = Sku::where('sku_code', 'LIKE', $base . '%')->count();
            $base .= '-' . ($count + 1);
        }

        return $base;
    }

    /**
     * Create variants from a color/size matrix.
     */
    protected function createVariantsFromMatrix(Design $design, array $data): void
    {
        // Create a single product per design
        $product = $this->createProduct($design, [
            'company_id' => $design->company_id,
            'name'       => $design->name,
        ]);

        foreach ($data['variants'] as $variantData) {
            $color = $variantData['color'];
            $sizes = $variantData['sizes'] ?? [];

            foreach ($sizes as $size) {
                $variant = $this->createVariant($product, [
                    'company_id' => $design->company_id,
                    'color'      => $color,
                    'size'       => $size,
                ]);

                $skuData = [
                    'company_id'    => $design->company_id,
                    'selling_price' => $variantData['selling_price'] ?? $data['selling_price'] ?? 0,
                    'cost_price'    => $variantData['cost_price'] ?? $data['cost_price'] ?? 0,
                    'mrp'           => $variantData['mrp'] ?? $data['mrp'] ?? 0,
                ];

                $this->createSku($variant, $skuData);
            }
        }
    }

    /**
     * Import products from a CSV file.
     *
     * Expected columns: design_code, design_name, category, hsn_code, gst_rate,
     *                   color, size, selling_price, cost_price, mrp
     *
     * @return array{created: int, errors: array}
     */
    public function importFromCsv(string $filePath, int $companyId): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ['created' => 0, 'errors' => ['Could not open file']];
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['created' => 0, 'errors' => ['Empty file or invalid CSV']];
        }

        $headers  = array_map(fn ($h) => strtolower(trim($h)), $headers);
        $created  = 0;
        $errors   = [];
        $row      = 1;
        $designs  = [];
        $products = [];

        while (($record = fgetcsv($handle)) !== false) {
            $row++;
            try {
                $data = array_combine($headers, $record);

                $designCode = $data['design_code'] ?? null;
                if (!$designCode) {
                    $errors[] = "Row {$row}: Missing design_code";
                    continue;
                }

                // Find or create design
                if (!isset($designs[$designCode])) {
                    $design = Design::where('code', $designCode)
                        ->where('company_id', $companyId)
                        ->first();

                    if (!$design) {
                        $design = $this->createDesign([
                            'company_id' => $companyId,
                            'code'       => $designCode,
                            'name'       => $data['design_name'] ?? $designCode,
                            'category'   => $data['category'] ?? null,
                            'hsn_code'   => $data['hsn_code'] ?? null,
                            'gst_rate'   => $data['gst_rate'] ?? 5.00,
                        ]);
                    }
                    $designs[$designCode] = $design;
                }

                $design = $designs[$designCode];

                // Find or create product
                if (!isset($products[$designCode])) {
                    $product = $design->products()->first();
                    if (!$product) {
                        $product = $this->createProduct($design, [
                            'company_id' => $companyId,
                        ]);
                    }
                    $products[$designCode] = $product;
                }

                $product = $products[$designCode];

                // Create variant + SKU
                $color = $data['color'] ?? null;
                $size  = $data['size'] ?? null;

                // Check if variant already exists
                $variant = $product->variants()
                    ->where('color', $color)
                    ->where('size', $size)
                    ->first();

                if (!$variant) {
                    $variant = $this->createVariant($product, [
                        'company_id' => $companyId,
                        'color'      => $color,
                        'size'       => $size,
                    ]);

                    $this->createSku($variant, [
                        'company_id'    => $companyId,
                        'selling_price' => $data['selling_price'] ?? 0,
                        'cost_price'    => $data['cost_price'] ?? 0,
                        'mrp'           => $data['mrp'] ?? 0,
                    ]);

                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Row {$row}: {$e->getMessage()}";
            }
        }

        fclose($handle);

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Map an external SKU to an internal SKU.
     */
    public function mapExternalSku(string $externalId, int $skuId, string $source, ?int $marketplaceAccountId = null): SkuMapping
    {
        return SkuMapping::create([
            'sku_id'                 => $skuId,
            'marketplace_account_id' => $marketplaceAccountId,
            'marketplace_sku'        => $externalId,
            'confidence_score'       => 1.00,
            'mapped_by'              => auth()->id(),
            'status'                 => 'active',
        ]);
    }
}
