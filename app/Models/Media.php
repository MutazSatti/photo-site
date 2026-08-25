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
    /** الشعار محمَّل لهذا الطلب — يُطلب في الترويسة والتذييل معًا. */
    private static ?self $logoMemo = null;

    private static bool $logoLoaded = false;

    /**
     * شعار الموقع، أو null إن لم يُرفع شعار فتُعرض الأيقونة الافتراضية.
     *
     * محفوظ داخل الطلب فقط: تخزين كائن Eloquent في الكاش المشترك يتطلب
     * تسلسله، وفكّه قبل تحميل الصنف يعطي __PHP_Incomplete_Class.
     * والاستعلام نفسه سطر واحد على فهرس usage.
     */
    public static function logo(): ?self
    {
        if (! self::$logoLoaded) {
            self::$logoMemo = static::where('usage', 'logo')->first();
            self::$logoLoaded = true;
        }

        return self::$logoMemo;
    }

    /** الأيقونة محمَّلة لهذا الطلب. */
    private static ?self $faviconMemo = null;

    private static bool $faviconLoaded = false;

    /**
     * أيقونة الموقع المرفوعة، أو null فتُستخدم الملفات الثابتة في public.
     */
    public static function favicon(): ?self
    {
        if (! self::$faviconLoaded) {
            self::$faviconMemo = static::where('usage', 'favicon')->first();
            self::$faviconLoaded = true;
        }

        return self::$faviconMemo;
    }

    /** يُستدعى بعد كل تغيير على الشعار أو الأيقونة. */
    public static function forgetLogo(): void
    {
        self::$logoMemo = null;
        self::$logoLoaded = false;
        self::$faviconMemo = null;
        self::$faviconLoaded = false;
    }

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

    /** سمة srcset كاملة ليختار المتصفح المقاس المناسب للشاشة. */
    public function srcset(): string
    {
        $widths = ['thumb' => 400, 'md' => 800, 'lg' => 1600];
        $paths = $this->variantPaths();

        $entries = [];

        foreach ($widths as $variant => $width) {
            if (isset($paths[$variant])) {
                $entries[] = Storage::disk($this->disk ?: 'public')->url($paths[$variant])." {$width}w";
            }
        }

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
