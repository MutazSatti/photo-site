<?php

use App\Models\Category;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HomeBlock;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use App\Models\Testimonial;
use App\Support\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function mount(): void
    {
        $city = config('site.location.city');
        $owner = config('site.owner_name');

        seo()
            ->set(
                title: setting('seo_title', "{$owner} | مصور فوتوغرافي محترف في {$city}"),
                description: setting('seo_description'),
                image: $this->heroImage?->url('lg'),
                type: 'website',
            )
            ->addGraph(
                [
                    '@type' => 'WebPage',
                    '@id' => route('home').'#page',
                    'url' => route('home'),
                    'name' => setting('seo_title'),
                    'description' => setting('seo_description'),
                    'inLanguage' => 'ar',
                    'isPartOf' => ['@id' => Schema::websiteId()],
                    'about' => ['@id' => Schema::businessId()],
                    'primaryImageOfPage' => ['@id' => Schema::personId()],
                ],
                Schema::faqPage($this->faqs, route('home')) ?? [],
            );
    }

    /**
     * مفاتيح العناصر المعروضة بترتيبها كما ضبطه المالك.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function blocks(): array
    {
        return HomeBlock::visibleKeys();
    }

    /** صورة واجهة الصفحة الرئيسية — تُرفع من إعدادات لوحة التحكم. */
    #[Computed]
    public function heroImage(): ?Media
    {
        return Media::where('usage', 'hero')->first();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()->active()->ordered()->with('activeCategories')->get();
    }

    #[Computed]
    public function serviceCategories()
    {
        return Category::query()
            ->active()
            ->ordered()
            ->whereHas('section', fn ($q) => $q->where('slug', Section::SERVICES))
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get();
    }

    #[Computed]
    public function featured()
    {
        return Post::query()
            ->published()
            ->featured()
            ->ordered()
            ->with(['section:id,slug,name', 'category:id,slug,name,section_id', 'media'])
            ->take(6)
            ->get();
    }

    #[Computed]
    public function latestWorks()
    {
        return Post::query()
            ->published()
            ->inSection(Section::SERVICES)
            ->ordered()
            ->with(['section:id,slug,name', 'category:id,slug,name,section_id', 'media'])
            ->take(6)
            ->get();
    }

    #[Computed]
    public function latestReading()
    {
        return Post::query()
            ->published()
            ->whereHas('section', fn ($q) => $q->whereIn('slug', [Section::ARTICLES, Section::TIPS]))
            ->ordered()
            ->with(['section:id,slug,name', 'media'])
            ->take(4)
            ->get();
    }

    #[Computed]
    public function workshops()
    {
        return Post::query()
            ->published()
            ->inSection(Section::WORKSHOPS)
            ->ordered()
            ->with(['section:id,slug,name', 'media'])
            ->take(3)
            ->get();
    }

    #[Computed]
    public function clients()
    {
        return Client::query()->active()->ordered()->with('logo')->get();
    }

    #[Computed]
    public function testimonials()
    {
        return Testimonial::query()->active()->ordered()->take(3)->get();
    }

    #[Computed]
    public function faqs()
    {
        return Faq::query()->active()->ordered()->take(6)->get();
    }
}; ?>


<div>
    {{--
        ترتيب العناصر وظهورها يأتيان من جدول home_blocks لا من هذا الملف،
        فيتحكّم بهما المالك من لوحة التحكم. كل مفتاح يقابل ملفًا في
        resources/views/home/، و@includeIf يتجاهل بأمان أي مفتاح بلا ملف.

        الإدراج هنا لا مكوّن Blade: الأجزاء تقرأ الخصائص المحسوبة عبر $this،
        وهي متاحة داخل @include لأنه يُصيَّر في سياق مكوّن Livewire نفسه. وميزة
        ذلك أن استعلام كل عنصر لا ينفَّذ إلا إذا كان العنصر معروضًا فعلًا.
    --}}
    @foreach ($this->blocks as $block)
        @includeIf('home.'.$block)
    @endforeach
</div>
