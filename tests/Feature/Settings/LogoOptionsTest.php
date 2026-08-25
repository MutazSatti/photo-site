<?php

namespace Tests\Feature\Settings;

use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogoOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class]);
        $this->actingAs(User::factory()->create());
    }

    /**
     * الخيار يُخزَّن نصًّا "0"/"1"، ومربّع الاختيار يحتاج boolean.
     * النص "0" صادق في JavaScript، فلو وصل كما هو ظهر المربّع
     * محدَّدًا وهو معطَّل، وعكَس الضغطُ عليه المقصود.
     */
    public function test_disabled_option_loads_as_false_not_string_zero(): void
    {
        Setting::put('logo_adapt_dark', '0');

        $value = Livewire::test('pages::admin.settings')->get('logoOpts')['logo_adapt_dark'];

        $this->assertIsBool($value, 'يجب أن يصل مربّع الاختيار boolean — النص "0" صادق في JavaScript.');
        $this->assertFalse($value);
    }

    public function test_enabled_option_loads_as_true(): void
    {
        Setting::put('logo_adapt_dark', '1');

        $value = Livewire::test('pages::admin.settings')->get('logoOpts')['logo_adapt_dark'];

        $this->assertIsBool($value);
        $this->assertTrue($value);
    }

    public function test_enabling_the_option_persists_as_string_one(): void
    {
        Setting::put('logo_adapt_dark', '0');

        Livewire::test('pages::admin.settings')
            ->set('logoOpts.logo_adapt_dark', true)
            ->call('saveLogoOptions')
            ->assertHasNoErrors();

        Setting::flush();

        $this->assertSame('1', Setting::get('logo_adapt_dark'));
    }

    public function test_disabling_the_option_persists_as_string_zero(): void
    {
        Setting::put('logo_adapt_dark', '1');

        Livewire::test('pages::admin.settings')
            ->set('logoOpts.logo_adapt_dark', false)
            ->call('saveLogoOptions')
            ->assertHasNoErrors();

        Setting::flush();

        $this->assertSame('0', Setting::get('logo_adapt_dark', '0'));
    }

    private function makeLogo(): void
    {
        Media::create([
            'usage' => 'logo', 'disk' => 'public', 'path' => 'media/test/logo.webp',
            'variants' => ['md' => 'media/test/logo-md.webp'], 'width' => 800, 'height' => 800,
        ]);
        Media::forgetLogo();
    }

    public function test_a_light_logo_gets_inverted_on_light_backgrounds(): void
    {
        $this->makeLogo();
        Setting::put('logo_adapt_dark', '1');
        Setting::put('logo_base_color', 'white');

        $this->get('/')->assertSee('brightness-0 dark:brightness-100', false);
    }

    public function test_a_dark_logo_gets_inverted_in_dark_mode(): void
    {
        $this->makeLogo();
        Setting::put('logo_adapt_dark', '1');
        Setting::put('logo_base_color', 'black');

        $this->get('/')->assertSee('dark:brightness-0 dark:invert', false);
    }
}
