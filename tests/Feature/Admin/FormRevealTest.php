<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Client;
use App\Models\Faq;
use App\Models\Section;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * نماذج اللوحة تُرسم في موضع ثابت والقوائم تطول تحتها أو فوقها، فيُفتح النموذج
 * خارج مجال النظر ويبدو الضغط بلا أثر. كل لوحة تُعلن فتح نموذجها بحدث، ومكوّن
 * x-admin.reveal يمرّر إليه. هذه الاختبارات تحرس طرف الإعلان.
 */
class FormRevealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    private function section(): Section
    {
        return Section::create([
            'slug' => 'events',
            'name' => 'مناسبات',
            'icon' => 'camera',
            'is_active' => true,
            'has_categories' => true,
        ]);
    }

    public function test_the_clients_panel_announces_its_form(): void
    {
        $client = Client::create(['name' => 'جهة']);

        Livewire::test('pages::admin.clients')
            ->call('create')
            ->assertDispatched('client-form-opened');

        Livewire::test('pages::admin.clients')
            ->call('edit', $client->id)
            ->assertDispatched('client-form-opened');
    }

    public function test_the_testimonials_panel_announces_its_form(): void
    {
        $item = Testimonial::create([
            'name' => 'عميل',
            'content' => 'نصّ الرأي كما وصل.',
            'rating' => 5,
        ]);

        Livewire::test('pages::admin.testimonials')
            ->call('create')
            ->assertDispatched('testimonial-form-opened');

        Livewire::test('pages::admin.testimonials')
            ->call('edit', $item->id)
            ->assertDispatched('testimonial-form-opened');
    }

    public function test_the_faqs_panel_announces_its_form(): void
    {
        $faq = Faq::create([
            'question' => 'كم تستغرق جلسة التصوير؟',
            'answer' => 'ساعتان في المتوسّط.',
        ]);

        Livewire::test('pages::admin.faqs')
            ->call('create')
            ->assertDispatched('faq-form-opened');

        Livewire::test('pages::admin.faqs')
            ->call('edit', $faq->id)
            ->assertDispatched('faq-form-opened');
    }

    /** أربعة مداخل هنا: قسم رئيسي وفرعي، إنشاءً وتعديلًا. */
    public function test_the_sections_panel_announces_its_form_from_every_entry(): void
    {
        $section = $this->section();

        $category = Category::create([
            'section_id' => $section->id,
            'slug' => 'weddings',
            'name' => 'أعراس',
            'is_active' => true,
        ]);

        Livewire::test('pages::admin.sections')
            ->call('newSection')
            ->assertDispatched('section-form-opened');

        Livewire::test('pages::admin.sections')
            ->call('editSection', $section->id)
            ->assertDispatched('section-form-opened');

        Livewire::test('pages::admin.sections')
            ->call('newCategory', $section->id)
            ->assertDispatched('section-form-opened');

        Livewire::test('pages::admin.sections')
            ->call('editCategory', $category->id)
            ->assertDispatched('section-form-opened');
    }
}
