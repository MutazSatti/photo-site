<?php

namespace Tests\Feature\Site;

use App\Models\Category;
use App\Models\Media;
use App\Models\PageBlock;
use App\Models\Post;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحة تصوير الفعاليات والمؤتمرات.
 *
 * غرضها أن تكون هي ما يفهمه محرّك البحث عن الخدمة، لا الصفحة الرئيسية ولا صفحة
 * الخدمات العامة. وذلك يقوم على عنوانٍ يسمّي الخدمة، وبنية عناوين فرعية تجيب
 * أسئلة الباحث بترتيبها، ونصٍّ يملكه صاحب الموقع لا القالب.
 */
class ActivitiesPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class]);

        (require database_path('migrations/2026_10_04_000001_build_activities_service_page.php'))->up();
    }

    private function category(): Category
    {
        return Category::where('slug', Category::ACTIVITIES)->firstOrFail();
    }

    /** مجموعة أعمال بصورها، ومُدرجة في قسم المعرض. */
    private function group(string $slug, string $title, int $photos = 2): Post
    {
        $category = $this->category();

        $post = Post::create([
            'section_id' => $category->section_id,
            'category_id' => $category->id,
            'slug' => $slug,
            'title' => $title,
            'excerpt' => "وصف {$title}",
            'location' => 'جدة',
            'status' => 'published',
            'published_at' => now(),
        ]);

        for ($i = 1; $i <= $photos; $i++) {
            Media::create([
                'post_id' => $post->id,
                'disk' => 'public',
                'path' => "media/{$slug}-{$i}.webp",
                'variants' => ['md' => "media/{$slug}-{$i}-md.webp"],
                'width' => 1600,
                'height' => 1067,
                'original_name' => "{$slug}-{$i}.jpg",
                'alt' => "صورة {$title} رقم {$i}",
                'is_cover' => $i === 1,
                'sort_order' => $i,
            ]);
        }

        $block = PageBlock::query()->onPage(PageBlock::ACTIVITIES)->where('key', 'works')->firstOrFail();
        $block->allItems()->create(['post_id' => $post->id, 'sort_order' => 99, 'is_active' => true]);

        return $post;
    }

    public function test_the_heading_names_the_service_not_the_category(): void
    {
        $response = $this->get('/services/activities')->assertOk();

        $content = $response->getContent();

        $this->assertSame(1, substr_count((string) $content, '<h1'), 'عنوان رئيسي واحد لا أكثر');
        $this->assertStringContainsString('تصوير الفعاليات والمؤتمرات في جدة', (string) $content);

        // العنوان القديم كان اسم التصنيف لا اسم الخدمة
        $this->assertDoesNotMatchRegularExpression('/<h1[^>]*>\s*الفعاليات في جدة\s*</u', (string) $content);
    }

    public function test_the_page_answers_the_questions_in_order(): void
    {
        // قسم الأعمال لا يُعرض إطارًا فارغًا، فيحتاج مجموعةً ليظهر عنوانه
        $this->group('mutamar-tartib', 'تغطية مؤتمر');

        $content = (string) $this->get('/services/activities')->assertOk()->getContent();

        $headings = [
            'خدمات تصوير الفعاليات والمؤتمرات',
            'ما الذي تشمله التغطية؟',
            'التسليم السريع أثناء الفعالية',
            'أعمال سابقة في تصوير الفعاليات',
            'لماذا تختار معتز ساتي لتصوير فعاليتك في جدة؟',
            'اطلب عرض سعر لتصوير فعاليتك',
        ];

        $previous = -1;

        foreach ($headings as $heading) {
            $at = mb_strpos($content, $heading);

            $this->assertNotFalse($at, "العنوان الفرعي غائب: {$heading}");
            $this->assertGreaterThan($previous, $at, "العنوان الفرعي خارج ترتيبه: {$heading}");
            $previous = $at;
        }
    }

    public function test_the_title_and_description_fit_a_search_result(): void
    {
        $content = (string) $this->get('/services/activities')->assertOk()->getContent();

        preg_match('/<title>(.*?)<\/title>/su', $content, $title);
        preg_match('/<meta name="description" content="(.*?)"/su', $content, $description);

        $this->assertStringStartsWith('تصوير الفعاليات والمؤتمرات في جدة', trim($title[1]));
        $this->assertLessThanOrEqual(60, mb_strlen(trim($title[1])));

        $this->assertLessThanOrEqual(160, mb_strlen(trim($description[1])));
        $this->assertStringContainsString('مؤتمرات', $description[1]);
        $this->assertStringContainsString('تسليم سريع', $description[1]);
    }

    public function test_the_canonical_points_at_this_page_and_it_is_indexable(): void
    {
        $content = (string) $this->get('/services/activities')->assertOk()->getContent();

        $this->assertStringContainsString('rel="canonical" href="'.url('/services/activities').'"', $content);
        $this->assertStringNotContainsString('noindex', $content);
    }

    public function test_the_works_section_shows_each_project_with_its_details(): void
    {
        $this->group('mutamar-test', 'تغطية مؤتمر تجريبي');

        $this->get('/services/activities')
            ->assertOk()
            ->assertSee('تغطية مؤتمر تجريبي')
            ->assertSee('وصف تغطية مؤتمر تجريبي')
            ->assertSee('جدة')
            ->assertSee('/services/activities/mutamar-test', false);
    }

    /** العنوان يُحرَّر من اللوحة، فهو صفٌّ في القاعدة لا نصٌّ في القالب. */
    public function test_the_owner_can_change_the_heading(): void
    {
        PageBlock::query()
            ->onPage(PageBlock::ACTIVITIES)
            ->where('key', 'hero')
            ->update(['title' => 'تصوير المؤتمرات والمعارض في جدة']);

        $content = (string) $this->get('/services/activities')->assertOk()->getContent();

        preg_match('/<h1[^>]*>(.*?)<\/h1>/su', $content, $h1);

        $this->assertSame('تصوير المؤتمرات والمعارض في جدة', trim(strip_tags($h1[1])));
    }

    public function test_hiding_a_block_removes_it_from_the_page(): void
    {
        PageBlock::query()
            ->onPage(PageBlock::ACTIVITIES)
            ->where('key', 'delivery')
            ->update(['is_active' => false]);

        $this->get('/services/activities')
            ->assertOk()
            ->assertDontSee('التسليم السريع أثناء الفعالية');
    }

    /** صفحة العمل تعود إلى القسم بنصٍّ يصف الخدمة، ولا يتكرّر حرفيًا في كلّ عمل. */
    public function test_each_project_links_back_with_descriptive_text(): void
    {
        $first = $this->group('mashru-awwal', 'مشروع أول');
        $second = $this->group('mashru-thani', 'مشروع ثانٍ');

        $anchors = [];

        foreach ([$first, $second] as $post) {
            $content = (string) $this->get($post->url())->assertOk()->getContent();

            $this->assertStringContainsString('/services/activities', $content);

            preg_match('/هذا العمل ضمن\s*<a[^>]*>(.*?)<\/a>/su', $content, $m);

            $this->assertNotEmpty($m, 'لا رابط عائد في صفحة العمل');
            $anchors[] = trim($m[1]);
        }

        foreach ($anchors as $anchor) {
            $this->assertNotSame('الفعاليات', $anchor, 'نصّ الرابط لا يصف الخدمة');
            $this->assertGreaterThan(12, mb_strlen($anchor), 'نصّ الرابط أقصر من أن يصف شيئًا');
        }

        $this->assertNotSame($anchors[0], $anchors[1], 'النصّ نفسه تكرّر في عملين');
    }

    /** والرئيسية وصفحة الخدمات تصلان إليها بنصٍّ وصفي لا باسم التصنيف. */
    public function test_the_home_and_services_pages_link_with_a_descriptive_text(): void
    {
        $this->group('mashru-lilrabt', 'مشروع للربط');

        foreach (['/', '/services'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('تصوير الفعاليات والمؤتمرات في جدة')
                ->assertSee('/services/activities', false);
        }
    }

    public function test_reseeding_keeps_what_the_owner_changed(): void
    {
        $block = PageBlock::query()->onPage(PageBlock::ACTIVITIES)->where('key', 'cta')->firstOrFail();
        $block->update(['title' => 'اطلب عرضك الآن']);

        (require database_path('migrations/2026_10_04_000001_build_activities_service_page.php'))->up();

        $this->assertSame('اطلب عرضك الآن', $block->fresh()->title);
    }
}
