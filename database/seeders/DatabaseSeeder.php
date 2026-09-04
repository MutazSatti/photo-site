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
            PostSeeder::class,
        ]);
    }
}
