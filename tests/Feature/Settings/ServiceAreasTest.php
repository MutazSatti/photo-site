<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use App\Support\Schema;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceAreasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class]);
    }

    public function test_the_seeded_scope_is_the_owner_city_alone(): void
    {
        $this->assertSame([config('site.location.city')], service_areas());
    }

    public function test_the_owner_can_list_several_cities(): void
    {
        Setting::put('service_areas', "جدة\nمكة المكرمة\nالطائف");

        $this->assertSame(['جدة', 'مكة المكرمة', 'الطائف'], service_areas());
    }

    /** الفواصل الثلاث مقبولة، فلا يضطرّ المالك إلى تذكّر صيغة. */
    public function test_commas_work_as_well_as_line_breaks(): void
    {
        Setting::put('service_areas', 'جدة، مكة المكرمة, الطائف');

        $this->assertSame(['جدة', 'مكة المكرمة', 'الطائف'], service_areas());
    }

    public function test_blank_entries_are_dropped(): void
    {
        Setting::put('service_areas', "جدة\n\n  \nمكة المكرمة\n");

        $this->assertSame(['جدة', 'مكة المكرمة'], service_areas());
    }

    /** حقلٌ مُسح سهوًا يجب ألا يترك صفحة التواصل بلا نطاق خدمة. */
    public function test_an_emptied_field_falls_back_to_the_config(): void
    {
        Setting::put('service_areas', '   ');

        $this->assertSame((array) config('site.service_areas'), service_areas());
        $this->assertNotSame([], service_areas());
    }

    public function test_the_scope_reaches_every_place_it_is_shown(): void
    {
        Setting::put('service_areas', 'جدة');

        // صفحة التواصل، وصفحة نبذة، وشريط الحجز في أسفلهما
        $this->get(route('contact'))->assertSee('التغطية متاحة في جدة.', false);
        $this->get(route('about'))->assertSee('نطاق الخدمة', false);

        // والبيانات المهيكلة التي تقرؤها محرّكات البحث — تُطبع منسّقة على
        // أسطر، فيُتحقّق من بنيتها لا من نصّها الحرفي
        $this->assertSame(
            [['@type' => 'City', 'name' => 'جدة']],
            Schema::serviceAreas(),
        );
    }

    public function test_editing_the_scope_from_the_panel_changes_the_site(): void
    {
        $this->actingAs(User::factory()->create());

        $field = Setting::where('key', 'service_areas')->firstOrFail();

        Livewire::test('pages::admin.settings')
            ->set('tab', 'contact')
            ->set('values.'.$field->key, "جدة\nينبع")
            ->call('save')
            ->assertHasNoErrors();

        Setting::flush();

        $this->assertSame(['جدة', 'ينبع'], service_areas());
    }

    /**
     * البذرة تُشغَّل مع كل نشر، فكتابتها القيمةَ في كل مرة كانت تمسح ما
     * حرّره المالك وتعيده إلى نصّ الشيفرة بصمت.
     */
    public function test_reseeding_keeps_what_the_owner_edited(): void
    {
        Setting::put('service_areas', 'الطائف');

        $this->seed(SettingSeeder::class);
        Setting::flush();

        $this->assertSame(['الطائف'], service_areas());
    }

    /**
     * البيانات المهيكلة لا تعلن نطاقًا أوسع ممّا تقوله الصفحة.
     *
     * نقطة التواصل كانت تنشر «SA» أي المملكة كلها، فيقرأ محرّك البحث وعدًا لم
     * يقطعه المالك ويصله طلب من مدينة لا يغطّيها.
     */
    public function test_no_entity_publishes_a_wider_scope_than_the_owner_set(): void
    {
        $json = json_encode([
            Schema::contactPage(),
            Schema::business(),
        ], JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('"areaServed":"SA"', (string) $json);
        $this->assertStringContainsString('جدة', (string) $json);
    }

    /** وتغيير النطاق من اللوحة يصل إلى البيانات المهيكلة كما يصل إلى الصفحة. */
    public function test_the_published_scope_follows_the_setting(): void
    {
        Setting::put('service_areas', 'ينبع');

        $json = (string) json_encode(Schema::contactPage(), JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('ينبع', $json);
        $this->assertStringNotContainsString('جدة', $json);
    }

    /** أما شرح الحقل فيُحدَّث دائمًا: هو وصفُه لا محتواه. */
    public function test_reseeding_still_refreshes_the_field_description(): void
    {
        Setting::where('key', 'service_areas')->update(['hint' => 'تلميح قديم', 'sort_order' => 99]);

        $this->seed(SettingSeeder::class);

        $field = Setting::where('key', 'service_areas')->firstOrFail();

        $this->assertNotSame('تلميح قديم', $field->hint);
        $this->assertSame(6, $field->sort_order);
    }
}
