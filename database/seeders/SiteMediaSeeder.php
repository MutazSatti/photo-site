<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Post;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * يبذر صور الصفحات من نسخة المستودع.
 *
 * النشر يتم بسحب من GitHub، والصور تعيش في storage/app/public وهو مستثنى من
 * المستودع — فبلا هذه البذرة تصل الشيفرة إلى الخادم وتبقى الصفحات فارغة:
 * مجموعات بعناوين بلا صور، وقسم «قبل وبعد» لا يظهر أصلًا.
 *
 * الأصول في assets/site-media، والمعرض منها بمقاس 1600 لا 2400: الصفحة لا
 * تعرض أكبر منه، والفرق في حجم المستودع أكثر من النصف. أما صورة الواجهة
 * والشعارات فبمقاسها الكامل لأنها تُعرض بعرض الصفحة.
 *
 * والبذرة لا تستبدل شيئًا: ما رُفع من لوحة التحكم يبقى، وما استُورد سابقًا
 * يُتخطّى بمطابقة الاسم الأصلي. فتشغيلها مرّتين لا يُنتج تكرارًا.
 */
class SiteMediaSeeder extends Seeder
{
    public function run(): void
    {
        $dir = __DIR__.'/assets/site-media';
        $manifest = $dir.'/manifest.json';

        if (! is_file($manifest)) {
            return;
        }

        /** @var array<int, array<string, mixed>> $items */
        $items = json_decode((string) file_get_contents($manifest), true) ?: [];

        $images = app(ImageService::class);

        foreach ($items as $item) {
            $file = $dir.'/'.$item['file'];

            if (! is_file($file)) {
                continue;
            }

            $usage = isset($item['usage']) ? (string) $item['usage'] : null;

            // الخانة محجوزة سلفًا، أو الصورة مستوردة سلفًا باسمها
            if ($usage !== null && Media::where('usage', $usage)->exists()) {
                continue;
            }

            if ($usage === null && Media::where('original_name', $item['file'])->exists()) {
                continue;
            }

            $post = null;

            if (isset($item['post'])) {
                $post = Post::where('slug', $item['post'])->first();

                if (! $post) {
                    continue;
                }
            }

            // نسخة مؤقتة لأن ImageService يستهلك الملف، والأصل مرجع دائم
            $temp = tempnam(sys_get_temp_dir(), 'seed').'.webp';
            copy($file, $temp);

            try {
                $media = $images->store(
                    file: new UploadedFile($temp, $item['file'], 'image/webp', null, true),
                    post: $post,
                    usage: $usage,
                    alt: isset($item['alt']) ? (string) $item['alt'] : null,
                    isCover: (bool) ($item['is_cover'] ?? false),
                );

                // الترتيب يأتي من البيان لا من تسلسل الإدخال، فيطابق ما أُقرّ
                $media->update([
                    'caption' => $item['caption'] ?? null,
                    'sort_order' => (int) ($item['sort_order'] ?? 0),
                ]);
            } catch (Throwable $e) {
                // صورة واحدة معطوبة لا توقف بذر البقية على خادم حيّ. أما في
                // الاختبار فالصمت يُخفي العطل الذي جاء الاختبار ليكشفه.
                if (app()->runningUnitTests()) {
                    throw $e;
                }
            } finally {
                @unlink($temp);
            }
        }

        Media::forgetLogo();
    }
}
