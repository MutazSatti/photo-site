<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // حساب الإدارة الوحيد — غيّر كلمة المرور فور أول دخول.
        // البريد بأحرف صغيرة لأن Fortify يحوّل اسم المستخدم إلى lowercase عند الدخول.
        User::updateOrCreate(
            ['email' => Str::lower(config('site.admin.email'))],
            [
                'name' => config('site.owner_name'),
                'password' => Hash::make(config('site.admin.password')),
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            LogoSeeder::class,
            SectionSeeder::class,
            SettingSeeder::class,
            HomeBlockSeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
            ClientSeeder::class,
            PostSeeder::class,
            WorkSeeder::class,
        ]);

        /*
         * مجموعات صفحة التصوير العقاري تُنشئها هجرة محتوى، وهي تعمل على تثبيت
         * جديد قبل بذر الأقسام فتجد القسم غائبًا وتنسحب. فتُستدعى هنا بعد وجوده.
         *
         * على تثبيت قائم لا أثر لهذا: الهجرة أنشأتها وقت الترحيل، والاستدعاء
         * مبنيّ على updateOrCreate فلا يكرّر ولا يستبدل.
         */
        (require database_path('migrations/2026_09_03_000002_build_real_estate_service_page.php'))->up();

        // بعد المجموعات والأعمال والجهات لأنها تعلّق صورها بها كلها
        $this->call(SiteMediaSeeder::class);
    }
}
