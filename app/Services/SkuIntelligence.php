<?php

namespace App\Services;

use App\Models\Sku;
use App\Models\SkuMapping;
use Illuminate\Support\Str;

class SkuIntelligence
{
    /**
     * Find matching internal SKUs for an external SKU string.
     *
     * Returns array of matches: [{sku, confidence, reason}]
     * Strategies applied in order (highest confidence first):
     *   1. Exact sku_mappings match
     *   2. Exact sku_code match
     *   3. SKU prefix + size match
     *   4. Color + size match from product name
     *   5. Alias/mapping name fuzzy match
     */
    public function findMatch(
        string $externalSku,
        ?string $productName = null,
        ?string $color = null,
        ?string $size = null,
        int $companyId = 0,
    ): array {
        $matches = [];
        $normalizedExternal = $this->normalize($externalSku);

        // ── Strategy 1: Exact sku_mappings match ──
        $mapping = SkuMapping::where('marketplace_sku', $externalSku)
            ->where('status', 'active')
            ->with('sku.variant.product.design')
            ->first();

        if ($mapping) {
            $matches[] = [
                'sku'        => $mapping->sku,
                'confidence' => 1.00,
                'reason'     => 'Exact mapping found',
                'strategy'   => 'mapping_exact',
            ];
            return $matches;
        }

        // ── Strategy 2: Exact sku_code match ──
        $query = Sku::where('sku_code', $externalSku)->active();
        if ($companyId) {
            $query->forCompany($companyId);
        }
        $exactSku = $query->with('variant.product.design')->first();

        if ($exactSku) {
            $matches[] = [
                'sku'        => $exactSku,
                'confidence' => 0.95,
                'reason'     => 'Exact SKU code match',
                'strategy'   => 'sku_exact',
            ];
            return $matches;
        }

        // ── Strategy 3: SKU prefix + size match ──
        // e.g. "SKD-Moon-Black" + "L" => "SKD-MOON-BLACK-L"
        if ($size) {
            $prefix = $normalizedExternal;
            $candidateCode = $prefix . '-' . strtoupper($size);

            $query = Sku::where('sku_code', $candidateCode)->active();
            if ($companyId) {
                $query->forCompany($companyId);
            }
            $prefixMatch = $query->with('variant.product.design')->first();

            if ($prefixMatch) {
                $matches[] = [
                    'sku'        => $prefixMatch,
                    'confidence' => 0.85,
                    'reason'     => "SKU prefix + size match: {$candidateCode}",
                    'strategy'   => 'prefix_size',
                ];
            }
        }

        // Also try without size appended but matching variant size
        $query = Sku::where('sku_code', 'LIKE', $normalizedExternal . '%')->active();
        if ($companyId) {
            $query->forCompany($companyId);
        }
        $prefixMatches = $query->with('variant.product.design')->get();

        foreach ($prefixMatches as $pm) {
            if ($size && strtoupper($pm->variant?->size ?? '') === strtoupper($size)) {
                $already = collect($matches)->contains(fn ($m) => $m['sku']->id === $pm->id);
                if (!$already) {
                    $matches[] = [
                        'sku'        => $pm,
                        'confidence' => 0.80,
                        'reason'     => "Prefix match with matching variant size",
                        'strategy'   => 'prefix_variant_size',
                    ];
                }
            }
        }

        // ── Strategy 4: Color + size match from product name ──
        if ($color && $size) {
            $query = Sku::whereHas('variant', function ($q) use ($color, $size) {
                $q->where('color', 'LIKE', "%{$color}%")
                  ->where('size', $size);
            })->active();

            if ($companyId) {
                $query->forCompany($companyId);
            }

            // If product name provided, also match on design name
            if ($productName) {
                $nameWords = $this->extractKeywords($productName);
                $query->whereHas('variant.product.design', function ($q) use ($nameWords) {
                    foreach ($nameWords as $word) {
                        $q->where(function ($qq) use ($word) {
                            $qq->where('name', 'LIKE', "%{$word}%")
                               ->orWhere('code', 'LIKE', "%{$word}%");
                        });
                    }
                });
            }

            $colorSizeMatches = $query->with('variant.product.design')->get();
            foreach ($colorSizeMatches as $csm) {
                $already = collect($matches)->contains(fn ($m) => $m['sku']->id === $csm->id);
                if (!$already) {
                    $matches[] = [
                        'sku'        => $csm,
                        'confidence' => $productName ? 0.70 : 0.60,
                        'reason'     => "Color ({$color}) + size ({$size}) match" . ($productName ? " with product name" : ''),
                        'strategy'   => 'color_size',
                    ];
                }
            }
        }

        // ── Strategy 5: Alias/mapping name fuzzy match ──
        $allMappings = SkuMapping::where('status', 'active')
            ->with('sku.variant.product.design')
            ->get();

        foreach ($allMappings as $mapping) {
            $normalizedMapping = $this->normalize($mapping->marketplace_sku ?? '');
            $similarity = 0;

            similar_text($normalizedExternal, $normalizedMapping, $similarity);

            if ($similarity >= 75) {
                $already = collect($matches)->contains(fn ($m) => $m['sku']->id === $mapping->sku_id);
                if (!$already) {
                    $matches[] = [
                        'sku'        => $mapping->sku,
                        'confidence' => round(($similarity / 100) * 0.60, 2),
                        'reason'     => "Fuzzy match ({$similarity}% similar to mapped SKU: {$mapping->marketplace_sku})",
                        'strategy'   => 'fuzzy',
                    ];
                }
            }
        }

        // Sort by confidence descending
        usort($matches, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return $matches;
    }

    /**
     * Suggest a mapping for an unmapped order item.
     *
     * @param  object $item  Expects ->external_sku, ->product_name, ->color, ->size
     * @return array|null    {sku, confidence, reason} or null
     */
    public function suggestMapping(object $item): ?array
    {
        $companyId = $item->company_id ?? 0;

        $matches = $this->findMatch(
            $item->external_sku ?? $item->marketplace_sku ?? '',
            $item->product_name ?? null,
            $item->color ?? null,
            $item->size ?? null,
            $companyId,
        );

        if (empty($matches)) {
            return null;
        }

        return $matches[0];
    }

    /**
     * Batch-map multiple items with confidence scores.
     *
     * @param  array $items     Each: {external_sku, product_name?, color?, size?}
     * @param  int   $companyId
     * @return array            [{external_sku, matches: [...], best_match: ?{...}}]
     */
    public function autoMapBatch(array $items, int $companyId): array
    {
        $results = [];

        foreach ($items as $item) {
            $externalSku = $item['external_sku'] ?? $item['marketplace_sku'] ?? '';
            $matches = $this->findMatch(
                $externalSku,
                $item['product_name'] ?? null,
                $item['color'] ?? null,
                $item['size'] ?? null,
                $companyId,
            );

            $results[] = [
                'external_sku' => $externalSku,
                'matches'      => $matches,
                'best_match'   => $matches[0] ?? null,
            ];
        }

        return $results;
    }

    /**
     * Normalize a string for comparison.
     */
    protected function normalize(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[\s_]+/', '-', $value);
        $value = preg_replace('/[^A-Z0-9\-]/', '', $value);

        return $value;
    }

    /**
     * Extract meaningful keywords from a product name.
     */
    protected function extractKeywords(string $name): array
    {
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'for', 'with', 'in', 'of', 'set', 'pack', 'combo'];
        $words = preg_split('/[\s\-_,]+/', strtolower($name));

        return array_values(array_filter($words, function ($w) use ($stopWords) {
            return strlen($w) > 2 && !in_array($w, $stopWords);
        }));
    }
}
