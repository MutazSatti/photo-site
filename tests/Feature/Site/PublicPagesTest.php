<?php

namespace Tests\Feature\Site;

use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class, PostSeeder::class]);
    }

    public function test_the_home_page_renders_with_the_photographer_identity(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(config('site.owner_name'))
            ->assertSee(config('site.location.city'))
            ->assertSee('أقسام المعرض');
    }

    public function test_every_main_section_has_a_working_page(): void
    {
        foreach (Section::all() as $section) {
            $this->get($section->url())
                ->assertOk()
                ->assertSee($section->name);
        }
    }

    public function test_every_service_category_has_a_working_page(): void
    {
        foreach (Category::all() as $category) {
            $this->get($category->url())
                ->assertOk()
                ->assertSee($category->name);
        }
    }

    public function test_published_posts_are_reachable_at_their_hierarchical_url(): void
    {
        foreach (Post::published()->with(['section', 'category'])->get() as $post) {
            if ($post->url() === route('portfolio')) {
                continue;
            }

            $this->get($post->url())
                ->assertOk()
                ->assertSee($post->title);
        }
    }

    public function test_a_draft_post_returns_not_found(): void
    {
        $post = Post::published()->whereNotNull('category_id')->firstOrFail();
        $url = $post->url();

        $post->update(['status' => 'draft']);

        $this->get($url)->assertNotFound();
    }

    /** الرابط الذي لا يطابق القسم الحقيقي للعنصر يجب ألا يعمل. */
    public function test_a_post_is_not_reachable_through_the_wrong_category(): void
    {
        $post = Post::published()->whereNotNull('category_id')->with('category')->firstOrFail();

        $otherCategory = Category::where('section_id', $post->category->section_id)
            ->whereKeyNot($post->category_id)
            ->firstOrFail();

        $this->get(route('work.show', [
            'section' => Section::SERVICES,
            'category' => $otherCategory->slug,
            'post' => $post->slug,
        ]))->assertNotFound();
    }

    public function test_pages_carry_structured_data_and_canonical_tags(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('application/ld+json', escape: false)
            ->assertSee('"@type": "Person"', escape: false)
            ->assertSee('"@type": "FAQPage"', escape: false)
            ->assertSee('<link rel="canonical"', escape: false)
            ->assertSee('property="og:image"', escape: false);
    }

    public function test_a_workshop_publishes_course_structured_data(): void
    {
        $workshop = Post::published()->inSection(Section::WORKSHOPS)->firstOrFail();

        $this->get($workshop->url())
            ->assertOk()
            ->assertSee('"@type": "Course"', escape: false)
            ->assertSee('"@type": "CourseInstance"', escape: false);
    }

    public function test_the_pages_are_arabic_and_right_to_left(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', escape: false);
    }

    public function test_navigation_links_use_livewire_navigate(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('wire:navigate', escape: false);
    }

    /** الاعتمادات تُعرض للزائر وتُنشر كبيانات مهيكلة من المرجع نفسه. */
    public function test_the_about_page_shows_the_official_accreditations(): void
    {
        $response = $this->get(route('about'))->assertOk();

        $response->assertSee('اعتمادات وتراخيص')
            ->assertSee('"@type": "EducationalOccupationalCredential"', escape: false)
            ->assertSee('"@type": "GovernmentOrganization"', escape: false);

        foreach (accreditations() as $accreditation) {
            $response->assertSee($accreditation['title'])
                ->assertSee($accreditation['authority'])
                ->assertSee($accreditation['number']);
        }
    }

    /**
     * لكل ترخيص جملة تامة تقرأ وحدها، معروضةً ومنشورة معًا.
     *
     * الأداة تقتبس جملة لا جدولًا، والجملة التي تُنشر في البيانات المهيكلة ولا
     * يراها الزائر نصٌّ مخفيّ — فيجب أن تكون الجملة نفسها في الموضعين.
     */
    public function test_each_credential_carries_a_quotable_sentence(): void
    {
        $response = $this->get(route('about'))->assertOk();

        $owner = (string) config('site.owner_name');

        foreach (accreditations() as $accreditation) {
            $sentence = $accreditation['description'];

            $this->assertStringStartsWith($owner, $sentence);
            $this->assertStringContainsString($accreditation['authority'], $sentence);
            $this->assertStringContainsString($accreditation['number'], $sentence);

            $response->assertSee($sentence)
                ->assertSee(json_encode($sentence, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), escape: false);
        }
    }

    /**
     * أسماء الجهات المنظِّمة كما تكتبها هي، لا كما تُختصر في الحديث.
     *
     * الاختصار الشائع يقلب ترتيب «التعليم والتدريب» ويُسقط «العامة» من اسم
     * هيئة الإعلام. والاسم الخطأ في صفحة اعتمادات يُضعف ما جاءت لتُثبته.
     */
    public function test_the_regulator_names_match_their_official_form(): void
    {
        $names = array_column(config('site.accreditations'), 'authority');

        $this->assertSame([
            'هيئة تقويم التعليم والتدريب',
            'الهيئة العامة للطيران المدني',
            'الهيئة العامة لتنظيم الإعلام',
        ], $names);
    }
}
