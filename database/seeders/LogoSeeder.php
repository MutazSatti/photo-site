<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * يبذر شعار الموقع من نسخة المستودع.
 *
 * صامت كبقية بذور المشروع: مخرجاتها تمرّ عبر ‎$this->command‎ التي لا تُضبط إلا
 * حين يعمل البذر من artisan، وحارسها في لارافل نفسه isset — وهو ما يعدّه
 * المحلّل الساكن زائدًا لأن التوثيق يعلن الخاصية غير قابلة للإفراغ. والصمت
 * يُغني عن الالتفاف على هذا الخلاف، ولا ينقص من عمل البذرة شيئًا.
 */
class LogoSeeder extends Seeder
{
    public function run(): void
    {
        // لا نستبدل شعارًا رفعه صاحب الموقع من لوحة التحكم.
        if (Media::where('usage', 'logo')->exists()) {
            return;
        }

        $source = __DIR__.'/assets/logo.png';

        if (! is_file($source)) {
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
    }
}
