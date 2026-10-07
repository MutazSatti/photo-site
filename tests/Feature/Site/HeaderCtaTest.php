<?php

namespace Tests\Feature\Site;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\HomeBlockSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * زرّ الحجز في الترويسة.
 *
 * هو الزرّ الذي يراه الزائر في كل صفحة، وكان وحده مكتوبًا في القالب بينما زرّ
 * الواجهة يُحرَّر — فصار المالك يملك الأقلّ ظهورًا ولا يملك الأكثر.
 */
class HeaderCtaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, HomeBlockSeeder::class]);

        (require database_path('migrations/2026_10_07_000001_add_header_cta_setting.php'))->up();
    }

    public function test_the_header_button_reads_its_text_from_the_setting(): void
    {
        $this->get('/')->assertOk()->assertSee('احجز موعد');
    }

    /** وتغييره يسري على كل صفحة لأن الترويسة واحدة. */
    public function test_changing_it_changes_every_page(): void
    {
        Setting::put('header_cta', 'احجز تصويرك');

        foreach (['/', '/about', '/contact', '/faq'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('احجز تصويرك')
                ->assertDontSee('>احجز موعد<', false);
        }
    }

    /** والفراغ يعيد النصّ الأصلي، فلا يبقى الزرّ بلا كلمة. */
    public function test_emptying_it_restores_the_shipped_text(): void
    {
        Setting::put('header_cta', '');

        $this->get('/')->assertOk()->assertSee('احجز موعد');
    }

    public function test_the_field_is_offered_in_the_contact_tab(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.settings')
            ->set('tab', 'contact')
            ->assertSee('نصّ زرّ الحجز في الترويسة')
            ->set('values.header_cta', 'احجز الآن')
            ->call('save')
            ->assertHasNoErrors();

        Setting::flush();

        $this->assertSame('احجز الآن', Setting::get('header_cta'));
        $this->get('/')->assertOk()->assertSee('احجز الآن');
    }

    /** واسما الحقلين لا يتشابهان، فلا يُحرَّر أحدهما مكان الآخر. */
    public function test_the_two_booking_fields_are_told_apart(): void
    {
        $labels = Setting::whereIn('key', ['header_cta', 'hero_cta'])->pluck('label', 'key');

        $this->assertNotSame($labels['header_cta'], $labels['hero_cta']);
        $this->assertStringContainsString('الترويسة', (string) $labels['header_cta']);
        $this->assertStringContainsString('الواجهة', (string) $labels['hero_cta']);
    }

    protected function tearDown(): void
    {
        Setting::flush();

        parent::tearDown();
    }
}
