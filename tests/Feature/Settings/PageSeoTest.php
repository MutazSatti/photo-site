<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PageSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class]);
    }

    public static function pages(): array
    {
        return [
            'المعرض' => ['portfolio', '/portfolio'],
            'النبذة' => ['about', '/about'],
            'التواصل' => ['contact', '/contact'],
            'الأسئلة' => ['faq', '/faq'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_page_title_and_description_come_from_settings(string $page, string $path): void
    {
        Setting::put("seo_{$page}_title", "عنوان {$page} المخصّص");
        Setting::put("seo_{$page}_description", "وصف {$page} المخصّص للاختبار.");

        $this->get($path)
            ->assertSee("عنوان {$page} المخصّص", false)
            ->assertSee("وصف {$page} المخصّص للاختبار.", false);
    }

    #[DataProvider('pages')]
    public function test_a_page_falls_back_to_its_default_when_the_setting_is_empty(string $page, string $path): void
    {
        Setting::put("seo_{$page}_title", '');
        Setting::put("seo_{$page}_description", '');

        // لا استثناء ولا عنوان فارغ — القيمة الافتراضية في الصفحة تتكفّل.
        $response = $this->get($path)->assertOk();

        preg_match('/<title>(.*?)<\/title>/s', $response->getContent(), $m);
        $this->assertNotEmpty(trim($m[1] ?? ''), 'العنوان لا يجوز أن يكون فارغًا.');
    }

    public function test_the_home_page_uses_the_general_seo_keys(): void
    {
        Setting::put('seo_title', 'عنوان الرئيسية المخصّص');
        Setting::put('seo_description', 'وصف الرئيسية المخصّص.');

        $this->get('/')
            ->assertSee('عنوان الرئيسية المخصّص', false)
            ->assertSee('وصف الرئيسية المخصّص.', false);
    }

    public function test_switching_pages_in_the_editor_loads_that_pages_values(): void
    {
        Setting::put('seo_faq_title', 'أسئلة وأجوبة');

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.settings')
            ->set('seoPage', 'faq')
            ->assertSet('seoValues.title', 'أسئلة وأجوبة');
    }

    public function test_saving_from_the_editor_persists_to_the_right_keys(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.settings')
            ->set('seoPage', 'about')
            ->set('seoValues.title', 'من نحن')
            ->set('seoValues.description', 'تعريف مختصر بالمصور.')
            ->call('saveSeoPage')
            ->assertHasNoErrors();

        Setting::flush();

        $this->assertSame('من نحن', Setting::get('seo_about_title'));
        $this->assertSame('تعريف مختصر بالمصور.', Setting::get('seo_about_description'));
    }

    public function test_an_over_long_title_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.settings')
            ->set('seoPage', 'faq')
            ->set('seoValues.title', str_repeat('ا', 121))
            ->call('saveSeoPage')
            ->assertHasErrors(['seoValues.title']);
    }

    public function test_social_tags_are_present_on_every_page(): void
    {
        foreach (['/', '/portfolio', '/about', '/contact', '/faq'] as $path) {
            $html = $this->get($path)->getContent();

            foreach (['og:title', 'og:description', 'og:image', 'og:url', 'twitter:card', 'twitter:image'] as $tag) {
                $this->assertStringContainsString($tag, $html, "الوسم {$tag} غائب عن {$path}");
            }
        }
    }
}
