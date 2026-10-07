<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * نصّ زرّ الحجز في الترويسة يصير حقلًا يُحرَّر.
 *
 * كان مكتوبًا في القالب، فالموقع فيه زرّا حجز: واحدٌ في الواجهة يملكه المالك،
 * وآخر في أعلى كل صفحة لا يملكه. والزرّ الأعلى هو الذي يراه الزائر في كل
 * صفحة، فكونه وحده خارج يده مقلوبٌ عن الصواب.
 *
 * والهجرة تُنشئ الحقل لا البذرةُ وحدها: البذرة تُشغَّل بعد النشر، والهجرة تجري
 * معه — فلا تمرّ لحظة يقرأ فيها الموقع إعدادًا غير موجود.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->renameHeroField();

        if (Setting::where('key', 'header_cta')->exists()) {
            return;
        }

        Setting::create([
            'key' => 'header_cta',
            'group' => 'contact',
            'type' => 'text',
            'label' => 'نصّ زرّ الحجز في الترويسة',
            'hint' => 'الزرّ الداكن في أعلى كل صفحة، ويقود إلى صفحة التواصل والحجز. اتركه قصيرًا — الشريط العلوي ضيّق.',
            'sort_order' => 7,
            'value' => 'احجز موعد',
        ]);

        Setting::flush();
    }

    /**
     * واسم حقل الواجهة يُفرَّق عنه.
     *
     * «نص زر الحجز» في موضعين يترك المالك يخمّن أيّهما غيّر. والاسم وصفُ
     * الحقل لا محتواه، فتحديثه لا يمسّ ما كتبه.
     */
    private function renameHeroField(): void
    {
        Setting::where('key', 'hero_cta')->update([
            'label' => 'نصّ زرّ الحجز في الواجهة',
            'hint' => 'الزرّ الكبير فوق صورة الواجهة. وزرّ الترويسة الذي يظهر في كل صفحة يُحرَّر من تبويب «التواصل».',
        ]);
    }

    public function down(): void
    {
        Setting::where('key', 'header_cta')->delete();
        Setting::flush();
    }
};
