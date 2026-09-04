<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * إعادة بناء محتوى صفحة التصوير العقاري: مجموعات مواضيعية بدل معرض واحد.
     *
     * الزائر يبحث عن مساحة بعينها لا عن ألبوم — المطوّر يريد المرافق، وضيف
     * الإيجار القصير يريد المطبخ والغسالة. فتُعرض الصور مجموعةً مجموعة.
     *
     * وكل مجموعة عملٌ في المعرض لا صفٌّ في ملف إعدادات، فيبقى تحريرها وإضافة
     * الصور إليها من لوحة التحكم كأي عمل آخر.
     *
     * الربط بالاسم الأصلي للملف لا بالمعرّف: قاعدة الموقع المنشور تحمل الصور
     * نفسها بمعرّفات أخرى، والاسم الأصلي هو ما يشترك فيه الطرفان.
     */
    public function up(): void
    {
        $section = Section::where('slug', Section::SERVICES)->first();

        $category = $section
            ? Category::where('section_id', $section->id)->where('slug', Category::REAL_ESTATE)->first()
            : null;

        if (! $section || ! $category) {
            return;
        }

        $emptied = [];

        foreach ($this->groups() as $order => $group) {
            $post = Post::updateOrCreate(
                ['category_id' => $category->id, 'slug' => $group['slug']],
                [
                    'section_id' => $section->id,
                    'title' => $group['title'],
                    'excerpt' => $group['excerpt'],
                    'location' => config('site.location.city'),
                    'status' => 'published',
                    'published_at' => now(),
                    'sort_order' => $order + 1,
                    'seo_title' => $group['title'].' — تصوير عقاري في '.config('site.location.city'),
                    'seo_description' => $group['excerpt'],
                ],
            );

            foreach (array_values($group['files']) as $i => $name) {
                $media = Media::where('original_name', $name)->first();

                if (! $media) {
                    continue;
                }

                if ($media->post_id && $media->post_id !== $post->id) {
                    $emptied[$media->post_id] = true;
                }

                $media->update([
                    'post_id' => $post->id,
                    'sort_order' => $i + 1,
                    'is_cover' => $i === 0,
                ]);
            }
        }

        /*
         * العمل الذي فرغ من صوره بعد النقل يعود مسودّة ولا يُحذف: الحذف يمحو
         * ملفات صوره عبر خطّاف الحذف، وصوره انتقلت للتوّ إلى المجموعات.
         */
        foreach (array_keys($emptied) as $id) {
            $post = Post::withCount('media')->find($id);

            if ($post && $post->media_count === 0) {
                $post->update(['status' => 'draft', 'is_featured' => false]);
            }
        }

        /*
         * خانات الصفحة: صورة الواجهة وصور المبادئ الثلاثة. تُعلَّم بـ usage لا
         * باسم الملف في القالب، فيبقى تبديلها لاحقًا تحديثَ صفٍّ واحد.
         */
        foreach ($this->slots() as $usage => $name) {
            Media::where('original_name', $name)->update(['usage' => $usage]);
        }

        foreach ($this->faqs() as $order => [$question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                [
                    'answer' => $answer,
                    'section_id' => $section->id,
                    'category_id' => $category->id,
                    'sort_order' => 20 + $order,
                    'is_active' => true,
                ],
            );
        }
    }

    public function down(): void
    {
        Media::whereIn('usage', array_keys($this->slots()))->update(['usage' => null]);

        foreach ($this->faqs() as [$question]) {
            Faq::where('question', $question)->delete();
        }

        /*
         * الصور تُفصل عن المجموعة قبل حذفها، وإلا محا خطّاف الحذف ملفاتها.
         * فصلها يُبقيها في قاعدة البيانات بلا عمل، فتعود بإعادة تشغيل الترحيل.
         */
        foreach (Post::whereIn('slug', array_column($this->groups(), 'slug'))->get() as $post) {
            Media::where('post_id', $post->id)->update(['post_id' => null]);
            $post->delete();
        }
    }

    /**
     * المجموعات بترتيب العرض — مسير الزائر: يقترب من المشروع ثم يدخله.
     *
     * @return array<int, array{slug: string, title: string, excerpt: string, files: array<int, string>}>
     */
    private function groups(): array
    {
        return [
            [
                'slug' => 'almrafq-alkharijiya',
                'title' => 'المرافق الخارجية واللاند سكيب',
                'excerpt' => 'المسابح والملاعب والساحات والواجهات — ما يبيع المشروع قبل أن يُفتح باب الوحدة. على منصات الحجز والإدراج هذه الصور هي ما يقرّر النقرة، قبل أن يرى الزائر غرفة واحدة من الداخل.',
                'files' => [
                    'Mutaz_Satti-18.jpg.jpeg',
                    'file_00000000878c720a8cc787bed7507212.jpg.jpeg',
                    'file_00000000c22881f78f2b56c835942cb4.jpg.jpeg',
                    'file_000000005bb0820ab56f64588d89ca27.jpg.jpeg',
                    'file_00000000db38820abf571235711cfff6.jpg.jpeg',
                    'Generated Image January 15, 2026 - 3_28PM.jpg.jpeg',
                    '1775813043623 (1).jpg.jpeg',
                    '@Mutaz_Satti -4.jpg.jpeg',
                ],
            ],
            [
                'slug' => 'almdakhl-walmmrat',
                'title' => 'المداخل والممرات',
                'excerpt' => 'الانطباع الأول قبل دخول الوحدة. المدخل يقول عن مستوى المشروع ما لا تقوله صورة المجلس، فيُصوَّر ولا يُتجاوز.',
                'files' => [],
            ],
            [
                'slug' => 'almjals-walmaisha',
                'title' => 'المجالس والمعيشة',
                'excerpt' => 'حيث يتخيّل المشتري أو الضيف نفسه جالسًا. أوسع مساحة في الوحدة وأكثرها حضورًا في قرار الشراء أو الحجز.',
                'files' => [
                    '@mutaz_satti (16).jpg.jpeg',
                ],
            ],
            [
                'slug' => 'ghrf-alnoom',
                'title' => 'غرف النوم',
                'excerpt' => 'الخصوصية والراحة في لقطة واحدة. الإضاءة هنا أهمّ من الزاوية: غرفة النوم تُصوَّر لتبدو هادئة لا لتبدو واسعة.',
                'files' => [
                    'Generated Image January 15, 2026 - 3_37PM.jpg.jpeg',
                    '@mutaz_satti-2449.jpg.jpeg',
                ],
            ],
            [
                'slug' => 'almtabkh-wmoaid-altaam',
                'title' => 'المطابخ وموائد الطعام',
                'excerpt' => 'أكثر مساحة يُسأل عنها في المعاينة. الأسطح العاكسة والأجهزة المدمجة تحتاج ضبط إضاءة دقيقًا حتى لا تتحوّل إلى بقع بيضاء.',
                'files' => [
                    'Generated Image January 15, 2026 - 4_38PM (1).jpg.jpeg',
                ],
            ],
            [
                'slug' => 'almrafq-waltfasyl',
                'title' => 'المرافق والتفاصيل',
                'excerpt' => 'ما يبحث عنه ضيف الإيجار القصير قبل أن يحجز: الغسالة، ركن القهوة، مكتب العمل، دورة المياه. تفاصيل صغيرة تتكرّر في كل رسالة تسبق الحجز.',
                'files' => [],
            ],
            [
                'slug' => 'almkatb-altjarya',
                'title' => 'المكاتب التجارية',
                'excerpt' => 'التصوير المؤسسي بلغة بصرية مختلفة: مكاتب، وقاعات اجتماعات، وواجهات أبراج. سوق يدفع أكثر ويطلب صورًا أرصن.',
                'files' => [
                    'HM7A8588.jpg.jpeg',
                    '@mutaz_satti (13).jpg.jpeg',
                ],
            ],
        ];
    }

    /**
     * خانات الصفحة الثابتة: usage ← الاسم الأصلي للملف.
     *
     * خانتا المقارنة (re_before و re_after) تُملآن حين تُستورد اللقطة الخام
     * ومعالَجتها من الأرشيف، وقسم «قبل وبعد» لا يظهر قبل امتلائهما.
     *
     * @return array<string, string>
     */
    private function slots(): array
    {
        return [
            're_hero' => 'Mutaz_Satti-18.jpg.jpeg',
            're_craft_verticals' => 'file_00000000878c720a8cc787bed7507212.jpg.jpeg',
            're_craft_bluehour' => '@mutaz_satti (13).jpg.jpeg',
            're_craft_styling' => '@mutaz_satti (16).jpg.jpeg',
        ];
    }

    /**
     * أسئلة تخصّ هذه الخدمة وحدها — تُنشر كبيانات FAQPage مهيكلة.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function faqs(): array
    {
        return [
            [
                'كم صورة تحتاج الشقة؟',
                'الشقة الاعتيادية بين 15 و25 صورة: المعيشة والمطبخ وكل غرفة نوم وحمّام، إضافة إلى المرافق والإطلالة. الاستوديو يكفيه 10 إلى 15 صورة، والفلل تحتاج أكثر بحسب عدد المجالس والأدوار.',
            ],
            [
                'هل تصوّر الوحدات غير المؤثثة؟',
                'نعم، وهي أصعب من المفروشة: لا يوجد أثاث يملأ الإطار أو يكسر الفراغ، فيصبح التكوين والإضاءة وتصحيح المنظور كل ما تعتمد عليه الصورة.',
            ],
            [
                'هل تسلّم مقاسات جاهزة لمنصات الإدراج وإنستقرام؟',
                'نعم. الصور تُسلَّم أفقية بالمقاس الذي تطلبه منصات الحجز والإدراج، ومعها نسخ رأسية لمن يسوّق وحدته على حساباته.',
            ],
            [
                'كيف تضمن تشابه الإضاءة بين وحدات المشروع الواحد؟',
                'بتثبيت إعدادات الكاميرا وترتيب الإضاءة وزوايا التصوير عبر الوحدات المتشابهة، ثم معالجتها كدفعة واحدة بالإعدادات نفسها. النتيجة ملف يبدو من جلسة واحدة لا من عشر جلسات.',
            ],
            [
                'ما الذي أجهّزه قبل وصولك لتصوير العقار؟',
                'إضاءة المصابيح كلها تعمل، الستائر مفتوحة، الأسطح خالية من الأدوات الشخصية والأسلاك، سلال المهملات مخفية، والمياه والمناشف مرتّبة. أرسل قائمة تفصيلية بعد الحجز.',
            ],
        ];
    }
};
