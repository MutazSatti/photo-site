<?php

use App\Models\Category;
use App\Models\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * دمج "المؤتمرات والمعارض" و"الدورات التدريبية" في قسم واحد: "الفعاليات".
     *
     * الأعمال المرتبطة بالقسمين تُنقل إلى القسم الجديد قبل الحذف — المفتاح
     * الأجنبي معرّف بـ nullOnDelete، فحذف القسم دون نقلها يجعلها بلا قسم فرعي
     * وبالتالي بلا رابط هرمي صالح، فتختفي من المعرض ومن خريطة الموقع.
     */
    public function up(): void
    {
        $section = Section::where('slug', Section::SERVICES)->first();

        if (! $section) {
            return;
        }

        $city = config('site.location.city');

        // القسم الجديد يرث ترتيب "المؤتمرات والمعارض" ليبقى في موضعه من القائمة
        $activities = Category::updateOrCreate(
            ['section_id' => $section->id, 'slug' => Category::ACTIVITIES],
            [
                'name' => 'الفعاليات',
                'name_en' => 'Events & Activities',
                'tagline' => 'تغطية إعلامية متكاملة',
                'description' => "تغطية تصويرية احترافية للفعاليات في {$city}: المؤتمرات والمعارض، الملتقيات والمنتديات، الفعاليات المؤسسية والمجتمعية، والبرامج التدريبية. توثيق للجلسات والمتحدثين وأجنحة العرض وتفاعل الحضور، مع تسليم سريع للصور المختارة أولًا بأول لاستخدامها في التغطية الإعلامية اللحظية.",
                'icon' => 'presentation',
                'sort_order' => 2,
                'is_active' => true,
                'seo_title' => "تصوير الفعاليات والمؤتمرات في {$city}",
                'seo_description' => "مصور فعاليات في {$city}. تغطية المؤتمرات والمعارض والملتقيات والفعاليات المؤسسية والبرامج التدريبية، مع تسليم فوري للصور المختارة.",
            ],
        );

        $merged = Category::where('section_id', $section->id)
            ->whereIn('slug', ['conferences', 'courses'])
            ->get();

        foreach ($merged as $category) {
            DB::table('posts')
                ->where('category_id', $category->id)
                ->update(['category_id' => $activities->id]);

            // الأسئلة الشائعة مرتبطة بالقسم الرئيسي لا الفرعي، فلا شيء ينقطع هنا
            $category->delete();
        }

        // العقارات كانت رابعة، وتصبح ثالثة بعد الدمج
        Category::where('section_id', $section->id)
            ->where('slug', Category::REAL_ESTATE)
            ->update(['sort_order' => 3]);
    }

    /**
     * الرجوع يعيد إنشاء القسمين فارغين. الأعمال تبقى تحت "الفعاليات" لأن
     * توزيعها الأصلي بينهما لا يمكن استنتاجه بعد الدمج.
     */
    public function down(): void
    {
        $section = Section::where('slug', Section::SERVICES)->first();

        if (! $section) {
            return;
        }

        $city = config('site.location.city');

        Category::updateOrCreate(
            ['section_id' => $section->id, 'slug' => 'conferences'],
            [
                'name' => 'المؤتمرات والمعارض',
                'tagline' => 'تغطية إعلامية متكاملة',
                'icon' => 'presentation',
                'sort_order' => 2,
                'is_active' => true,
                'seo_title' => "تصوير المؤتمرات والمعارض في {$city}",
            ],
        );

        Category::updateOrCreate(
            ['section_id' => $section->id, 'slug' => 'courses'],
            [
                'name' => 'الدورات التدريبية',
                'tagline' => 'توثيق بيئة التدريب',
                'icon' => 'academic',
                'sort_order' => 3,
                'is_active' => true,
                'seo_title' => "تصوير الدورات والبرامج التدريبية في {$city}",
            ],
        );

        Category::where('section_id', $section->id)
            ->where('slug', Category::ACTIVITIES)
            ->delete();

        Category::where('section_id', $section->id)
            ->where('slug', Category::REAL_ESTATE)
            ->update(['sort_order' => 4]);
    }
};
