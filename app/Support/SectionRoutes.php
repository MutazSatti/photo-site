<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Section;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * أنماط الروابط الهرمية مبنيّة من الأقسام الموجودة فعلًا في قاعدة البيانات،
 * بدل قائمة ثابتة في ملف المسارات. هذا ما يسمح بإضافة قسم رئيسي أو فرعي
 * جديد من لوحة التحكم فيعمل رابطه فورًا.
 *
 * المسارات تُسجَّل مرة في كل طلب، لذا تُخزَّن القائمتان مؤقتًا وتُفرَّغان
 * تلقائيًا عند أي حفظ أو حذف لقسم (انظر Section::booted و Category::booted).
 */
final class SectionRoutes
{
    public const CACHE_KEY = 'routes.section-patterns';

    /** نمط لا يطابق أي مقطع — يُستخدم حين لا تكون هناك أقسام بعد. */
    private const NEVER = '(?!)';

    /**
     * مقاطع محجوزة لصفحات ثابتة أو لمسارات الحزم؛ منْحها لقسم يعني
     * أن يحجب القسمُ تلك الصفحة، لذا تُرفض في نموذج التحرير.
     *
     * @var array<int, string>
     */
    public const RESERVED_SLUGS = [
        'admin', 'dashboard', 'settings', 'login', 'logout', 'register',
        'password', 'forgot-password', 'reset-password', 'confirm-password',
        'verify-email', 'two-factor-challenge', 'user', 'livewire', 'storage',
        'portfolio', 'about', 'contact', 'faq', 'sync', 'up',
        'sitemap.xml', 'feed.xml', 'llms.txt', 'robots.txt',
    ];

    /**
     * @return array{sections: string, categories: string}
     */
    public static function patterns(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn (): array => [
                'sections' => self::pattern(Section::query()->pluck('slug')->all()),
                'categories' => self::pattern(Category::query()->pluck('slug')->all()),
            ]);
        } catch (Throwable) {
            // قبل تنفيذ الترحيلات لا وجود للجداول؛ ثوابت النموذجين تكفي لإقلاع التطبيق.
            // تُقرأ بالانعكاس لا تُكتب هنا يدويًا: القائمة المكتوبة تتخلّف عن الثوابت
            // عند إضافة قسم جديد، فينتج رابط لا يعمل في بيئة لم تُهيَّأ فيها القاعدة.
            return [
                'sections' => self::pattern(self::slugConstants(Section::class)),
                'categories' => self::pattern(self::slugConstants(Category::class)),
            ];
        }
    }

    /**
     * ثوابت الروابط المعرّفة في النموذج.
     *
     * @param  class-string  $model
     * @return array<int, string>
     */
    private static function slugConstants(string $model): array
    {
        $constants = (new \ReflectionClass($model))->getConstants();

        return array_values(array_filter(
            $constants,
            fn (mixed $value): bool => is_string($value) && preg_match('/^[a-z0-9-]+$/', $value) === 1,
        ));
    }

    /**
     * تُستدعى بعد كل تعديل على الأقسام. ملف المسارات المُجمَّع مسبقًا
     * (route:cache) يحمل النمط القديم، فيُمسح هو أيضًا إن وُجد.
     */
    public static function flush(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);

            $compiled = app()->getCachedRoutesPath();

            if (is_file($compiled)) {
                @unlink($compiled);
            }
        } catch (Throwable) {
            // تفريغ التخزين المؤقت ليس سببًا كافيًا لإفشال الطلب
        }
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private static function pattern(array $slugs): string
    {
        $slugs = array_values(array_filter(array_unique($slugs)));

        return $slugs === []
            ? self::NEVER
            : implode('|', array_map(fn (string $slug): string => preg_quote($slug, '/'), $slugs));
    }
}
