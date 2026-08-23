<?php

namespace Tests\Feature\Site;

use App\Models\ContactMessage;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class]);
    }

    public function test_a_visitor_can_send_a_booking_request(): void
    {
        Livewire::test('pages::contact')
            ->set('name', 'عبدالله محمد')
            ->set('phone', '0551234567')
            ->set('email', 'abdullah@example.com')
            ->set('service', 'المناسبات')
            ->set('message', 'أرغب في تغطية حفل تخرّج في جدة يوم الخميس القادم من العصر حتى المساء.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'عبدالله محمد',
            'phone' => '0551234567',
            'service' => 'المناسبات',
            'status' => 'new',
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        Livewire::test('pages::contact')
            ->set('name', 'ا')
            ->set('phone', '')
            ->set('message', 'قصير')
            ->call('submit')
            ->assertHasErrors(['name', 'phone', 'message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_a_past_event_date_is_rejected(): void
    {
        Livewire::test('pages::contact')
            ->set('name', 'سارة أحمد')
            ->set('phone', '0559876543')
            ->set('eventDate', now()->subWeek()->toDateString())
            ->set('message', 'أرغب في حجز جلسة تصوير عائلية في نهاية الأسبوع القادم.')
            ->call('submit')
            ->assertHasErrors(['eventDate']);
    }

    /** الحقل المخفي يملؤه الروبوت فقط — النتيجة نجاح ظاهري بلا حفظ. */
    public function test_the_honeypot_field_silently_discards_bot_submissions(): void
    {
        Livewire::test('pages::contact')
            ->set('name', 'Spam Bot')
            ->set('phone', '0000000000')
            ->set('message', 'buy cheap things now')
            ->set('website', 'http://spam.example')
            ->call('submit')
            ->assertSet('sent', true);

        $this->assertSame(0, ContactMessage::count());
    }
}
