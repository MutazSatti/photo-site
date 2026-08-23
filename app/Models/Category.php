<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $section_id
 * @property string $slug
 * @property string $name
 * @property string|null $name_en
 * @property string|null $tagline
 * @property string|null $description
 * @property string $icon
 * @property int $sort_order
 * @property bool $is_active
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Section|null $section
 * @property-read Collection<int, Post> $posts
 */
class Category extends Model
{
    /** الأقسام الفرعية تحت "خدمات التصوير". */
    public const EVENTS = 'events';

    public const ACTIVITIES = 'activities';

    public const REAL_ESTATE = 'real-estate';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
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

    public function url(): string
    {
        return route('category.show', [
            'section' => $this->section->slug ?? Section::SERVICES,
            'category' => $this->slug,
        ]);
    }

    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->name;
    }

    public function metaDescription(): string
    {
        return $this->seo_description ?: (string) str($this->description)->stripTags()->limit(160);
    }
}
