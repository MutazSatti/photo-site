<?php

namespace Tests\Feature\Admin;

use App\Models\Faq;
use App\Models\PageBlock;
use App\Models\PageBlockItem;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\FaqSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * لوحة التحكم تملك صفحة الزواجات والمناسبات كاملةً.
 *
 * كل ما يظهر للزائر — نصًّا وترتيبًا وظهورًا وعناصر ومجموعات — يجب أن يُغيَّر من
 * هذه الشاشة بلا مبرمج. وهذا الملف يُثبت ذلك فعلًا لا وصفًا.
 */
class PageContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class]);

        (require database_path('migrations/2026_09_17_000002_build_events_service_page.php'))->up();

        $this->actingAs(User::factory()->create());
    }

    private function block(string $key): PageBlock
    {
        return PageBlock::query()->onPage(PageBlock::EVENTS)->where('key', $key)->firstOrFail();
    }

    public function test_the_screen_lists_the_page_blocks(): void
    {
        Livewire::test('pages::admin.pages')
            ->assertOk()
            ->assertSee('الواجهة')
            ->assertSee('شريط الأرقام')
            ->assertSee('كيف تسير الليلة');
    }

    public function test_block_text_is_edited_and_saved(): void
    {
        $block = $this->block('timeline');

        Livewire::test('pages::admin.pages')
            ->set("blockText.{$block->id}.title", 'ترتيب التغطية')
            ->set("blockText.{$block->id}.subtitle", 'أربع مراحل لا أكثر.')
            ->call('saveBlockText', $block->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('page_blocks', [
            'id' => $block->id,
            'title' => 'ترتيب التغطية',
            'subtitle' => 'أربع مراحل لا أكثر.',
        ]);
    }

    /** الفراغ يعني «أعد المبدئي»، فيُحفظ null لا سلسلة فارغة. */
    public function test_an_emptied_field_is_stored_as_null(): void
    {
        $block = $this->block('timeline');
        $block->update(['title' => 'عنوان مؤقّت']);

        Livewire::test('pages::admin.pages')
            ->set("blockText.{$block->id}.title", '   ')
            ->call('saveBlockText', $block->id)
            ->assertHasNoErrors();

        $this->assertNull($block->fresh()->title);
    }

    public function test_a_block_is_hidden_and_shown_again(): void
    {
        $block = $this->block('figures');

        Livewire::test('pages::admin.pages')
            ->call('toggleBlock', $block->id)
            ->assertOk();

        $this->assertFalse($block->fresh()->is_active);

        Livewire::test('pages::admin.pages')->call('toggleBlock', $block->id);

        $this->assertTrue($block->fresh()->is_active);
    }

    /** الواجهة مثبّتة: صفحة بلا مدخل ليست خيارًا يُتاح بزرّ. */
    public function test_the_locked_block_is_neither_hidden_nor_moved(): void
    {
        $hero = $this->block('hero');

        Livewire::test('pages::admin.pages')
            ->call('toggleBlock', $hero->id)
            ->call('moveBlock', $hero->id, 'down');

        $this->assertTrue($hero->fresh()->is_active);
        $this->assertSame(0, $hero->fresh()->sort_order);
    }

    public function test_blocks_are_reordered(): void
    {
        $tracks = $this->block('tracks');
        $timeline = $this->block('timeline');

        Livewire::test('pages::admin.pages')
            ->call('moveBlock', $tracks->id, 'down');

        $this->assertGreaterThan($timeline->fresh()->sort_order, $tracks->fresh()->sort_order);
    }

    public function test_an_item_is_added_edited_and_deleted(): void
    {
        $block = $this->block('figures');

        Livewire::test('pages::admin.pages')
            ->call('startItem', $block->id)
            ->set('newItem.icon', 'drone')
            ->set('newItem.title', 'تصوير جوي')
            ->set('newItem.subtitle', 'ضمن التغطية')
            ->call('addItem')
            ->assertHasNoErrors();

        $item = PageBlockItem::where('title', 'تصوير جوي')->firstOrFail();

        $this->assertSame('drone', $item->icon);
        $this->assertSame($block->id, $item->page_block_id);

        Livewire::test('pages::admin.pages')
            ->set("itemText.{$item->id}.subtitle", 'عند الحاجة')
            ->call('saveItem', $item->id)
            ->assertHasNoErrors();

        $this->assertSame('عند الحاجة', $item->fresh()->subtitle);

        Livewire::test('pages::admin.pages')->call('deleteItem', $item->id);

        $this->assertDatabaseMissing('page_block_items', ['id' => $item->id]);
    }

    public function test_an_unknown_icon_is_refused(): void
    {
        $block = $this->block('figures');

        Livewire::test('pages::admin.pages')
            ->call('startItem', $block->id)
            ->set('newItem.icon', 'skull')
            ->set('newItem.title', 'عنصر')
            ->call('addItem')
            ->assertHasErrors('newItem.icon');
    }

    public function test_a_point_is_added_inside_a_track(): void
    {
        $block = $this->block('tracks');
        $track = $block->items()->orderBy('sort_order')->firstOrFail();

        Livewire::test('pages::admin.pages')
            ->call('startItem', $block->id, $track->id)
            ->set('newItem.title', 'تسليم متّفق عليه')
            ->set('newItem.subtitle', 'يُكتب في الاتفاق لا بعده.')
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('page_block_items', [
            'parent_id' => $track->id,
            'title' => 'تسليم متّفق عليه',
        ]);
    }

    public function test_items_are_reordered_within_their_parent(): void
    {
        $block = $this->block('timeline');
        $items = $block->items()->orderBy('sort_order')->get();

        Livewire::test('pages::admin.pages')
            ->call('moveItem', $items[0]->id, 'down');

        $this->assertGreaterThan($items[1]->fresh()->sort_order, $items[0]->fresh()->sort_order);
    }

    public function test_a_group_is_added_to_a_gallery_and_removed_again(): void
    {
        $block = $this->block('corporate');
        $post = Post::where('slug', 'burtreh-alaris')->firstOrFail();

        Livewire::test('pages::admin.pages')
            ->set("newGroup.{$block->id}", (string) $post->id)
            ->call('addGroup', $block->id)
            ->assertHasNoErrors();

        $item = PageBlockItem::where('page_block_id', $block->id)->where('post_id', $post->id)->firstOrFail();

        Livewire::test('pages::admin.pages')->call('deleteItem', $item->id);

        $this->assertDatabaseMissing('page_block_items', ['id' => $item->id]);

        // إزالة المجموعة من الصفحة لا تمسّ العمل نفسه ولا صوره
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    /**
     * النشر لا يُلغي ما حرّره المالك.
     *
     * البذرة تُشغَّل بعد كل سحب من GitHub وتستدعي هجرة المحتوى معها. ولو كتبت
     * الهجرة نصّها كل مرّة لعاد كل تعديل إلى ما في الشيفرة بلا سبب ظاهر —
     * وهذا نقيض ما وُضعت الشاشة له.
     */
    public function test_reseeding_keeps_what_the_owner_changed(): void
    {
        $block = $this->block('cta');
        $post = Post::where('slug', 'burtreh-alaris')->firstOrFail();
        $faq = Faq::where('question', 'كيف أحجز؟')->firstOrFail();

        Livewire::test('pages::admin.pages')
            ->set("blockText.{$block->id}.title", 'احجز ليلتك')
            ->call('saveBlockText', $block->id)
            ->call('toggleBlock', $block->id);

        $post->update(['title' => 'بورتريهات العرسان', 'status' => 'draft']);
        $faq->update(['answer' => 'اتصل بنا مباشرة.']);

        (require database_path('migrations/2026_09_17_000002_build_events_service_page.php'))->up();

        $this->assertSame('احجز ليلتك', $block->fresh()->title);
        $this->assertFalse($block->fresh()->is_active);
        $this->assertSame('بورتريهات العرسان', $post->fresh()->title);
        $this->assertSame('draft', $post->fresh()->status);
        $this->assertSame('اتصل بنا مباشرة.', $faq->fresh()->answer);
    }

    public function test_the_screen_is_closed_to_guests(): void
    {
        auth()->logout();

        $this->get('/admin/pages')->assertRedirect('/login');
    }
}
