<?php

use App\Models\Faq;
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
                title: setting('seo_faq_title', 'الأسئلة الشائعة'),
                description: setting('seo_faq_description', "إجابات مباشرة عن أكثر ما يُسأل عنه قبل حجز مصور في {$city}: الأسعار، مدة التسليم، أنواع التصوير، نطاق التغطية، وطريقة الحجز مع {$owner}."),
            )
            ->breadcrumbs([['label' => 'الأسئلة الشائعة', 'url' => route('faq')]])
            ->addGraph(
                Schema::faqPage($this->faqs, route('faq')) ?? [],
                [
                    '@type' => 'WebPage',
                    '@id' => route('faq').'#page',
                    'url' => route('faq'),
                    'name' => 'الأسئلة الشائعة',
                    'inLanguage' => 'ar',
                    'isPartOf' => ['@id' => Schema::websiteId()],
                    'about' => ['@id' => Schema::businessId()],
                ],
            );
    }

    #[Computed]
    public function faqs()
    {
        return Faq::query()->active()->ordered()->with('section:id,name,slug')->get();
    }

    #[Computed]
    public function grouped()
    {
        return $this->faqs->groupBy(fn (Faq $faq) => $faq->section?->name ?? 'أسئلة عامة');
    }
}; ?>

<div>
    <x-site.page-header
        title="الأسئلة الشائعة"
        tagline="إجابات مباشرة قبل الحجز"
        :description="'كل ما يُسأل عنه عادة قبل حجز مصور في ' . config('site.location.city') . ' — الأسعار، التسليم، نطاق التغطية، وطريقة الحجز.'"
        icon="help"
        :breadcrumbs="[['label' => 'الأسئلة الشائعة']]"
    />

    <div class="px-4 py-12 mx-auto max-w-4xl sm:px-6 lg:px-8 lg:py-16">
        @forelse ($this->grouped as $group => $items)
            <section class="mb-12 last:mb-0">
                <h2 class="text-xl font-extrabold text-ink-900 dark:text-ink-50">{{ $group }}</h2>

                <div class="mt-5 space-y-3">
                    @foreach ($items as $faq)
                        <details
                            id="q{{ $faq->id }}"
                            class="p-5 transition-colors border group rounded-2xl border-ink-200 open:bg-ink-50 target:border-brand-400 dark:border-ink-800 dark:open:bg-ink-900"
                        >
                            <summary class="flex cursor-pointer items-center justify-between gap-4 text-sm font-extrabold text-ink-900 marker:content-none dark:text-ink-100">
                                {{ $faq->question }}
                                <span class="transition-transform shrink-0 text-ink-400 group-open:rotate-180">
                                    <x-icon name="chevron-down" :size="18" />
                                </span>
                            </summary>

                            <div class="mt-4 text-sm leading-8 text-ink-600 dark:text-ink-400">
                                {{ $faq->answer }}
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>
        @empty
            <x-site.empty-state
                icon="help"
                title="لم تُضف أسئلة بعد"
                description="أضف الأسئلة الشائعة من لوحة التحكم لتظهر هنا وتُنشر كبيانات مهيكلة."
            />
        @endforelse

        <div class="p-6 mt-4 text-center bg-ink-50 rounded-2xl dark:bg-ink-900">
            <h2 class="text-lg font-extrabold text-ink-900 dark:text-ink-50">لم تجد إجابة سؤالك؟</h2>
            <p class="mt-2 text-sm text-ink-600 dark:text-ink-400">اسأل مباشرة وسيصلك الرد في وقت قصير.</p>

            <div class="flex flex-wrap justify-center gap-3 mt-6">
                <x-ui.button href="{{ whatsapp_url() }}" variant="whatsapp" icon="whatsapp" :navigate="false" target="_blank" rel="noopener">
                    اسأل على الواتساب
                </x-ui.button>
                <x-ui.button href="{{ route('contact') }}" variant="outline" icon="send">صفحة التواصل</x-ui.button>
            </div>
        </div>
    </div>
</div>
