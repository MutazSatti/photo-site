<?php

use App\Models\Media;
use App\Models\Section;
use App\Support\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function mount(): void
    {
        $owner = config('site.owner_name');
        $city = config('site.location.city');

        seo()
            ->set(
                title: setting('seo_about_title', "نبذة عن {$owner}"),
                description: setting('seo_about_description', "تعرّف على {$owner}، مصور فوتوغرافي محترف في {$city} بخبرة في تصوير المناسبات والفعاليات والمؤتمرات والمعارض والعقارات، ومدرّب في ورش التصوير الفوتوغرافي."),
                imageMedia: $this->portrait,
                type: 'profile',
            )
            ->breadcrumbs([['label' => 'نبذة', 'url' => route('about')]])
            ->addGraph([
                '@type' => 'AboutPage',
                '@id' => route('about').'#page',
                'url' => route('about'),
                'name' => "نبذة عن {$owner}",
                'inLanguage' => 'ar',
                'isPartOf' => ['@id' => Schema::websiteId()],
                'mainEntity' => ['@id' => Schema::personId()],
            ]);
    }

    #[Computed]
    public function portrait(): ?Media
    {
        return Media::where('usage', 'owner_portrait')->first();
    }

    #[Computed]
    public function sections()
    {
        return Section::query()->active()->ordered()->withCount(['posts' => fn ($q) => $q->published()])->get();
    }

    /**
     * شعارات الجهات المانحة، مفهرسة بمفتاح الاعتماد.
     *
     * @return array<string, Media>
     */
    #[Computed]
    public function accreditationLogos(): array
    {
        return Media::query()
            ->whereIn('usage', ['accr_etec', 'accr_gaca', 'accr_gamr'])
            ->get()
            ->keyBy(fn (Media $m) => (string) $m->usage)
            ->all();
    }

    /**
     * الاعتمادات كما في config/site.php — المرجع نفسه الذي تنشره البيانات المهيكلة.
     *
     * @return array<int, array<string, string>>
     */
    public function accreditations(): array
    {
        return accreditations();
    }
}; ?>

