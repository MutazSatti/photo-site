<?php

namespace Tests\Feature\Site;

use App\Models\PageBlock;
use App\Models\PageBlockItem;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\HomeBlockSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * أرقام الصفحة الرئيسية وشريط الثقة تحتها.
 *
 * كانت أربع بطاقات: رقمُ كلٍّ منها في الإعدادات وعنوانه في القالب — فنصفها
 * يُحرَّر ونصفها لا. وصارت بطاقةً كاملة يملكها صاحب الموقع.
 */
class HomeStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, HomeBlockSeeder::class]);

        (require database_path('migrations/2026_10_06_000001_move_home_stats_into_page_blocks.php'))->up();
    }

    private function block(string $key): PageBlock
    {
        return PageBlock::query()->onPage(PageBlock::HOME)->where('key', $key)->firstOrFail();
    }

    public function test_the_hero_shows_three_cards_and_no_years_card(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('+450 مشروع');
        $response->assertSee('مشاريع تصوير متنوعة');
        $response->assertSee('+180 عميل');
        $response->assertSee('عملاء أفراد وجهات');
        $response->assertSee('+35 ورشة');
        $response->assertSee('ورش وبرامج تدريبية');

        $response->assertDontSee('سنوات خبرة');
    }

    /** ثلاث بطاقات لا أربع، والشبكة تتبع عددها فلا يبقى فراغ. */
    public function test_the_grid_follows_the_number_of_cards(): void
    {
        $this->assertSame(3, $this->block('stats')->items()->count());

        $this->assertStringContainsString('grid-cols-3', (string) $this->get('/')->getContent());
    }

    public function test_the_trust_strip_is_shown_under_the_numbers(): void
    {
        $content = (string) $this->get('/')->assertOk()->getContent();

        foreach (['مصوّر مرخّص', 'مشغّل درون مرخّص', 'مدرّب معتمد'] as $claim) {
            $this->assertStringContainsString($claim, $content);
        }

        // أهدأ من الأرقام: أصغر خطًّا وأخفت لونًا
        $this->assertMatchesRegularExpression('/text-xs text-white\/65/u', $content);

        $numbersAt = mb_strpos($content, '+450 مشروع');
        $trustAt = mb_strpos($content, 'مصوّر مرخّص');

        $this->assertGreaterThan($numbersAt, $trustAt, 'شريط الثقة يأتي بعد الأرقام');
    }

    public function test_the_owner_can_edit_a_card(): void
    {
        $item = $this->block('stats')->items()->orderBy('sort_order')->firstOrFail();
        $item->update(['title' => '+500 مشروع', 'subtitle' => 'تغطيات مكتملة']);

        $this->get('/')
            ->assertOk()
            ->assertSee('+500 مشروع')
            ->assertSee('تغطيات مكتملة')
            ->assertDontSee('+450 مشروع');
    }

    public function test_the_owner_can_remove_a_card_without_leaving_a_gap(): void
    {
        $this->block('stats')->items()->orderBy('sort_order')->firstOrFail()->delete();

        $content = (string) $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('+450 مشروع', $content);
        $this->assertStringContainsString('grid-cols-2', $content);
    }

    public function test_hiding_the_trust_strip_removes_it(): void
    {
        $this->block('trust')->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('مشغّل درون مرخّص');
    }

    /** الرقم لم يعد في الإعدادات، فلا حقل يُملأ ولا يظهر أثره. */
    public function test_the_retired_number_settings_are_gone(): void
    {
        foreach (['stat_projects', 'stat_clients', 'stat_workshops'] as $key) {
            $this->assertDatabaseMissing('settings', ['key' => $key]);
        }

        // وسنوات الخبرة باقية لأن صفحة «نبذة» تعرضها
        $this->assertDatabaseHas('settings', ['key' => 'about_years']);
        $this->get('/about')->assertOk()->assertSee('سنوات الخبرة');
    }

    public function test_reseeding_keeps_an_edited_card(): void
    {
        $item = $this->block('stats')->items()->orderBy('sort_order')->firstOrFail();
        $item->update(['title' => '+600 مشروع']);

        (require database_path('migrations/2026_10_06_000001_move_home_stats_into_page_blocks.php'))->up();

        $this->assertSame('+600 مشروع', $item->fresh()->title);
    }

    /** واللوحة تصل إلى الصفحات الثلاث لا إلى واحدة. */
    public function test_the_dashboard_reaches_every_page(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.pages')
            ->assertSee('الصفحة الرئيسية')
            ->assertSee('الزواجات والمناسبات')
            ->assertSee('الفعاليات والمؤتمرات')
            ->assertSee('أرقام الواجهة')
            ->assertSee('شريط الثقة')
            // الانتقال إلى تبويب آخر يُخرج أقسام الرئيسية من الشاشة
            ->set('page', PageBlock::EVENTS)
            ->assertDontSee('أرقام الواجهة')
            ->assertDontSee('شريط الثقة');
    }

    /** والبطاقة تُضاف من الشاشة نفسها. */
    public function test_a_card_is_added_from_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $stats = $this->block('stats');

        Livewire::test('pages::admin.pages')
            ->call('startItem', $stats->id)
            ->set('newItem.title', '+12 جهة')
            ->set('newItem.subtitle', 'جهات حكومية')
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('page_block_items', [
            'page_block_id' => $stats->id,
            'title' => '+12 جهة',
        ]);

        $this->assertSame(4, PageBlockItem::where('page_block_id', $stats->id)->count());
    }

    protected function tearDown(): void
    {
        Setting::flush();
        PageBlock::forget();

        parent::tearDown();
    }
}
