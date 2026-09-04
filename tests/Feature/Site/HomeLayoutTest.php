<?php

namespace Tests\Feature\Site;

use App\Models\Client;
use App\Models\HomeBlock;
use App\Models\Section;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\SectionRoutes;
use Database\Seeders\FaqSeeder;
use Database\Seeders\HomeBlockSeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HomeLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class, PostSeeder::class, HomeBlockSeeder::class]);

        // عنصر آراء العملاء لا يُصيَّر أصلًا بلا رأي منشور
        Testimonial::create([
            'name' => 'عميل للاختبار',
            'content' => 'تغطية منظّمة والصور وصلت في وقتها المتفق عليه.',
            'rating' => 5,
            'is_active' => true,
        ]);

        // وكذلك عنصر الجهات لا يُصيَّر بلا جهة ظاهرة
        Client::create(['name' => 'جهة للاختبار', 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * ترتيب العناصر كما ظهرت فعلًا في الصفحة.
     *
     * القراءة من سمة data-block لا من العناوين: نصوص العناوين تتكرّر في شريط
     * التنقّل والتذييل، فالمطابقة عليها تعطي نتائج كاذبة.
     *
     * @return array<int, string>
     */
    private function renderedBlocks(): array
    {
        preg_match_all('/data-block="([a-z]+)"/', $this->get(route('home'))->getContent(), $matches);

        return $matches[1];
    }

    public function test_the_home_page_renders_blocks_in_the_stored_order(): void
    {
        $this->assertSame(
            HomeBlock::query()->active()->ordered()->pluck('key')->all(),
            $this->renderedBlocks(),
        );
    }

    public function test_reordering_blocks_changes_the_page(): void
    {
        $before = $this->renderedBlocks();
        $this->assertLessThan(array_search('faq', $before, true), array_search('testimonials', $before, true));

        $testimonials = HomeBlock::where('key', 'testimonials')->firstOrFail();
        $faq = HomeBlock::where('key', 'faq')->firstOrFail();

        $order = $testimonials->sort_order;
        $testimonials->update(['sort_order' => $faq->sort_order]);
        $faq->update(['sort_order' => $order]);

        $after = $this->renderedBlocks();

        $this->assertLessThan(
            array_search('testimonials', $after, true),
            array_search('faq', $after, true),
            'بعد التبديل يسبق عنصر الأسئلة الشائعة عنصر آراء العملاء.',
        );
    }

    public function test_hiding_a_block_removes_it_from_the_page(): void
    {
        $this->assertContains('faq', $this->renderedBlocks());

        HomeBlock::where('key', 'faq')->update(['is_active' => false]);

        $this->assertNotContains('faq', $this->renderedBlocks());
    }

    public function test_the_admin_can_move_a_block(): void
    {
        $this->actingAs($this->admin());

        $faq = HomeBlock::where('key', 'faq')->firstOrFail();
        $before = $faq->sort_order;

        Livewire::test('pages::admin.settings')
            ->set('tab', 'home')
            ->call('moveBlock', $faq->id, 'up');

        $this->assertLessThan($before, $faq->refresh()->sort_order);
    }

    /** الواجهة أوّل ما يراه الزائر — إتاحة نقلها أو إخفائها تُنتج صفحة بلا مدخل. */
    public function test_the_locked_hero_block_cannot_be_moved_or_hidden(): void
    {
        $this->actingAs($this->admin());

        $hero = HomeBlock::where('key', 'hero')->firstOrFail();

        Livewire::test('pages::admin.settings')
            ->set('tab', 'home')
            ->call('moveBlock', $hero->id, 'down')
            ->call('toggleBlock', $hero->id);

        $hero->refresh();

        $this->assertSame(0, $hero->sort_order);
        $this->assertTrue($hero->is_active);
    }

    public function test_the_block_after_a_locked_one_cannot_swap_into_its_place(): void
    {
        $this->actingAs($this->admin());

        $sections = HomeBlock::where('key', 'sections')->firstOrFail();
        $before = $sections->sort_order;

        Livewire::test('pages::admin.settings')
            ->set('tab', 'home')
            ->call('moveBlock', $sections->id, 'up');

        $this->assertSame($before, $sections->refresh()->sort_order);
    }

    /** المخفي لا يُستعلم عنه أصلًا — هذا ما يجعل الإخفاء يسرّع الصفحة. */
    public function test_a_hidden_block_runs_no_queries(): void
    {
        HomeBlock::query()->where('key', '!=', 'hero')->update(['is_active' => false]);

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('home'))->assertOk();
        $lean = $queries;

        HomeBlock::query()->update(['is_active' => true]);

        $queries = 0;
        $this->get(route('home'))->assertOk();

        $this->assertGreaterThan($lean, $queries, 'إظهار كل العناصر ينفّذ استعلامات أكثر من إظهار الواجهة وحدها.');
    }

    public function test_each_section_publishes_its_own_colour(): void
    {
        $html = $this->get(route('home'))->getContent();

        foreach (Section::query()->active()->get() as $section) {
            $this->assertStringContainsString($section->colorStyle(), $html);
        }
    }

    public function test_the_section_page_carries_the_section_colour(): void
    {
        $section = Section::where('slug', Section::WORKSHOPS)->firstOrFail();
        $section->update(['color' => 'rose']);

        $this->get($section->url())
            ->assertOk()
            ->assertSee('--sec:'.config('site.section_colors.rose.light'), escape: false);
    }

    public function test_the_admin_rejects_a_colour_outside_the_palette(): void
    {
        $this->actingAs($this->admin());

        $section = Section::firstOrFail();

        Livewire::test('pages::admin.sections')
            ->call('editSection', $section->id)
            ->set('color', 'neon-pink')
            ->call('save')
            ->assertHasErrors('color');
    }

    public function test_the_admin_can_reorder_sections(): void
    {
        $this->actingAs($this->admin());

        $ordered = Section::query()->ordered()->get();
        $second = $ordered[1];

        Livewire::test('pages::admin.sections')
            ->call('moveSection', $second->id, 'up');

        $this->assertSame(0, $second->refresh()->sort_order);
        $this->assertSame(1, $ordered[0]->refresh()->sort_order);
    }

    public function test_a_new_section_appears_on_the_site_immediately(): void
    {
        $this->actingAs($this->admin());

        Livewire::test('pages::admin.sections')
            ->call('newSection')
            ->set('name', 'تصوير المنتجات')
            ->set('name_en', 'Products')
            ->set('slug', 'products')
            ->set('icon', 'camera')
            ->set('color', 'sky')
            ->set('tagline', 'صور تُظهر المنتج كما هو')
            ->call('save')
            ->assertHasNoErrors();

        $section = Section::where('slug', 'products')->firstOrFail();

        $this->assertSame('sky', $section->color);
        $this->get(route('home'))->assertSee('تصوير المنتجات');

        // رابط القسم يعمل متى قبله نمط {section}. النمط يُبنى عند تسجيل المسارات،
        // أي مرة واحدة لكل إقلاع؛ في الإنتاج يقلع التطبيق مع كل طلب فيلتقط القسم
        // فورًا، أما هنا فالتطبيق واحد طوال الاختبار. لذلك يُفحص النمط نفسه بدل
        // طلب HTTP — وهو الموضع الذي يقرّر فعلًا نجاح الرابط أو فشله.
        $this->assertStringContainsString('products', SectionRoutes::patterns()['sections']);
    }
}
