<?php

namespace Tests\Feature\Site;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrawlerFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class, PostSeeder::class]);
    }

    public function test_the_sitemap_lists_every_published_page(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', escape: false)
            ->assertSee(route('portfolio'), escape: false)
            ->assertSee(route('faq'), escape: false);

        foreach (Post::published()->with(['section', 'category'])->get() as $post) {
            if ($post->url() !== route('portfolio')) {
                $response->assertSee($post->url(), escape: false);
            }
        }
    }

    public function test_the_rss_feed_is_valid_and_arabic(): void
    {
        $this->get(route('feed'))
            ->assertOk()
            ->assertSee('<rss', escape: false)
            ->assertSee('<language>ar</language>', escape: false)
            ->assertSee(config('site.owner_name'), escape: false);
    }

    /** الملف الذي تقرؤه أدوات الذكاء الاصطناعي — يجب أن يحمل الهوية والتواصل والأسئلة. */
    public function test_the_llms_file_summarises_the_business(): void
    {
        $response = $this->get(route('llms'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee(config('site.owner_name'))
            ->assertSee(config('site.location.city'))
            ->assertSee(config('site.phone_local'))
            ->assertSee('## الخدمات')
            ->assertSee('## أسئلة وأجوبة');
    }

    public function test_robots_allows_ai_crawlers_and_blocks_the_admin_panel(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk()
            ->assertSee('User-agent: GPTBot')
            ->assertSee('User-agent: ClaudeBot')
            ->assertSee('User-agent: PerplexityBot')
            ->assertSee('User-agent: Google-Extended')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_the_admin_panel_is_marked_noindex(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', escape: false);
    }
}
