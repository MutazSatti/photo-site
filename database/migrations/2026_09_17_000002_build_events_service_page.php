<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\Media;
use App\Models\PageBlock;
use App\Models\Post;
use App\Models\Section;
use Database\Seeders\PageBlockSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * محتوى صفحة الزواجات والمناسبات.
 *
 * ما كان معروضًا قبل هذه الهجرة هو تجميعات النشر كما هي: ثلاث صور مركّبة في
 * إطار واحد بمقاس القصص. وهي تصلح لحساب انستغرام ولا تصلح لمعرض — الزائر يرى
 * ثلاث صور مصغّرة لا صورة واحدة. فالمعروض الآن إطارات مفردة، مرتّبة في
 * مجموعات بتسلسل الليلة.
 *
 * والأعمال الأربعة القديمة تعود مسودّات لا تُحذف: صورها المركّبة باقية معها،
 * وإعادتها للنشر زرٌّ واحد في «الأعمال والمحتوى».
 *
 * القسم الرجالي وحده — لا كوشة ولا خروج، فتلك من نصيب تغطية العروس.
 */
return new class extends Migration
{
    public function up(): void
    {
        $section = Section::where('slug', Section::SERVICES)->first();

        if (! $section) {
            return;
        }

        $events = Category::where('section_id', $section->id)->where('slug', Category::EVENTS)->first();
        $activities = Category::where('section_id', $section->id)->where('slug', Category::ACTIVITIES)->first();

        if (! $events || ! $activities) {
            return;
        }

        /*
         * البذرة تستدعي هذه الهجرة بعد كل نشر، فلا يصحّ أن تكتب شيئًا مرّتين:
         * مالكٌ أعاد نشر عمل قديم أو غيّر عنوان مجموعة يجب ألّا يجد تعديله قد
         * أُلغي بصمت في النشر التالي. فما يُكتب هنا يُكتب مرّة واحدة، وأوّل
         * تشغيل يُعرف بغياب أقسام الصفحة من القاعدة.
         */
        $firstRun = ! PageBlock::onPage(PageBlock::EVENTS)->exists();

        if ($firstRun) {
            $this->renameCategory($events);
            $this->retireOldGroups($events);
        }

        foreach ($this->groups() as $order => $group) {
            $category = $group['category'] === Category::EVENTS ? $events : $activities;
            $keys = ['category_id' => $category->id, 'slug' => $group['slug']];

            if (Post::where($keys)->exists()) {
                continue;
            }

            Post::create([
                ...$keys,
                'section_id' => $section->id,
                'title' => $group['title'],
                'excerpt' => $group['excerpt'],
                'location' => config('site.location.city'),
                'status' => 'published',
                'published_at' => now(),
                'sort_order' => $order + 1,
                'seo_title' => $group['title'].' — '.$group['seo'],
                'seo_description' => $group['excerpt'],
            ]);
        }

        (new PageBlockSeeder)->seedPage(PageBlock::EVENTS);

        foreach ($this->faqs() as $order => [$question, $answer]) {
            if (Faq::where('question', $question)->exists()) {
                continue;
            }

            Faq::create([
                'question' => $question,
                'answer' => $answer,
                'section_id' => $section->id,
                'category_id' => $events->id,
                'sort_order' => 40 + $order,
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        Media::where('usage', 'ev_hero')->update(['usage' => null]);

        foreach ($this->faqs() as [$question]) {
            Faq::where('question', $question)->delete();
        }

        PageBlock::onPage(PageBlock::EVENTS)->delete();

        /*
         * الصور تُفصل عن المجموعة قبل حذفها، وإلا محا خطّاف الحذف ملفاتها.
         * فصلها يُبقيها في قاعدة البيانات بلا عمل، فتعود بإعادة تشغيل الترحيل.
         */
        foreach (Post::whereIn('slug', array_column($this->groups(), 'slug'))->get() as $post) {
            Media::where('post_id', $post->id)->update(['post_id' => null]);
            $post->delete();
        }

        foreach ($this->oldGroups() as $slug) {
            Post::where('slug', $slug)->update(['status' => 'published']);
        }
    }

    /**
     * الاسم والوصف المنشوران — تغييرهما مرّة واحدة هنا لا في البذرة.
     *
     * البذرة تكتب النصّ عند الإنشاء فقط، فلا تصل تعديلاتها إلى موقع قائم،
     * وهذا هو موضعها الصحيح: هجرة تجري مرّة ثم تبقى القاعدة سيّدة نصّها.
     */
    private function renameCategory(Category $category): void
    {
        $city = config('site.location.city');
        $owner = config('site.owner_name');

        $category->update([
            'name' => 'الزواجات والمناسبات',
            'name_en' => 'Weddings & Events',
            'tagline' => 'ليلة تمرّ مرّة واحدة',
            'description' => "تغطية تصويرية لليالي الزواج والمناسبات في {$city} — القسم الرجالي: الاستقبال والتهاني، والصور الخاصة بالعريس في ركن بإضاءة مضبوطة، والزفّة، والصور الجماعية مع العائلة والضيوف. وتشمل كذلك حفلات التخرّج والتكريم والمعايدات.",
            'icon' => 'rings',
            'seo_title' => "تصوير زواجات ومناسبات في {$city}",
            'seo_description' => "مصوّر زواجات ومناسبات في {$city}. تغطية ليلة الزواج للقسم الرجالي من الاستقبال إلى الصور الجماعية، وحفلات التخرّج والتكريم — {$owner}.",
        ]);
    }

    /** الأعمال القديمة تعود مسودّات بصورها، فلا يختلط المركّب بالمفرد. */
    private function retireOldGroups(Category $category): void
    {
        Post::where('category_id', $category->id)
            ->whereIn('slug', $this->oldGroups())
            ->update(['status' => 'draft', 'is_featured' => false]);
    }

    /** @return array<int, string> */
    private function oldGroups(): array
    {
        return [
            'burtreh-alaris-laylat-alzawaj',
            'taghtiyat-hafl-alzawaj',
            'sayarat-alzifaf-wal-khuruj',
            'hafalat-altakrim-waltakharruj',
        ];
    }

    /**
     * المجموعات بترتيب العرض.
     *
     * الترتيب من الركن الهادئ إلى آخر لقطات الليل: البورتريه أولًا لأنه أقوى ما
     * في الملف، والسيارة آخرًا لأنها آخر ما في الليلة.
     *
     * @return array<int, array{slug: string, title: string, excerpt: string, category: string, seo: string}>
     */
    private function groups(): array
    {
        $city = config('site.location.city');

        return [
            [
                'slug' => 'burtreh-alaris',
                'title' => 'البورتريه',
                'excerpt' => 'اللقطة التي تُطبع وتُعلَّق. خلفية هادئة وإضاءة مضبوطة، لا زحام القاعة خلف الكتف.',
                'category' => Category::EVENTS,
                'seo' => "تصوير زواجات في {$city}",
            ],
            [
                'slug' => 'fi-alqaa',
                'title' => 'في القاعة',
                'excerpt' => 'بين الضيوف وتحت الثريّات، بإضاءة القاعة نفسها لا بإضاءة ركن مجهّز.',
                'category' => Category::EVENTS,
                'seo' => "تصوير زواجات في {$city}",
            ],
            [
                'slug' => 'maqad-alaris',
                'title' => 'مقعد العريس',
                'excerpt' => 'الجلوس والتهاني: الإضاءة الذهبية صعبة، وتصحيحها هو الفرق بين صورة تذكارية وصورة تُطبع.',
                'category' => Category::EVENTS,
                'seo' => "تصوير زواجات في {$city}",
            ],
            [
                'slug' => 'albakhur-waldiyafa',
                'title' => 'البخور والضيافة',
                'excerpt' => 'الدخان يتصاعد والمبخرة في اليد — لحظة لا تتكرّر في الليلة، وتحتاج سرعة لا إعدادًا.',
                'category' => Category::EVENTS,
                'seo' => "تصوير زواجات في {$city}",
            ],
            [
                'slug' => 'tafasil-laylat-alzawaj',
                'title' => 'التفاصيل',
                'excerpt' => 'الخاتم والساعة. صغيرة في الليلة، كبيرة في الألبوم.',
                'category' => Category::EVENTS,
                'seo' => "تصوير زواجات في {$city}",
            ],
            [
                'slug' => 'sayarat-alaris',
                'title' => 'السيارة',
                'excerpt' => 'الكشّافات والورد والليل — أكثر لقطات الليلة سينمائية.',
                'category' => Category::EVENTS,
                'seo' => "تصوير زواجات في {$city}",
            ],
            [
                'slug' => 'alfaaliyat-almuassasiya',
                'title' => 'الفعاليات المؤسسية',
                'excerpt' => 'افتتاحات المشاريع والحملات المجتمعية وحفلات الشركات: قصّ الشريط، والقصاصات، والشعار حاضر في الإطار.',
                'category' => Category::ACTIVITIES,
                'seo' => "تصوير فعاليات في {$city}",
            ],
        ];
    }

    /**
     * أسئلة الصفحة — تُنشر كبيانات مهيكلة وتُحرَّر من «الأسئلة الشائعة».
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function faqs(): array
    {
        return [
            ['ماذا تشمل تغطية ليلة الزواج؟', 'الاستقبال والتهاني، ثم الصور الخاصة بالعريس، ثم الزفّة، ثم الصور الجماعية مع العائلة والضيوف. عدد الصور ومدّة التسليم يُتَّفق عليهما عند الحجز بحسب حجم المناسبة.'],
            ['ما المعدات المستخدمة في التغطية؟', 'كاميرات حديثة، ومانع اهتزاز للّقطات السينمائية المتحرّكة، ووحدات إضاءة تُضبط على إضاءة القاعة لا تُصارعها، وطائرة مسيّرة للّقطات العلوية.'],
            ['هل يُصوَّر البورتريه في ركن مخصّص؟', 'نعم. يُختار ركن بإضاءة مضبوطة وخلفية هادئة قبل امتلاء القاعة، فتخرج اللقطة صالحة للطباعة لا صورة عابرة بين المصافحات.'],
            ['هل تصوّر حفلات التخرّج والتكريم والافتتاحات؟', 'نعم، وهي جزء أصيل من العمل: حفلات مدارس، وافتتاحات مشاريع، وحفلات شركات، واجتماعات جمعيات.'],
            ['كيف أحجز؟', 'أرسل التاريخ والقاعة ونوع المناسبة عبر الواتساب، ويصلك التفصيل والاتفاق قبل الحجز.'],
        ];
    }
};
