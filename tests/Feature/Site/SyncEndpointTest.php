<?php

namespace Tests\Feature\Site;

use App\Models\Post;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class, PostSeeder::class]);
    }

    public function test_the_manifest_is_small_and_carries_a_version(): void
    {
        $response = $this->getJson(route('sync.manifest'));

        $response->assertOk()
            ->assertJsonStructure(['version', 'counts', 'generated_at']);

        $this->assertLessThan(1024, strlen($response->getContent()));
    }

    public function test_the_payload_contains_every_content_store(): void
    {
        $this->getJson(route('sync.data'))
            ->assertOk()
            ->assertJsonStructure([
                'manifest',
                'sections' => [['id', 'slug', 'name', 'url']],
                'categories' => [['id', 'slug', 'name', 'section_slug', 'url']],
                'posts' => [['id', 'slug', 'title', 'body_text', 'section_slug', 'url']],
                'settings',
                'faqs' => [['id', 'question', 'answer']],
                'testimonials',
            ]);
    }

    /** الحمولة نصوص وأرقام فقط — لا محتوى صور أو فيديو مضمّن. */
    public function test_the_payload_carries_no_binary_media(): void
    {
        $body = $this->getJson(route('sync.data'))->getContent();

        $this->assertStringNotContainsString('data:image', $body);
        $this->assertStringNotContainsString('base64', $body);
    }

    public function test_draft_posts_are_excluded_from_the_payload(): void
    {
        $post = Post::published()->firstOrFail();
        $post->update(['status' => 'draft']);

        $slugs = collect($this->getJson(route('sync.data'))->json('posts'))->pluck('slug');

        $this->assertNotContains($post->slug, $slugs);
    }

    /** تغيّر المحتوى يجب أن يغيّر البصمة، وإلا لن يعرف المتصفح أن عليه إعادة السحب. */
    public function test_the_version_changes_when_content_changes(): void
    {
        $before = $this->getJson(route('sync.manifest'))->json('version');

        $this->travel(1)->second();
        cache()->flush();
        Post::published()->firstOrFail()->update(['title' => 'عنوان معدَّل للاختبار']);

        $after = $this->getJson(route('sync.manifest'))->json('version');

        $this->assertNotSame($before, $after);
    }
}
