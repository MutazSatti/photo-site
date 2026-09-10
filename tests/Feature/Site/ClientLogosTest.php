<?php

namespace Tests\Feature\Site;

use App\Models\Client;
use App\Models\Media;
use App\Models\User;
use App\Services\ImageService;
use Database\Seeders\FaqSeeder;
use Database\Seeders\HomeBlockSeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ClientLogosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class, PostSeeder::class, HomeBlockSeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * شعار حقيقي — رفع صورة مزيّفة الاسم فقط لا يمرّ من محوّل WebP.
     *
     * UploadedFile::fake()->image يولّد ملف PNG حقيقيًا عبر GD، ويعيد
     * Illuminate\Http\Testing\File التي تحمل خاصية name العامة. ومسار الرفع في
     * اختبارات Livewire يقرأ هذه الخاصية بعينها، فبناء UploadedFile مباشرةً
     * يمرّ بلا صورة مزيّفة لكنه يسقط عند القراءة منها.
     */
    private function logoFile(string $name = 'logo.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 600, 200);
    }

    private function skipWithoutWebp(): void
    {
        if (! ImageService::webpSupported()) {
            $this->markTestSkipped('إضافة GD أو Imagick غير مفعّلة في هذه البيئة.');
        }
    }

    /**
     * شعار محفوظ سلفًا بلا مرور من محوّل الصور.
     *
     * قواعد ملكية الشعار — حذفه مع الجهة، واستبداله عند رفع غيره — يجب أن
     * تُختبر في كل بيئة، لا في البيئات التي تملك GD وحدها.
     */
    private function attachLogo(Client $client, string $path = 'media/test/logo.webp'): Media
    {
        Storage::disk('public')->put($path, 'webp-bytes');

        $media = Media::create([
            'usage' => Client::LOGO_USAGE,
            'disk' => 'public',
            'path' => $path,
            'variants' => ['full' => $path, 'thumb' => $path],
            'width' => 600,
            'height' => 200,
            'alt' => $client->logoAlt(),
        ]);

        $client->update(['media_id' => $media->id]);
        $client->setRelation('logo', $media);

        return $media;
    }

    /** القسم بلا جهات يترك فراغًا بعنوان معلّق — فلا يُصيَّر أصلًا. */
    public function test_the_block_stays_out_of_the_page_until_a_client_is_added(): void
    {
        $this->get(route('home'))->assertDontSee('data-block="clients"', escape: false);

        Client::create(['name' => 'غرفة تجارة المدينة']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-block="clients"', escape: false)
            ->assertSee('غرفة تجارة المدينة');
    }

    public function test_a_hidden_client_does_not_appear_on_the_page(): void
    {
        Client::create(['name' => 'جهة ظاهرة']);
        Client::create(['name' => 'جهة مخفية', 'is_active' => false]);

        $this->get(route('home'))
            ->assertSee('جهة ظاهرة')
            ->assertDontSee('جهة مخفية');
    }

    public function test_clients_appear_in_the_stored_order(): void
    {
        Client::create(['name' => 'الجهة الثانية', 'sort_order' => 2]);
        Client::create(['name' => 'الجهة الأولى', 'sort_order' => 1]);

        $html = $this->get(route('home'))->getContent();

        $this->assertLessThan(
            strpos($html, 'الجهة الثانية'),
            strpos($html, 'الجهة الأولى'),
        );
    }

    /** الجهة ذات الرابط تصبح شعارها رابطًا، وبلا رابط تبقى بطاقة صامتة. */
    public function test_a_client_with_a_site_links_to_it(): void
    {
        Client::create(['name' => 'جهة برابط', 'url' => 'https://example.sa']);
        Client::create(['name' => 'جهة بلا رابط']);

        $this->get(route('home'))
            ->assertSee('https://example.sa', escape: false)
            ->assertSee('rel="noopener nofollow"', escape: false);
    }

    public function test_the_admin_uploads_a_logo_and_it_becomes_webp(): void
    {
        $this->skipWithoutWebp();
        $this->actingAs($this->admin());

        Livewire::test('pages::admin.clients')
            ->call('create')
            ->set('name', 'مركز التدريب الأهلي')
            ->set('url', 'https://example.sa')
            ->set('logo', $this->logoFile())
            ->call('save')
            ->assertHasNoErrors();

        $client = Client::where('name', 'مركز التدريب الأهلي')->firstOrFail();

        $this->assertNotNull($client->logo);
        $this->assertStringEndsWith('.webp', $client->logo->path);
        $this->assertSame(Client::LOGO_USAGE, $client->logo->usage);
        $this->assertSame('شعار مركز التدريب الأهلي', $client->logo->alt);
        Storage::disk('public')->assertExists($client->logo->path);

        // الشعار يظهر في الصفحة الرئيسية بمعالجته اللونية الموحّدة
        $this->get(route('home'))
            ->assertSee('logo-mark', escape: false)
            ->assertSee('شعار مركز التدريب الأهلي');
    }

    public function test_replacing_a_logo_removes_the_previous_file(): void
    {
        $this->skipWithoutWebp();
        $this->actingAs($this->admin());

        $client = Client::create(['name' => 'جهة']);
        $old = $this->attachLogo($client, 'media/test/old.webp');

        Livewire::test('pages::admin.clients')
            ->call('edit', $client->id)
            ->set('logo', $this->logoFile('new.png'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Media::find($old->id));
        Storage::disk('public')->assertMissing($old->path);

        $client->refresh();
        $this->assertNotNull($client->logo);
        $this->assertNotSame($old->id, $client->media_id);
    }

    public function test_deleting_a_client_deletes_its_logo(): void
    {
        $this->actingAs($this->admin());

        $client = Client::create(['name' => 'جهة تُحذف']);
        $media = $this->attachLogo($client);

        Livewire::test('pages::admin.clients')->call('delete', $client->id);

        $this->assertNull(Client::find($client->id));
        $this->assertNull(Media::find($media->id));
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_the_admin_removes_a_logo_without_deleting_the_client(): void
    {
        $this->actingAs($this->admin());

        $client = Client::create(['name' => 'جهة تحتفظ باسمها']);
        $media = $this->attachLogo($client);

        Livewire::test('pages::admin.clients')->call('deleteLogo', $client->id);

        $this->assertNull(Media::find($media->id));
        Storage::disk('public')->assertMissing($media->path);
        $this->assertNull($client->refresh()->media_id);

        // بلا شعار تبقى الجهة معروضة باسمها لا بمربّع فارغ
        $this->get(route('home'))->assertSee('جهة تحتفظ باسمها');
    }

    /** النص البديل للشعار هو اسم الجهة، فتعديل الاسم يجب أن يتبعه. */
    public function test_renaming_a_client_updates_its_logo_alt_text(): void
    {
        $this->actingAs($this->admin());

        $client = Client::create(['name' => 'الاسم القديم']);
        $media = $this->attachLogo($client);

        Livewire::test('pages::admin.clients')
            ->call('edit', $client->id)
            ->set('name', 'الاسم الجديد')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('شعار الاسم الجديد', $media->refresh()->alt);
    }

    public function test_the_admin_can_reorder_clients(): void
    {
        $this->actingAs($this->admin());

        $first = Client::create(['name' => 'الأولى', 'sort_order' => 0]);
        $second = Client::create(['name' => 'الثانية', 'sort_order' => 1]);

        Livewire::test('pages::admin.clients')->call('move', $second->id, 'up');

        $this->assertSame(0, $second->refresh()->sort_order);
        $this->assertSame(1, $first->refresh()->sort_order);
    }

    public function test_the_admin_rejects_a_malformed_site_address(): void
    {
        $this->actingAs($this->admin());

        Livewire::test('pages::admin.clients')
            ->call('create')
            ->set('name', 'جهة')
            ->set('url', 'not-a-url')
            ->call('save')
            ->assertHasErrors(['url' => 'url']);
    }

    public function test_the_clients_page_needs_a_signed_in_admin(): void
    {
        $this->get(route('admin.clients'))->assertRedirect(route('login'));

        $this->actingAs($this->admin())->get(route('admin.clients'))->assertOk();
    }
}
