<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * شاشة الإعدادات نموذج واحد، وتبويباتها أقسامٌ فيه لا نماذج مستقلة.
 *
 * ما كُتب في تبويب ولم يُحفظ كان يُمحى بمجرّد الانتقال إلى تبويب آخر: يكتب
 * المالك بريدًا جديدًا، ثم يعدّل سنوات الخبرة ويحفظ، فيجد البريد وقد عاد إلى
 * ما كان — ويبدو كأن تعديل السنوات هو ما غيّره.
 */
class SettingsScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class]);

        $this->actingAs(User::factory()->create());
    }

    public function test_an_edit_in_another_tab_survives_the_switch_and_is_saved(): void
    {
        Livewire::test('pages::admin.settings')
            ->set('values.contact_email', 'studio@example.com')
            ->set('values.hero_cta', 'احجز الآن')
            ->set('tab', 'general')
            ->set('values.about_years', '12')
            ->call('save')
            ->assertHasNoErrors();

        Setting::flush();

        $this->assertSame('studio@example.com', Setting::get('contact_email'));
        $this->assertSame('احجز الآن', Setting::get('hero_cta'));
        $this->assertSame('12', Setting::get('about_years'));
    }

    /** وما لم يُلمس لا يتغيّر: الحفظ لا يعيد كتابة الشاشة كلها بقيم أخرى. */
    public function test_saving_leaves_untouched_settings_alone(): void
    {
        $before = Setting::orderBy('key')->pluck('value', 'key')->all();

        Livewire::test('pages::admin.settings')
            ->set('tab', 'general')
            ->set('values.about_years', '12')
            ->call('save')
            ->assertHasNoErrors();

        Setting::flush();
        $after = Setting::orderBy('key')->pluck('value', 'key')->all();

        unset($before['about_years'], $after['about_years']);

        $this->assertSame($before, $after);
    }

    /**
     * بصمة المزامنة تتحرّك بعد الحفظ.
     *
     * البصمة تُبنى من MAX(updated_at) في كل جدول، والكتابة عبر باني الاستعلام
     * لا تحرّك الطابع الزمني — فكان ما في قاعدة بيانات المتصفح يبقى قديمًا بلا
     * انتهاء، ولا يعرف الجهاز أن إعدادًا تغيّر.
     */
    public function test_saving_moves_the_sync_fingerprint(): void
    {
        $before = $this->getJson('/sync/manifest')->json('version');

        // الطوابع الزمنية في sqlite بدقّة الثانية، والبذر والحفظ يقعان في
        // الثانية نفسها داخل الاختبار — فيتقدّم الوقت ليقاس الفرق فعلًا
        $this->travel(1)->minutes();

        Livewire::test('pages::admin.settings')
            ->set('tab', 'general')
            ->set('values.about_years', '12')
            ->call('save')
            ->assertHasNoErrors();

        $after = $this->getJson('/sync/manifest')->json('version');

        $this->assertNotSame($before, $after);
    }

    /** خيارات الشعار لها نموذجها، فلا يكتب زرّ الحفظ العام فوقها قيمًا قديمة. */
    public function test_saving_does_not_overwrite_the_logo_options(): void
    {
        $component = Livewire::test('pages::admin.settings');

        Setting::put('brand_name', 'اسم جديد');

        $component->set('tab', 'general')->set('values.about_years', '12')->call('save');

        Setting::flush();

        $this->assertSame('اسم جديد', Setting::get('brand_name'));
    }

    /** وكذلك سيو الرئيسية حين يُحفظ بزرّه الخاص قبل الحفظ العام. */
    public function test_saving_does_not_overwrite_the_home_seo(): void
    {
        Livewire::test('pages::admin.settings')
            ->set('tab', 'seo')
            ->set('seoValues.title', 'عنوان جديد للرئيسية')
            ->call('saveSeoPage')
            ->set('values.about_years', '12')
            ->call('save')
            ->assertHasNoErrors();

        Setting::flush();

        $this->assertSame('عنوان جديد للرئيسية', Setting::get('seo_title'));
    }
}
