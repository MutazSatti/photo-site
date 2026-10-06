<?php

use App\Models\PageBlock;
use App\Models\Setting;
use Database\Seeders\PageBlockSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * أرقام الصفحة الرئيسية تنتقل إلى محتوى الصفحات.
 *
 * كانت أربع بطاقات: رقمُ كلٍّ منها صفٌّ في الإعدادات، وعنوانه مكتوب في القالب.
 * فالمالك يغيّر «450» ولا يستطيع تغيير «مشروع مصوَّر» إلى غيرها، ولا حذف بطاقة
 * ولا إضافة أخرى. وصارت البطاقة كاملةً — رقمها وسطرها — عنصرًا يُحرَّر ويُرتَّب
 * ويُحذف من «محتوى الصفحات ← الصفحة الرئيسية».
 *
 * وبطاقة سنوات الخبرة حُذفت من الواجهة بطلب المالك. والرقم نفسه باقٍ في
 * الإعدادات لأن صفحة «نبذة» ما تزال تعرضه.
 *
 * أما مفاتيح الأرقام الثلاثة فتُحذف: بقاؤها في شاشة الإعدادات بعد أن صارت
 * تُحرَّر في مكان آخر يعني حقلًا يُملأ ولا يظهر أثره — وهو أسوأ من غيابه.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const RETIRED = ['stat_projects', 'stat_clients', 'stat_workshops'];

    public function up(): void
    {
        (new PageBlockSeeder)->seedPage(PageBlock::HOME);

        Setting::whereIn('key', self::RETIRED)->delete();
        Setting::flush();
    }

    public function down(): void
    {
        PageBlock::onPage(PageBlock::HOME)->delete();

        foreach ($this->retiredFields() as $index => $field) {
            Setting::updateOrCreate(['key' => $field['key']], [
                'group' => 'general',
                'type' => 'number',
                'label' => $field['label'],
                'sort_order' => 4 + $index,
                'value' => $field['value'],
            ]);
        }

        Setting::flush();
    }

    /**
     * @return array<int, array{key: string, label: string, value: string}>
     */
    private function retiredFields(): array
    {
        return [
            ['key' => 'stat_projects', 'label' => 'عدد المشاريع', 'value' => '450'],
            ['key' => 'stat_clients', 'label' => 'عدد العملاء', 'value' => '180'],
            ['key' => 'stat_workshops', 'label' => 'عدد الورش', 'value' => '35'],
        ];
    }
};
