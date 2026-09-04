<?php

namespace Tests\Feature\Site;

use App\Models\GoogleConnection;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\GoogleReviewsService;
use App\Support\Schema;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GoogleReviewsImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array<string, mixed>> */
    private array $googleReviews = [];

    /** عند ضبطها ترد واجهة التقييمات بخطأ 403 بدل البيانات. */
    private ?string $googleError = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
        ]);

        // يُسجَّل مرة واحدة: استدعاء Http::fake ثانيةً يُضيف ولا يستبدل، فأول نمط
        // مطابق هو الذي يردّ دائمًا. القراءة من خاصية متغيّرة تتيح تبديل الرد
        // بين مزامنة وأخرى داخل الاختبار الواحد.
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3599]),
            'mybusiness.googleapis.com/*' => fn () => $this->googleError
                ? Http::response(['error' => ['message' => $this->googleError]], 403)
                : Http::response(['reviews' => $this->googleReviews, 'nextPageToken' => null]),
        ]);

        $this->seed([SectionSeeder::class, SettingSeeder::class]);
    }

    private function connection(array $overrides = []): GoogleConnection
    {
        return GoogleConnection::create([
            'refresh_token' => 'refresh-token',
            'connected_email' => 'mutaz@example.com',
            'account_name' => 'accounts/111',
            'location_name' => 'locations/222',
            'location_title' => 'معتز ساتي للتصوير',
            ...$overrides,
        ]);
    }

    /** @param  array<int, array<string, mixed>>  $reviews */
    private function fakeGoogle(array $reviews): void
    {
        $this->googleReviews = $reviews;
    }

    private function review(string $id, string $stars, string $comment, string $name = 'عبدالله'): array
    {
        return [
            'reviewId' => $id,
            'reviewer' => ['displayName' => $name],
            'starRating' => $stars,
            'comment' => $comment,
            'createTime' => '2026-07-01T10:00:00Z',
            'updateTime' => '2026-07-01T10:00:00Z',
        ];
    }

    public function test_reviews_are_imported_as_google_sourced_testimonials(): void
    {
        $this->fakeGoogle([
            $this->review('r1', 'FIVE', 'تغطية ممتازة والصور وصلت في وقتها.', 'عبدالله محمد'),
            $this->review('r2', 'FOUR', 'عمل احترافي وتعامل راقٍ جدًا.', 'سارة أحمد'),
        ]);

        $stats = app(GoogleReviewsService::class)->sync($this->connection());

        $this->assertSame(2, $stats['imported']);

        $this->assertDatabaseHas('testimonials', [
            'external_id' => 'r1',
            'name' => 'عبدالله محمد',
            'source' => Testimonial::SOURCE_GOOGLE,
            'rating' => 5,
            'is_active' => true,
        ]);

        $this->assertSame(4, Testimonial::where('external_id', 'r2')->value('rating'));
    }

    /** التقييمات دون الحد تُستورد لكن تبقى مخفية — يراها المالك ولا يراها الزوار. */
    public function test_reviews_below_the_threshold_are_imported_hidden(): void
    {
        $this->fakeGoogle([
            $this->review('low', 'TWO', 'التسليم تأخّر أكثر مما توقّعت للأسف.'),
        ]);

        app(GoogleReviewsService::class)->sync($this->connection(['min_rating' => 4]));

        $this->assertDatabaseHas('testimonials', ['external_id' => 'low', 'is_active' => false]);
    }

    public function test_disabling_auto_publish_holds_everything_for_review(): void
    {
        $this->fakeGoogle([$this->review('r1', 'FIVE', 'صور رائعة وخدمة سريعة جدًا.')]);

        app(GoogleReviewsService::class)->sync($this->connection(['auto_publish' => false]));

        $this->assertDatabaseHas('testimonials', ['external_id' => 'r1', 'is_active' => false]);
    }

    /** إعادة المزامنة لا تكرّر: البحث بالمعرّف لا بالنص. */
    public function test_syncing_twice_updates_instead_of_duplicating(): void
    {
        $service = app(GoogleReviewsService::class);
        $connection = $this->connection();

        $this->fakeGoogle([$this->review('r1', 'FOUR', 'النص الأصلي قبل تعديل صاحبه له.')]);
        $service->sync($connection);

        $this->fakeGoogle([$this->review('r1', 'FIVE', 'النص بعد أن عدّله صاحبه ورفع تقييمه.')]);
        $stats = $service->sync($connection);

        $this->assertSame(0, $stats['imported']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(1, Testimonial::count());

        $row = Testimonial::firstOrFail();
        $this->assertSame(5, $row->rating);
        $this->assertStringContainsString('بعد أن عدّله', $row->content);
    }

    /** قرار الإظهار للمالك: المزامنة تحدّث النص ولا تعيد نشر ما أخفاه. */
    public function test_a_manually_hidden_review_stays_hidden_after_resync(): void
    {
        $service = app(GoogleReviewsService::class);
        $connection = $this->connection();

        $this->fakeGoogle([$this->review('r1', 'FIVE', 'رأي سيخفيه المالك بعد قليل.')]);
        $service->sync($connection);

        Testimonial::where('external_id', 'r1')->update(['is_active' => false]);

        $this->fakeGoogle([$this->review('r1', 'FIVE', 'رأي سيخفيه المالك بعد قليل.')]);
        $service->sync($connection);

        $this->assertFalse(Testimonial::where('external_id', 'r1')->value('is_active'));
    }

    /** حذف العميل لتقييمه من Google يجب أن يزيله من الموقع أيضًا. */
    public function test_a_review_deleted_at_google_is_removed_from_the_site(): void
    {
        $service = app(GoogleReviewsService::class);
        $connection = $this->connection();

        $this->fakeGoogle([
            $this->review('keep', 'FIVE', 'رأي سيبقى موجودًا على Google.'),
            $this->review('gone', 'FIVE', 'رأي سيحذفه صاحبه لاحقًا من Google.'),
        ]);
        $service->sync($connection);
        $this->assertSame(2, Testimonial::count());

        $this->fakeGoogle([$this->review('keep', 'FIVE', 'رأي سيبقى موجودًا على Google.')]);
        $stats = $service->sync($connection);

        $this->assertSame(1, $stats['removed']);
        $this->assertNull(Testimonial::where('external_id', 'gone')->first());
        $this->assertNotNull(Testimonial::where('external_id', 'keep')->first());
    }

    /** ردّ فارغ قد يعني عطلًا مؤقتًا — لا يجوز أن يمسح كل الآراء. */
    public function test_an_empty_response_never_wipes_existing_reviews(): void
    {
        $service = app(GoogleReviewsService::class);
        $connection = $this->connection();

        $this->fakeGoogle([$this->review('r1', 'FIVE', 'رأي موجود ويجب ألا يختفي.')]);
        $service->sync($connection);

        $this->fakeGoogle([]);
        $stats = $service->sync($connection);

        $this->assertSame(0, $stats['removed']);
        $this->assertSame(1, Testimonial::count());
    }

    public function test_star_only_reviews_without_text_are_skipped(): void
    {
        $this->fakeGoogle([
            ['reviewId' => 'silent', 'reviewer' => ['displayName' => 'زائر'], 'starRating' => 'FIVE'],
            $this->review('written', 'FIVE', 'رأي مكتوب يصلح للعرض في الموقع.'),
        ]);

        $stats = app(GoogleReviewsService::class)->sync($this->connection());

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(1, Testimonial::count());
    }

    /** الغرض كله: المستورد يُعرض ولا يُحتسب في البيانات المهيكلة. */
    public function test_imported_reviews_stay_out_of_the_aggregate_rating(): void
    {
        Testimonial::create([
            'name' => 'عميل مباشر', 'content' => 'رأي وصل مباشرة عبر الواتساب بعد التسليم.',
            'rating' => 4, 'source' => Testimonial::SOURCE_DIRECT, 'is_active' => true,
        ]);

        $this->fakeGoogle([
            $this->review('r1', 'FIVE', 'رأي منقول من Google وله نجوم كاملة.'),
            $this->review('r2', 'FIVE', 'رأي آخر منقول من Google بنجوم كاملة.'),
        ]);

        app(GoogleReviewsService::class)->sync($this->connection());

        $rating = Schema::aggregateRating();

        $this->assertSame(1, $rating['reviewCount']);
        $this->assertSame(4.0, $rating['ratingValue']);
        $this->assertSame(3, Testimonial::active()->count(), 'الثلاثة معروضة في الصفحة.');
    }

    public function test_the_sync_command_reports_when_nothing_is_connected(): void
    {
        $this->artisan('reviews:sync')
            ->expectsOutputToContain('لا يوجد حساب Google مربوط')
            ->assertSuccessful();
    }

    public function test_the_sync_command_records_failures_for_the_admin_screen(): void
    {
        $connection = $this->connection();

        $this->googleError = 'Permission denied';

        $this->artisan('reviews:sync')->assertFailed();

        $this->assertStringContainsString('Permission denied', (string) $connection->refresh()->last_error);
    }

    public function test_the_admin_screen_shows_the_setup_guide_before_credentials_exist(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.google')
            ->assertSee('الخطوة الأولى')
            ->assertSee(route('admin.google.callback'));
    }

    public function test_the_admin_screen_can_trigger_a_sync(): void
    {
        $this->connection();
        $this->fakeGoogle([$this->review('r1', 'FIVE', 'رأي يصل عبر زر الاستيراد اليدوي.')]);

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.google')
            ->call('syncNow')
            ->assertSee('تمت المزامنة');

        $this->assertSame(1, Testimonial::where('source', Testimonial::SOURCE_GOOGLE)->count());
    }

    public function test_the_oauth_callback_rejects_a_mismatched_state(): void
    {
        $this->actingAs(User::factory()->create());

        $this->withSession(['google_oauth_state' => 'expected'])
            ->get(route('admin.google.callback', ['code' => 'x', 'state' => 'forged']))
            ->assertRedirect(route('admin.google'))
            ->assertSessionHas('google_error');

        $this->assertSame(0, GoogleConnection::count());
    }

    public function test_the_refresh_token_is_encrypted_at_rest(): void
    {
        $this->connection(['refresh_token' => 'super-secret-token']);

        $stored = (string) DB::table('google_connections')->value('refresh_token');

        $this->assertStringNotContainsString('super-secret-token', $stored);
        $this->assertSame('super-secret-token', GoogleConnection::current()->refresh_token);
    }
}
