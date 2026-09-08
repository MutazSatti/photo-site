<?php

namespace Tests\Feature\Site;

use App\Models\Client;
use App\Models\HomeBlock;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\FaqSeeder;
use Database\Seeders\HomeBlockSeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeBlockTextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ذاكرة العناصر ساكنة، فتعبر بين الاختبارات ما لم تُفرَّغ
        HomeBlock::forget();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class, PostSeeder::class, HomeBlockSeeder::class]);

        Testimonial::create([
            'name' => 'عميل للاختبار',
            'content' => 'تغطية منظّمة والصور وصلت في وقتها المتفق عليه.',
            'rating' => 5,
            'is_active' => true,
        ]);

        Client::create(['name' => 'جهة للاختبار', 'is_active' => true]);
    }

    public function test_a_block_falls_back_to_the_heading_written_in_the_code(): void
    {
        $this->assertSame('أعمال مختارة', HomeBlock::for('featured')->heading());

        Livewire::test('pages::home')->assertSee('أعمال مختارة');
    }

    public function test_an_edited_heading_replaces_the_default(): void
    {
        HomeBlock::where('key', 'featured')->update(['title' => 'مختارات من الأرشيف']);
        HomeBlock::forget();

        Livewire::test('pages::home')
            ->assertSee('مختارات من الأرشيف')
            ->assertDontSee('أعمال مختارة');
    }

    /** الفراغ يعني «أعد المبدئي» لا «احذف العنوان» — وإخفاء القسم له زرّه. */
    public function test_clearing_a_heading_restores_the_default(): void
    {
        HomeBlock::where('key', 'featured')->update(['title' => 'مختارات']);
        HomeBlock::forget();
        $this->assertSame('مختارات', HomeBlock::for('featured')->heading());

        HomeBlock::where('key', 'featured')->update(['title' => null]);
        HomeBlock::forget();

        $this->assertSame('أعمال مختارة', HomeBlock::for('featured')->heading());
    }

    public function test_the_city_placeholder_follows_the_site_settings(): void
    {
        config(['site.location.city' => 'الطائف']);
        HomeBlock::forget();

        $this->assertSame('خدمات التصوير في الطائف', HomeBlock::for('services')->heading());
    }

    /** شريط الدعوة يظهر في كل صفحة، فنصّه يُحرَّر مرة ويسري عليها كلها. */
    public function test_the_call_to_action_reads_its_copy_from_the_block(): void
    {
        HomeBlock::where('key', 'cta')->update(['title' => 'احجز موعدك الآن']);
        HomeBlock::forget();

        $this->get(route('portfolio'))->assertSee('احجز موعدك الآن', false);
    }

    public function test_the_owner_can_edit_a_block_heading_from_the_panel(): void
    {
        $this->actingAs(User::factory()->create());

        $block = HomeBlock::where('key', 'reading')->firstOrFail();

        Livewire::test('pages::admin.settings')
            ->set('blockText.'.$block->id.'.title', 'مكتبة المصوّر')
            ->set('blockText.'.$block->id.'.subtitle', '')
            ->call('saveBlockText', $block->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('home_blocks', ['key' => 'reading', 'title' => 'مكتبة المصوّر', 'subtitle' => null]);

        HomeBlock::forget();
        $this->assertSame('مكتبة المصوّر', HomeBlock::for('reading')->heading());

        // المقدّمة فُرّغت، فعادت إلى نصّها الأصلي لا إلى فراغ
        $this->assertSame('مقالات معمّقة ومنشورات تعليمية قصيرة — خلاصة تجربة ميدانية.', HomeBlock::for('reading')->intro());
    }

    public function test_an_overlong_heading_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $block = HomeBlock::where('key', 'reading')->firstOrFail();

        Livewire::test('pages::admin.settings')
            ->set('blockText.'.$block->id.'.title', str_repeat('ا', 200))
            ->call('saveBlockText', $block->id)
            ->assertHasErrors('blockText.'.$block->id.'.title');

        $this->assertDatabaseHas('home_blocks', ['key' => 'reading', 'title' => null]);
    }

    /** تثبيت جديد لم تُشغَّل بذوره يجب أن يعرض صفحة كاملة لا بيضاء. */
    public function test_an_empty_block_table_still_renders_every_section(): void
    {
        HomeBlock::query()->delete();
        HomeBlock::forget();

        $blocks = HomeBlock::visible();

        $this->assertCount(count(HomeBlock::definitions()), $blocks);
        $this->assertSame('أقسام المعرض', $blocks->firstWhere('key', 'sections')->heading());
        $this->assertFalse($blocks->first()->exists, 'النماذج المبنيّة من التعريفات لا تُحفظ.');
    }
}
