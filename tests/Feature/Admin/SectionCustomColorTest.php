<?php

namespace Tests\Feature\Admin;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SectionCustomColorTest extends TestCase
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
            'slug' => 'stories',
            'name' => 'قصص مصوّرة',
            'icon' => 'camera',
            'color' => 'teal',
            'is_active' => true,
            'has_categories' => false,
        ]);
    }

    public function test_a_preset_colour_still_works(): void
    {
        $section = $this->section();

        $this->assertFalse($section->hasCustomColor());
        $this->assertSame(['#0f766e', '#2dd4bf'], $section->colorPair());
        $this->assertSame('--sec:#0f766e;--sec-dark:#2dd4bf', $section->colorStyle());
    }

    public function test_the_owner_can_save_a_colour_outside_the_palette(): void
    {
        $section = $this->section();

        Livewire::test('pages::admin.sections')
            ->call('editSection', $section->id)
            ->set('color', 'custom')
            ->set('color_light', '#7b2d8e')
            ->set('color_dark', '#d9a7e8')
            ->call('save')
            ->assertHasNoErrors();

        $section->refresh();

        $this->assertTrue($section->hasCustomColor());
        $this->assertSame(['#7b2d8e', '#d9a7e8'], $section->colorPair());
        $this->assertSame('لون مخصّص', $section->colorLabel());
    }

    /** المخصّص يُمسح عند العودة إلى اللوحة، فلا يبقى صفٌّ يحمل الاثنين. */
    public function test_choosing_a_preset_again_clears_the_custom_pair(): void
    {
        $section = $this->section();
        $section->update(['color_light' => '#7b2d8e', 'color_dark' => '#d9a7e8']);

        Livewire::test('pages::admin.sections')
            ->call('editSection', $section->id)
            ->set('color', 'rose')
            ->call('save')
            ->assertHasNoErrors();

        $section->refresh();

        $this->assertFalse($section->hasCustomColor());
        $this->assertNull($section->color_light);
        $this->assertSame('rose', $section->color);
    }

    public function test_editing_a_custom_section_reopens_on_the_custom_swatch(): void
    {
        $section = $this->section();
        $section->update(['color_light' => '#123456', 'color_dark' => '#abcdef']);

        Livewire::test('pages::admin.sections')
            ->call('editSection', $section->id)
            ->assertSet('color', 'custom')
            ->assertSet('color_light', '#123456')
            ->assertSet('color_dark', '#abcdef');
    }

    public function test_a_malformed_colour_is_rejected(): void
    {
        $section = $this->section();

        Livewire::test('pages::admin.sections')
            ->call('editSection', $section->id)
            ->set('color', 'custom')
            ->set('color_light', 'red')
            ->set('color_dark', '#d9a7e8')
            ->call('save')
            ->assertHasErrors('color_light');

        $this->assertNull($section->refresh()->color_light);
    }

    /** لونٌ واحد يعني قسمًا يقرأ في وضع ويختفي في الآخر. */
    public function test_both_shades_are_required_together(): void
    {
        $section = $this->section();

        Livewire::test('pages::admin.sections')
            ->call('editSection', $section->id)
            ->set('color', 'custom')
            ->set('color_light', '#7b2d8e')
            ->set('color_dark', '')
            ->call('save')
            ->assertHasErrors('color_dark');
    }

    /**
     * القيمة تُطبع داخل سمة style، فصفٌّ فاسد من بذرة أو تعديل مباشر على
     * القاعدة يجب ألا يخرج منها إلى بقية التنسيق.
     */
    public function test_a_corrupt_stored_colour_falls_back_to_the_palette(): void
    {
        $section = $this->section();
        $section->forceFill(['color_light' => 'red; }', 'color_dark' => '#abcdef'])->save();

        $this->assertFalse($section->fresh()->hasCustomColor());
        $this->assertStringNotContainsString('red', $section->fresh()->colorStyle());
    }

    public function test_a_category_inherits_the_custom_colour_of_its_section(): void
    {
        $section = $this->section();
        $section->update(['has_categories' => true, 'color_light' => '#7b2d8e', 'color_dark' => '#d9a7e8']);

        $this->assertSame('--sec:#7b2d8e;--sec-dark:#d9a7e8', $section->fresh()->colorStyle());
    }
}
