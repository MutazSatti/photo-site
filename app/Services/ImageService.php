<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

/**
 * كل صورة تُرفع للموقع تمرّ من هنا.
 *
 * تُحوَّل إلى WebP بمقاسات متعدّدة (thumb / md / lg) بحيث يحمّل المتصفح
 * أصغر مقاس يناسب الشاشة عبر srcset، وتُحفظ أبعادها لتفادي قفزة التخطيط.
 */
class ImageService
{
    /**
     * يرفع صورة ويحوّلها إلى WebP بكل المقاسات، ثم ينشئ سجل Media.
     *
     * @param  string|null  $usage  للصور غير المرتبطة بعنصر (شعار، صورة شخصية، غلاف الرئيسية)
     */
    public function store(
        UploadedFile $file,
        ?Post $post = null,
        ?string $usage = null,
        ?string $alt = null,
        bool $isCover = false,
    ): Media {
        $disk = config('filesystems.default') === 'public' ? 'public' : 'public';
        $directory = 'media/'.now()->format('Y/m');
        $basename = $this->uniqueBasename($file);

        // صور الكاميرات الحديثة كبيرة، والتحويل يستغرق ثوانيَ لكل صورة.
        // إعادة ضبط المهلة لكل ملف تمنع انقطاع رفع دفعة كبيرة في المنتصف.
        @set_time_limit(120);

        // intervention/image 4.1: القراءة عبر decodePath والترميز عبر WebpEncoder
        $source = Image::decodePath($file->getRealPath());

        // احترام دوران الصورة المسجّل في EXIF قبل أي معالجة، وإلا خرجت مقلوبة
        $source = $source->orient();

        $quality = (int) config('site.images.quality', 82);
        $maxWidth = (int) config('site.images.max_width', 2400);

        // strip يزيل بيانات EXIF — يقلّل الحجم ويمنع نشر إحداثيات موقع التصوير
        $encoder = new WebpEncoder(quality: $quality, strip: true);

        // معدِّلات intervention تعمل على النسخة نفسها وتعيدها، ولا نحتاج الأصل
        // بعد هذه النقطة — فتفادي clone هنا يوفّر نسخ صورة كاملة في الذاكرة.
        $full = $source->scaleDown(width: $maxWidth);
        $fullPath = "{$directory}/{$basename}.webp";
        $fullEncoded = $full->encode($encoder);

        Storage::disk($disk)->put($fullPath, (string) $fullEncoded);

        // تُلتقط الأبعاد الآن لأن المقاسات الفرعية تقلّص هذه النسخة نفسها
        $fullWidth = $full->width();
        $fullHeight = $full->height();

        // المقاسات الفرعية تُولَّد بالتسلسل من الأكبر إلى الأصغر: كل مقاس يُشتق
        // من سابقه لا من الأصل. تقليص 1600 إلى 800 أرخص بكثير من تقليص 3000 إلى
        // 800، وهذا وحده يقصّر زمن معالجة الصورة الواحدة إلى أقل من النصف.
        $variants = ['full' => $fullPath];

        $sizes = config('site.images.variants', []);
        arsort($sizes);

        $current = $full;

        foreach ($sizes as $name => $width) {
            // الصورة أصغر من هذا المقاس أصلًا — نشير إلى النسخة الكاملة
            // بدل كتابة ملف مطابق لها على القرص
            if ($current->width() <= $width) {
                $variants[$name] = $fullPath;

                continue;
            }

            $current = $current->scaleDown(width: $width);

            $variantPath = "{$directory}/{$basename}-{$name}.webp";

            Storage::disk($disk)->put($variantPath, (string) $current->encode($encoder));

            $variants[$name] = $variantPath;
        }

        return Media::create([
            'post_id' => $post?->id,
            'usage' => $usage,
            'disk' => $disk,
            'path' => $fullPath,
            'variants' => $variants,
            'width' => $fullWidth,
            'height' => $fullHeight,
            'size' => strlen((string) $fullEncoded),
            'original_name' => $file->getClientOriginalName(),
            'alt' => $alt ?: $this->altFromFilename($file, $post),
            'is_cover' => $isCover,
            'sort_order' => $post
                ? ((int) Media::where('post_id', $post->id)->max('sort_order') + 1)
                : 0,
        ]);
    }

    /**
     * يستبدل صورة مخصّصة لاستخدام معيّن (مثل صورة "نبذة عني")،
     * ويحذف القديمة حتى لا تتراكم ملفات معطّلة على القرص.
     */
    public function replaceForUsage(UploadedFile $file, string $usage, ?string $alt = null): Media
    {
        Media::where('usage', $usage)->get()->each->delete();

        return $this->store($file, usage: $usage, alt: $alt);
    }

    /** يجعل صورة واحدة هي الغلاف ويلغي العلامة عن بقية صور العنصر. */
    public function makeCover(Media $media): void
    {
        if (! $media->post_id) {
            return;
        }

        Media::where('post_id', $media->post_id)->update(['is_cover' => false]);

        $media->update(['is_cover' => true]);
    }

    /**
     * يعيد ترتيب صور عنصر بحسب ترتيب المعرّفات الممرّرة.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $index => $id) {
            Media::whereKey($id)->update(['sort_order' => $index]);
        }
    }

    /** اسم ملف فريد مشتق من الاسم الأصلي — يبقى مقروءًا ولا يتعارض. */
    private function uniqueBasename(UploadedFile $file): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        // أسماء الملفات العربية تُفرَّغ بعد slug، فنستبدلها باسم محايد
        if ($name === '') {
            $name = 'image';
        }

        return Str::limit($name, 40, '').'-'.Str::lower(Str::random(8));
    }

    /** نص بديل مبدئي — يُستحسن تعديله يدويًا من لوحة التحكم لتحسين السيو. */
    private function altFromFilename(UploadedFile $file, ?Post $post): string
    {
        if ($post) {
            return $post->title.' — '.config('site.owner_name');
        }

        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return Str::of($name)->replace(['-', '_'], ' ')->squish()->value()
            ?: config('site.owner_name');
    }

    /**
     * هل بيئة PHP الحالية قادرة على إنتاج WebP؟
     * تُستخدم لعرض تحذير واضح في لوحة التحكم بدل فشل صامت عند الرفع.
     */
    public static function webpSupported(): bool
    {
        return function_exists('imagewebp') || class_exists(\Imagick::class);
    }

    /** يقصّ الصورة إلى نسبة ثابتة — يُستخدم للصور المصغّرة في الشبكة. */
    public function cropTo(ImageInterface $image, int $width, int $height): ImageInterface
    {
        return $image->cover($width, $height);
    }
}
