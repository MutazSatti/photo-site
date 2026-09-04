<?php

namespace Tests\Feature\Site;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\PostSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImageConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! ImageService::webpSupported()) {
            $this->markTestSkipped('إضافة GD أو Imagick غير مفعّلة في هذه البيئة.');
        }

        Storage::fake('public');

        $this->seed([SectionSeeder::class, SettingSeeder::class, PostSeeder::class]);
    }

    private function jpeg(int $width = 1800, int $height = 1200, string $name = 'photo.jpg'): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 200, 140, 40));

        $path = tempnam(sys_get_temp_dir(), 'test').'.jpg';
        imagejpeg($image, $path, 88);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    public function test_an_uploaded_image_becomes_webp_in_every_size(): void
    {
        $post = Post::firstOrFail();

        $media = app(ImageService::class)->store(file: $this->jpeg(), post: $post);

        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertSame($post->id, $media->post_id);

        foreach (['full', 'lg', 'md', 'thumb'] as $variant) {
            $this->assertArrayHasKey($variant, $media->variants);
            Storage::disk('public')->assertExists($media->variants[$variant]);
            $this->assertStringEndsWith('.webp', $media->variants[$variant]);
        }
    }

    public function test_oversized_images_are_scaled_down_to_the_configured_limit(): void
    {
        $media = app(ImageService::class)->store(
            file: $this->jpeg(width: 4000, height: 3000),
            post: Post::firstOrFail(),
        );

        $this->assertSame((int) config('site.images.max_width'), $media->width);
        $this->assertSame(1800, $media->height);
    }

    /** الأبعاد تُحفظ ليحجز المتصفح المساحة قبل التحميل فلا يقفز التخطيط. */
    public function test_dimensions_are_recorded_for_layout_stability(): void
    {
        $media = app(ImageService::class)->store(file: $this->jpeg(), post: Post::firstOrFail());

        $this->assertSame(1800, $media->width);
        $this->assertSame(1200, $media->height);
        $this->assertSame('1800 / 1200', $media->aspectRatio());
        $this->assertStringContainsString('400w', $media->srcset());
        $this->assertStringContainsString('1600w', $media->srcset());
    }

    public function test_arabic_file_names_produce_a_usable_slug(): void
    {
        $media = app(ImageService::class)->store(
            file: $this->jpeg(name: 'حفل التخرج 2026.jpg'),
            post: Post::firstOrFail(),
        );

        $this->assertMatchesRegularExpression('#^media/\d{4}/\d{2}/[a-z0-9-]+\.webp$#', $media->path);
        $this->assertSame('حفل التخرج 2026.jpg', $media->original_name);
    }

    public function test_deleting_media_removes_every_file_from_disk(): void
    {
        $media = app(ImageService::class)->store(file: $this->jpeg(), post: Post::firstOrFail());
        $paths = array_values($media->variants);

        $media->delete();

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_deleting_a_post_removes_its_images(): void
    {
        $post = Post::firstOrFail();
        $media = app(ImageService::class)->store(file: $this->jpeg(), post: $post);

        $post->delete();

        $this->assertSame(0, Media::whereKey($media->id)->count());
        Storage::disk('public')->assertMissing($media->path);
    }

    /** المسار الكامل كما يستخدمه المصور: رفع من لوحة التحكم ثم تحويل. */
    public function test_uploading_through_the_admin_screen_converts_and_attaches(): void
    {
        $post = Post::firstOrFail();

        $this->actingAs(User::factory()->create());

        // ملفات Livewire المزيّفة تمرّ عبر نفس مسار الرفع المؤقت الحقيقي
        Livewire::test('pages::admin.post-edit', ['post' => $post])
            ->set('uploads', [
                UploadedFile::fake()->image('first.jpg', 1800, 1200),
                UploadedFile::fake()->image('second.jpg', 1200, 900),
            ])
            ->call('saveUploads')
            ->assertHasNoErrors();

        $media = Media::where('post_id', $post->id)->orderBy('sort_order')->get();

        $this->assertCount(2, $media);
        $this->assertTrue($media->first()->is_cover, 'أول صورة في أول دفعة تصبح الغلاف تلقائيًا.');

        foreach ($media as $item) {
            Storage::disk('public')->assertExists($item->path);
            $this->assertStringEndsWith('.webp', $item->path);
        }
    }

    public function test_uploading_a_non_image_is_rejected(): void
    {
        $post = Post::firstOrFail();

        $this->actingAs(User::factory()->create());

        Livewire::test('pages::admin.post-edit', ['post' => $post])
            ->set('uploads', [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')])
            ->call('saveUploads')
            ->assertHasErrors('uploads.0');

        $this->assertSame(0, Media::where('post_id', $post->id)->count());
    }

    /**
     * الواصف يجب أن يقول عرض الملف الحقيقي.
     *
     * التصغير لا يكبّر، فصورة أصغر من المقاس المستهدف تنتج ملفًا بعرضها هي.
     * إعلانها بالمقاس المستهدف يخدع المتصفح فيختارها لمساحة أوسع مما تحتمل.
     */
    public function test_the_srcset_reports_real_widths_not_target_sizes(): void
    {
        $media = app(ImageService::class)->store(
            file: $this->jpeg(width: 1200, height: 800),
            post: Post::firstOrFail(),
        );

        $srcset = $media->srcset();

        $this->assertStringContainsString('400w', $srcset);
        $this->assertStringContainsString('1200w', $srcset);
        $this->assertStringNotContainsString('1600w', $srcset, 'لا يجوز إعلان عرض أكبر من الأصل.');
        $this->assertStringNotContainsString('2400w', $srcset);

        // الملف الواحد لا يتكرّر حين يتطابق فيه مقاسان
        $urls = array_map(fn (string $e) => explode(' ', trim($e))[0], explode(',', $srcset));
        $this->assertSame(count($urls), count(array_unique($urls)));
    }

    public function test_making_an_image_the_cover_clears_the_previous_one(): void
    {
        $post = Post::firstOrFail();
        $service = app(ImageService::class);

        $first = $service->store(file: $this->jpeg(), post: $post, isCover: true);
        $second = $service->store(file: $this->jpeg(), post: $post);

        $service->makeCover($second);

        $this->assertFalse($first->refresh()->is_cover);
        $this->assertTrue($second->refresh()->is_cover);
    }
}