<div>
    <x-site.page-header
        :title="setting('about_title', 'نبذة عني')"
        :tagline="config('site.job_title') . ' — ' . config('site.location.city')"
        icon="user"
        :breadcrumbs="[['label' => 'نبذة']]"
    />

    <div class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-12">

            {{-- النص --}}
            <div class="lg:col-span-7">
                <div class="prose-ar max-w-none">
                    @foreach (preg_split('/\n\s*\n/', (string) setting('about_body')) as $paragraph)
                        @if (trim($paragraph) !== '')
                            <p>{{ trim($paragraph) }}</p>
                        @endif
                    @endforeach
                </div>

                {{--
                    الاعتمادات: شعار الجهة يقول ما لا يقوله الاسم وحده.

                    بطاقة الشعار تبقى بيضاء في الوضعين، لأن هذه الشعارات مصمَّمة
                    لتُقرأ على أبيض، وقلبها في الوضع الداكن يمحو تدرّجاتها. وهي
                    تُعرض بألوانها كاملة لا رمادية كشعارات العملاء: الختم الرسمي
                    يُثبت شيئًا، وتحييد لونه يُضعف ما جاء ليقوله.
                --}}
                <div class="mt-10">
                    <h2 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">اعتمادات وتراخيص</h2>
                    <p class="mt-2 text-sm leading-7 text-ink-500 dark:text-ink-400">
                        تراخيص سارية من الجهات المنظِّمة، بأرقامها القابلة للتحقّق.
                    </p>

                    <ul class="mt-5 space-y-3">
                        @foreach ($this->accreditations() as $item)
                            <li class="p-4 border rounded-2xl border-ink-200 dark:border-ink-800">
                                <div class="flex items-center gap-4">
                                    <span class="flex items-center justify-center p-2 bg-white border shrink-0 w-28 h-16 rounded-xl border-ink-200 dark:border-ink-300">
                                        <x-site.picture
                                            :media="$this->accreditationLogos[$item['key']] ?? null"
                                            variant="md"
                                            fit="contain"
                                            sizes="112px"
                                            class="size-full"
                                        />
                                    </span>

                                    <div class="min-w-0">
                                        <h3 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $item['title'] }}</h3>
                                        <p class="mt-0.5 text-xs text-ink-600 dark:text-ink-400">{{ $item['authority'] }}</p>
                                        <p class="mt-1 text-xs font-bold text-ink-500 dark:text-ink-500">
                                            {{ $item['label'] }}:
                                            <span dir="ltr" class="font-mono text-ink-800 dark:text-ink-200">{{ $item['number'] }}</span>
                                        </p>
                                    </div>
                                </div>

                                @if (! empty($item['description']))
                                    <p class="pt-3 mt-3 text-xs leading-7 border-t text-ink-600 border-ink-100 dark:border-ink-800 dark:text-ink-400">
                                        {{ $item['description'] }}
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- ما أقدّمه --}}
                <div class="mt-10">
                    <h2 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">ما أقدّمه</h2>

                    <div class="grid gap-4 mt-5 sm:grid-cols-2">
                        @foreach ($this->sections as $section)
                            <a
                                href="{{ $section->url() }}"
                                wire:navigate
                                class="flex items-start gap-4 p-5 transition-colors border group rounded-2xl border-ink-200 hover:border-brand-400 hover:bg-ink-50 dark:border-ink-800 dark:hover:border-brand-600 dark:hover:bg-ink-900"
                            >
                                <span class="flex items-center justify-center transition-colors shrink-0 size-10 rounded-xl bg-ink-100 text-ink-600 group-hover:bg-brand-500 group-hover:text-ink-950 dark:bg-ink-800 dark:text-ink-400">
                                    <x-icon :name="$section->icon" :size="19" />
                                </span>

                                <div>
                                    <h3 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">{{ $section->name }}</h3>
                                    <p class="mt-1 text-xs leading-6 text-ink-500 dark:text-ink-400">{{ $section->tagline }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- البطاقة الجانبية --}}
            <aside class="lg:col-span-5">
                <div class="lg:sticky lg:top-24">
                    <div class="overflow-hidden border rounded-3xl border-ink-200 dark:border-ink-800">
                        <x-site.picture
                            :media="$this->portrait"
                            variant="lg"
                            eager
                            sizes="(min-width: 1024px) 40vw, 100vw"
                            class="w-full aspect-4/5"
                        />
                    </div>

                    <div class="p-6 mt-6 bg-ink-50 rounded-2xl dark:bg-ink-900">
                        <h2 class="text-sm font-extrabold text-ink-900 dark:text-ink-100">بيانات سريعة</h2>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">الاسم</dt>
                                <dd class="font-bold text-ink-900 dark:text-ink-100">{{ config('site.owner_name') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">التخصص</dt>
                                <dd class="font-bold text-ink-900 dark:text-ink-100">{{ config('site.job_title') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">المقر</dt>
                                <dd class="font-bold text-ink-900 dark:text-ink-100">{{ config('site.location.city') }}، {{ config('site.location.country_name') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400">سنوات الخبرة</dt>
                                <dd class="font-bold text-ink-900 dark:text-ink-100" dir="ltr">+{{ setting('about_years', 10) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-ink-500 dark:text-ink-400 shrink-0">نطاق الخدمة</dt>
                                <dd class="font-bold text-left text-ink-900 dark:text-ink-100">
                                    {{ implode('، ', config('site.service_areas')) }}
                                </dd>
                            </div>
                        </dl>

                        <div class="grid gap-2 pt-5 mt-5 border-t border-ink-200 dark:border-ink-800">
                            <x-ui.button href="{{ whatsapp_url() }}" variant="whatsapp" icon="whatsapp" :navigate="false" target="_blank" rel="noopener">
                                تواصل عبر الواتساب
                            </x-ui.button>
                            <x-ui.button href="{{ route('contact') }}" variant="outline" icon="send">
                                نموذج الحجز
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <x-site.cta compact />
</div>
