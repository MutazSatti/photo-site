<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * عنصر متكرّر داخل قسم من صفحة: رقم في الشريط، أو مرحلة في الليلة، أو مسار في
 * المبدّل — أو نقطة داخل مسار.
 *
 * الشكل واحد في كل الحالات: أيقونة وعنوان وسطر وفقرة. وما يختلف هو أيّ الحقول
 * يستعمله القالب، وذلك مكتوب في تعريف القسم لا هنا.
 *
 * @property int $id
 * @property int $page_block_id
 * @property int|null $parent_id
 * @property int|null $post_id
 * @property string|null $icon
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $body
 * @property int $sort_order
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read EloquentCollection<int, self> $children
 */
class PageBlockItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<PageBlock, $this> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class, 'page_block_id');
    }

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * الأيقونات المتاحة للاختيار من اللوحة، بأسمائها العربية.
     *
     * قائمة مغلقة لا حقل نصّ: مفتاح غير موجود في x-icon يُخرج فراغًا في
     * الصفحة، والمالك لا يعرف أسماء الأيقونات ولا ينبغي أن يحفظها.
     *
     * @return array<string, string>
     */
    public static function icons(): array
    {
        return [
            'rings' => 'خاتما الزواج',
            'crown' => 'تاج',
            'door' => 'باب',
            'car' => 'سيارة',
            'camera' => 'كاميرا',
            'images' => 'صور',
            'users' => 'مجموعة أشخاص',
            'user' => 'شخص',
            'academic' => 'قبّعة تخرّج',
            'building' => 'مبنى',
            'calendar' => 'تقويم',
            'clock' => 'ساعة',
            'map-pin' => 'موقع',
            'drone' => 'طائرة مسيّرة',
            'presentation' => 'منصّة عرض',
            'sparkles' => 'لمعة',
            'star' => 'نجمة',
            'check' => 'علامة صحّ',
            'aperture' => 'عدسة',
            'lightbulb' => 'فكرة',
        ];
    }

    /**
     * نموذج غير محفوظ مبنيّ على تعريف، بأبنائه غير المحفوظين كذلك.
     *
     * @param  array<string, mixed>  $definition
     */
    public static function fromDefinition(array $definition): self
    {
        $item = new self([
            'icon' => $definition['icon'] ?? null,
            'title' => $definition['title'] ?? null,
            'subtitle' => $definition['subtitle'] ?? null,
            'body' => $definition['body'] ?? null,
        ]);

        $children = array_map(
            fn (array $child): self => self::fromDefinition($child),
            $definition['children'] ?? [],
        );

        $item->setRelation('children', new EloquentCollection($children));

        return $item;
    }

    /** العنوان بعد استبدال ‎:city‎ و‎:owner‎. */
    public function heading(): ?string
    {
        return $this->title === null ? null : PageBlock::tokens($this->title);
    }

    /** السطر المرافق بعد استبدال الرموز. */
    public function line(): ?string
    {
        return $this->subtitle === null ? null : PageBlock::tokens($this->subtitle);
    }

    /** الفقرة بعد استبدال الرموز. */
    public function text(): ?string
    {
        return $this->body === null ? null : PageBlock::tokens($this->body);
    }
}
