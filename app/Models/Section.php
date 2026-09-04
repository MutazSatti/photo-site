<?php

namespace App\Models;

use App\Support\SectionRoutes;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $name_en
 * @property string|null $tagline
 * @property string|null $description
 * @property string $icon
 * @property string $color
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $has_categories
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Category> $activeCategories
 * @property-read Collection<int, Post> $posts
 */
class Section extends Model
{
    /** الأقسام الرئيسية الأربعة — تُستخدم كثوابت لتفادي الاعتماد على المعرّفات الرقمية. */
    public const SERVICES = 'services';

    public const WORKSHOPS = 'workshops';

    public const ARTICLES = 'articles';

    public const TIPS = 'tips';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'has_categories' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    /** @return HasMany<Category, $this> */
    public function activeCategories(): HasMany
    {
        return $this->categories()->where('is_active', true);
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** @return HasMany<Faq, $this> */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    /**
     * كل صور هذا القسم عبر عناصره — تُستخدم لعرض شبكة المعرض.
     *
     * @return HasManyThrough<Media, Post, $this>
     */
    public function media(): HasManyThrough
    {
        return $this->hasManyThrough(Media::class, Post::class);
    }

    /**
     * حذف العناصر عبر Eloquent لا عبر تتالي قاعدة البيانات، حتى يمرّ الحذف
     * على نموذج Post فيمسح بدوره ملفات الصور من القرص.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $section) {
            $section->posts()->get()->each->delete();
        });

        // قيود المسارات مبنيّة على قائمة الأقسام، فأي تغيير فيها يُبطلها
        static::saved(fn () => SectionRoutes::flush());
        static::deleted(fn () => SectionRoutes::flush());
    }

    /**
     * متغيّرات CSS للون القسم، تُوضع في سمة style على أي حاوية.
     *
     * التلوين عبر متغيّرات لا عبر أصناف Tailwind مبنيّة باسم اللون: الأصناف
     * الديناميكية لا يراها Tailwind وقت البناء فلا تُولَّد أصلًا. المتغيّر
     * يحمل قيمتين — واحدة للوضع الفاتح وأخرى للداكن — ويُبدَّل بينهما في CSS.
     */
    public function colorStyle(): string
    {
        $palette = config('site.section_colors');
        $color = $palette[$this->color] ?? $palette['brand'];

        return sprintf('--sec:%s;--sec-dark:%s', $color['light'], $color['dark']);
    }

    public function colorLabel(): string
    {
        return config('site.section_colors.'.$this->color.'.label')
            ?? config('site.section_colors.brand.label');
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
        return route('section.show', $this->slug);
    }

    /** عنوان الصفحة المستخدم في وسم title وبيانات Open Graph. */
    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->name;
    }

    public function metaDescription(): string
    {
        return $this->seo_description ?: (string) str($this->description)->stripTags()->limit(160);
    }
}
