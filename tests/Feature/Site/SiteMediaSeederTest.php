<?php

namespace Tests\Feature\Site;

use App\Models\Media;
use App\Models\Post;
use App\Services\ImageService;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SiteMediaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * صور الصفحات تصل الخادم عبر المستودع لا عبر رفع يدوي.
 *
 * النشر سحبٌ من GitHub، وstorage/app/public مستثنى منه — فلولا هذه البذرة
 * لوصلت الشيفرة وبقيت الصفحات بعناوين بلا صور.
 */
class SiteMediaSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed([SectionSeeder::class]);
    }

    private function skipWithoutWebp(): void
    {
        if (! ImageService::webpSupported()) {
            $this->markTestSkipped('إضافة GD أو Imagick غير مفعّلة في هذه البيئة.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function manifest(): array
    {
        $path = database_path('seeders/assets/site-media/manifest.json');

        if (! is_file($path)) {
            $this->fail('بيان أصول الصور غير موجود.');
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    public function test_the_bundle_and_its_manifest_agree(): void
    {
        $dir = database_path('seeders/assets/site-media');

        foreach ($this->manifest() as $item) {
            $this->assertFileExists($dir.'/'.$item['file'], "ملف مذكور في البيان ومفقود: {$item['file']}");
            $this->assertArrayHasKey('alt', $item, "بلا نصّ بديل: {$item['file']}");
            $this->assertNotEmpty($item['alt'], "نصّ بديل فارغ: {$item['file']}");
        }
    }

    public function test_it_fills_the_page_slots_and_the_group_galleries(): void
    {
        $this->skipWithoutWebp();

        // المجموعات تُنشئها هجرة المحتوى، وهي انسحبت وقت الترحيل لغياب
        // الأقسام — فتُستدعى بعد بذرها، كما تفعل DatabaseSeeder تمامًا
        (require database_path('migrations/2026_09_03_000002_build_real_estate_service_page.php'))->up();

        $this->seed([SiteMediaSeeder::class]);

        foreach (['re_hero', 're_before', 're_after', 'accr_etec', 'accr_gaca', 'accr_gamr'] as $usage) {
            $this->assertNotNull(
                Media::where('usage', $usage)->first(),
                "خانة فارغة بعد البذر: {$usage}",
            );
        }

        $exteriors = Post::where('slug', 'almrafq-alkharijiya')->first();

        $this->assertNotNull($exteriors, 'مجموعة المرافق الخارجية غير موجودة');
        $this->assertGreaterThan(0, $exteriors->media()->count());
        $this->assertSame(1, $exteriors->media()->where('is_cover', true)->count());
    }

    /** تشغيلها مرّتين لا يُنتج تكرارًا ولا يستبدل ما رُفع من لوحة التحكم. */
    public function test_it_is_idempotent(): void
    {
        $this->skipWithoutWebp();

        (require database_path('migrations/2026_09_03_000002_build_real_estate_service_page.php'))->up();

        $this->seed([SiteMediaSeeder::class]);
        $first = Media::count();

        $this->seed([SiteMediaSeeder::class]);

        $this->assertSame($first, Media::count());
    }
}
