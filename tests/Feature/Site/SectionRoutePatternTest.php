<?php

namespace Tests\Feature\Site;

use App\Models\Category;
use App\Models\Section;
use App\Support\SectionRoutes;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SectionRoutePatternTest extends TestCase
{
    use RefreshDatabase;

    /**
     * حين لا تكون الجداول موجودة بعد، يرجع بناء المسارات إلى ثوابت النموذجين.
     *
     * كانت هذه القائمة تُكتب يدويًا فتخلّفت عن الثوابت عند إضافة قسم فرعي جديد،
     * فصار رابطه يُطابَق كعنصر محتوى ويردّ 404. الآن تُقرأ بالانعكاس — وهذا
     * الاختبار يثبت أن كل ثابت يصل إلى النمط الاحتياطي.
     */
    public function test_the_fallback_pattern_covers_every_slug_constant(): void
    {
        // اتصال فارغ بلا جداول يعيد إنتاج حالة "قبل تنفيذ الترحيلات" بأمان،
        // بلا المساس بقاعدة بيانات الاختبار نفسها
        config(['database.connections.no_tables' => ['driver' => 'sqlite', 'database' => ':memory:']]);
        DB::setDefaultConnection('no_tables');
        Cache::forget(SectionRoutes::CACHE_KEY);

        try {
            $patterns = SectionRoutes::patterns();
        } finally {
            DB::setDefaultConnection('sqlite');
            Cache::forget(SectionRoutes::CACHE_KEY);
        }

        foreach ([Category::EVENTS, Category::ACTIVITIES, Category::REAL_ESTATE, Category::AERIAL] as $slug) {
            $this->assertStringContainsString(
                preg_quote($slug, '/'),
                $patterns['categories'],
                "الرابط {$slug} غائب عن النمط الاحتياطي للأقسام الفرعية.",
            );
        }

        foreach ([Section::SERVICES, Section::WORKSHOPS, Section::ARTICLES, Section::TIPS] as $slug) {
            $this->assertStringContainsString($slug, $patterns['sections']);
        }
    }

    public function test_the_live_pattern_follows_the_database(): void
    {
        $this->seed([SectionSeeder::class, SettingSeeder::class]);

        Cache::forget(SectionRoutes::CACHE_KEY);

        $patterns = SectionRoutes::patterns();

        foreach (Category::pluck('slug') as $slug) {
            $this->assertStringContainsString(preg_quote($slug, '/'), $patterns['categories']);
        }
    }

    /** رابط قسم فرعي جديد يعمل فور إنشائه، ولا يُطابَق كعنصر محتوى. */
    public function test_every_seeded_category_resolves_to_its_own_page(): void
    {
        $this->seed([SectionSeeder::class, SettingSeeder::class]);

        foreach (Category::with('section')->get() as $category) {
            $this->get($category->url())
                ->assertOk()
                ->assertSee($category->name);
        }
    }
}
