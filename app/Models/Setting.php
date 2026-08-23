<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $group
 * @property string $type
 * @property string|null $label
 * @property string|null $hint
 * @property int $sort_order
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class Setting extends Model
{
    protected $guarded = [];

    public const CACHE_KEY = 'site.settings';

    /**
     * كل الإعدادات كمصفوفة key => value، مخزّنة مؤقتًا.
     *
     * @return array<string, string|null>
     */
    public static function all_values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->pluck('value', 'key')
            ->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::all_values()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        static::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flush());
        static::deleted(fn () => static::flush());
    }
}
