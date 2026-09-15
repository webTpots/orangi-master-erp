<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSetting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = [
        'company_id',
        'group',
        'key',
        'value',
        'type',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Static Helpers ─────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::whereNull('company_id')
            ->where('key', $key)
            ->first();

        return $setting ? static::castValue($setting->value, $setting->type) : $default;
    }

    public static function set(string $key, mixed $value, ?string $type = null): void
    {
        static::updateOrCreate(
            ['key' => $key, 'company_id' => null],
            [
                'value' => (string) $value,
                'type'  => $type ?? 'string',
            ]
        );
    }

    public static function getForCompany(int $companyId, string $key, mixed $default = null): mixed
    {
        $setting = static::where('company_id', $companyId)
            ->where('key', $key)
            ->first();

        if ($setting) {
            return static::castValue($setting->value, $setting->type);
        }

        // Fall back to global setting
        return static::get($key, $default);
    }

    // ── Internal ───────────────────────────────────────────────────

    protected static function castValue(?string $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int'  => (int) $value,
            'float', 'double' => (float) $value,
            'json', 'array'   => json_decode($value, true),
            default           => $value,
        };
    }
}
