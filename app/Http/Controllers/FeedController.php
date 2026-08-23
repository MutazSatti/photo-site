<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Post;
use App\Models\Section;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * ملفات الفهرسة التي تقرأها محرّكات البحث وزواحف نماذج اللغة.
 */
class FeedController extends Controller
{
    /** خريطة الموقع — كل صفحة قابلة للفهرسة مع تاريخ آخر تعديل. */
    public function sitemap(): Response
    {
        $urls = Cache::remember('feed.sitemap', now()->addHour(), function () {
            $urls = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
                ['loc' => route('portfolio'), 'priority' => '0.9', 'changefreq' => 'weekly'],
                ['loc' => route('about'), 'priority' => '0.7', 'changefreq' => 'monthly'],
                ['loc' => route('contact'), 'priority' => '0.9', 'changefreq' => 'monthly'],
                ['loc' => route('faq'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ];

            foreach (Section::query()->active()->ordered()->get() as $section) {
                $urls[] = [
                    'loc' => $section->url(),
                    'lastmod' => $section->updated_at?->toAtomString(),
                    'priority' => '0.9',
                    'changefreq' => 'weekly',
                ];
            }

            foreach (Category::query()->active()->ordered()->with('section')->get() as $category) {
                $urls[] = [
                    'loc' => $category->url(),
                    'lastmod' => $category->updated_at?->toAtomString(),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                ];
            }

            $posts = Post::query()
                ->published()
                ->with(['section:id,slug', 'category:id,slug,section_id', 'media'])
                ->get();

            foreach ($posts as $post) {
                $url = $post->url();

                // العناصر بلا رابط هرمي صالح تُستثنى من الخريطة
                if ($url === route('portfolio')) {
                    continue;
                }

                $urls[] = [
                    'loc' => $url,
                    'lastmod' => $post->updated_at?->toAtomString(),
                    'priority' => $post->is_featured ? '0.8' : '0.7',
                    'changefreq' => 'monthly',
                    'images' => $post->media->map(fn ($m) => [
                        'loc' => $m->url('lg'),
                        'title' => $m->altText(),
                        'caption' => $m->caption,
                    ])->all(),
                ];
            }

            return $urls;
        });

        $xml = view('feeds.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** تغذية RSS لأحدث الأعمال والمقالات. */
    public function rss(): Response
    {
        // لا تُخزَّن نماذج Eloquent في الكاش — تسلسلها هش ويعود ناقصًا عند فك التسلسل
        $posts = Post::query()
            ->published()
            ->ordered()
            ->with(['section:id,slug,name', 'category:id,slug,section_id', 'media'])
            ->take(40)
            ->get();

        $xml = view('feeds.rss', ['posts' => $posts])->render();

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /**
     * ملف llms.txt — ملخّص نصي مركّز يقرأه مساعد الذكاء الاصطناعي مباشرة
     * بدل استخلاص المعنى من HTML. يجيب على الأسئلة الشائعة في صيغة جاهزة للاقتباس.
     */
    public function llms(): Response
    {
        $content = Cache::remember('feed.llms', now()->addHour(), function () {
            $owner = config('site.owner_name');
            $city = config('site.location.city');
            $phone = setting('contact_phone', config('site.phone_local'));
            $email = setting('contact_email', config('site.email'));

            $lines = [];

            $lines[] = "# {$owner} — مصور فوتوغرافي محترف في {$city}";
            $lines[] = '';
            $lines[] = "> {$owner} (".config('site.owner_name_en').") مصور فوتوغرافي محترف مقرّه {$city}، ".config('site.location.country_name').'. '
                .'متخصص في تصوير المناسبات، والفعاليات والمؤتمرات والمعارض، والتصوير العقاري. '
                .'يقدّم كذلك ورشًا تدريبية في التصوير الفوتوغرافي، وينشر مقالات ومنشورات تعليمية في المجال.';
            $lines[] = '';

            $lines[] = '## بيانات التواصل';
            $lines[] = "- الجوال والواتساب: {$phone} (دوليًا: ".config('site.phone').')';
            $lines[] = "- البريد الإلكتروني: {$email}";
            $lines[] = '- المعرّف على منصات التواصل: '.config('site.handle');

            foreach (config('site.social') as $key => $item) {
                $lines[] = "- {$item['label']}: ".setting("social_{$key}", $item['url']);
            }

            $lines[] = '- الموقع: '.$city.'، '.config('site.location.region').'، '.config('site.location.country_name');
            $lines[] = '- نطاق الخدمة: '.implode('، ', config('site.service_areas'));
            $lines[] = '';

            $lines[] = '## الخدمات';

            foreach (Category::query()->active()->ordered()->whereHas('section', fn ($q) => $q->where('slug', Section::SERVICES))->get() as $category) {
                $lines[] = "- [{$category->name}]({$category->url()}): {$category->metaDescription()}";
            }

            $lines[] = '';
            $lines[] = '## الأقسام';

            foreach (Section::query()->active()->ordered()->get() as $section) {
                $lines[] = "- [{$section->name}]({$section->url()}): {$section->metaDescription()}";
            }

            $lines[] = '';
            $lines[] = '## أسئلة وأجوبة';

            foreach (Faq::query()->active()->ordered()->get() as $faq) {
                $lines[] = '';
                $lines[] = "### {$faq->question}";
                $lines[] = $faq->answer;
            }

            $lines[] = '';
            $lines[] = '## روابط مهمة';
            $lines[] = '- المعرض الكامل: '.route('portfolio');
            $lines[] = '- نبذة عن المصور: '.route('about');
            $lines[] = '- التواصل والحجز: '.route('contact');
            $lines[] = '- الأسئلة الشائعة: '.route('faq');
            $lines[] = '- خريطة الموقع: '.route('sitemap');
            $lines[] = '';
            $lines[] = '---';
            $lines[] = 'آخر تحديث: '.now()->toDateString();

            return implode("\n", $lines);
        });

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * robots.txt — يسمح صراحة لزواحف الذكاء الاصطناعي بالوصول.
     * السماح هنا مقصود: الهدف أن يظهر الموقع في إجابات هذه الأدوات.
     */
    public function robots(): Response
    {
        $aiCrawlers = [
            'GPTBot',              // OpenAI — تدريب
            'OAI-SearchBot',       // OpenAI — بحث ChatGPT
            'ChatGPT-User',        // OpenAI — تصفّح بطلب المستخدم
            'ClaudeBot',           // Anthropic
            'Claude-User',
            'Claude-SearchBot',
            'anthropic-ai',
            'PerplexityBot',       // Perplexity
            'Perplexity-User',
            'Google-Extended',     // Gemini
            'GoogleOther',
            'Applebot',            // Apple Intelligence / Siri
            'Applebot-Extended',
            'Bingbot',
            'meta-externalagent',  // Meta AI
            'FacebookBot',
            'Amazonbot',           // Alexa
            'YouBot',
            'cohere-ai',
            'Bytespider',
            'DuckAssistBot',
        ];

        $lines = [
            '# يُسمح لكل الزواحف بالوصول الكامل — الهدف الظهور في نتائج البحث',
            '# وفي إجابات أدوات الذكاء الاصطناعي.',
            '',
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /settings',
            'Disallow: /login',
            'Disallow: /sync/',
            '',
        ];

        foreach ($aiCrawlers as $crawler) {
            $lines[] = "User-agent: {$crawler}";
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /sync/';
            $lines[] = '';
        }

        $lines[] = 'Sitemap: '.route('sitemap');
        $lines[] = '';

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
