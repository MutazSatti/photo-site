<?php

namespace Tests\Feature\Site;

use App\Models\Faq;
use Database\Seeders\FaqSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الأسئلة محتوى المالك، لا نصٌّ يُعاد كتابته بعد كل نشر.
 */
class FaqContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SectionSeeder::class, SettingSeeder::class, FaqSeeder::class]);
    }

    /**
     * البذرة تُشغَّل بعد كل سحب من GitHub. ولو أعادت كتابة الأجوبة لعاد كل
     * تحرير من اللوحة إلى ما في الشيفرة بلا سبب ظاهر.
     */
    public function test_reseeding_keeps_an_edited_answer(): void
    {
        $faq = Faq::where('question', 'like', '%خارج جدة%')->firstOrFail();

        $faq->update(['answer' => 'التغطية في جدة وحدها.']);

        $this->seed(FaqSeeder::class);

        $this->assertSame('التغطية في جدة وحدها.', $faq->fresh()->answer);
    }

    public function test_reseeding_does_not_duplicate_a_question(): void
    {
        $before = Faq::count();

        $this->seed(FaqSeeder::class);

        $this->assertSame($before, Faq::count());
    }

    /** الصفّ كما هو في موقعٍ قائم قبل تغيير اسم القسم. */
    private function legacyQuestion(string $answer): Faq
    {
        return Faq::create([
            'question' => 'ما الفرق بين قسم "المناسبات" وقسم "الفعاليات"؟',
            'answer' => $answer,
            'sort_order' => 8,
            'is_active' => true,
        ]);
    }

    private function shippedAnswer(): string
    {
        return 'قسم "المناسبات" للمناسبات الخاصة والعائلية: حفلات التخرّج، الأعياد، والاحتفالات العائلية. أما "الفعاليات" فللأنشطة المؤسسية والعامة: المؤتمرات، المعارض، الملتقيات، والبرامج التدريبية. الفرق ليس شكليًا — لكل منهما أسلوب تغطية ومتطلبات تسليم مختلفة.';
    }

    /** السؤال الفارق بين القسمين يسمّي القسم باسمه بعد تغييره. */
    public function test_the_section_question_carries_the_current_name(): void
    {
        $this->assertDatabaseHas('faqs', [
            'question' => 'ما الفرق بين قسم "الزواجات والمناسبات" وقسم "الفعاليات"؟',
        ]);

        $this->assertDatabaseMissing('faqs', [
            'question' => 'ما الفرق بين قسم "المناسبات" وقسم "الفعاليات"؟',
        ]);

        $this->assertStringNotContainsString(
            'الاحتفالات العائلية',
            (string) Faq::where('question', 'like', '%الزواجات والمناسبات%')->value('answer'),
        );
    }

    /** وعلى موقع قائم تصحّح الهجرة الاسم والوصف معًا. */
    public function test_the_migration_renames_a_legacy_row(): void
    {
        Faq::where('question', 'like', '%الزواجات والمناسبات%')->delete();
        $faq = $this->legacyQuestion($this->shippedAnswer());

        (require database_path('migrations/2026_09_17_000003_rename_events_section_in_faq.php'))->up();

        $this->assertStringContainsString('الزواجات والمناسبات', $faq->fresh()->question);
        $this->assertStringNotContainsString('الاحتفالات العائلية', $faq->fresh()->answer);
    }

    /** أما جوابٌ حرّره المالك فلا تمسّه الهجرة، وتكتفي بتصحيح اسم القسم. */
    public function test_the_rename_does_not_overwrite_an_edited_answer(): void
    {
        Faq::where('question', 'like', '%الزواجات والمناسبات%')->delete();
        $faq = $this->legacyQuestion('جواب من عندي.');

        (require database_path('migrations/2026_09_17_000003_rename_events_section_in_faq.php'))->up();

        $this->assertSame('جواب من عندي.', $faq->fresh()->answer);
        $this->assertStringContainsString('الزواجات والمناسبات', $faq->fresh()->question);
    }
}
