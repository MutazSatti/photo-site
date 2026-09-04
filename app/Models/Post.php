<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $section_id
 * @property int|null $category_id
 * @property string $slug
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $location
 * @property string|null $client
 * @property CarbonInterface|null $event_date
 * @property string|null $price
 * @property string|null $duration
 * @property int|null $seats
 * @property int|null $reading_minutes
 * @property int $views
 * @property bool $is_featured
 * @property string $status
 * @property CarbonInterface|null $published_at
 * @property int $sort_order
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property array<int, string>|null $keywords
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Section|null $section
 * @property-read Category|null $category
 * @property-read Collection<int, Media> $media
 * @property-read Media|null $cover
 */
class Post extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'event_date' => 'date',
            'published_at' => 'datetime',
            'keywords' => 'array',
            'price' => 'decimal:2',
            'views' => 'integer',
            'seats' => 'integer',
            'reading_minutes' => 'integer',
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

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<Media, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasOne<Media, $this> */
    public function cover(): HasOne
    {
        return $this->hasOne(Media::class)->where('is_cover', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInSection(Builder $query, string $slug): Builder
    {
        return $query->whereHas('section', fn (Builder $q) => $q->where('slug', $slug));
    }

    /**
     * صورة الغلاف، وإن لم تُحدَّد فأول صورة في المعرض.
     * لا يستدعي استعلامًا إضافيًا إذا كانت العلاقة محمّلة مسبقًا.
     */
    public function coverImage(): ?Media
    {
        if ($this->relationLoaded('media')) {
            return $this->media->firstWhere('is_cover', true) ?? $this->media->first();
        }

        return $this->cover()->first() ?? $this->media()->first();
    }

    /**
     * العنصر المنتمي إلى قسم فرعي يعيش تحته، وما عداه يعيش مباشرة تحت قسمه
     * الرئيسي. الفرق يظهر في الرابط لأنه يوضّح البنية لمحرّكات البحث ولمن
     * يقرأ الرابط. لكل عنصر رابط واحد فقط، لا رابطان.
     */
    public function url(): string
    {
        $sectionSlug = $this->section->slug ?? Section::ARTICLES;
        $categorySlug = $this->category->slug ?? null;

        return $categorySlug
            ? route('work.show', ['section' => $sectionSlug, 'category' => $categorySlug, 'post' => $this->slug])
            : route('post.show', ['section' => $sectionSlug, 'post' => $this->slug]);
    }

    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function metaDescription(): string
    {
        if ($this->seo_description) {
            return $this->seo_description;
        }

        $source = $this->excerpt ?: $this->body;

        return (string) str($source)->stripTags()->squish()->limit(160);
    }

    /**
     * حذف الصور صراحةً قبل حذف العنصر.
     *
     * المفتاح الأجنبي معرّف بـ cascadeOnDelete، فقاعدة البيانات تمسح صفوف
     * media وحدها ولا يمرّ الحذف على Eloquent — ما يعني أن حدث deleting في
     * نموذج Media لا يُطلق وتبقى ملفات WebP يتيمة على القرص إلى الأبد.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $post) {
            $post->media()->get()->each->delete();
        });
    }

    /** يقدّر زمن القراءة من طول النص عندما لا يُحدَّد يدويًا. */
    public function readingTime(): int
    {
        if ($this->reading_minutes) {
            return $this->reading_minutes;
        }

        $words = str_word_count((string) strip_tags((string) $this->body))
            ?: mb_substr_count((string) strip_tags((string) $this->body), ' ');

        return max(1, (int) ceil($words / 180));
    }
}
