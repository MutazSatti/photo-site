<?php

namespace App\Providers;

use App\Support\Seo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // حامل بيانات السيو للطلب الحالي — تملؤه كل صفحة في mount()
        $this->app->scoped(Seo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureBlade();
        $this->configureUploads();
    }

    /**
     * حدّ الرفع المؤقّت في Livewire يتبع حدّ الموقع.
     *
     * Livewire يفحص الملف عند وصوله المؤقّت قبل أن يبلغ الشاشة، وحدّه الافتراضي
     * اثنا عشر ميجابايت. فصورة الكاميرا أو الدرون تُرفض هناك صامتةً: لا تُرفَق،
     * ولا تعمل قاعدة التحقّق التي تشرح السبب، ويقول الزرّ «الحقل مطلوب» — وهي
     * رسالة لا تدلّ على شيء، وتجعل المالك يظنّ العطل في الزرّ لا في حجم الصورة.
     *
     * والحدّ هنا هو max_upload_kb نفسه، فمصدرٌ واحد يحكم الطرفين.
     */
    protected function configureUploads(): void
    {
        config([
            'livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.max_upload_kb()],
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // يمنع حفظ حقل غير موجود في الجدول بصمت — يكشف أخطاء النماذج مبكرًا
        // دون منع التحميل الكسول الذي تحتاجه القوالب.
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // خلف بروكسي أو نطاق https، تبقى الروابط المطلقة في sitemap وبيانات
        // Schema صحيحة إذا كان APP_URL يبدأ بـ https
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * توجيهات Blade مختصرة تُستخدم كثيرًا في القوالب.
     */
    protected function configureBlade(): void
    {
        // @money(1200) → "1,200 ريال"
        Blade::directive('money', fn (string $expression) => "<?php echo number_format((float) ({$expression}), 0).' ريال'; ?>");
    }
}
