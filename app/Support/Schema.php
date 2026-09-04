<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Support\Collection;

/**
 * يبني رسم بيانات Schema.org المهيكلة للموقع.
 *
 * الفكرة الأساسية: تعريف الكيانات مرة واحدة بمعرّفات @id ثابتة، ثم الإشارة
 * إليها من كل صفحة بدل تكرارها. هذا ما يمكّن محرّكات الإجابة ونماذج اللغة من
 * ربط "من هو المصور" بـ"ما الخدمات" بـ"كيف أتواصل" في كيان واحد متماسك.
 */
class Schema
{
    /** معرّفات الكيانات الثابتة عبر كل صفحات الموقع. */
    public static function personId(): string
    {
        return url('/').'/#person';
    }

    public static function businessId(): string
    {
        return url('/').'/#business';
    }

    public static function websiteId(): string
    {
        return url('/').'/#website';
    }

    /**
     * الكيانات الأساسية التي تُضمَّن في كل صفحة.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function baseGraph(): array
    {
        return [
            self::person(),
            self::business(),
            self::website(),
        ];
    }

    /**
     * الشخص — المصور نفسه.
     *
     * @return array<string, mixed>
     */
    public static function person(): array
    {
        $name = Setting::get('seo_owner_name', config('site.owner_name'));
        $city = config('site.location.city');

        return array_filter([
            '@type' => 'Person',
            '@id' => self::personId(),
            'name' => $name,
            'alternateName' => config('site.owner_name_en'),
            'jobTitle' => config('site.job_title'),
            'description' => "{$name} مصور فوتوغرافي محترف في {$city}، متخصص في تصوير المناسبات والفعاليات والمؤتمرات والمعارض والعقارات، ويقدّم ورشًا تدريبية في التصوير الفوتوغرافي.",
            'url' => url('/'),
            'image' => self::ownerImage(),
            'telephone' => config('site.phone'),
            'email' => Setting::get('contact_email', config('site.email')),
            'knowsLanguage' => ['ar', 'en'],
            'knowsAbout' => [
                'التصوير الفوتوغرافي',
                'تصوير المناسبات',
                'تصوير الفعاليات',
                'تصوير المؤتمرات والمعارض',
                'التصوير العقاري',
                'التصوير الجوي بالطائرات المسيّرة',
                'الإضاءة في التصوير',
                'معالجة الصور',
            ],
            'address' => self::address(),
            'worksFor' => ['@id' => self::businessId()],
            'hasCredential' => self::credentials(),
            'sameAs' => self::socialLinks(),
        ]);
    }

