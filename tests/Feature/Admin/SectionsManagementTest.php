<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SectionsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    private function section(string $slug, string $name): Section
    {
        return Section::create([
            'slug' => $slug,
            'name' => $name,
            'icon' => 'camera',
            'is_active' => true,
            'has_categories' => false,
        ]);
    }

    public function test_a_new_main_section_can_be_created(): void
    {
        Livewire::test('pages::admin.sections')
            ->call('newSection')
            ->set('name', 'قصص مصوّرة')
            ->set('slug', 'stories')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sections', ['slug' => 'stories', 'name' => 'قصص مصوّرة']);
    }

    public function test_a_main_section_slug_can_be_changed(): void
    {
        $section = $this->section('workshops', 'ورش تدريبية');

        Livewire::test('pages::admin.sections')
            ->call('editSection', $section->id)
            ->set('slug', 'training')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('training', $section->refresh()->slug);
    }

    public function test_a_slug_reserved_for_a_static_page_is_rejected(): void
    {
        Livewire::test('pages::admin.sections')
            ->call('newSection')
            ->set('name', 'من نحن')
            ->set('slug', 'about')
            ->call('save')
            ->assertHasErrors('slug');

        $this->assertDatabaseMissing('sections', ['slug' => 'about']);
    }

    public function test_two_main_sections_cannot_share_a_slug(): void
    {
        $this->section('articles', 'مقالات');

        Livewire::test('pages::admin.sections')
            ->call('newSection')
            ->set('name', 'مقالات أخرى')
            ->set('slug', 'articles')
            ->call('save')
            ->assertHasErrors('slug');

        $this->assertSame(1, Section::where('slug', 'articles')->count());
    }

    /** لم تكن الأقسام الفرعية متاحة إلا تحت "خدمات التصوير". */
    public function test_a_subsection_can_be_added_to_any_main_section(): void
    {
        $workshops = $this->section('workshops', 'ورش تدريبية');

        Livewire::test('pages::admin.sections')
            ->call('newCategory', $workshops->id)
            ->set('name', 'الإضاءة')
            ->set('slug', 'lighting')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'section_id' => $workshops->id,
            'slug' => 'lighting',
        ]);

        // العلم يتبع الواقع، فتعرف بقية الشيفرة أن للقسم أقسامًا فرعية
        $this->assertTrue($workshops->refresh()->has_categories);
    }

    public function test_a_subsection_is_deleted_and_the_flag_follows(): void
    {
        $section = $this->section('tips', 'منشورات تعليمية');

        $category = Category::create([
            'section_id' => $section->id,
            'slug' => 'basics',
            'name' => 'أساسيات',
            'icon' => 'camera',
            'is_active' => true,
        ]);

        $section->update(['has_categories' => true]);

        Livewire::test('pages::admin.sections')->call('deleteCategory', $category->id);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertFalse($section->refresh()->has_categories);
    }

    public function test_a_main_section_holding_items_is_not_deleted(): void
    {
        $section = $this->section('services', 'خدمات التصوير');

        Post::create([
            'section_id' => $section->id,
            'slug' => 'a-wedding',
            'title' => 'حفل زفاف',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Livewire::test('pages::admin.sections')->call('deleteSection', $section->id);

        $this->assertDatabaseHas('sections', ['id' => $section->id]);
    }

    public function test_an_empty_main_section_is_deleted_with_its_empty_subsections(): void
    {
        $section = $this->section('stories', 'قصص مصوّرة');

        $category = Category::create([
            'section_id' => $section->id,
            'slug' => 'weddings',
            'name' => 'أعراس',
            'icon' => 'camera',
            'is_active' => true,
        ]);

        Livewire::test('pages::admin.sections')->call('deleteSection', $section->id);

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
