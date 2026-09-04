<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $post_id
 * @property string|null $usage
 * @property string $disk
 * @property string $path
 * @property array<string, string>|null $variants
 * @property int|null $width
 * @property int|null $height
 * @property int|null $size
 * @property string|null $original_name
 * @property string|null $alt
 * @property string|null $caption
 * @property bool $is_cover
 * @property int $sort_order
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Post|null $post
 */
class Media extends Model
{
    protected $table = 'media';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'is_cover' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * مسارات المقاسات كمصفوفة دائمًا — العمود قد يكون null لسجل قديم أو مستورد.
     *
     * @return array<string, string>
     */
    public function variantPaths(): array
    {
        return is_array($this->variants) ? $this->variants : [];
    }

    /** الرابط العام لمقاس محدّد، مع الرجوع إلى النسخة الكاملة إن لم يوجد. */
    public function url(string $variant = 'lg'): string
    {
        $path = $this->variantPaths()[$variant] ?? $this->path;

        return Storage::disk($this->disk ?: 'public')->url($path);
    }

    /**
     * سمة srcset كاملة ليختار المتصفح المقاس المناسب للشاشة.
     *
     * الواصف يحمل العرض الحقيقي للملف لا المقاس المستهدف. التصغير لا يكبّر أبدًا،
     * فصورة أصلها 1200 بكسل تُنتج ملفًا واحدًا بـ1200 مهما بلغت المقاسات المطلوبة.
     * لو أعلنّا عنه 1600 لصدّق المتصفح ذلك واختاره لمساحة أوسع مما يحتمل، فظهر
     * ناعم الحواف — وهو بالضبط ما يُفترض بـ srcset أن يمنعه.
     *
     * الفهرسة بالعرض تُسقط التكرار تلقائيًا حين يتطابق مقاسان في ملف واحد.
     */
    public function srcset(): string
    {
        $targets = [
            'thumb' => 400,
            'md' => 800,
            'lg' => 1600,
            'full' => (int) config('site.images.max_width', 2400),
        ];

        $paths = $this->variantPaths();
        $disk = Storage::disk($this->disk ?: 'public');

        $entries = [];

        foreach ($targets as $variant => $target) {
            if (! isset($paths[$variant])) {
                continue;
            }

            $width = $this->width ? min($target, $this->width) : $target;

            $entries[$width] = $disk->url($paths[$variant])." {$width}w";
        }

        ksort($entries);

        return implode(', ', $entries);
    }

    /** نسبة العرض إلى الارتفاع — تمنع قفزة التخطيط أثناء تحميل الصورة. */
    public function aspectRatio(): string
    {
        if (! $this->width || ! $this->height) {
            return '4 / 3';
        }

        return "{$this->width} / {$this->height}";
    }

    /**
     * النص البديل للصورة.
     *
     * ImageService يملأ alt عند الرفع (من عنوان العنصر أو اسم الملف)،
     * ولوحة التحكم تفرض تعبئته عند التعديل — فلا حاجة لتحميل العلاقة هنا،
     * وتجنّبها يمنع استعلامًا إضافيًا لكل صورة في شبكة المعرض.
     */
    public function altText(): string
    {
        return $this->alt ?: config('site.owner_name');
    }

    /** يحذف كل ملفات المقاسات من القرص عند حذف السجل. */
    protected static function booted(): void
    {
        static::deleting(function (self $media) {
            $disk = Storage::disk($media->disk ?: 'public');

            foreach (array_merge([$media->path], array_values($media->variantPaths())) as $path) {
                if ($path && $disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        });
    }
}
