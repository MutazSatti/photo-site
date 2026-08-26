<?php

namespace Tests\Feature\Site;

use App\Models\Media;
use App\Models\Post;
use App\Support\Schema;
use Database\Seeders\PostSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, PostSeeder::class]);
    }

    private function postWithImage(): Post
    {
        // لا factory للمقالات في هذا المشروع — نأخذ عنصرًا مبذورًا ونُلحق به صورة.
        $post = Post::query()->firstOrFail();

        Media::create([
            'post_id' => $post->id,
            'disk' => 'public',
            'path' => 'media/test/shot.webp',
            'variants' => ['lg' => 'media/test/shot-lg.webp', 'thumb' => 'media/test/shot-thumb.webp'],
            'width' => 1600,
            'height' => 1067,
            'alt' => 'لقطة اختبار',
            'is_cover' => true,
        ]);

        return $post->fresh('media');
    }

    /**
     * Search Console يبلّغ عن غياب acquireLicensePage تحت
     * "البيانات الوصفية للصور"، وهو حقل يدلّ على مكان طلب الترخيص.
     */
    public function test_every_image_object_carries_both_license_fields(): void
    {
        $gallery = Schema::imageGallery($this->postWithImage());

        $this->assertNotNull($gallery);

        foreach ($gallery['associatedMedia'] as $image) {
            $this->assertArrayHasKey('license', $image);
            $this->assertArrayHasKey('acquireLicensePage', $image, 'الحقل الذي تطلبه Google للصور.');
            $this->assertSame(route('contact'), $image['acquireLicensePage']);
        }
    }

    public function test_image_objects_keep_their_attribution_fields(): void
    {
        $gallery = Schema::imageGallery($this->postWithImage());

        foreach ($gallery['associatedMedia'] as $image) {
            foreach (['contentUrl', 'creator', 'copyrightHolder', 'creditText', 'width', 'height'] as $field) {
                $this->assertArrayHasKey($field, $image, "الحقل {$field} غائب.");
            }
        }
    }

    /**
     * الصفحة الرئيسية تمرّر صورة الواجهة، وهي أكثر صفحة تُشارَك.
     * كان تمرير الرابط وحده يُسقط الأبعاد فيعرض واتساب مصغَّرًا صغيرًا.
     */
    public function test_the_home_page_publishes_its_social_image_dimensions(): void
    {
        Media::create([
            'usage' => 'hero', 'disk' => 'public', 'path' => 'media/test/hero.webp',
            'variants' => ['lg' => 'media/test/hero-lg.webp'], 'width' => 1600, 'height' => 900,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('og:image:width', $html);
        $this->assertStringContainsString('og:image:height', $html);
        $this->assertStringContainsString('content="1600"', $html);
        $this->assertStringContainsString('content="900"', $html);
    }

    public function test_the_rendered_page_includes_the_license_page_url(): void
    {
        $post = $this->postWithImage();

        $this->get($post->url())
            ->assertOk()
            ->assertSee('acquireLicensePage', false);
    }
}
