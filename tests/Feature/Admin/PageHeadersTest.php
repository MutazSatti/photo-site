<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PageHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // ذاكرة الصور ساكنة على النموذج، فتعبر من اختبار إلى آخر في العملية
        // الواحدة ما لم تُفرَّغ — وهذا بالضبط ما تفعله اللوحة بعد كل تغيير.
        Media::forgetHeaders();

        $this->seed([SectionSeeder::class, SettingSeeder::class]);

        $this->actingAs(User::factory()->create());
    }

    /** صورة واجهة مرفوعة، منشأة مباشرةً لأن المحوّل يحتاج GD وقد لا تكون مفعّلة. */
    private function uploadedHeader(string $key): Media
    {
        return Media::create([
            'usage' => Media::HEADER_USAGE_PREFIX.$key,
            'disk' => 'public',
            'path' => 'media/test/'.$key.'.webp',
            'variants' => ['full' => 'media/test/'.$key.'.webp'],
            'width' => 1600,
            'height' => 900,
            'original_name' => $key.'.webp',
            'alt' => 'صورة واجهة',
        ]);
    }

    public function test_the_page_lists_static_pages_and_sections_from_the_database(): void
    {
        Livewire::test('pages::admin.headers')
            ->assertOk()
            ->assertSee('معرض الأعمال')
            ->assertSee('التواصل والحجز')
            ->assertSee('الأسئلة الشائعة')
            ->assertSee('خدمات التصوير')
            ->assertSee('ورش تدريبية');
    }

    /**
     * القسم الفرعي ذو الصفحة المخصّصة لا يمرّ بمكوّن الترويسة العام، فعرض
     * حقلِ رفعٍ له يعِد بما لا يحدث.
     */
    public function test_a_category_with_its_own_page_is_not_offered_a_header(): void
    {
        $keys = array_column(Livewire::test('pages::admin.headers')->get('groups')[0]['slots'], 'usage');

        $this->assertContains(Media::HEADER_USAGE_PREFIX.'events', $keys);
        $this->assertNotContains(Media::HEADER_USAGE_PREFIX.'real-estate', $keys);
    }

    /** صفحة العقارات وشعارات الاعتمادات لها خاناتها، فلا تبقى صورًا لا تُبدَّل. */
    public function test_the_fixed_slots_of_other_pages_are_offered_too(): void
    {
        $groups = Livewire::test('pages::admin.headers')->get('groups');

        $usages = array_merge(...array_map(
            fn (array $g): array => array_column($g['slots'], 'usage'),
            $groups,
        ));

        foreach (['re_hero', 're_before', 're_after', 're_craft_verticals', 'accr_etec', 'accr_gaca', 'accr_gamr'] as $usage) {
            $this->assertContains($usage, $usages, $usage.' يجب أن تكون له خانة.');
        }
    }

    /** خانة بلا صورة أصلية: حذفها يترك فراغًا تتعامل معه صفحتها. */
    public function test_a_fixed_slot_has_no_shipped_default(): void
    {
        Media::create([
            'usage' => 're_hero',
            'disk' => 'public',
            'path' => 'media/test/re.webp',
            'variants' => ['full' => 'media/test/re.webp'],
            'width' => 2400,
            'height' => 1600,
            'original_name' => 're.webp',
        ]);

        Livewire::test('pages::admin.headers')->call('remove', 're_hero');

        $this->assertDatabaseMissing('media', ['usage' => 're_hero']);
    }

    public function test_the_shipped_file_is_used_when_nothing_was_uploaded(): void
    {
        $this->assertNotNull(header_photo('events'), 'الصورة المشحونة مع الشيفرة يجب أن تُستعمل افتراضيًا.');
        $this->assertStringContainsString('images/headers/events.webp', (string) header_photo('events'));
    }

    public function test_an_upload_takes_precedence_over_the_shipped_file(): void
    {
        $this->uploadedHeader('events');
        Media::forgetHeaders();

        $this->assertStringContainsString('media/test/events.webp', (string) header_photo('events'));
    }

    public function test_removing_an_upload_falls_back_to_the_shipped_file(): void
    {
        $this->uploadedHeader('events');
        Media::forgetHeaders();

        Livewire::test('pages::admin.headers')->call('remove', Media::HEADER_USAGE_PREFIX.'events');

        $this->assertDatabaseMissing('media', ['usage' => Media::HEADER_USAGE_PREFIX.'events']);
        $this->assertStringContainsString('images/headers/events.webp', (string) header_photo('events'));
    }

    public function test_an_unknown_page_key_is_rejected(): void
    {
        $this->uploadedHeader('wp-admin');
        Media::forgetHeaders();

        Livewire::test('pages::admin.headers')
            ->call('remove', Media::HEADER_USAGE_PREFIX.'wp-admin')
            ->assertNotFound();

        $this->assertDatabaseHas('media', ['usage' => Media::HEADER_USAGE_PREFIX.'wp-admin']);
    }

    public function test_an_uploaded_header_replaces_the_previous_one(): void
    {
        if (! ImageService::webpSupported()) {
            $this->markTestSkipped('إضافة GD أو Imagick غير مفعّلة في هذه البيئة.');
        }

        Livewire::test('pages::admin.headers')
            ->set('uploads.header:events', UploadedFile::fake()->image('first.jpg', 1600, 900))
            ->call('save', Media::HEADER_USAGE_PREFIX.'events')
            ->assertHasNoErrors();

        Livewire::test('pages::admin.headers')
            ->set('uploads.header:events', UploadedFile::fake()->image('second.jpg', 1600, 900))
            ->call('save', Media::HEADER_USAGE_PREFIX.'events')
            ->assertHasNoErrors();

        // replaceForUsage يحذف السابق، فلا يتراكم صفّان على مفتاح واحد
        $this->assertSame(1, Media::where('usage', Media::HEADER_USAGE_PREFIX.'events')->count());
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Livewire::test('pages::admin.headers')
            ->set('uploads.header:events', UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'))
            ->call('save', Media::HEADER_USAGE_PREFIX.'events')
            ->assertHasErrors('uploads.header:events');

        $this->assertDatabaseMissing('media', ['usage' => Media::HEADER_USAGE_PREFIX.'events']);
    }

    public function test_a_guest_cannot_reach_the_page(): void
    {
        auth()->logout();

        $this->get(route('admin.headers'))->assertRedirect(route('login'));
    }
}
