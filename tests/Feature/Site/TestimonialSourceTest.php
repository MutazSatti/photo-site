<?php

namespace Tests\Feature\Site;

use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Schema;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TestimonialSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class]);
    }

    private function testimonial(string $source, int $rating = 5): Testimonial
    {
        return Testimonial::create([
            'name' => 'عميل '.$source.' '.$rating,
            'role' => 'منظّم فعاليات',
            'content' => 'تغطية منظّمة من البداية للنهاية والصور وصلت في وقتها المتفق عليه.',
            'source' => $source,
            'rating' => $rating,
            'is_active' => true,
        ]);
    }

    public function test_source_defaults_to_direct(): void
    {
        $plain = Testimonial::create([
            'name' => 'عميل بلا مصدر محدّد',
            'content' => 'صور ممتازة وتسليم سريع، شكرًا على الاحترافية العالية.',
            'rating' => 5,
        ]);

        $this->assertSame(Testimonial::SOURCE_DIRECT, $plain->refresh()->source);
        $this->assertFalse($plain->isFromGoogle());
    }

    /**
     * الجوهر: إرشادات Google للبيانات المهيكلة تمنع تجميع تقييمات مصدرها مواقع
     * أخرى، فالمنقول من Google يُعرض ولا يُحتسب.
     */
    public function test_google_sourced_reviews_are_excluded_from_the_aggregate_rating(): void
    {
        $this->testimonial(Testimonial::SOURCE_DIRECT, rating: 4);
        $this->testimonial(Testimonial::SOURCE_GOOGLE, rating: 5);
        $this->testimonial(Testimonial::SOURCE_GOOGLE, rating: 5);

        $rating = Schema::aggregateRating();

        $this->assertSame(1, $rating['reviewCount'], 'الرأي المباشر وحده يُحتسب.');
        $this->assertSame(4.0, $rating['ratingValue']);
    }

    public function test_the_aggregate_rating_disappears_when_every_review_came_from_google(): void
    {
        $this->testimonial(Testimonial::SOURCE_GOOGLE);
        $this->testimonial(Testimonial::SOURCE_GOOGLE);

        $this->assertNull(Schema::aggregateRating());

        // ولا يظهر المفتاح في بيانات النشاط التجاري المنشورة
        $this->assertArrayNotHasKey('aggregateRating', Schema::business());
    }

    public function test_hidden_reviews_never_count_regardless_of_source(): void
    {
        $this->testimonial(Testimonial::SOURCE_DIRECT)->update(['is_active' => false]);

        $this->assertNull(Schema::aggregateRating());
    }

    public function test_the_card_discloses_a_google_sourced_review(): void
    {
        $this->testimonial(Testimonial::SOURCE_GOOGLE);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('من تقييمات Google');
    }

    public function test_a_direct_review_carries_no_source_label(): void
    {
        $this->testimonial(Testimonial::SOURCE_DIRECT);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('من تقييمات Google');
    }

    public function test_the_google_profile_link_appears_only_once_configured(): void
    {
        $this->testimonial(Testimonial::SOURCE_GOOGLE);

        $this->get(route('home'))->assertDontSee('شاهد كل التقييمات على Google');

        Setting::put('google_reviews_url', 'https://maps.app.goo.gl/example');

        $this->get(route('home'))
            ->assertSee('شاهد كل التقييمات على Google')
            ->assertSee('https://maps.app.goo.gl/example', escape: false);
    }

    public function test_the_admin_screen_saves_the_chosen_source(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.testimonials')
            ->call('create')
            ->set('name', 'خالد العمري')
            ->set('role', 'مدير تسويق')
            ->set('content', 'الصور أظهرت المساحات بشكل واقعي وزادت الاستفسارات فعلًا.')
            ->set('source', Testimonial::SOURCE_GOOGLE)
            ->set('rating', 5)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('testimonials', [
            'name' => 'خالد العمري',
            'source' => Testimonial::SOURCE_GOOGLE,
        ]);
    }

    public function test_the_admin_list_marks_google_sourced_reviews(): void
    {
        $this->testimonial(Testimonial::SOURCE_GOOGLE);
        $this->testimonial(Testimonial::SOURCE_DIRECT);

        $this->actingAs(User::factory()->create());

        $html = Livewire::test('pages::admin.testimonials')->html();

        $this->assertStringContainsString('data-source="google"', $html);
        $this->assertStringContainsString('data-source="direct"', $html);

        // الشارة تظهر للمنقول وحده، لا لكل البطاقات
        $this->assertSame(1, substr_count($html, 'data-badge="google"'));
    }

    public function test_the_admin_screen_rejects_an_unknown_source(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.testimonials')
            ->call('create')
            ->set('name', 'اسم ما')
            ->set('content', 'نص رأي طويل بما يكفي لتجاوز الحد الأدنى المطلوب.')
            ->set('source', 'facebook')
            ->call('save')
            ->assertHasErrors('source');
    }
}
