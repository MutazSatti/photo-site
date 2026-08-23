<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * آراء مبدئية لعرض شكل القسم — استبدلها بآراء عملاء حقيقيين من لوحة التحكم
 * قبل نشر الموقع، فالمراجعات المهيكلة يجب أن تكون صادقة.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'نموذج — أحد العملاء',
                'role' => 'منظّم فعاليات',
                'content' => 'التغطية كانت منظّمة من البداية للنهاية، والصور المختارة وصلت أثناء الفعالية نفسها فاستفدنا منها في النشر اللحظي.',
                'rating' => 5,
                'sort_order' => 1,
                'is_active' => false,
            ],
            [
                'name' => 'نموذج — جهة تدريبية',
                'role' => 'مسؤول تسويق',
                'content' => 'وثّق الدورة بصور احترافية استخدمناها في التقرير الختامي وفي التسويق للدفعة التالية.',
                'rating' => 5,
                'sort_order' => 2,
                'is_active' => false,
            ],
            [
                'name' => 'نموذج — مكتب عقاري',
                'role' => 'مدير مبيعات',
                'content' => 'الصور أظهرت المساحات بشكل واقعي وجذاب، وانعكس ذلك مباشرة على عدد الاستفسارات.',
                'rating' => 5,
                'sort_order' => 3,
                'is_active' => false,
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