    /**
     * التراخيص الرسمية كبيانات مهيكلة.
     *
     * hasCredential هو ما يفصل «يقول إنه مرخَّص» عن «مرخَّص برقم من جهة
     * مسمّاة»: الجهة المانحة كيان مستقل له اسم وموقع، والرقم مُعرِّف قابل
     * للتحقّق. وهذا ما تقتبسه أدوات الذكاء الاصطناعي حين تُسأل عمّن يملك
     * تصريح تصوير جوي.
     *
     * description جملة تامة لا وسم: الأداة تقتبس جملة تقرأ وحدها، والحقول
     * المفكّكة تصلح للفهرسة لا للاقتباس. وهي نفسها المعروضة في صفحة «نبذة»،
     * فلا نصّ مخفيّ يخالف ما يراه الزائر.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function credentials(): array
    {
        return array_map(fn (array $a): array => array_filter([
            '@type' => 'EducationalOccupationalCredential',
            'name' => $a['title'],
            'description' => $a['description'] ?? null,
            'credentialCategory' => $a['category'],
            'identifier' => $a['number'],
            'recognizedBy' => array_filter([
                '@type' => 'GovernmentOrganization',
                'name' => $a['authority'],
                'alternateName' => $a['authority_en'] ?? null,
                'url' => $a['authority_url'] ?? null,
            ]),
        ]), accreditations());
    }

    /**
     * النشاط التجاري — هذا ما تقرأه أدوات البحث المحلي.
     *
     * @return array<string, mixed>
     */
    public static function business(): array
    {
        $name = Setting::get('seo_owner_name', config('site.owner_name'));
        $city = config('site.location.city');

        return array_filter([
            '@type' => ['ProfessionalService', 'LocalBusiness'],
            '@id' => self::businessId(),
            'name' => "{$name} للتصوير الفوتوغرافي",
            'alternateName' => config('site.owner_name_en').' Photography',
            'description' => Setting::get(
                'seo_description',
                "خدمات تصوير فوتوغرافي احترافي في {$city}: المناسبات، الفعاليات والمؤتمرات والمعارض، والتصوير العقاري، والتصوير الجوي، إضافة إلى ورش تدريبية في التصوير.",
            ),
            'url' => url('/'),
            'image' => self::ownerImage(),
            'logo' => url('/images/logo.png'),
            'telephone' => config('site.phone'),
            'email' => Setting::get('contact_email', config('site.email')),
            'priceRange' => '$$',
            'currenciesAccepted' => 'SAR',
            'founder' => ['@id' => self::personId()],
            'address' => self::address(),
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => config('site.location.latitude'),
                'longitude' => config('site.location.longitude'),
            ],
            'areaServed' => self::serviceAreas(),
            'openingHoursSpecification' => self::openingHours(),
            'hasOfferCatalog' => self::offerCatalog(),
            'aggregateRating' => self::aggregateRating(),
            'sameAs' => self::socialLinks(),
        ]);
    }

    /**
     * الموقع نفسه — يتيح ظهور مربّع بحث داخلي في نتائج البحث.
     *
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::websiteId(),
            'url' => url('/'),
            'name' => Setting::get('seo_title', config('app.name')),
            'inLanguage' => 'ar',
            'publisher' => ['@id' => self::personId()],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('portfolio').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * كتالوج الخدمات مبنيًا من الأقسام الفرعية الفعلية في قاعدة البيانات.
     *
     * @return array<string, mixed>|null
     */
    public static function offerCatalog(): ?array
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->whereHas('section', fn ($q) => $q->where('slug', Section::SERVICES))
            ->get();

        if ($categories->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'OfferCatalog',
            'name' => 'خدمات التصوير الفوتوغرافي',
            'itemListElement' => $categories->map(fn (Category $category) => [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $category->name,
                    'description' => $category->metaDescription(),
                    'serviceType' => $category->name_en ?: $category->name,
                    'url' => $category->url(),
                    'provider' => ['@id' => self::businessId()],
                    'areaServed' => ['@type' => 'City', 'name' => config('site.location.city')],
                ],
            ])->all(),
        ];
    }

    /**
     * التقييم الإجمالي.
     *
     * يُحتسب من الآراء التي وصلت إلى الموقع مباشرةً فقط. الآراء المنقولة من
     * Google تُعرض في الصفحة لكنها لا تدخل هنا: إرشادات Google للبيانات المهيكلة
     * تمنع تجميع التقييمات من مواقع أخرى، ومخالفتها تعرّض الموقع لإجراء يدوي.
     *
     * @return array<string, mixed>|null
     */
    public static function aggregateRating(): ?array
    {
        $testimonials = Testimonial::active()->firstParty()->get();

        if ($testimonials->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => round($testimonials->avg('rating'), 1),
            'reviewCount' => $testimonials->count(),
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    /**
     * صفحة قسم — قائمة عناصر مرتّبة تفهمها المحرّكات كمعرض أعمال.
     *
     * @param  Collection<int, Post>  $posts
     * @return array<string, mixed>
     */
    public static function sectionPage(Section $section, Collection $posts): array
    {
        return array_filter([
            '@type' => 'CollectionPage',
            '@id' => $section->url().'#page',
            'url' => $section->url(),
            'name' => $section->metaTitle(),
            'description' => $section->metaDescription(),
            'inLanguage' => 'ar',
            'isPartOf' => ['@id' => self::websiteId()],
            'about' => ['@id' => self::businessId()],
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $posts->count(),
                'itemListElement' => $posts->values()->map(fn (Post $post, int $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => $post->url(),
                    'name' => $post->title,
                ])->all(),
            ],
        ]);
    }

    /**
     * صفحة قسم فرعي من خدمات التصوير — تُنشر ككيان خدمة مستقل.
     *
     * @return array<string, mixed>
     */
    public static function servicePage(Category $category): array
    {
        return array_filter([
            '@type' => 'Service',
            '@id' => $category->url().'#service',
            'url' => $category->url(),
            'name' => $category->name,
            'alternateName' => $category->name_en,
            'description' => $category->metaDescription(),
            'serviceType' => $category->name,
            'category' => 'التصوير الفوتوغرافي',
            'provider' => ['@id' => self::businessId()],
            'areaServed' => self::serviceAreas(),
            'availableChannel' => [
                '@type' => 'ServiceChannel',
                'serviceUrl' => route('contact'),
                'servicePhone' => [
                    '@type' => 'ContactPoint',
                    'telephone' => config('site.phone'),
                    'contactType' => 'حجز وتصوير',
                    'availableLanguage' => ['ar', 'en'],
                ],
            ],
        ]);
    }

    /**
     * عنصر محتوى — يتحوّل إلى Article أو Course بحسب قسمه.
     *
     * @return array<string, mixed>
     */
    public static function post(Post $post): array
    {
        $isCourse = in_array($post->section->slug ?? '', [Section::WORKSHOPS], true);
        $images = $post->media->map(fn ($m) => $m->url('lg'))->all();

        if ($isCourse) {
            return array_filter([
                '@type' => 'Course',
                '@id' => $post->url().'#course',
                'url' => $post->url(),
                'name' => $post->title,
                'description' => $post->metaDescription(),
                'image' => $images ?: null,
                'inLanguage' => 'ar',
                'provider' => ['@id' => self::businessId()],
                'hasCourseInstance' => array_filter([
                    '@type' => 'CourseInstance',
                    'courseMode' => 'onsite',
                    'courseWorkload' => $post->duration,
                    'location' => [
                        '@type' => 'Place',
                        'name' => $post->location ?: config('site.location.city'),
                        'address' => self::address(),
                    ],
                    'instructor' => ['@id' => self::personId()],
                    'startDate' => $post->event_date?->toDateString(),
                ]),
                'offers' => $post->price ? [
                    '@type' => 'Offer',
                    'price' => (string) $post->price,
                    'priceCurrency' => 'SAR',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $post->url(),
                ] : null,
            ]);
        }

        return array_filter([
            '@type' => $images ? 'Article' : 'Article',
            '@id' => $post->url().'#article',
            'url' => $post->url(),
            'headline' => $post->title,
            'alternativeHeadline' => $post->subtitle,
            'description' => $post->metaDescription(),
            'image' => $images ?: null,
            'inLanguage' => 'ar',
            'datePublished' => ($post->published_at ?? $post->created_at)?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => ['@id' => self::personId()],
            'publisher' => ['@id' => self::personId()],
            'keywords' => $post->keywords ? implode(', ', $post->keywords) : null,
            'articleSection' => $post->section->name ?? null,
            'contentLocation' => $post->location ? [
                '@type' => 'Place',
                'name' => $post->location,
            ] : null,
            'isPartOf' => ['@id' => self::websiteId()],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $post->url()],
        ]);
    }

    /**
     * معرض صور عنصر — يجعل الصور نفسها قابلة للفهرسة كوحدات مستقلة.
     *
     * @return array<string, mixed>|null
     */
    public static function imageGallery(Post $post): ?array
    {
        if ($post->media->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'ImageGallery',
            '@id' => $post->url().'#gallery',
            'name' => $post->title,
            'associatedMedia' => $post->media->map(fn ($media) => array_filter([
                '@type' => 'ImageObject',
                'contentUrl' => $media->url('lg'),
                'thumbnailUrl' => $media->url('thumb'),
                'width' => $media->width,
                'height' => $media->height,
                'caption' => $media->caption ?: $media->altText(),
                'description' => $media->altText(),
                'encodingFormat' => 'image/webp',
                'creator' => ['@id' => self::personId()],
                'copyrightHolder' => ['@id' => self::personId()],
                'creditText' => config('site.owner_name'),
                'license' => url('/'),
            ]))->all(),
        ];
    }

    /**
     * الأسئلة الشائعة — أعلى أنواع البيانات المهيكلة أثرًا في محرّكات الإجابة،
     * لأن السؤال والجواب يصلان جاهزين للاقتباس.
     */
    /**
     * @param  Collection<int, Faq>  $faqs
     * @return array<string, mixed>|null
     */
    public static function faqPage(Collection $faqs, ?string $url = null): ?array
    {
        if ($faqs->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            '@id' => ($url ?: url()->current()).'#faq',
            'inLanguage' => 'ar',
            'mainEntity' => $faqs->map(fn (Faq $faq) => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->answer,
                ],
            ])->all(),
        ];
    }

    /**
     * مسار التنقّل — يظهر كسلسلة في نتائج البحث.
     *
     * @param  array<int, array{label: string, url?: string|null}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => url()->current().'#breadcrumb',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['label'],
                'item' => $item['url'] ?? null,
            ])->all(),
        ];
    }

    /**
     * صفحة التواصل.
     *
     * @return array<string, mixed>
     */
    public static function contactPage(): array
    {
        return [
            '@type' => 'ContactPage',
            '@id' => route('contact').'#page',
            'url' => route('contact'),
            'name' => 'التواصل والحجز',
            'inLanguage' => 'ar',
            'about' => ['@id' => self::businessId()],
            'mainEntity' => [
                '@type' => 'ContactPoint',
                'telephone' => config('site.phone'),
                'email' => Setting::get('contact_email', config('site.email')),
                'contactType' => 'حجز وتصوير',
                'areaServed' => 'SA',
                'availableLanguage' => ['ar', 'en'],
            ],
        ];
    }

    /**
     * المدن التي تُغطّى — تُنشر ضمن areaServed في أكثر من كيان.
     *
     * @return array<int, array<string, string>>
     */
    public static function serviceAreas(): array
    {
        $areas = [];

        foreach ((array) config('site.service_areas') as $area) {
            $areas[] = ['@type' => 'City', 'name' => (string) $area];
        }

        return $areas;
    }

    /**
     * ساعات العمل بصيغة Schema.org.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function openingHours(): array
    {
        $slots = [];

        foreach ((array) config('site.opening_hours') as $slot) {
            $slots[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $slot['days'],
                'opens' => $slot['opens'],
                'closes' => $slot['closes'],
            ];
        }

        return $slots;
    }

    /**
     * العنوان الجغرافي المشترك بين كل الكيانات.
     *
     * @return array<string, string>
     */
    public static function address(): array
    {
        return [
            '@type' => 'PostalAddress',
            'addressLocality' => config('site.location.city'),
            'addressRegion' => config('site.location.region'),
            'addressCountry' => config('site.location.country'),
        ];
    }

    /**
     * روابط الحسابات — تربط الموقع بالحضور على المنصات الأخرى.
     *
     * @return array<int, string>
     */
    public static function socialLinks(): array
    {
        $links = [];

        foreach ((array) config('site.social') as $key => $item) {
            $url = Setting::get("social_{$key}", $item['url']);

            if (is_string($url) && $url !== '') {
                $links[] = $url;
            }
        }

        return $links;
    }

    /** صورة المصور المعتمدة في البيانات المهيكلة. */
    private static function ownerImage(): ?string
    {
        $media = Media::where('usage', 'owner_portrait')->first();

        return $media?->url('lg');
    }

    /**
     * يغلّف الرسم البياني في وثيقة JSON-LD كاملة.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     */
    public static function document(array $nodes): string
    {
        return json_encode([
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter($nodes)),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
