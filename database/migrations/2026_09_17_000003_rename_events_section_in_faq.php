<?php

use App\Models\Faq;
use Illuminate\Database\Migrations\Migration;

/**
 * السؤال الذي يفرّق بين القسمين صار يسمّي قسمًا بغير اسمه.
 *
 * «المناسبات» صارت «الزواجات والمناسبات»، ووصفها في الجواب بقي على ما كان:
 * مناسبات عائلية وأعياد. وذلك ليس ما يغطّيه القسم اليوم — تغطيته لليلة الزواج
 * وحفلات التخرّج والتكريم، في القسم الرجالي.
 *
 * والجواب لا يُستبدل إلا إن كان كما شُحن: مالكٌ حرّره من اللوحة أعلم بما كتب،
 * وهجرةٌ تمحو تحريره أسوأ من سؤال يحمل اسمًا قديمًا. أما السؤال نفسه فيُصحَّح
 * دائمًا، لأنه اسم قسمٍ في الموقع لا رأيًا يُحرَّر.
 */
return new class extends Migration
{
    public function up(): void
    {
        $faq = Faq::where('question', $this->oldQuestion())->first();

        if (! $faq) {
            return;
        }

        $faq->update([
            'question' => $this->newQuestion(),
            ...($faq->answer === $this->oldAnswer() ? ['answer' => $this->newAnswer()] : []),
        ]);
    }

    public function down(): void
    {
        $faq = Faq::where('question', $this->newQuestion())->first();

        if (! $faq) {
            return;
        }

        $faq->update([
            'question' => $this->oldQuestion(),
            ...($faq->answer === $this->newAnswer() ? ['answer' => $this->oldAnswer()] : []),
        ]);
    }

    private function oldQuestion(): string
    {
        return 'ما الفرق بين قسم "المناسبات" وقسم "الفعاليات"؟';
    }

    private function newQuestion(): string
    {
        return 'ما الفرق بين قسم "الزواجات والمناسبات" وقسم "الفعاليات"؟';
    }

    private function oldAnswer(): string
    {
        return 'قسم "المناسبات" للمناسبات الخاصة والعائلية: حفلات التخرّج، الأعياد، والاحتفالات العائلية. أما "الفعاليات" فللأنشطة المؤسسية والعامة: المؤتمرات، المعارض، الملتقيات، والبرامج التدريبية. الفرق ليس شكليًا — لكل منهما أسلوب تغطية ومتطلبات تسليم مختلفة.';
    }

    private function newAnswer(): string
    {
        return 'قسم "الزواجات والمناسبات" لليلة الزواج وما يشبهها: الاستقبال والتهاني، والصور الخاصة بالعريس، والزفّة، والصور الجماعية مع العائلة والضيوف — إضافة إلى حفلات التخرّج والتكريم والمعايدات، والتغطية فيه للقسم الرجالي. أما "الفعاليات" فللأنشطة المؤسسية والعامة: المؤتمرات، المعارض، الملتقيات، والبرامج التدريبية. الفرق ليس شكليًا — لكل منهما أسلوب تغطية ومتطلبات تسليم مختلفة.';
    }
};
