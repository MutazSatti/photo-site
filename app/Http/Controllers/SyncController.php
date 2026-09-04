<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Post;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * يغذّي نظام المزامنة مع قاعدة بيانات المتصفح (IndexedDB).
 *
 * ما يُرسل هنا نصوص وأرقام فقط — لا ملفات صور ولا فيديو. الصور تبقى تُحمَّل
 * عبر HTTP كالمعتاد ويتكفّل كاش المتصفح بها؛ ما يُخزَّن هنا هو بياناتها الوصفية
 * (المسار، الأبعاد، النص البديل) لأنها نصوص وأرقام يحتاجها العرض الفوري.
 */
class SyncController extends Controller
{
    /**
     * بصمة خفيفة للمحتوى — يستدعيها المتصفح أولًا ليعرف هل تغيّر شيء.
     * حجمها بضع مئات من البايتات مقابل عشرات الكيلوبايتات للحمولة الكاملة.
     */
    public function manifest(): JsonResponse
    {
        return response()
            ->json($this->buildManifest())
            ->setPublic()
            ->setMaxAge(60);
    }

    /**
     * الحمولة الكاملة — تُطلب فقط عندما تختلف البصمة عمّا هو مخزّن محليًا.
     */
    public function data(): JsonResponse
    {
        $payload = Cache::remember('sync.payload', now()->addMinutes(10), function () {
            return [
                'manifest' => $this->buildManifest(),

                'sections' => Section::query()->active()->ordered()->get()->map(fn (Section $s) => [
                    'id' => $s->id,
                    'slug' => $s->slug,
                    'name' => $s->name,
                    'name_en' => $s->name_en,
                    'tagline' => $s->tagline,
                    'description' => $s->description,
                    'icon' => $s->icon,
                    'sort_order' => $s->sort_order,
                    'url' => $s->url(),
                ])->all(),

                'categories' => Category::query()->active()->ordered()->with('section:id,slug')->get()->map(fn (Category $c) => [
                    'id' => $c->id,
                    'section_id' => $c->section_id,
                    'section_slug' => $c->section->slug ?? null,
                    'slug' => $c->slug,
                    'name' => $c->name,
                    'name_en' => $c->name_en,
                    'tagline' => $c->tagline,
                    'description' => $c->description,
                    'icon' => $c->icon,
                    'sort_order' => $c->sort_order,
                    'url' => $c->url(),
                ])->all(),

                'posts' => Post::query()
                    ->published()
                    ->ordered()
                    ->with(['section:id,slug,name', 'category:id,slug,name', 'media'])
                    ->get()
                    ->map(fn (Post $p) => [
                        'id' => $p->id,
                        'slug' => $p->slug,
                        'title' => $p->title,
                        'subtitle' => $p->subtitle,
                        'excerpt' => $p->excerpt,
                        // النص الكامل بلا وسوم — يتيح بحثًا فوريًا داخل المحتوى دون طلب شبكة
                        'body_text' => (string) str($p->body)->stripTags()->squish(),
                        'section_id' => $p->section_id,
                        'section_slug' => $p->section->slug ?? null,
                        'section_name' => $p->section->name ?? null,
                        'category_id' => $p->category_id,
                        'category_slug' => $p->category->slug ?? null,
                        'category_name' => $p->category->name ?? null,
                        'location' => $p->location,
                        'client' => $p->client,
                        'event_date' => $p->event_date?->toDateString(),
                        'price' => $p->price ? (float) $p->price : null,
                        'duration' => $p->duration,
                        'seats' => $p->seats,
                        'reading_minutes' => $p->readingTime(),
                        'views' => $p->views,
                        'is_featured' => $p->is_featured,
                        'published_at' => $p->published_at?->toIso8601String(),
                        'images_count' => $p->media->count(),
                        // بيانات وصفية للصور فقط — لا محتوى ثنائي
                        'cover' => $this->mediaMeta($p->coverImage()),
                        'url' => $p->url(),
                    ])->all(),

                'settings' => Setting::query()
                    ->whereNotIn('group', ['seo'])
                    ->get()
                    ->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])
                    ->all(),

                'faqs' => Faq::query()->active()->ordered()->get()->map(fn (Faq $f) => [
                    'id' => $f->id,
                    'section_id' => $f->section_id,
                    'question' => $f->question,
                    'answer' => $f->answer,
                    'sort_order' => $f->sort_order,
                ])->all(),

                'clients' => Client::query()->active()->ordered()->with('logo')->get()->map(fn (Client $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'name_en' => $c->name_en,
                    'url' => $c->url,
                    'logo' => $this->mediaMeta($c->logo),
                ])->all(),

                'testimonials' => Testimonial::query()->active()->ordered()->get()->map(fn (Testimonial $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'role' => $t->role,
                    'content' => $t->content,
                    'rating' => $t->rating,
                ])->all(),
            ];
        });

        return response()
            ->json($payload)
            ->setPublic()
            ->setMaxAge(300);
    }

    /**
     * البصمة = أحدث تعديل + عدد السجلات في كل جدول.
     * أي إضافة أو تعديل أو حذف يغيّرها، فيعرف المتصفح أن عليه إعادة السحب.
     *
     * @return array{version: string, counts: array<string, int>, generated_at: string}
     */
    private function buildManifest(): array
    {
        return Cache::remember('sync.manifest', now()->addMinutes(2), function () {
            $tables = ['sections', 'categories', 'posts', 'media', 'settings', 'faqs', 'testimonials', 'clients'];
            $parts = [];
            $counts = [];

            foreach ($tables as $table) {
                $row = DB::table($table)
                    ->selectRaw('COUNT(*) as total, MAX(updated_at) as latest')
                    ->first();

                $counts[$table] = (int) ($row->total ?? 0);
                $parts[] = $table.':'.($row->total ?? 0).':'.($row->latest ?? '');
            }

            return [
                'version' => substr(hash('xxh128', implode('|', $parts)), 0, 16),
                'counts' => $counts,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * بيانات الصورة الوصفية فقط — مسار وأبعاد ونص بديل، بلا أي محتوى ثنائي.
     *
     * @return array{thumb: string, md: string, lg: string, width: int|null, height: int|null, alt: string}|null
     */
    private function mediaMeta(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'thumb' => $media->url('thumb'),
            'md' => $media->url('md'),
            'lg' => $media->url('lg'),
            'width' => $media->width,
            'height' => $media->height,
            'alt' => $media->altText(),
        ];
    }
}
