<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class LogoSeeder extends Seeder
{
    public function run(): void
    {
        // لا نستبدل شعارًا رفعه صاحب الموقع من لوحة التحكم.
        if (Media::where('usage', 'logo')->exists()) {
            $this->command?->info('الشعار موجود — تُرك كما هو.');

            return;
        }

        $source = __DIR__.'/assets/logo.png';

        if (! is_file($source)) {
            $this->command?->warn("ملف الشعار غير موجود: {$source}");

            return;
        }

        // نسخة مؤقتة لأن ImageService يستهلك الملف، والأصل مرجع دائم في المستودع.
        $temp = tempnam(sys_get_temp_dir(), 'logo').'.png';
        copy($source, $temp);

        app(ImageService::class)->replaceForUsage(
            file: new UploadedFile($temp, 'logo.png', 'image/png', null, true),
            usage: 'logo',
            alt: config('site.owner_name').' — شعار الموقع',
        );

        @unlink($temp);
        Media::forgetLogo();

        $this->command?->info('بُذر شعار الموقع.');
    }
}
