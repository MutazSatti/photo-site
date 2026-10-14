<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * رفع الصور لا يسقط كلّه بسقوط صورة.
 *
 * عطل الرفع كان يظهر متأخّرًا ومُربِكًا: صورةٌ تتعثّر فيسقط الطلب كله، فيخرج
 * العمل بلا صورة واحدة ولا رسالة تقول أيّ ملف كان ولا لماذا.
 */
class UploadResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class]);
        $this->actingAs(User::factory()->create());
    }

    private function project(): Post
    {
        $category = Category::where('slug', Category::EVENTS)->firstOrFail();

        return Post::create([
            'section_id' => $category->section_id,
            'category_id' => $category->id,
            'slug' => 'mashru-rafaa',
            'title' => 'مشروع رفع',
            'status' => 'published',
            'published_at' => now(),
        ])->fresh();
    }

    /** محوّلٌ يتعثّر في ملفٍ بعينه، كما يتعثّر الخادم بملفٍ أكبر من حدّه. */
    private function failingOn(string $needle): void
    {
        $this->instance(ImageService::class, new class($needle) extends ImageService
        {
            public function __construct(private string $needle) {}

            public function store(
                UploadedFile $file,
                ?Post $post = null,
                ?string $usage = null,
                ?string $alt = null,
                bool $isCover = false,
            ): Media {
                if (str_contains($file->getClientOriginalName(), $this->needle)) {
                    throw new RuntimeException('تعذّر التحويل');
                }

                return Media::create([
                    'post_id' => $post?->id,
                    'usage' => $usage,
                    'disk' => 'public',
                    'path' => 'media/'.$file->getClientOriginalName().'.webp',
                    'variants' => ['md' => 'media/'.$file->getClientOriginalName().'-md.webp'],
                    'width' => 1600,
                    'height' => 1067,
                    'original_name' => $file->getClientOriginalName(),
                    'alt' => $alt,
                    'is_cover' => $isCover,
                ]);
            }
        });
    }

    public function test_one_failing_image_does_not_lose_the_others(): void
    {
        $post = $this->project();
        $this->failingOn('كبيرة');

        Livewire::test('pages::admin.post-edit', ['post' => $post])
            ->set('uploads', [
                UploadedFile::fake()->image('كبيرة.jpg'),
                UploadedFile::fake()->image('سليمة-١.jpg'),
                UploadedFile::fake()->image('سليمة-٢.jpg'),
            ])
            ->call('saveUploads')
            ->assertHasNoErrors();

        $this->assertSame(2, Media::where('post_id', $post->id)->count());
        $this->assertDatabaseHas('media', ['original_name' => 'سليمة-١.jpg']);
        $this->assertDatabaseMissing('media', ['original_name' => 'كبيرة.jpg']);
    }

    /** والغلاف يُعلَّق بأوّل ما نجح لا بأوّل ما حاول. */
    public function test_the_cover_goes_to_the_first_image_that_survived(): void
    {
        $post = $this->project();
        $this->failingOn('كبيرة');

        Livewire::test('pages::admin.post-edit', ['post' => $post])
            ->set('uploads', [
                UploadedFile::fake()->image('كبيرة.jpg'),
                UploadedFile::fake()->image('سليمة.jpg'),
            ])
            ->call('saveUploads');

        $cover = Media::where('post_id', $post->id)->where('is_cover', true)->first();

        $this->assertNotNull($cover, 'العمل بلا غلاف');
        $this->assertSame('سليمة.jpg', $cover->original_name);
    }

    /** والرسالة تسمّي الملف وتقول الحدّ، فلا يبقى المالك يخمّن. */
    public function test_the_message_names_the_file_and_the_limit(): void
    {
        $post = $this->project();
        $this->failingOn('كبيرة');

        Livewire::test('pages::admin.post-edit', ['post' => $post])
            ->set('uploads', [UploadedFile::fake()->image('كبيرة.jpg')])
            ->call('saveUploads')
            ->assertDispatched('notify', function (string $event, array $params): bool {
                return ($params['variant'] ?? null) === 'danger'
                    && str_contains($params['message'], 'كبيرة.jpg')
                    && str_contains($params['message'], (string) max_upload_mb());
            });

        $this->assertSame(0, Media::where('post_id', $post->id)->count());
    }

    public function test_a_clean_batch_still_reports_success(): void
    {
        $post = $this->project();
        $this->failingOn('لا-يوجد');

        Livewire::test('pages::admin.post-edit', ['post' => $post])
            ->set('uploads', [UploadedFile::fake()->image('أ.jpg'), UploadedFile::fake()->image('ب.jpg')])
            ->call('saveUploads')
            ->assertDispatched('notify', fn (string $event, array $params): bool => ! isset($params['variant'])
                && str_contains($params['message'], '2'));
    }

    /**
     * الحدّ المعروض هو ما يقبله الخادم فعلًا.
     *
     * الملف الأكبر من upload_max_filesize يرفضه PHP قبل لارافيل، فلا تعمل
     * قاعدة التحقّق ولا تظهر رسالة — ووعدُ الشاشة بحدٍّ أوسع يُضلّل.
     */
    public function test_the_shown_limit_never_exceeds_the_server(): void
    {
        $this->assertLessThanOrEqual((int) config('site.images.max_upload_kb'), max_upload_kb());

        foreach (['upload_max_filesize', 'post_max_size'] as $directive) {
            $raw = trim((string) ini_get($directive));

            if ($raw === '' || ! preg_match('/^(\d+(?:\.\d+)?)([KMG])?$/i', $raw, $m)) {
                continue;
            }

            $kb = (float) $m[1] * match (strtoupper($m[2] ?? '')) {
                'G' => 1024 * 1024,
                'M' => 1024,
                'K' => 1,
                default => 1 / 1024,
            };

            $this->assertLessThanOrEqual((int) $kb, max_upload_kb(), $directive.' أصغر من الحدّ المعروض');
        }
    }

    public function test_the_dashboard_reports_the_upload_health(): void
    {
        Livewire::test('pages::admin.dashboard')
            ->assertOk()
            ->assertSee('حالة الرفع والتخزين')
            ->assertSee('رابط التخزين')
            ->assertSee('أكبر صورة')
            ->assertSee(max_upload_mb().' ميجابايت');
    }

    /**
     * الرفع المؤقّت في Livewire يفحص الملف قبل الشاشة، فحدّه هو حدّ الموقع.
     *
     * لولا ذلك لرُفضت الصورة هناك صامتةً: لا تُرفَق، ولا تعمل قاعدة التحقّق
     * التي تشرح، ويقول الزرّ «الحقل مطلوب».
     */
    public function test_livewire_rejects_at_the_same_limit_as_the_site(): void
    {
        $this->assertSame(
            ['required', 'file', 'max:'.max_upload_kb()],
            config('livewire.temporary_file_upload.rules'),
        );
    }

    /** وحين لا تصل صورة تقول الرسالة لماذا، لا «الحقل مطلوب». */
    public function test_an_oversized_image_explains_itself(): void
    {
        $project = $this->project();

        $component = Livewire::test('pages::admin.post-edit', ['post' => $project])
            ->set('uploads', [
                UploadedFile::fake()->create('DJI_0431.jpg', max_upload_kb() + 5000, 'image/jpeg'),
            ])
            ->call('saveUploads');

        $messages = implode(' ', $component->errors()->all());

        $this->assertStringNotContainsString('حقل uploads مطلوب', $messages);
        $this->assertStringContainsString('لم تصل أي صورة', $messages);
        $this->assertStringContainsString((string) max_upload_mb(), $messages);
    }
}
