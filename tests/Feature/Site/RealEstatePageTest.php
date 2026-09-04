<?php

namespace Tests\Feature\Site;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use Database\Seeders\FaqSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealEstatePageTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class]);

        $this->category = Category::query()
            ->whereRelation('section', 'slug', Section::SERVICES)
            ->where('slug', Category::REAL_ESTATE)
            ->firstOrFail();
    }

    /** مجموعة صور تحت التصوير العقاري — العمل وصوره معًا. */
    private function group(string $title, string $slug, int $photos, int $order = 1): Post
    {
        $post = Post::create([
            'section_id' => $this->category->section_id,
            'category_id' => $this->category->id,
            'slug' => $slug,
            'title' => $title,
            'excerpt' => "وصف {$title}",
            'status' => 'published',
            'published_at' => now(),
            'sort_order' => $order,
        ]);

        for ($i = 1; $i <= $photos; $i++) {
            Media::create([
                'post_id' => $post->id,
                'disk' => 'public',
                'path' => "media/{$slug}-{$i}.webp",
                'variants' => ['thumb' => "media/{$slug}-{$i}-thumb.webp", 'md' => "media/{$slug}-{$i}-md.webp"],
                'width' => 1600,
                'height' => 1067,
                'original_name' => "{$slug}-{$i}.jpg",
                'alt' => "صورة {$title} رقم {$i}",
                'is_cover' => $i === 1,
                'sort_order' => $i,
            ]);
        }

        return $post;
    }

    public function test_the_dedicated_page_answers_the_category_url(): void
    {
        $this->assertSame(route('services.real-estate'), $this->category->url());

        $this->get($this->category->url())
            ->assertOk()
            ->assertSee('التصوير العقاري')
            ->assertSee('ما الذي تصوّره؟')
            ->assertSee('مستثمر إيجار قصير');
    }

    public function test_it_shows_each_group_with_its_photos(): void
    {
        $this->group('المرافق الخارجية واللاند سكيب', 'almrafq-alkharijiya', 3);
        $this->group('المجالس والمعيشة', 'almjals-walmaisha', 2, 2);

        $this->get(route('services.real-estate'))
            ->assertOk()
            ->assertSee('المرافق الخارجية واللاند سكيب')
            ->assertSee('المجالس والمعيشة')
            ->assertSee('5 صورة')
            ->assertSee('صورة المرافق الخارجية واللاند سكيب رقم 1');
    }

    /** مجموعة بلا صور لا تُعرض عنوانًا فوق فراغ. */
    public function test_a_group_without_photos_is_not_rendered(): void
    {
        $this->group('المرافق الخارجية واللاند سكيب', 'almrafq-alkharijiya', 2);
        $this->group('المداخل والممرات', 'almdakhl-walmmrat', 0, 2);

        $this->get(route('services.real-estate'))
            ->assertOk()
            ->assertSee('المرافق الخارجية واللاند سكيب')
            ->assertDontSee('المداخل والممرات');
    }

    public function test_the_service_questions_are_published_as_structured_data(): void
    {
        Faq::create([
            'question' => 'كم صورة تحتاج الشقة؟',
            'answer' => 'الشقة الاعتيادية بين 15 و25 صورة بحسب عدد الغرف والمرافق.',
            'section_id' => $this->category->section_id,
            'category_id' => $this->category->id,
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $this->get(route('services.real-estate'))
            ->assertOk()
            ->assertSee('كم صورة تحتاج الشقة؟')
            ->assertSee('"@type": "FAQPage"', escape: false)
            ->assertSee('"@type": "Question"', escape: false);
    }

    /** أسئلة الأقسام الأخرى لا تتسرّب إلى هذه الصفحة. */
    public function test_it_only_shows_its_own_questions(): void
    {
        Faq::create([
            'question' => 'هل تصوّر حفلات الزفاف؟',
            'answer' => 'نعم، تصوير المناسبات من الخدمات المتاحة طوال أيام الأسبوع.',
            'section_id' => $this->category->section_id,
            'sort_order' => 30,
            'is_active' => true,
        ]);

        $this->get(route('services.real-estate'))
            ->assertOk()
            ->assertDontSee('هل تصوّر حفلات الزفاف؟');
    }

    public function test_it_returns_404_when_the_category_is_hidden(): void
    {
        $this->category->update(['is_active' => false]);

        $this->get(route('services.real-estate'))->assertNotFound();
    }
}
