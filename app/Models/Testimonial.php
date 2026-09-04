<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $role
 * @property string $content
 * @property string $source
 * @property string|null $external_id
 * @property int $rating
 * @property CarbonInterface|null $reviewed_at
 * @property int $sort_order
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class Testimonial extends Model
{
    /** رأي وصل إلى المصور مباشرةً — يدخل في التقييم الإجمالي المهيكل. */
    public const SOURCE_DIRECT = 'direct';

    /** رأي منقول من تقييمات Google — يُعرض بشارة ولا يدخل في التقييم المهيكل. */
    public const SOURCE_GOOGLE = 'google';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rating' => 'integer',
            'reviewed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * الآراء التي وصلت مباشرةً — وحدها المؤهّلة للبيانات المهيكلة.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFirstParty(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_DIRECT);
    }

    public function isFromGoogle(): bool
    {
        return $this->source === self::SOURCE_GOOGLE;
    }

    /**
     * الخيارات كما تظهر في لوحة التحكم.
     *
     * @return array<string, string>
     */
    public static function sourceOptions(): array
    {
        return [
            self::SOURCE_DIRECT => 'وصلني مباشرة',
            self::SOURCE_GOOGLE => 'منقول من Google',
        ];
    }
}
