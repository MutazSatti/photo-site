<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string|null $hint
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_locked
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class HomeBlock extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
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
     * تعريف العناصر المتاحة وترتيبها المبدئي — المرجع الوحيد لهما.
     *
     * كل مفتاح يقابل ملفًا في resources/views/home/.
     *
     * @return array<int, array{key: string, label: string, hint: string, locked?: bool}>
     */
    public static function definitions(): array
    {
        return [
            ['key' => 'hero', 'label' => 'الواجهة', 'hint' => 'الصورة الكبيرة والعنوان وأزرار الحجز', 'locked' => true],
            ['key' => 'sections', 'label' => 'الأقسام الرئيسية', 'hint' => 'بطاقات أقسام المعرض'],
            ['key' => 'services', 'label' => 'خدمات التصوير', 'hint' => 'الأقسام الفرعية لخدمات التصوير'],
            ['key' => 'featured', 'label' => 'أعمال مختارة', 'hint' => 'الأعمال المعلّمة كمميّزة'],
            ['key' => 'workshops', 'label' => 'الورش التدريبية', 'hint' => 'أحدث الورش المنشورة'],
            ['key' => 'reading', 'label' => 'مقالات ومنشورات', 'hint' => 'أحدث المقالات والمنشورات التعليمية'],
            ['key' => 'clients', 'label' => 'جهات وثقت بعدستي', 'hint' => 'شعارات الجهات التي تعاملت معها'],
            ['key' => 'testimonials', 'label' => 'آراء العملاء', 'hint' => 'المراجعات المنشورة'],
            ['key' => 'faq', 'label' => 'الأسئلة الشائعة', 'hint' => 'تُنشر كبيانات مهيكلة تقرؤها أدوات الذكاء الاصطناعي'],
            ['key' => 'cta', 'label' => 'دعوة للحجز', 'hint' => 'الشريط الأخير قبل التذييل'],
        ];
    }

    /**
     * مفاتيح العناصر المعروضة بالترتيب.
     *
     * بلا تخزين مؤقت: استعلام واحد على جدول من تسعة صفوف كسبه مهمل، وثمنه سطح
     * إبطال يسهل أن يتسرّب منه خطأ — تحديث جماعي عبر باني الاستعلام لا يُطلق
     * أحداث النموذج فيبقى الكاش قديمًا بصمت. والصفحة تستدعيه مرة واحدة على أي
     * حال لأن الخاصية المحسوبة في مكوّن Livewire تحفظ نتيجته طوال التصيير.
     *
     * الرجوع إلى الترتيب المبدئي حين يكون الجدول فارغًا مقصود: تثبيت جديد لم
     * تُشغَّل بذوره بعد يجب أن يعرض صفحة كاملة لا صفحة بيضاء.
     *
     * @return array<int, string>
     */
    public static function visibleKeys(): array
    {
        $keys = static::query()->active()->ordered()->pluck('key')->all();

        if ($keys !== [] || static::query()->exists()) {
            return $keys;
        }

        return array_column(static::definitions(), 'key');
    }
}
