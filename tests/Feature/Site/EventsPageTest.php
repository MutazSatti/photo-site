<?php

namespace Tests\Feature\Site;

use App\Models\Media;
use App\Models\PageBlock;
use App\Models\Post;
use Database\Seeders\FaqSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحة الزواجات والمناسبات تُبنى من القاعدة لا من القالب.
 *
 * ما يثبته هذا الملف هو الوعد نفسه: أن المالك يملك الصفحة. نصُّها يتغيّر من
 * اللوحة، وترتيب أقسامها كذلك، وإخفاء قسم يُخفيه فعلًا من الصفحة المنشورة.
 */
class EventsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class]);

        // هجرة المحتوى انسحبت وقت الترحيل لغياب الأقسام، فتُستدعى بعد بذرها
        (require database_path('migrations/2026_09_17_000002_build_events_service_page.php'))->up();
    }

    /** يعلّق صورًا بمجموعة حتى تُعرض — المجموعة بلا صور لا تظهر. */
    private function fill(string $slug, int $photos = 2): Post
    {
        $post = Post::where('slug', $slug)->firstOrFail();

        for ($i = 1; $i <= $photos; $i++) {
            Media::create([
                'post_id' => $post->id,
                'disk' => 'public',
                'path' => "media/{$slug}-{$i}.webp",
                'variants' => ['thumb' => "media/{$slug}-{$i}-thumb.webp", 'md' => "media/{$slug}-{$i}-md.webp"],
                'width' => 1600,
                'height' => 2844,
                'original_name' => "{$slug}-{$i}.jpg",
                'alt' => "صورة {$slug} رقم {$i}",
                'is_cover' => $i === 1,
                'sort_order' => $i,
            ]);
        }

        return $post;
    }

    public function test_the_page_renders_with_its_default_text(): void
    {
        $this->fill('burtreh-alaris');

        $this->get('/services/events')
            ->assertOk()
            ->assertSee('الزواجات والمناسبات')
            ->assertSee('ما المناسبة؟')
            ->assertSee('كيف تسير الليلة')
            ->assertSee('البورتريه');
    }

    public function test_the_coverage_is_the_mens_section_only(): void
    {
        $response = $this->get('/services/events')->assertOk();

        // لا كوشة ولا خروج في تغطية القسم الرجالي
        $response->assertDontSee('الكوشة');
        $response->assertDontSee('الخروج');
    }

    public function test_edited_text_replaces_the_default(): void
    {
        PageBlock::query()
            ->onPage(PageBlock::EVENTS)
            ->where('key', 'timeline')
            ->update(['title' => 'ترتيب التغطية']);

        $this->get('/services/events')
            ->assertOk()
            ->assertSee('ترتيب التغطية')
            ->assertDontSee('كيف تسير الليلة');
    }

    public function test_emptying_a_field_restores_its_default(): void
    {
        $block = PageBlock::query()->onPage(PageBlock::EVENTS)->where('key', 'timeline')->firstOrFail();

        $block->update(['title' => 'عنوان مؤقّت']);
        $block->update(['title' => null]);

        $this->get('/services/events')
            ->assertOk()
            ->assertSee('كيف تسير الليلة');
    }

    public function test_hiding_a_block_removes_it_from_the_page(): void
    {
        PageBlock::query()
            ->onPage(PageBlock::EVENTS)
            ->where('key', 'timeline')
            ->update(['is_active' => false]);

        $this->get('/services/events')
            ->assertOk()
            ->assertDontSee('كيف تسير الليلة');
    }

    public function test_block_order_decides_the_order_on_the_page(): void
    {
        $content = $this->get('/services/events')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, 'كيف تسير الليلة'),
            strpos($content, 'ما المناسبة؟'),
            'المبدّل يسبق المراحل في الترتيب المبدئي',
        );

        PageBlock::query()->onPage(PageBlock::EVENTS)->where('key', 'tracks')->update(['sort_order' => 30]);

        $content = $this->get('/services/events')->assertOk()->getContent();

        $this->assertGreaterThan(
            strpos($content, 'كيف تسير الليلة'),
            strpos($content, 'ما المناسبة؟'),
            'بعد النقل يجب أن يتبع المبدّل المراحل',
        );
    }

    public function test_only_the_chosen_groups_appear_in_a_gallery(): void
    {
        $this->fill('burtreh-alaris');
        $shown = $this->fill('sayarat-alaris');

        // مجموعة منشورة بصور لكنها ليست من عناصر القسم — لا تظهر
        $hidden = Post::create([
            'section_id' => $shown->section_id,
            'category_id' => $shown->category_id,
            'slug' => 'majmua-gher-mudrija',
            'title' => 'مجموعة غير مدرجة',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Media::create([
            'post_id' => $hidden->id,
            'disk' => 'public',
            'path' => 'media/x.webp',
            'variants' => ['md' => 'media/x-md.webp'],
            'width' => 1600,
            'height' => 2844,
            'original_name' => 'x.jpg',
            'alt' => 'صورة',
            'is_cover' => true,
        ]);

        $this->get('/services/events')
            ->assertOk()
            ->assertSee('البورتريه')
            ->assertSee('السيارة')
            ->assertDontSee('مجموعة غير مدرجة');
    }

    public function test_a_group_without_photos_is_not_shown_as_an_empty_frame(): void
    {
        $this->get('/services/events')
            ->assertOk()
            ->assertDontSee('البخور والضيافة');
    }

    public function test_the_questions_are_published_as_structured_data(): void
    {
        $this->get('/services/events')
            ->assertOk()
            ->assertSee('"@type": "FAQPage"', false)
            ->assertSee('ما المعدات المستخدمة في التغطية؟');
    }
}
