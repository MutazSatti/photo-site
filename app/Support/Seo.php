<?php

namespace App\Support;

use App\Models\Media;
use App\Models\Setting;

/**
 * حامل بيانات السيو للصفحة الحالية.
 *
 * كل صفحة Livewire تملأ هذا الكائن داخل mount() — أي قبل أي عملية تصيير —
 * ثم يقرأه قالب <head>. هذا يضمن أن وسوم الميتا وبيانات JSON-LD تكون جاهزة
 * مهما كان ترتيب تصيير المكوّن والقالب.
 */
class Seo
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $image = null;

    public ?string $canonical = null;

    public string $type = 'website';

    public string $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    /**
     * عقد Schema.org الخاصة بهذه الصفحة (تُضاف فوق العقد الأساسية).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $graph = [];

    /**
     * مسار التنقّل: [['label' => '...', 'url' => '...'], ...]
     *
     * @var array<int, array{label: string, url?: string|null}>
     */
    public array $breadcrumbs = [];

    public function set(
        ?string $title = null,
        ?string $description = null,
        ?string $image = null,
        ?string $canonical = null,
        ?string $type = null,
        ?string $robots = null,
    ): static {
        $this->title = $title ?? $this->title;
        $this->description = $description ?? $this->description;
        $this->image = $image ?? $this->image;
        $this->canonical = $canonical ?? $this->canonical;
        $this->type = $type ?? $this->type;
        $this->robots = $robots ?? $this->robots;

        return $this;
    }

    /**
     * يضيف عقدة أو أكثر إلى رسم البيانات المهيكلة.
     *
     * @param  array<string, mixed>  ...$nodes
     */
    public function addGraph(array ...$nodes): static
    {
        foreach ($nodes as $node) {
            if ($node !== []) {
                $this->graph[] = $node;
            }
        }

        return $this;
    }

    /**
     * يحدّد مسار التنقّل ويضيفه تلقائيًا إلى البيانات المهيكلة.
     *
     * @param  array<int, array{label: string, url?: string|null}>  $items
     */
    public function breadcrumbs(array $items): static
    {
        // الرئيسية أول عنصر دائمًا
        $this->breadcrumbs = array_merge(
            [['label' => 'الرئيسية', 'url' => route('home')]],
            $items,
        );

        return $this;
    }

    /** العنوان الكامل كما يظهر في تبويب المتصفح ونتائج البحث. */
    public function fullTitle(): string
    {
        $siteName = Setting::get('seo_title', config('app.name'));

        if (! $this->title) {
            return $siteName;
        }

        // تفادي تكرار الاسم عندما يكون العنوان هو اسم الموقع نفسه
        if (str_contains($this->title, config('site.owner_name'))) {
            return $this->title;
        }

        return $this->title.' | '.config('site.owner_name').' — مصور '.config('site.location.city');
    }

    public function metaDescription(): string
    {
        return $this->description
            ?: Setting::get('seo_description', config('app.name'));
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?: url()->current();
    }

    /**
     * صورة المشاركة على الشبكات الاجتماعية.
     *
     * الترتيب: صورة الصفحة نفسها، ثم صورة الواجهة (أقوى صورة في الموقع وأنسبها
     * لبطاقة مشاركة عريضة)، ثم الصورة الشخصية، وأخيرًا بطاقة العلامة المولّدة.
     */
    public function socialImage(): string
    {
        if ($this->image) {
            return $this->image;
        }

        $fallback = Media::whereIn('usage', ['hero', 'owner_portrait'])
            ->orderByRaw("CASE usage WHEN 'hero' THEN 0 ELSE 1 END")
            ->first();

        return $fallback?->url('lg') ?: url('/images/og-default.png');
    }

    /**
     * الرسم البياني الكامل: الكيانات الأساسية + عقد الصفحة + مسار التنقّل.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fullGraph(): array
    {
        $nodes = Schema::baseGraph();

        if ($this->breadcrumbs !== []) {
            $nodes[] = Schema::breadcrumbs($this->breadcrumbs);
        }

        return array_merge($nodes, $this->graph);
    }

    public function jsonLd(): string
    {
        return Schema::document($this->fullGraph());
    }
}
